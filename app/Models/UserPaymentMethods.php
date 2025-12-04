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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethods newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethods newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethods query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethods whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethods whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethods whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethods whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethods whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethods whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPaymentMethods whereUserId($value)
 * @mixin \Eloquent
 */
class UserPaymentMethods extends Model
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
            'is_default' => 'boolean'
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
