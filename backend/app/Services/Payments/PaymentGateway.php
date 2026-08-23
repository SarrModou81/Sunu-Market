<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Ouvre une session de paiement chez le fournisseur pour le montant du Payment donné.
     */
    public function initiateCheckout(Payment $payment): CheckoutResult;

    /**
     * Vérifie l'authenticité de la requête webhook (signature) avant tout traitement.
     * Ne doit jamais faire confiance à un webhook non vérifié.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Extrait un événement normalisé d'une requête webhook déjà vérifiée.
     */
    public function parseWebhook(Request $request): WebhookEvent;

    /**
     * Interroge activement le fournisseur sur le statut d'un paiement (filet de
     * sécurité si le webhook n'arrive jamais).
     */
    public function checkStatus(Payment $payment): bool;
}
