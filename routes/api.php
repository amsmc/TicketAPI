<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\TicketController;
use App\Http\Controllers\Api\v1\TransactionController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\UserManagementController;
use App\Http\Controllers\GoogleController;

// Auth biasa
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Route yang memerlukan autentikasi
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/profile', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/photo', [AuthController::class, 'updatePhoto']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    
    // User routes
    Route::post('/purchase', [TransactionController::class, 'purchase']);
    Route::get('/my-transactions', [TransactionController::class, 'userHistory']);
    Route::get('/download-ticket/{id}', [TransactionController::class, 'downloadTicket']);
    
    // Admin routes
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard']);
        Route::get('/admin/transactions', [TransactionController::class, 'allTransactions']);
        Route::patch('/admin/transactions/{id}/verify', [TransactionController::class, 'verifyPayment']);
        Route::get('/admin/export-report', [DashboardController::class, 'exportReport']);
    });
    
    // Owner routes
    Route::middleware('role:owner')->group(function () {
        Route::get('/owner/dashboard', [DashboardController::class, 'ownerDashboard']);
        Route::post('/owner/tickets', [TicketController::class, 'store']);
        Route::put('/owner/tickets/{id}', [TicketController::class, 'update']);
        Route::delete('/owner/tickets/{id}', [TicketController::class, 'destroy']);
        Route::get('/owner/tickets/{id}/sales', [TicketController::class, 'salesData']);
    });
});
