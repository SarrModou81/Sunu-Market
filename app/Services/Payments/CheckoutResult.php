<?php

namespace App\Services\Payments;

/**
 * Résultat de l'ouverture d'une session de paiement chez le fournisseur.
 */
final readonly class CheckoutResult
{
    public function __construct(
        public string $providerReference,
        public ?string $checkoutUrl,
        /** @var array<string, mixed> */
        public array $raw = [],
    ) {}
}
