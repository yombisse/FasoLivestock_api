<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\User;
use App\Models\Lot;
use App\Models\Transaction;
use App\Models\Naissance;
use App\Models\SanteRappel;
use App\Models\Ration;
use App\Models\Espece;
use App\Policies\AnimalPolicy;
use App\Policies\FarmPolicy;
use App\Policies\UserPolicy;
use App\Policies\LotPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\NaissancePolicy;
use App\Policies\SanteRappelPolicy;
use App\Policies\RationPolicy;
use App\Policies\EspecePolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Animal::class => AnimalPolicy::class,
        Farm::class => FarmPolicy::class,
        User::class => UserPolicy::class,
        Lot::class => LotPolicy::class,
        Transaction::class => TransactionPolicy::class,
        Naissance::class => NaissancePolicy::class,
        SanteRappel::class => SanteRappelPolicy::class,
        Ration::class => RationPolicy::class,
        Espece::class => EspecePolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
