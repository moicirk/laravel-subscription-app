<?php

namespace App\Repositories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PlanRepository
{
    /**
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Plan
    {
        return Plan::findOrFail($id);
    }
}
