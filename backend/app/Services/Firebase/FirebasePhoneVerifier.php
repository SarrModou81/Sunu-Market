<?php

namespace App\Services\Firebase;

use App\Exceptions\FirebaseAuthException;

interface FirebasePhoneVerifier
{
    /**
     * Vérifie un jeton d'authentification par téléphone et retourne le numéro
     * vérifié au format canonique +221XXXXXXXXX.
     *
     * @throws FirebaseAuthException
     */
    public function verify(string $idToken): string;
}
