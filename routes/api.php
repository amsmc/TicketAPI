 <?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\TicketController;
use App\Http\Controllers\Api\v1\TransactionController;
use App\Http\Controllers\Api\v1\DashboardController;
use App\Http\Controllers\Api\v1\UserManagementController;
use App\Http\Controllers\GoogleController;


// Auth routes - tidak perlu autentikasi

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes - bisa diakses tanpa login
Route::get('/tickets', [TicketController::class, 'index']); // Daftar tiket yang tersedia
Route::get('/tickets/{id}', [TicketController::class, 'show']); // Detail tiket

// Google Auth (jika diperlukan)
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

// Route yang memerlukan autentikasi
Route::middleware('auth:sanctum')->group(function () {
    // ... existing routes

    // Profile management
    Route::get('/profile', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/photo', [AuthController::class, 'updatePhoto']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);

    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    // User routes - semua role bisa akses
    Route::post('/purchase', [TransactionController::class, 'purchase']);
    Route::get('/my-transactions', [TransactionController::class, 'userHistory']);
    Route::get('/download-ticket/{id}', [TransactionController::class, 'downloadTicket']);

    // Admin routes
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard']);
        Route::get('/admin/transactions', [TransactionController::class, 'allTransactions']);
        Route::patch('/admin/transactions/{id}/verify', [TransactionController::class, 'verifyPayment']);
        Route::get('/admin/export-report', [DashboardController::class, 'exportReport']);

        // User management untuk admin
        // Route::get('/admin/users', [UserManagementController::class, 'index']);
        // Route::get('/admin/users/{id}', [UserManagementController::class, 'show']);
        // Route::put('/admin/users/{id}', [UserManagementController::class, 'update']);
        // Route::delete('/admin/users/{id}', [UserManagementController::class, 'destroy']);
    });
    // Owner routes
    Route::middleware('role:owner')->group(function () {
        Route::get('/owner/dashboard', [DashboardController::class, 'ownerDashboard']);

        // Ticket management untuk owner
        Route::post('/owner/tickets', [TicketController::class, 'store']);
        Route::put('/owner/tickets/{id}', [TicketController::class, 'update']);
        Route::delete('/owner/tickets/{id}', [TicketController::class, 'destroy']);
        Route::get('/owner/tickets/{id}/sales', [TicketController::class, 'salesData']);
    });
});
// Catch-all route untuk handle 404
Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Route not found'
    ], 404);
});
