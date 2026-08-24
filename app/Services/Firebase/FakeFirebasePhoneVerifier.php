<?php

namespace App\Services\Firebase;

use App\Exceptions\FirebaseAuthException;
use App\Support\PhoneNumber;

/**
 * Fournisseur de développement/tests : traite le jeton fourni comme un numéro
 * de téléphone en clair, sans appel réseau ni projet Firebase réel. Permet de
 * valider tout le flux d'authentification sans compte Firebase. Ne doit
 * jamais être utilisé en production (voir FirebaseVerifierFactory).
 */
class FakeFirebasePhoneVerifier implements FirebasePhoneVerifier
{
    public function verify(string $idToken): string
    {
        $normalized = PhoneNumber::normalize($idToken);

        if ($normalized === null) {
            throw FirebaseAuthException::invalidToken();
        }

        return $normalized;
    }
}
