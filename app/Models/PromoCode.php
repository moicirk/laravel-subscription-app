<?php

namespace App\Models;

use App\Enums\PromoCodeTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'type',
        'value'
    ];

    protected function casts(): array
    {
        return [
            'type' => PromoCodeTypeEnum::class,
            'value' => 'decimal:2'
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
