<?php

namespace App\Providers;

use App\Repositories\FeedbackRepositoryInterface;
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
    }

    public function boot(): void
    {
        //
    }
}