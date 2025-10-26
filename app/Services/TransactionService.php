<?php

namespace App\Services;

use App\Models\BankAccountMoota;
use App\Models\Transactions;
use App\Models\Tripay;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TransactionService
{
    protected $notificationService;
    protected $url;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->url = $this->getTripayUrl();
    }

    /**
     * Get Tripay API URL based on environment
     */
    protected function getTripayUrl(): string
    {
        $tripay = Tripay::latest()->first();
        if (!$tripay) {
            return "https://tripay.co.id/api-sandbox/";
        }
        return $tripay->is_production === 1 
            ? "https://tripay.co.id/api/"
            : "https://tripay.co.id/api-sandbox/";
    }

    /**
     * Create transaction via Tripay
     */
    public function createTripayTransaction(array $data): array
    {
        $tripay = Tripay::latest()->first();
        
        if (!$tripay) {
            throw new Exception('Payment gateway not configured');
        }

        $signature = $this->generateSignature(
            $data['order_id'], 
            $data['amount'],
            $tripay
        );

        $requestData = [
            'method' => $data['method_code'],
            'merchant_ref' => $data['order_id'],
            'amount' => $data['amount'],
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['email_customer'],
            'customer_phone' => $data['phone_number'],
            'order_items' => [
                [
                    'sku' => $data['product_code'],
                    'name' => $data['product_name'],
                    'price' => $data['product_price'],
                    'quantity' => 1,
                ]
            ],
            'expired_time' => (time() + (3600)), // 1 hour
            'signature' => $signature,
        ];

        Log::info('Creating Tripay transaction', ['request' => $requestData]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tripay->api_key,
        ])->post($this->url . 'transaction/create', $requestData);

        if (!$response->successful()) {
            $error = $response->json();
            Log::error('Tripay transaction failed', ['error' => $error]);
            throw new Exception($error['message'] ?? 'Transaction failed');
        }

        return $response->json();
    }

    /**
     * Create bank transfer transaction
     */
    public function createBankTransferTransaction(array $data): Transactions
    {
        $bankAccount = BankAccountMoota::where('bank_id', $data['bankID'])->first();
        
        if (!$bankAccount) {
            throw new Exception('Bank account not found');
        }

        $transaction = Transactions::create([
            'trx_id' => $data['order_id'],
            'user_id' => $data['user_id'],
            'buyer_id' => $data['buyer_id'],
            'server_id' => $data['server_id'],
            'user_name' => $data['username'],
            'email' => $data['email_customer'],
            'phone_number' => $data['phone_number'],
            'buyer_sku_code' => $data['product_code'],
            'product_brand' => $data['product_brand'],
            'product_name' => $data['product_name'],
            'product_price' => $data['product_price'],
            'amount' => $data['product_price'],
            'unique_code' => $data['unique_code'],
            'fee' => 0,
            'status' => 'pending',
            'digiflazz_status' => 'pending',
            'payment_method' => $bankAccount->label,
            'payment_name' => $bankAccount->label,
            'no_rekening' => $bankAccount->account_number,
            'payment_status' => 'UNPAID',
            'expired_time' => now()->addHour(),
            'data_trx' => json_encode($data['values'] ?? [])
        ]);

        Log::info('Bank transfer transaction created', ['transaction_id' => $transaction->id]);

        return $transaction;
    }

    /**
     * Store Tripay transaction to database
     */
    public function storeTripayTransaction(array $responseData, array $originalData): Transactions
    {
        $data = $responseData['data'];
        
        $transaction = Transactions::create([
            'trx_id' => $data['merchant_ref'],
            'user_id' => $originalData['user_id'],
            'buyer_id' => $originalData['buyer_id'],
            'server_id' => $originalData['server_id'],
            'user_name' => $originalData['username'],
            'email' => $originalData['email_customer'],
            'phone_number' => $originalData['phone_number'],
            'buyer_sku_code' => $data['order_items'][0]['sku'],
            'product_brand' => $originalData['product_brand'],
            'product_name' => $data['order_items'][0]['name'],
            'product_price' => $data['order_items'][0]['price'],
            'amount' => $data['amount'],
            'fee' => $data['total_fee'],
            'status' => $data['status'] === 'PAID' ? 'process' : 'pending',
            'digiflazz_status' => $data['status'] === 'PAID' ? 'process' : 'pending',
            'payment_method' => $data['payment_method'],
            'payment_name' => $data['payment_name'],
            'payment_status' => $data['status'],
            'expired_time' => date('Y-m-d H:i:s', $data['expired_time']),
            'qr_url' => $data['qr_url'] ?? null,
            'qr_string' => $data['qr_string'] ?? null,
            'no_va' => $data['pay_code'] ?? null,
            'data_trx' => json_encode($originalData['values'] ?? [])
        ]);

        Log::info('Tripay transaction stored', ['transaction_id' => $transaction->id]);

        return $transaction;
    }

    /**
     * Generate signature for Tripay
     */
    protected function generateSignature(string $merchantRef, int $amount, Tripay $tripay): string
    {
        return hash_hmac(
            'sha256', 
            $tripay->merchant_code . $merchantRef . $amount, 
            $tripay->private_key
        );
    }

    /**
     * Get payment instructions from Tripay
     */
    public function getPaymentInstructions(Transactions $transaction): ?array
    {
        $tripay = Tripay::latest()->first();
        
        if (!$tripay) {
            return null;
        }

        $data = [
            'code' => $transaction->payment_method,
            'paycode' => $transaction->no_va,
            'amount' => $transaction->amount,
            'allow_html' => 1
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tripay->api_key,
        ])->get($this->url . 'payment/instruction', $data);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}
