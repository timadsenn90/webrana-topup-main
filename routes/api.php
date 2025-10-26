<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\BankAccountController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API routes with rate limiting
Route::middleware('throttle:api')->group(function () {
    // User authentication
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    // Public endpoints
    Route::get('/settings/app-name', [\App\Http\Controllers\WebIdentityController::class, 'getAppName']);
    Route::get('/bank-list', [BankAccountController::class, 'getList']);
    Route::get('/bank/bank-account', [\App\Http\Controllers\BankAccountController::class, 'getAllAccounts']);

    // Admin endpoints with additional rate limiting
    Route::middleware('checkRole:admin')->prefix('admin')->group(function () {
        Route::get('/digiflazz/fetch', [\App\Http\Controllers\Admin\DigiflazzController::class, 'fetchAndStorePriceList']);
        Route::get('/recent-transactions', [\App\Http\Controllers\Admin\DashboardController::class, 'getRecentTransactions']);
    });

    // Transaction creation with stricter rate limiting
    Route::middleware('throttle:transaction')->group(function () {
        Route::post('/createTransaction', [\App\Http\Controllers\TransactionController::class, 'createTransaction'])
            ->name('tripay.create.transaction');
        Route::post('/checkusername', [\App\Http\Controllers\CheckUserNameController::class, 'checkUserName']);
    });

    // Bank operations
    Route::post('/check-mutation/{amount}', [BankAccountController::class, 'checkMutation']);
    Route::post('/mutations', [BankAccountController::class, 'getMutations']);
    Route::post('/match-transaction', [BankAccountController::class, 'matchTransaction'])->name('match.transaction');
    Route::post('/get-statements/{account_id}', [BankAccountController::class, 'getAccountStatements'])->name('get.statements');
    Route::get('/rerun-check-mutation/{account_id}', [BankAccountController::class, 'rerunCheckMutation']);

    // Form and brand operations
    Route::post('/addform', [\App\Http\Controllers\FormInputController::class, 'store']);
    Route::post('/brands/{id}', [BrandController::class, 'update']);
    Route::post('/settings/app-name', [\App\Http\Controllers\WebIdentityController::class, 'setAppName']);
    Route::post('/send-message', [\App\Http\Controllers\Admin\WhatsappGatewayController::class, 'sendMessage']);
});

// Webhook endpoints (no rate limiting but with signature verification)
Route::post('/payment-callback', [\App\Http\Controllers\CallbackController::class, 'handle'])
    ->middleware('verify.webhook:tripay');
    
Route::post('/digiflazz/webhook', [\App\Http\Controllers\DigiflazzWebhookController::class, 'handle'])
    ->middleware('verify.webhook:digiflazz');
    
Route::post('/bank-transfer/webhook', [\App\Http\Controllers\BankTransferWebhook::class, 'handle']);
