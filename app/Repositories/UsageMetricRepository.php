<?php

namespace App\Repositories;

use App\Enums\UsageMetricResourceEnum;
use App\Enums\UsageMetricTypeEnum;
use App\Models\UsageMetric;
use App\Models\User;

class UsageMetricRepository
{
    public function create(
        User $user,
        UsageMetricTypeEnum $type,
        UsageMetricResourceEnum $resource = UsageMetricResourceEnum::WEB,
        array $details = []
    ): UsageMetric {
        return UsageMetric::create([
            'user_id' => $user->id,
            'type' => $type,
            'resource' => $resource,
            'details' => $details,
            'created_at' => now()
        ]);
    }
}
