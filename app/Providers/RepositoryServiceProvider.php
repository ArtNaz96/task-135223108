<?php

namespace App\Providers;

use App\Repositories\FeedbackInsightRepositoryInterface;
use App\Repositories\FeedbackRepositoryInterface;
use App\Repositories\LogFeedbackInsightRepository;
use App\Repositories\LogFeedbackRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            FeedbackRepositoryInterface::class,
            LogFeedbackRepository::class
        );

        $this->app->bind(
            FeedbackInsightRepositoryInterface::class,
            LogFeedbackInsightRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}