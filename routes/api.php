<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DepositAccountController;
use App\Http\Controllers\Api\V1\GeneralController;
use App\Http\Controllers\Api\V1\LoanAccountController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SavingsAccountController;
use App\Http\Controllers\Api\V1\TransferController;
use App\Http\Controllers\Api\V1\TransferFavoriteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Banking API Routes (v1)
|--------------------------------------------------------------------------
|
| Prefix: /api/v1
| Guard: mobile (Sanctum)
|
*/

// === PUBLIC (rate-limited, no auth) ===
Route::middleware('throttle:mobile-auth')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware('throttle:mobile-otp')->group(function () {
    Route::post('/auth/otp/request', [AuthController::class, 'requestOtp']);
    Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp']);
});

Route::get('/system/app-info', [GeneralController::class, 'appInfo']);

// === AUTHENTICATED ===
Route::middleware(['auth:mobile', 'mobile.active', 'mobile.device', 'throttle:mobile-api'])->group(function () {

    // Auth management
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/device/register', [AuthController::class, 'registerDevice']);
    Route::post('/auth/pin/reset/request', [AuthController::class, 'requestPinReset']);
    Route::post('/auth/pin/reset/verify', [AuthController::class, 'resetPin']);

    // PIN-protected auth
    Route::middleware('mobile.pin')->group(function () {
        Route::post('/auth/pin/change', [AuthController::class, 'changePin']);
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/profile/detail', [ProfileController::class, 'detail']);
    Route::put('/profile/fcm-token', [ProfileController::class, 'updateFcmToken']);
    Route::put('/profile/phone', [ProfileController::class, 'updatePhone']);

    // Savings
    Route::prefix('savings')->group(function () {
        Route::get('/', [SavingsAccountController::class, 'index']);
        Route::get('/{accountNumber}', [SavingsAccountController::class, 'show']);
        Route::get('/{accountNumber}/balance', [SavingsAccountController::class, 'balance']);
        Route::get('/{accountNumber}/transactions', [SavingsAccountController::class, 'transactions']);
        Route::get('/{accountNumber}/mini-statement', [SavingsAccountController::class, 'miniStatement']);
        Route::get('/{accountNumber}/monthly-statement', [SavingsAccountController::class, 'monthlyStatement']);
    });

    // Deposits
    Route::prefix('deposits')->group(function () {
        Route::get('/products', [DepositAccountController::class, 'products']);
        Route::get('/', [DepositAccountController::class, 'index']);
        Route::get('/{accountNumber}', [DepositAccountController::class, 'show']);
        Route::get('/{accountNumber}/transactions', [DepositAccountController::class, 'transactions']);
        Route::get('/{accountNumber}/interest-projection', [DepositAccountController::class, 'interestProjection']);
    });

    // Loans
    Route::prefix('loans')->group(function () {
        Route::get('/', [LoanAccountController::class, 'index']);
        Route::get('/{accountNumber}', [LoanAccountController::class, 'show']);
        Route::get('/{accountNumber}/schedule', [LoanAccountController::class, 'schedule']);
        Route::get('/{accountNumber}/next-installment', [LoanAccountController::class, 'nextInstallment']);
        Route::get('/{accountNumber}/payments', [LoanAccountController::class, 'payments']);
        Route::get('/{accountNumber}/overdue', [LoanAccountController::class, 'overdue']);

        Route::middleware(['mobile.pin', 'throttle:mobile-transaction'])->group(function () {
            Route::post('/{accountNumber}/pay-from-savings', [LoanAccountController::class, 'payFromSavings']);
        });
    });

    // Transfers
    Route::prefix('transfers')->group(function () {
        Route::post('/validate', [TransferController::class, 'validateTransfer']);
        Route::get('/history', [TransferController::class, 'history']);
        Route::get('/favorites', [TransferFavoriteController::class, 'index']);
        Route::post('/favorites', [TransferFavoriteController::class, 'store']);
        Route::delete('/favorites/{transferFavorite}', [TransferFavoriteController::class, 'destroy']);
        Route::get('/{referenceNumber}', [TransferController::class, 'show']);

        Route::middleware(['mobile.pin', 'throttle:mobile-transaction'])->group(function () {
            Route::post('/own-account', [TransferController::class, 'ownAccount']);
            Route::post('/internal', [TransferController::class, 'internal']);
        });
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/read-all', [NotificationController::class, 'markAllRead']);
        Route::put('/{mobileNotification}/read', [NotificationController::class, 'markRead']);
    });

    // Products & General
    Route::get('/products/savings', [ProductController::class, 'savings']);
    Route::get('/products/deposits', [ProductController::class, 'deposits']);
    Route::get('/products/loans', [ProductController::class, 'loans']);
    Route::get('/branches', [GeneralController::class, 'branches']);
    Route::get('/system/holidays', [GeneralController::class, 'holidays']);
});
