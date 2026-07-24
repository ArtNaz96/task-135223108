<?php

namespace App\Providers;

use App\Services\AI\AiGatewayInterface;
use App\Services\AI\OpenAiGateway;
use App\Services\AiProcessingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiGatewayInterface::class, function () {
            return new OpenAiGateway(
                baseUrl: (string) config('services.ai_gateway.url'),
                apiKey: (string) config('services.ai_gateway.api_key'),
                model: (string) config('services.ai_gateway.model'),
                timeout: (int) config('services.ai_gateway.timeout'),
            );
        });

        $this->app->when(AiProcessingService::class)
            ->needs('$systemPrompt')
            ->give(fn () => file_get_contents(resource_path('ai-prompts/feedback-extraction.txt')));
    }

    public function boot(): void
    {
        RateLimiter::for('contact-form', function (Request $request) {
            $limit = (int) env('FEEDBACK_RATE_LIMIT_PER_MINUTE', 5);
            return Limit::perMinute($limit)->by($request->ip());
        });
    }
}