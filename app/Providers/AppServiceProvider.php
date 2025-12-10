<?php

namespace App\Providers;

use App\Services\Subscription\SubscriptionService;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
