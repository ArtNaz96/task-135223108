<?php

use App\Http\Controllers\Api\V1\FeedbackController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MetricsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    Route::post('/contact', FeedbackController::class)
        ->middleware(['api.token', 'throttle:contact-form'])
        ->name('contact.store');

    Route::get('/health', HealthController::class)->name('health.show');
    Route::get('/metrics', MetricsController::class)->name('metrics.show');
});
