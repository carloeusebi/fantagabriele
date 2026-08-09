<?php

namespace App\Providers;

use App\AuctionAssistant\Contracts\AuctionAssistant;
use App\AuctionAssistant\LaravelAiAuctionAssistant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuctionAssistant::class, LaravelAiAuctionAssistant::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Model::unguard();

        Password::defaults(fn (): Password => Password::min(8));
    }
}
