<?php

namespace App\Repositories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubscriptionRepository
{
    /**
     * @throws ModelNotFoundException
     */
    public function find(int $id): Subscription
    {
        return Subscription::findOrFail($id);
    }

    /**
     * Get subscriptions for the concrete user
     */
    public function allForUser(User $user): Collection
    {
        return $user->subscriptions()
            ->with(['plan', 'promoCode'])
            ->latest()
            ->get();
    }

    /**
     * Return current subscription for the user, if it exists
     *
     * @throws ModelNotFoundException
     */
    public function currentForUser(User $user): Subscription
    {
        return $user->subscriptions()
            ->with(['plan', 'promoCode'])
            ->where('end_date', '>', now())
            ->latest()
            ->firstOrFail();
    }
}
