<?php

namespace App\Models;

use App\Enums\UsageMetricTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'resource',
        'details',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => UsageMetricTypeEnum::class,
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
