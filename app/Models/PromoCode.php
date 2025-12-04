<?php

namespace App\Models;

use App\Enums\PromoCodeTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $code
 * @property PromoCodeTypeEnum $type
 * @property numeric $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromoCode whereValue($value)
 * @mixin \Eloquent
 */
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
