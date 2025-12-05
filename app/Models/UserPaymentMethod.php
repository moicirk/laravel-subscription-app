<?php

namespace App\Models;

use App\Enums\UserPaymentMethodTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property UserPaymentMethodTypeEnum $type
 * @property string $details
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethod query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethod whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethod whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethod whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethod whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethod whereUserId($value)
 * @mixin \Eloquent
 */
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
