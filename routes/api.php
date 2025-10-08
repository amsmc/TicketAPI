<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\TicketController;
use App\Http\Controllers\Api\v1\TransactionController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\UserManagementController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\GoogleController;

// =======================================================
// ===============  AUTHENTIKASI (TANPA LOGIN)  ==========
// =======================================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// =======================================================
// ===================  TICKET ROUTES  ===================
// =======================================================
Route::prefix('tickets')->group(function () {
    Route::get('/', [TicketController::class, 'index']);
    Route::get('/{id}', [TicketController::class, 'show']);
    Route::get('/{id}/availability', [TicketController::class, 'checkAvailability']);
    Route::get('/{ticket}/sessions', [TicketController::class, 'getSessions']);
});

// =======================================================
// ==================  GOOGLE AUTH  ======================
// =======================================================
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

// =======================================================
// ============  MIDTRANS PAYMENT CALLBACK  ==============
// =======================================================
Route::post('/payment/notification', [PaymentController::class, 'handleNotification']);

// =======================================================
// ==============  ROUTE DENGAN AUTENTIKASI  =============
// =======================================================
Route::middleware('auth:sanctum')->group(function () {
    // ========== PAYMENT ==========
    Route::post('/payment/create', [PaymentController::class, 'createTransaction']);
    Route::get('/payment/status/{orderId}', [PaymentController::class, 'checkStatus']);
    Route::get('/transactions/{orderId}', [TransactionController::class, 'show']);
    Route::get('/user/tickets', [TicketController::class, 'getUserTickets']);
    Route::get('/my-tickets', [TicketController::class, 'myTickets']);
    Route::get('/tickets/{id}/verify', [TicketController::class, 'verifyTicket']);

    // ========== PROFILE ==========
    Route::get('/profile', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/photo', [AuthController::class, 'updatePhoto']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);

    // ========== AUTH ==========
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);

    // ========== TRANSACTIONS (USER) ==========
    Route::post('/purchase', [TransactionController::class, 'purchase']);
    Route::get('/my-transactions', [TransactionController::class, 'userHistory']);
    Route::get('/download-ticket/{id}', [TransactionController::class, 'downloadTicket']);

    // ========== ADMIN ==========
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard']);
        Route::get('/admin/transactions', [TransactionController::class, 'allTransactions']);
        Route::patch('/admin/transactions/{id}/verify', [TransactionController::class, 'verifyPayment']);
        Route::get('/admin/export-report', [DashboardController::class, 'exportReport']);
    });

    // ========== OWNER ==========
    Route::middleware('role:owner')->group(function () {
        Route::get('/owner/dashboard', [DashboardController::class, 'ownerDashboard']);
        Route::post('/owner/tickets', [TicketController::class, 'store']);
        Route::put('/owner/tickets/{id}', [TicketController::class, 'update']);
        Route::delete('/owner/tickets/{id}', [TicketController::class, 'destroy']);
        Route::get('/owner/tickets/{id}/sales', [TicketController::class, 'salesData']);
    });
});

// =======================================================
// ==================  ROUTE 404 HANDLER  =================
// =======================================================
Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Route not found'
    ], 404);
});
