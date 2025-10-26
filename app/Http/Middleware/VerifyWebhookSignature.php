<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'tripay'): Response
    {
        if ($type === 'tripay') {
            return $this->verifyTripaySignature($request, $next);
        }

        if ($type === 'xendit') {
            return $this->verifyXenditSignature($request, $next);
        }

        if ($type === 'digiflazz') {
            return $this->verifyDigiflazzSignature($request, $next);
        }

        return response()->json(['message' => 'Invalid webhook type'], 400);
    }

    /**
     * Verify Tripay webhook signature
     */
    protected function verifyTripaySignature(Request $request, Closure $next): Response
    {
        $tripay = \App\Models\Tripay::latest()->first();
        
        if (!$tripay) {
            Log::error('Tripay webhook: Configuration not found');
            return response()->json(['message' => 'Configuration error'], 500);
        }

        $callbackSignature = $request->server('HTTP_X_CALLBACK_SIGNATURE');
        $json = $request->getContent();
        $signature = hash_hmac('sha256', $json, $tripay->private_key);

        if ($callbackSignature !== $signature) {
            Log::warning('Tripay webhook: Invalid signature', [
                'received' => $callbackSignature,
                'expected' => $signature,
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }

    /**
     * Verify Xendit webhook signature
     */
    protected function verifyXenditSignature(Request $request, Closure $next): Response
    {
        $xendit = \App\Models\Xendit::latest()->first();
        
        if (!$xendit || !$xendit->webhook_token) {
            Log::error('Xendit webhook: Configuration not found');
            return response()->json(['message' => 'Configuration error'], 500);
        }

        $webhookToken = $request->header('x-callback-token');

        if ($webhookToken !== $xendit->webhook_token) {
            Log::warning('Xendit webhook: Invalid token');
            return response()->json(['message' => 'Invalid token'], 401);
        }

        return $next($request);
    }

    /**
     * Verify Digiflazz webhook signature
     */
    protected function verifyDigiflazzSignature(Request $request, Closure $next): Response
    {
        // Digiflazz uses username + API key verification
        $digiAuth = \App\Models\DigiAuth::latest()->first();
        
        if (!$digiAuth) {
            Log::error('Digiflazz webhook: Configuration not found');
            return response()->json(['message' => 'Configuration error'], 500);
        }

        // Add your Digiflazz verification logic here
        // For now, we'll just log and continue
        Log::info('Digiflazz webhook received', $request->all());

        return $next($request);
    }
}
