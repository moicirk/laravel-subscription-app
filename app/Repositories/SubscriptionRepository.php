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
     * @param int $id
     * @return Subscription
     * @throws ModelNotFoundException
     */
    public function find(int $id): Subscription
    {
        return Subscription::findOrFail($id);
    }

    /**
     * Create a new subscription.
     *
     * @param array $data
     * @return Subscription
     */
    public function create(array $data): Subscription
    {
        return Subscription::create($data);
    }

    /**
     * Update a subscription.
     *
     * @param Subscription $subscription
     * @param array $data
     * @return bool
     */
    public function update(Subscription $subscription, array $data): bool
    {
        return $subscription->update($data);
    }

    /**
     * Get subscriptions for the concrete user.
     *
     * @param User $user
     * @return Collection
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
     * @param User $user
     * @return Subscription
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
     *
     * @param int $userId
     * @return Subscription|null
     */
    public function getLatestActiveForUser(int $userId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('end_date', '>', now())
            ->latest()
            ->first();
    }
}
