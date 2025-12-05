<?php

namespace App\Models;

use App\Enums\UserPaymentMethodTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'details',
        'is_default'
    ];

    protected function casts(): array
    {
        return [
            'type' => UserPaymentMethodTypeEnum::class,
            'is_default' => 'boolean',
            'details' => 'array'
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
