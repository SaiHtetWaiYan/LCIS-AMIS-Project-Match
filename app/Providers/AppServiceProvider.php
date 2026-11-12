<?php

namespace App\Providers;

use App\Services\Amis\AmisApiClient;
use App\Services\Lcis\LcisApiClient;
use App\Services\ProvinceDirectory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LcisApiClient::class, fn (): LcisApiClient => new LcisApiClient(config('services.lcis')));
        $this->app->bind(AmisApiClient::class, fn (): AmisApiClient => new AmisApiClient(config('services.amis')));
        $this->app->scoped(ProvinceDirectory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
