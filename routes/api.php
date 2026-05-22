<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubmissionController;
use App\Http\Middleware\CheckAdminToken;

Route::post('/contact', [SubmissionController::class, 'storeContact']);
Route::post('/reviews', [SubmissionController::class, 'storeReview']);

Route::middleware([CheckAdminToken::class])->get('/submissions', [SubmissionController::class, 'index']);

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
