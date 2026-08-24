<?php

return [
    // "fake" (développement/tests, accepte n'importe quel jeton et le traite comme un
    // numéro de téléphone déjà normalisé) ou "kreait" (vérification réelle via
    // kreait/firebase-tokens contre les clés publiques Google du projet Firebase).
    'driver' => env('FIREBASE_DRIVER', 'fake'),

    // Identifiant du projet Firebase (Console Firebase > Paramètres du projet).
    // Seule information nécessaire pour vérifier un jeton d'authentification par
    // téléphone : aucune clé de compte de service n'est requise pour cet usage.
    'project_id' => env('FIREBASE_PROJECT_ID'),
];
