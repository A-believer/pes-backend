<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\Admin\QuoteAdminController;
use App\Http\Controllers\Admin\JobMonitorController;
use App\Http\Controllers\Admin\InvoiceAdminController;
use App\Http\Middleware\CheckAdminToken;

// Handle browser CORS preflight OPTIONS requests for all API endpoints
Route::options('/{any}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN');
})->where('any', '.*');

Route::post('/contact', [SubmissionController::class, 'storeContact']);
Route::post('/landing-page-lead', [SubmissionController::class, 'storeLandingPageLead']);
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

    // Job monitoring
    Route::get('/jobs/stats',        [JobMonitorController::class, 'stats']);
    Route::get('/jobs/queue',        [JobMonitorController::class, 'queue']);
    Route::get('/jobs/failed',       [JobMonitorController::class, 'failed']);
    Route::delete('/jobs/failed',    [JobMonitorController::class, 'flushFailed']);
    Route::delete('/jobs/failed/{id}', [JobMonitorController::class, 'deleteFailed']);

    // Invoices & Receipts
    Route::get('/invoices',                  [InvoiceAdminController::class, 'index']);
    Route::post('/invoices',                 [InvoiceAdminController::class, 'store']);
    Route::get('/invoices/{id}',             [InvoiceAdminController::class, 'show']);
    Route::put('/invoices/{id}',             [InvoiceAdminController::class, 'update']);
    Route::delete('/invoices/{id}',          [InvoiceAdminController::class, 'destroy']);
    Route::post('/invoices/{id}/send-email', [InvoiceAdminController::class, 'sendEmail']);
    Route::post('/invoices/{id}/record-payment', [InvoiceAdminController::class, 'recordPayment']);
    Route::get('/invoices/{id}/pdf',         [InvoiceAdminController::class, 'pdf']);
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
