<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Fournisseur de développement : écrit le SMS dans les logs au lieu de l'envoyer réellement.
 * Permet de tester le parcours OTP sans dépendre d'un fournisseur SMS tiers.
 */
class LogSmsProvider implements SmsProvider
{
    public function send(string $phone, string $message): bool
    {
        Log::info('[SMS:dev] '.$phone.' -> '.$message);

        return true;
    }
}
