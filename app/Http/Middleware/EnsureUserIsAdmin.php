<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Autorise les routes de back-office. Par défaut réservé aux administrateurs ;
     * passer "moderator" (middleware "admin:moderator") pour l'ouvrir aussi aux modérateurs.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role = 'admin'): Response
    {
        $user = $request->user();
        $allowed = $role === 'moderator' ? $user?->isModerator() : $user?->isAdmin();

        if (! $allowed) {
            abort(403, "Accès réservé à l'administration.");
        }

        return $next($request);
    }
}
