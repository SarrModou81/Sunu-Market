<?php

namespace App\Providers;

use App\Models\Boost;
use App\Models\Message;
use App\Models\Product;
use App\Models\Report;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Alias courts pour les relations polymorphiques (reports, payments...) :
        // évite d'exposer/accepter des noms de classe PHP complets via l'API.
        Relation::enforceMorphMap([
            'product' => Product::class,
            'user' => User::class,
            'message' => Message::class,
            'boost' => Boost::class,
            'subscription' => Subscription::class,
            'report' => Report::class,
        ]);
    }
}
