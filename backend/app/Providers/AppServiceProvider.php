<?php

namespace App\Providers;

use App\Models\Boost;
use App\Models\Message;
use App\Models\Product;
use App\Models\Report;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        // Limite globale par défaut pour toutes les routes API (au-delà des limites plus
        // strictes déjà posées sur les routes sensibles dans routes/api.php) : par
        // utilisateur authentifié si possible, sinon par IP.
        RateLimiter::for('api', function (Request $request) {
            // Le middleware "throttle:api" s'exécute avant "auth:sanctum" sur la requête ;
            // il faut donc interroger explicitement le guard "sanctum" pour retrouver
            // l'utilisateur authentifié par jeton Bearer (le guard par défaut est "web").
            return Limit::perMinute(120)->by($request->user('sanctum')?->id ?: $request->ip());
        });
    }
}
