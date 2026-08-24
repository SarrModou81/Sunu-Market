<?php

namespace App\Exceptions;

use Exception;

class FirebaseAuthException extends Exception
{
    public static function invalidToken(): self
    {
        return new self("Jeton d'authentification invalide ou expiré.");
    }

    public static function missingPhoneNumberClaim(): self
    {
        return new self("Le jeton d'authentification ne contient pas de numéro de téléphone vérifié.");
    }

    public static function unrecognizedPhoneNumber(): self
    {
        return new self("Le numéro de téléphone vérifié n'est pas un numéro sénégalais valide.");
    }

    public static function missingProfile(): self
    {
        return new self('Aucun compte pour ce numéro : first_name et last_name sont requis pour créer un compte.');
    }
}
