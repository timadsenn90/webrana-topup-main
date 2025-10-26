<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\CreateTransactionRequest;
use App\Models\BankAccount;
use App\Models\Transactions;
use App\Services\NotificationService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TransactionController extends Controller
{
    protected $transactionService;
    protected $notificationService;

    public function __construct(
        TransactionService $transactionService,
        NotificationService $notificationService
    ) {
        $this->transactionService = $transactionService;
        $this->notificationService = $notificationService;
    }
    /**
     * Display transaction detail
     */
    public function show(string $id)
    {
        try {
            $transaction = Transactions::where('trx_id', $id)->first();

            if (!$transaction) {
                abort(404, 'Transaction not found');
            }

            $bankAccount = null;
            if ($transaction->no_rekening) {
                $bankAccount = BankAccount::where('account_no', $transaction->no_rekening)->first();
            }

            $paymentInstruction = $this->transactionService->getPaymentInstructions($transaction);

            return Inertia::render('DetailTransaction', [
                'transaction' => $transaction,
                'bank_account' => $bankAccount,
                'paymentInstruction' => $paymentInstruction,
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing transaction', [
                'transaction_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Gagal menampilkan detail transaksi');
        }
    }

    /**
     * Create new transaction
     */
    public function createTransaction(CreateTransactionRequest $request)
    {
        DB::beginTransaction();
        
        try {
            $validated = $request->validated();
            
            // Prepare transaction data
            $transactionData = [
                'user_id' => $validated['user_id'],
                'server_id' => $validated['server_id'] ?? 'default_server_id',
                'amount' => $validated['amount'],
                'method_code' => $validated['method_code'] ?? null,
                'customer_name' => $validated['customer_name'],
                'email_customer' => $validated['email_customer'],
                'phone_number' => $validated['phone_number'],
                'product_code' => $validated['product_code'],
                'product_name' => $validated['product_name'],
                'product_brand' => $validated['product_brand'],
                'product_price' => $validated['product_price'],
                'values' => $validated['values'] ?? [],
                'unique_code' => $validated['unique_code'] ?? null,
                'bankID' => $validated['bankID'] ?? null,
                'buyer_id' => auth()->check() ? auth()->user()->id : null,
                'username' => $validated['username'] ?? 'Guest',
                'order_id' => UuidController::generateCustomUuid($validated['product_brand']),
            ];

            // Determine transaction type: Bank Transfer or Payment Gateway
            if ($transactionData['unique_code'] && $transactionData['bankID']) {
                // Bank Transfer Transaction
                $transaction = $this->transactionService->createBankTransferTransaction($transactionData);
            } else {
                // Tripay Payment Gateway Transaction
                $response = $this->transactionService->createTripayTransaction($transactionData);
                $transaction = $this->transactionService->storeTripayTransaction($response, $transactionData);
            }

            // Send notification
            try {
                $this->notificationService->sendTransactionInvoice($transaction);
            } catch (\Exception $e) {
                Log::warning('Failed to send transaction notification', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            Log::info('Transaction created successfully', [
                'transaction_id' => $transaction->trx_id
            ]);

            return redirect()->route('detail.transaction', $transaction->trx_id)
                ->with('success', 'Transaksi berhasil dibuat');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Transaction creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Send error notification to owner
            try {
                $this->notificationService->sendErrorToOwner($e->getMessage());
            } catch (\Exception $notifError) {
                Log::error('Failed to send error notification', [
                    'error' => $notifError->getMessage()
                ]);
            }

            return redirect()->back()
                ->with('error', 'Transaksi gagal: ' . $e->getMessage())
                ->withInput();
        }
    }
    function generateOrderId($appName, $length = 8): string
    {
        // Memisahkan kata dalam nama aplikasi
        $words = explode(" ", $appName);
        $initials = '';

        // Mengambil inisial dari setiap kata
        foreach ($words as $word) {
            // Menghilangkan spasi dan karakter khusus dari setiap kata
            $word = preg_replace("/[^A-Za-z0-9]/", '', $word);
            // Mengambil $length karakter pertama dari setiap kata
            $initials .= substr($word, 0, $length);
        }

        // Menambahkan timestamp untuk membuat ID lebih unik
        $timestamp = time();

        // Menggabungkan inisial aplikasi dengan timestamp
        $orderId = $initials . $timestamp;

        return $orderId;
    }

    public function generateSignature($merchantRef,$amount): string
    {
        // Define the required parameters
        $latestTripay = Tripay::latest()->first();

        // Generate the signature
        $signature = hash_hmac('sha256', $latestTripay->merchant_code  . $merchantRef . $amount, $latestTripay->private_key);

        // Return the signature as a response
        return $signature;
    }

    private function sendInvoiceToWhatsApp($transaction, $phone_number): void
    {
        Carbon::setLocale('id');
        $appNameFull = config('app.name');
        $appNameParts = explode(' |', $appNameFull);
        $appName = $appNameParts[0];
        $appUrl = config('app.url');
//        $appUrl = "http://webranastore.com";
        $expiredTime = Carbon::parse($transaction->expired_time);
        $formattedExpiredTime = $expiredTime->translatedFormat('d F Y, H:i:s');


        $formattedDataTrx = '';
        if (!is_null($transaction->data_trx)) {
            $dataTrxArray = json_decode($transaction->data_trx, true) ?? [];
            // Format the decoded JSON data into a readable string
            foreach ($dataTrxArray as $key => $value) {
                $formattedDataTrx .= ucwords(str_replace('_', ' ', $key)) . ": " . $value . "\n";
            }
        }



        $message = "Halo {$transaction->user_name},\n\n";
        $message .= "Terima kasih telah melakukan transaksi. Berikut adalah detail transaksi Anda:\n\n";
        $message .= "ID Transaksi: *{$transaction->trx_id}*\n";
        $message .= "Status Transaksi: *".ucwords($transaction->status)."*\n";
        $message .= "{$formattedDataTrx}";
        if ($transaction->user_name) {
            $message .= "Username: *{$transaction->user_name}*\n";
        }
        $message .= "Nama Produk: *".strtoupper($transaction->product_name)."*\n";
        $message .= "Merek Produk: *".strtoupper($transaction->product_brand)."*\n";
        $message .= "Harga Produk: *Rp" . number_format($transaction->product_price, 0, ',', '.') . "*\n";
        if ($transaction->fee > 0){
            $message .= "Biaya Admin: *Rp" . number_format($transaction->fee, 0, ',', '.') . "*\n";
        }
        if ($transaction->unique_code){
            $message .= "Kode Unik: *" . $transaction->unique_code . "*\n";
        }
        if ($transaction->fee > 0){
            $message .= "Total Pembayaran: *Rp" . number_format($transaction->amount+$transaction->fee, 0, ',', '.') . "*\n";
        }
        if ($transaction->unique_code){
            $message .= "Total Pembayaran: *Rp" . number_format($transaction->amount+$transaction->unique_code, 0, ',', '.') . "*\n";

        }
//        $message .= "Total Pembayaran: *Rp" . number_format($transaction->amount, 0, ',', '.') . "*\n";
        $message .= "Status Pembayaran: *Menunggu Pembayaran*\n";
        $message .= "Metode Pembayaran: *{$transaction->payment_name}*\n";

        if ($transaction->no_va) {
            $message .= "Kode Pembayaran: *{$transaction->no_va}*\n";
        }

        $message .= "\n\nLakukan pembayaran sebelum *{$formattedExpiredTime}*\n\n";
        $message .= "Detail Pembayaran : {$appUrl}/transaction/{$transaction->trx_id}\n\n";
        $message .= "Jika ada pertanyaan, silakan hubungi kami.\n\n";
        $message .= "Terima kasih,\n";
        $message .= "Tim {$appName}";

        $this->fonnteService->sendMessage([
            'target' => $phone_number,
            'message' => $message,
        ]);
    }

    private function sendErrorWhatsAppOwner($messageError): void
    {
        $appNameFull = config('app.name');
        $appNameParts = explode(' |', $appNameFull);
        $appName = $appNameParts[0];

        $message = "Halo {$appName}, ada kegagalan sistem nih,\n\n";
        $message .= "{$messageError}";
        $message .= "\n\nTerima kasih";

        $this->fonnteService->sendMessage([
            'target' => $this->wa_owner,
            'message' => $message,
        ]);
    }

}
