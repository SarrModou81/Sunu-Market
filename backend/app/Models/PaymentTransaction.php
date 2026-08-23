<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_id', 'type', 'provider_reference', 'status', 'raw_payload', 'signature_valid', 'occurred_at'])]
class PaymentTransaction extends Model
{
    public const TYPE_CHECKOUT_INITIATED = 'checkout_initiated';

    public const TYPE_WEBHOOK = 'webhook';

    public const TYPE_STATUS_CHECK = 'status_check';

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
