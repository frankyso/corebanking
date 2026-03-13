<?php

use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DepositAccountController;
use App\Http\Controllers\Api\V1\GeneralController;
use App\Http\Controllers\Api\V1\LoanAccountController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SavingsAccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Open API Routes (v1)
|--------------------------------------------------------------------------
|
| Prefix: /api/v1
| Auth: HMAC Signature (X-Client-Id, X-Timestamp, X-Signature)
|
*/

// Public endpoint
Route::get('/system/app-info', [GeneralController::class, 'appInfo']);

// Authenticated endpoints (HMAC Signature + Cache)
Route::middleware(['api.auth', 'throttle:open-api'])->group(function () {

    // Customer
    Route::prefix('customers/{cif}')->group(function () {
        Route::get('/', [CustomerController::class, 'show'])->middleware('api.cache:900');
        Route::get('/detail', [CustomerController::class, 'detail'])->middleware('api.cache:900');
        Route::get('/savings', [CustomerController::class, 'savings'])->middleware('api.cache:300');
        Route::get('/deposits', [CustomerController::class, 'deposits'])->middleware('api.cache:300');
        Route::get('/loans', [CustomerController::class, 'loans'])->middleware('api.cache:300');
    });

    // Savings Accounts
    Route::prefix('savings/{accountNumber}')->group(function () {
        Route::get('/', [SavingsAccountController::class, 'show'])->middleware('api.cache:300');
        Route::get('/balance', [SavingsAccountController::class, 'balance'])->middleware('api.cache:60');
        Route::get('/transactions', [SavingsAccountController::class, 'transactions'])->middleware('api.cache:120');
        Route::get('/statement', [SavingsAccountController::class, 'statement'])->middleware('api.cache:120');
    });

    // Deposit Accounts
    Route::prefix('deposits/{accountNumber}')->group(function () {
        Route::get('/', [DepositAccountController::class, 'show'])->middleware('api.cache:300');
        Route::get('/transactions', [DepositAccountController::class, 'transactions'])->middleware('api.cache:120');
    });

    // Loan Accounts
    Route::prefix('loans/{accountNumber}')->group(function () {
        Route::get('/', [LoanAccountController::class, 'show'])->middleware('api.cache:300');
        Route::get('/schedule', [LoanAccountController::class, 'schedule'])->middleware('api.cache:300');
        Route::get('/payments', [LoanAccountController::class, 'payments'])->middleware('api.cache:300');
        Route::get('/overdue', [LoanAccountController::class, 'overdue'])->middleware('api.cache:300');
    });

    // Products
    Route::get('/products/savings', [ProductController::class, 'savings'])->middleware('api.cache:3600');
    Route::get('/products/deposits', [ProductController::class, 'deposits'])->middleware('api.cache:3600');
    Route::get('/products/loans', [ProductController::class, 'loans'])->middleware('api.cache:3600');

    // General
    Route::get('/branches', [GeneralController::class, 'branches'])->middleware('api.cache:3600');
    Route::get('/system/holidays', [GeneralController::class, 'holidays'])->middleware('api.cache:86400');
});
