<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\Admin\QuoteAdminController;
use App\Http\Middleware\CheckAdminToken;

Route::post('/contact', [SubmissionController::class, 'storeContact']);
Route::post('/reviews', [SubmissionController::class, 'storeReview']);

// Public Quote endpoints — rate limited to 3 requests per hour per IP
Route::middleware('throttle:3,60')->group(function () {
    Route::post('/quotes', [QuoteController::class, 'store']);
});
Route::get('/quotes/{reference}', [QuoteController::class, 'show']);

Route::middleware([CheckAdminToken::class])->get('/submissions', [SubmissionController::class, 'index']);

// Admin Quote Management
Route::middleware([CheckAdminToken::class])->prefix('admin')->group(function () {
    Route::get('/quotes', [QuoteAdminController::class, 'index']);
    Route::get('/quotes-analytics', [QuoteAdminController::class, 'analytics']);
    Route::get('/quotes/{id}', [QuoteAdminController::class, 'show']);
    Route::patch('/quotes/{id}', [QuoteAdminController::class, 'update']);
    Route::post('/quotes/{id}/approve', [QuoteAdminController::class, 'approve']);
    Route::post('/quotes/{id}/reject', [QuoteAdminController::class, 'reject']);
    Route::post('/quotes/{id}/convert', [QuoteAdminController::class, 'convert']);
});


// Secure utility route to run migrations via browser (lifesaver if cPanel SSH/Terminal is disabled)
Route::get('/run-migrations', function (\Illuminate\Http\Request $request) {
    $token = $request->query('token');
    $adminKey = env('ADMIN_API_KEY');

    if (!$token || $token !== $adminKey) {
        return response()->json(['error' => 'Unauthorized access'], 401);
    }

    try {
        $command = $request->query('fresh') === 'true' ? 'migrate:fresh' : 'migrate';
        \Illuminate\Support\Facades\Artisan::call($command, ['--force' => true]);
        return response()->json([
            'message' => 'Migrations completed successfully!',
            'command_run' => $command,
            'output' => \Illuminate\Support\Facades\Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Migration failed',
            'details' => $e->getMessage()
        ], 500);
    }
});
