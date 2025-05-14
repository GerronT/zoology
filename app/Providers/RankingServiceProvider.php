<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RankingService;

class RankingServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind the RankingService as a singleton
        $this->app->singleton(RankingService::class, function ($app) {
            return new RankingService();
        });
    }

    public function boot()
    {
        //
    }
}

