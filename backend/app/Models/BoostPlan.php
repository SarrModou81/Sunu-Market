<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'price', 'duration_hours', 'is_active'])]
class BoostPlan extends Model
{
    use HasFactory;

    public const CODE_72H = '72h';

    public const CODE_7D = '7d';

    public const CODE_30D = '30d';

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_hours' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function boosts(): HasMany
    {
        return $this->hasMany(Boost::class);
    }
}
