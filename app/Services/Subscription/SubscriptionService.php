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

    protected function calculateEndDate(Carbon $startDate, PlanTypeEnum $type): Carbon
    {
        return match ($type) {
            PlanTypeEnum::DAILY => $startDate->copy()->addDay(),
            PlanTypeEnum::MONTHLY => $startDate->copy()->addMonth(),
            PlanTypeEnum::YEARLY => $startDate->copy()->addYear(),
        };
    }

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

    protected function calculateTax(float $price): float
    {
        return $price * 0.24;
    }

    protected function getDailyRate(Plan $plan): float
    {
        return match ($plan->type) {
            PlanTypeEnum::DAILY => (float) $plan->price,
            PlanTypeEnum::MONTHLY => (float) $plan->price / 30,
            PlanTypeEnum::YEARLY => (float) $plan->price / 365,
        };
    }
}
