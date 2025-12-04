<?php

namespace App\Services\Subscription;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PromoCode;
use App\Models\Subscription;
use App\Models\User;

interface SubscriptionServiceInterface
{
    public function subscribe(User $tenant, Plan $plan, ?PromoCode $promoCode = null): Subscription;

    public function upgrade(Subscription $subscription, Plan $newPlan): void;

    public function downgrade(Subscription $subscription, Plan $newPlan): void;

    public function cancel(Subscription $subscription, string $reason): void;

    public function renew(Subscription $subscription): void;

    public function calculateProration(Subscription $subscription, Plan $newPlan): float;

    public function checkUsageLimit(User $tenant, PlanFeature $feature): bool;
}
