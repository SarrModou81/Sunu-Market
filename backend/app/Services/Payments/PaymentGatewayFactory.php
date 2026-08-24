<?php

namespace App\Services\Payments;

use App\Models\Payment;
use InvalidArgumentException;
use RuntimeException;

class PaymentGatewayFactory
{
    public function make(string $provider): PaymentGateway
    {
        // Le pilote "fake" ne doit jamais pouvoir traiter un paiement réel en production,
        // quel que soit le fournisseur demandé côté client.
        if (app()->environment('production') && config('payments.default_driver') === 'fake') {
            throw new RuntimeException('Le fournisseur de paiement de test ne peut pas être utilisé en production.');
        }

        if (config('payments.default_driver') === 'fake' && ! app()->environment('production')) {
            return app(FakeGateway::class);
        }

        return match ($provider) {
            Payment::PROVIDER_WAVE => app(WaveGateway::class),
            Payment::PROVIDER_ORANGE_MONEY => app(OrangeMoneyGateway::class),
            Payment::PROVIDER_PAYTECH => app(PaytechGateway::class),
            default => throw new InvalidArgumentException("Fournisseur de paiement inconnu : {$provider}"),
        };
    }
}
