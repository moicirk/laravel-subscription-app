<?php

namespace App\Repositories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SubscriptionRepository
{
    /**
     * Find a subscription by ID.
     *
     * @throws ModelNotFoundException
     */
    public function find(int $id): Subscription
    {
        return Subscription::findOrFail($id);
    }

    /**
     * Create a new subscription.
     */
    public function create(array $data): Subscription
    {
        return Subscription::create($data);
    }

    /**
     * Update a subscription.
     */
    public function update(Subscription $subscription, array $data): bool
    {
        return $subscription->update($data);
    }

    /**
     * Get subscriptions for the concrete user.
     */
    public function allForUser(User $user): Collection
    {
        return $user->subscriptions()
            ->with(['plan', 'promoCode'])
            ->latest()
            ->get();
    }

    /**
     * Return current subscription for the user, if it exists.
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

    /**
     * Get the latest active subscription for a user.
     */
    public function getLatestActiveForUser(int $userId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('end_date', '>', now())
            ->latest()
            ->first();
    }
}
