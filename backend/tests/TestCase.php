<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Authentifie les prochaines requêtes de test en tant que $user, via un token Sanctum.
     *
     * Le guard "sanctum" met en cache l'utilisateur résolu pour la durée du test ; sans
     * réinitialisation, une deuxième authentification avec un autre utilisateur dans le
     * même test continuerait de résoudre le premier. Toujours utiliser ce helper plutôt
     * que withHeader('Authorization', ...) manuel dès qu'un test authentifie plus d'un
     * utilisateur différent au cours de son exécution.
     */
    protected function authenticateAs(User $user): static
    {
        $this->app['auth']->forgetGuards();

        $token = $user->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', "Bearer {$token}");
    }
}
