<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['phone', 'code_hash', 'purpose', 'expires_at', 'consumed_at', 'attempts', 'ip_address'])]
class OtpCode extends Model
{
    public const PURPOSE_REGISTER = 'register';

    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_RESET_PASSWORD = 'reset_password';

    public const PURPOSE_CHANGE_PHONE = 'change_phone';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }
}
