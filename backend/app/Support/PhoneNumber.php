<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalise un numéro de téléphone sénégalais vers le format canonique +221XXXXXXXXX.
     * Accepte les formats courants : 771234567, 0771234567, 221771234567, +221771234567.
     * Retourne null si le numéro ne correspond pas à un mobile sénégalais valide.
     */
    public static function normalize(string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', $raw);

        if (str_starts_with($digits, '00221')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '221') && strlen($digits) === 12) {
            $national = substr($digits, 3);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $national = substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            $national = $digits;
        } else {
            return null;
        }

        if (! preg_match('/^7[0-8]\d{7}$/', $national)) {
            return null;
        }

        return '+221'.$national;
    }

    public static function isValid(string $raw): bool
    {
        return self::normalize($raw) !== null;
    }
}
