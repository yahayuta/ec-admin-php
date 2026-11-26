<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Fail loudly in development when a relation is loaded lazily inside a loop.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
