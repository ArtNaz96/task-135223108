<?php

namespace App\Providers;

use App\Repositories\DatabaseFeedbackInsightRepository;
use App\Repositories\DatabaseFeedbackRepository;
use App\Repositories\FeedbackInsightRepositoryInterface;
use App\Repositories\FeedbackRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            FeedbackRepositoryInterface::class,
            DatabaseFeedbackRepository::class
        );

        $this->app->bind(
            FeedbackInsightRepositoryInterface::class,
            DatabaseFeedbackInsightRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}