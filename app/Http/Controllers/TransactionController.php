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
}
