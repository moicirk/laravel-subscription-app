<?php

namespace App\Services\Subscription;

use App\Enums\InvoiceStatusEnum;
use App\Enums\PlanTypeEnum;
use App\Enums\PromoCodeTypeEnum;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PromoCode;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService implements SubscriptionServiceInterface
{
    /**
     * Create a new subscription for a user with a given plan.
     *
     * Creates a subscription record, calculates pricing with optional promo code,
     * and generates a pending invoice for the subscription period.
     *
     * @param User $tenant The user who is subscribing
     * @param Plan $plan The plan to subscribe to
     * @param PromoCode|null $promoCode Optional promo code to apply discount
     * @return Subscription The created subscription instance
     */
    public function subscribe(User $tenant, Plan $plan, ?PromoCode $promoCode = null): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $promoCode) {
            $startDate = Carbon::now();
            $endDate = $this->calculateEndDate($startDate, $plan->type);

            $subscription = Subscription::create([
                'user_id' => $tenant->id,
                'plan_id' => $plan->id,
                'promo_code_id' => $promoCode?->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            $price = $this->calculatePrice($plan, $promoCode);

            Invoice::create([
                'user_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'status' => InvoiceStatusEnum::PENDING,
                'price' => $price,
                'tax' => $this->calculateTax($price),
            ]);

            return $subscription;
        });
    }

    /**
     * Upgrade a subscription to a higher-tier plan.
     *
     * Updates the subscription to the new plan, calculates prorated charges
     * for the remaining period, and creates an invoice for the difference if positive.
     *
     * @param Subscription $subscription The subscription to upgrade
     * @param Plan $newPlan The new plan to upgrade to
     * @return void
     */
    public function upgrade(Subscription $subscription, Plan $newPlan): void
    {
        DB::transaction(function () use ($subscription, $newPlan) {
            $proration = $this->calculateProration($subscription, $newPlan);

            $oldEndDate = $subscription->end_date;
            $newEndDate = $this->calculateEndDate(Carbon::now(), $newPlan->type);

            $subscription->update([
                'plan_id' => $newPlan->id,
                'end_date' => $newEndDate,
            ]);

            if ($proration > 0) {
                Invoice::create([
                    'user_id' => $subscription->user_id,
                    'subscription_id' => $subscription->id,
                    'status' => InvoiceStatusEnum::PENDING,
                    'price' => $proration,
                    'tax' => $this->calculateTax($proration),
                ]);
            }
        });
    }

    /**
     * Downgrade a subscription to a lower-tier plan.
     *
     * Updates the subscription to the new plan and recalculates the end date
     * based on the new plan type. No refund is issued for unused time.
     *
     * @param Subscription $subscription The subscription to downgrade
     * @param Plan $newPlan The new plan to downgrade to
     * @return void
     */
    public function downgrade(Subscription $subscription, Plan $newPlan): void
    {
        DB::transaction(function () use ($subscription, $newPlan) {
            $newEndDate = $this->calculateEndDate(Carbon::now(), $newPlan->type);

            $subscription->update([
                'plan_id' => $newPlan->id,
                'end_date' => $newEndDate,
            ]);
        });
    }

    /**
     * Cancel an active subscription.
     *
     * Sets the subscription end date to the current time and cancels
     * all pending invoices associated with the subscription.
     *
     * @param Subscription $subscription The subscription to cancel
     * @param string $reason The reason for cancellation (currently unused)
     * @return void
     */
    public function cancel(Subscription $subscription, string $reason): void
    {
        DB::transaction(function () use ($subscription) {
            $subscription->update([
                'end_date' => Carbon::now(),
            ]);

            Invoice::where('subscription_id', $subscription->id)
                ->where('status', InvoiceStatusEnum::PENDING)
                ->update(['status' => InvoiceStatusEnum::CANCELED]);
        });
    }

    /**
     * Renew a subscription for another billing period.
     *
     * Extends the subscription from its current end date for another period
     * based on the plan type, and creates a new pending invoice for the renewal.
     *
     * @param Subscription $subscription The subscription to renew
     * @return void
     */
    public function renew(Subscription $subscription): void
    {
        DB::transaction(function () use ($subscription) {
            $newStartDate = $subscription->end_date;
            $newEndDate = $this->calculateEndDate($newStartDate, $subscription->plan->type);

            $subscription->update([
                'start_date' => $newStartDate,
                'end_date' => $newEndDate,
            ]);

            $price = $this->calculatePrice($subscription->plan, $subscription->promoCode);

            Invoice::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'status' => InvoiceStatusEnum::PENDING,
                'price' => $price,
                'tax' => $this->calculateTax($price),
            ]);
        });
    }

    /**
     * Calculate the prorated amount when changing plans.
     *
     * Calculates the cost difference between the current plan and new plan
     * for the remaining days in the current billing period. Returns the amount
     * to charge (or 0 if the new plan is cheaper).
     *
     * @param Subscription $subscription The current subscription
     * @param Plan $newPlan The plan to switch to
     * @return float The prorated amount to charge (0 or positive)
     */
    public function calculateProration(Subscription $subscription, Plan $newPlan): float
    {
        $currentPlan = $subscription->plan;
        $now = Carbon::now();
        $remainingDays = $now->diffInDays($subscription->end_date);
        $totalDays = $subscription->start_date->diffInDays($subscription->end_date);

        if ($totalDays === 0) {
            return 0;
        }

        $unusedAmount = ($currentPlan->price / $totalDays) * $remainingDays;
        $newPlanDailyRate = $this->getDailyRate($newPlan);
        $newPlanCost = $newPlanDailyRate * $remainingDays;

        $proration = $newPlanCost - $unusedAmount;

        return max(0, $proration);
    }

    /**
     * Check if a user has access to a specific feature based on their subscription.
     *
     * Verifies if the user has an active subscription and if that subscription's
     * plan includes the specified feature.
     *
     * @param User $tenant The user to check access for
     * @param PlanFeature $feature The feature to verify access to
     * @return bool True if the user has access to the feature, false otherwise
     */
    public function checkUsageLimit(User $tenant, PlanFeature $feature): bool
    {
        $subscription = Subscription::where('user_id', $tenant->id)
            ->where('end_date', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$subscription) {
            return false;
        }

        $planFeatures = $subscription->plan->features ?? collect();

        return $planFeatures->contains('id', $feature->id);
    }

    /**
     * Calculate the end date for a subscription based on plan type.
     *
     * Adds the appropriate time period (day, month, or year) to the start date
     * based on the plan's billing cycle type.
     *
     * @param Carbon $startDate The subscription start date
     * @param PlanTypeEnum $type The plan billing cycle type (daily, monthly, yearly)
     * @return Carbon The calculated end date
     */
    protected function calculateEndDate(Carbon $startDate, PlanTypeEnum $type): Carbon
    {
        return match ($type) {
            PlanTypeEnum::DAILY => $startDate->copy()->addDay(),
            PlanTypeEnum::MONTHLY => $startDate->copy()->addMonth(),
            PlanTypeEnum::YEARLY => $startDate->copy()->addYear(),
        };
    }

    /**
     * Calculate the final price for a plan with optional promo code discount.
     *
     * Applies promo code discounts (fixed amount or percentage) to the plan price.
     * Ensures the final price is never negative.
     *
     * @param Plan $plan The plan to calculate price for
     * @param PromoCode|null $promoCode Optional promo code to apply
     * @return float The final calculated price after discounts
     */
    protected function calculatePrice(Plan $plan, ?PromoCode $promoCode): float
    {
        $price = (float) $plan->price;

        if (!$promoCode) {
            return $price;
        }

        return match ($promoCode->type) {
            PromoCodeTypeEnum::FIXED => max(0, $price - (float) $promoCode->value),
            PromoCodeTypeEnum::PERCENTAGE => $price * (1 - ((float) $promoCode->value / 100)),
        };
    }

    /**
     * Calculate the tax amount for a given price.
     *
     * Applies a 24% tax rate to the provided price.
     *
     * @param float $price The price to calculate tax for
     * @return float The calculated tax amount
     */
    protected function calculateTax(float $price): float
    {
        return $price * 0.24;
    }

    /**
     * Get the daily rate for a plan based on its billing cycle.
     *
     * Converts the plan price to a daily rate for proration calculations.
     * Uses 30 days for monthly plans and 365 days for yearly plans.
     *
     * @param Plan $plan The plan to calculate daily rate for
     * @return float The daily rate for the plan
     */
    protected function getDailyRate(Plan $plan): float
    {
        return match ($plan->type) {
            PlanTypeEnum::DAILY => (float) $plan->price,
            PlanTypeEnum::MONTHLY => (float) $plan->price / 30,
            PlanTypeEnum::YEARLY => (float) $plan->price / 365,
        };
    }
}
