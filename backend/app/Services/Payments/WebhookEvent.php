<?php

namespace App\Services\Payments;

/**
 * Événement de paiement normalisé, extrait d'un webhook fournisseur après
 * vérification de la signature.
 */
final readonly class WebhookEvent
{
    public function __construct(
        public string $providerReference,
        public bool $succeeded,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
