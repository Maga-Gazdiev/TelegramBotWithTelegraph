<?php

namespace App\Providers;

use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Support\ServiceProvider;
use App\Services\Telegram\TelegramService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TelegramService::class, function ($app) {
            return new TelegramService();
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
