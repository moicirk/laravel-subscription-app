<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionActive implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(SubscriptionService $subscriptionService): void
    {
        User::whereDoesntHave('subscriptions', function ($query) {
                $query->where('end_date', '>', now());
            })
            ->whereNotNull('plan_id')
            ->where('auto_subscription', true)
            ->chunk(100, function ($users) use ($subscriptionService) {
                foreach ($users as $user) {
                    $this->checkUserSubscription($user, $subscriptionService);
                }
            });
    }

    private function checkUserSubscription(User $user, SubscriptionService $subscriptionService): void
    {
        try {
            $subscriptionService->subscribe($user, $user->plan);
        } catch (\Throwable $exception) {
            Log::error("Could not create the subscription for user {$user->id}: {$exception->getMessage()}");
        }
    }
}
