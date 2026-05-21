<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubmissionController;
use App\Http\Middleware\CheckAdminToken;

Route::post('/contact', [SubmissionController::class, 'storeContact']);
Route::post('/reviews', [SubmissionController::class, 'storeReview']);

Route::middleware([CheckAdminToken::class])->get('/submissions', [SubmissionController::class, 'index']);
