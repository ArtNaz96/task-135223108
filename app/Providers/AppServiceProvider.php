<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('contact-form', function (Request $request) {
            $limit = (int) env('RATE_LIMIT_CONTACT_PER_MINUTE', 5);
            return Limit::perMinute($limit)->by($request->ip());
        });
    }
}