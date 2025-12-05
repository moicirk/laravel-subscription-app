<?php

namespace App\Models;

use App\Enums\InvoiceStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_id',
        'status',
        'price',
        'tax'
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatusEnum::class,
            'price' => 'decimal:2',
            'tax' => 'decimal:2'
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
