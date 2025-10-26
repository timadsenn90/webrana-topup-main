<?php

namespace App\Services;

use App\Models\Fonnte;
use App\Models\Transactions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $fonnteUrl = 'https://api.fonnte.com/send';

    /**
     * Send WhatsApp message via Fonnte
     */
    public function sendWhatsApp(string $phoneNumber, string $message): bool
    {
        $fonnte = Fonnte::latest()->first();
        
        if (!$fonnte || !$fonnte->token) {
            Log::warning('Fonnte not configured, skipping WhatsApp notification');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $fonnte->token,
            ])->post($this->fonnteUrl, [
                'target' => $phoneNumber,
                'message' => $message,
                'countryCode' => '62',
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp sent successfully', ['phone' => $phoneNumber]);
                return true;
            }

            Log::error('WhatsApp send failed', [
                'phone' => $phoneNumber,
                'response' => $response->json()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp send exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send transaction invoice to customer
     */
    public function sendTransactionInvoice(Transactions $transaction): bool
    {
        if (!$transaction->phone_number) {
            return false;
        }

        $message = $this->buildInvoiceMessage($transaction);
        return $this->sendWhatsApp($transaction->phone_number, $message);
    }

    /**
     * Send error notification to owner
     */
    public function sendErrorToOwner(string $errorMessage): bool
    {
        $fonnte = Fonnte::latest()->first();
        
        if (!$fonnte || !$fonnte->wa_owner) {
            return false;
        }

        $appName = config('app.name');
        $message = "Halo Admin,\n\n";
        $message .= "Ada kegagalan sistem:\n\n";
        $message .= "{$errorMessage}\n\n";
        $message .= "Mohon segera ditindaklanjuti.\n\n";
        $message .= "- System {$appName}";

        return $this->sendWhatsApp($fonnte->wa_owner, $message);
    }

    /**
     * Build invoice message for transaction
     */
    protected function buildInvoiceMessage(Transactions $transaction): string
    {
        Carbon::setLocale('id');
        $appName = explode(' |', config('app.name'))[0];
        $appUrl = config('app.url');
        
        $expiredTime = Carbon::parse($transaction->expired_time);
        $formattedExpiredTime = $expiredTime->translatedFormat('d F Y, H:i:s');

        // Format transaction data
        $formattedDataTrx = '';
        if ($transaction->data_trx) {
            $dataTrxArray = json_decode($transaction->data_trx, true) ?? [];
            foreach ($dataTrxArray as $key => $value) {
                $formattedDataTrx .= ucwords(str_replace('_', ' ', $key)) . ": " . $value . "\n";
            }
        }

        $message = "Halo {$transaction->user_name},\n\n";
        $message .= "Terima kasih telah melakukan transaksi. Berikut adalah detail transaksi Anda:\n\n";
        $message .= "ID Transaksi: *{$transaction->trx_id}*\n";
        $message .= "Status: *" . ucfirst($transaction->status) . "*\n";
        
        if ($formattedDataTrx) {
            $message .= "\n{$formattedDataTrx}";
        }
        
        $message .= "\nProduk: *" . strtoupper($transaction->product_name) . "*\n";
        $message .= "Brand: *" . strtoupper($transaction->product_brand) . "*\n";
        $message .= "Harga: *Rp" . number_format($transaction->product_price, 0, ',', '.') . "*\n";
        
        if ($transaction->fee > 0) {
            $message .= "Biaya Admin: *Rp" . number_format($transaction->fee, 0, ',', '.') . "*\n";
        }
        
        if ($transaction->unique_code) {
            $message .= "Kode Unik: *{$transaction->unique_code}*\n";
        }
        
        $totalAmount = $transaction->amount + ($transaction->fee ?? 0) + ($transaction->unique_code ?? 0);
        $message .= "\n*Total Pembayaran: Rp" . number_format($totalAmount, 0, ',', '.') . "*\n";
        $message .= "Metode Pembayaran: *{$transaction->payment_name}*\n";
        
        if ($transaction->no_va) {
            $message .= "Kode Pembayaran: *{$transaction->no_va}*\n";
        }
        
        $message .= "\nBatas Waktu: *{$formattedExpiredTime}*\n";
        $message .= "\nDetail: {$appUrl}/transaction/{$transaction->trx_id}\n\n";
        $message .= "Jika ada pertanyaan, silakan hubungi kami.\n\n";
        $message .= "Terima kasih,\n{$appName}";

        return $message;
    }
}
