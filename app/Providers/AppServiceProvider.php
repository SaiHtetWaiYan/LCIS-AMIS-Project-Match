<?php

namespace App\Providers;

use App\Services\Amis\AmisApiClient;
use App\Services\Lcis\LcisApiClient;
use App\Services\ProvinceDirectory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('api-client', fn (Request $request): Limit => Limit::perMinute(120)->by(
            filled($request->bearerToken()) ? hash('sha256', $request->bearerToken()) : $request->ip(),
        ));
    }
}
