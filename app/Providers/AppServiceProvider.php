<?php

namespace App\Providers;

use App\Services\Cart;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->scoped(Cart::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('nl');
    }
}
