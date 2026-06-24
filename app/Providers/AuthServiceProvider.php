<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\User;
use App\Policies\AnimalPolicy;
use App\Policies\FarmPolicy;
use App\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Animal::class => AnimalPolicy::class,
        Farm::class => FarmPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
