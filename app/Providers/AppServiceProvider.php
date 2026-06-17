<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Services\Admin\AuthApiService;
use App\Services\Admin\UserApiService;
use App\Services\Admin\RoleApiService;
use App\Services\Admin\FarmApiService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthApiService::class);
        $this->app->singleton(UserApiService::class);
        $this->app->singleton(RoleApiService::class);
        $this->app->singleton(FarmApiService::class);
    }

    public function boot(): void
    {
        // ─── Forcer l'URL de base pour toutes les redirections ─
        URL::forceRootUrl(config('app.url'));
    }
}