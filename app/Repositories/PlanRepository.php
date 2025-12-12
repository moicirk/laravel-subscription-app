<?php

namespace App\Repositories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PlanRepository
{
    /**
     * Find a plan by ID or fail.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Plan
    {
        return Plan::findOrFail($id);
    }

    /**
     * Get all plans with their features.
     */
    public function allWithFeatures(): Collection
    {
        return Plan::with('features')
            ->orderBy('position')
            ->get();
    }
}
