<?php

namespace App\Services\Firebase;

use App\Exceptions\FirebaseAuthException;
use App\Support\PhoneNumber;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\IdTokenVerifier;

/**
 * Vérification réelle d'un jeton Firebase Authentication (connexion par
 * téléphone), via kreait/firebase-tokens. La vérification se fait entièrement
 * contre les clés publiques du projet Firebase (JWKS Google) : aucune clé de
 * compte de service n'est nécessaire côté serveur pour ce seul usage.
 */
class KreaitFirebasePhoneVerifier implements FirebasePhoneVerifier
{
    public function verify(string $idToken): string
    {
        $projectId = config('firebase.project_id');

        if (blank($projectId)) {
            throw FirebaseAuthException::invalidToken();
        }

        try {
            $token = IdTokenVerifier::createWithProjectId($projectId)->verifyIdToken($idToken);
        } catch (IdTokenVerificationFailed) {
            throw FirebaseAuthException::invalidToken();
        }

        $phoneNumber = $token->payload()['phone_number'] ?? null;

        if (blank($phoneNumber)) {
            throw FirebaseAuthException::missingPhoneNumberClaim();
        }

        $normalized = PhoneNumber::normalize($phoneNumber);

        if ($normalized === null) {
            throw FirebaseAuthException::unrecognizedPhoneNumber();
        }

        return $normalized;
    }
}
