<?php

namespace App\Providers;

use App\Contracts\WhatsAppProviderInterface;
use App\Models\CreditApplication;
use App\Models\DanaTunaiApplication;
use App\Observers\CreditApplicationObserver;
use App\Observers\DanaTunaiApplicationObserver;
use App\Services\BirdWhatsAppProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(WhatsAppProviderInterface::class, BirdWhatsAppProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        CreditApplication::observe(CreditApplicationObserver::class);
        DanaTunaiApplication::observe(DanaTunaiApplicationObserver::class);
    }
}
