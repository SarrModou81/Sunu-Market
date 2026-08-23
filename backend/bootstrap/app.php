<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // Rate limiting global sur toutes les routes API (cahier des charges §21),
        // en complément des limites plus strictes définies route par route.
        $middleware->throttleApi();

        $middleware->api(append: [SecurityHeaders::class]);

        // Ne fait confiance à aucun proxy par défaut (IP client = IP de connexion réelle,
        // ce qui sécurise le rate limiting par IP). En production derrière un load
        // balancer/reverse proxy connu, renseigner TRUSTED_PROXIES (IP ou CIDR, séparés
        // par des virgules) pour que l'en-tête X-Forwarded-For soit pris en compte.
        if ($trustedProxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: explode(',', $trustedProxies));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
