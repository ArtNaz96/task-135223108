<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\FeedbackController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/health', [ApiController::class, 'health']);

Route::post('/v1/contact', [FeedbackController::class, 'store'])
    ->middleware('throttle:contact-form');