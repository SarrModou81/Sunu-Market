<?php

namespace App\Exceptions;

use Exception;

class OtpException extends Exception
{
    public static function tooSoon(int $secondsRemaining): self
    {
        return new self("Veuillez patienter {$secondsRemaining} secondes avant de redemander un code.");
    }

    public static function invalidOrExpired(): self
    {
        return new self('Code invalide ou expiré. Veuillez redemander un nouveau code.');
    }

    public static function tooManyAttempts(): self
    {
        return new self('Trop de tentatives. Veuillez redemander un nouveau code.');
    }
}
