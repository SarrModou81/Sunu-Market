<?php

namespace App\Services\Firebase;

use RuntimeException;

class FirebaseVerifierFactory
{
    public function make(): FirebasePhoneVerifier
    {
        // Le pilote "fake" ne doit jamais pouvoir authentifier un utilisateur réel en
        // production, quelle que soit la configuration côté client.
        if (app()->environment('production') && config('firebase.driver') === 'fake') {
            throw new RuntimeException('Le vérificateur Firebase de test ne peut pas être utilisé en production.');
        }

        return match (config('firebase.driver')) {
            'kreait' => app(KreaitFirebasePhoneVerifier::class),
            default => app(FakeFirebasePhoneVerifier::class),
        };
    }
}
