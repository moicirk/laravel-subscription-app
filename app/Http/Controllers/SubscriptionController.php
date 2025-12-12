<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelSubscriptionRequest;
use App\Http\Requests\SubscribeRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Repositories\PlanRepository;
use App\Repositories\PromoCodeRepository;
use App\Repositories\SubscriptionRepository;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly PlanRepository $planRepository,
        private readonly PromoCodeRepository $promoCodeRepository
    ) {
        $this->middleware('auth');
    }

    /**
     * Subscribe to a new plan.
     */
    public function subscribe(SubscribeRequest $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $plan = $this->planRepository->findOrFail($request->input('plan_id'));

            $promoCode = null;
            if ($request->filled('promo_code')) {
                $promoCode = $this->promoCodeRepository->findByCode($request->input('promo_code'));
            }

            DB::beginTransaction();

            $subscription = $this->subscriptionService->subscribe($user, $plan, $promoCode);

            DB::commit();

            return redirect()
                ->route('plans.index')
                ->with('success', "Successfully subscribed to {$plan->name} plan!");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Failed to create subscription: '.$e->getMessage());
        }
    }

    /**
     * Upgrade subscription to a new plan.
     */
    public function upgrade(UpdateSubscriptionRequest $request, int $id): RedirectResponse
    {
        try {
            $subscription = $this->subscriptionRepository->find($id);

            if ($subscription->user_id !== $request->user()->id) {
                return redirect()
                    ->back()
                    ->with('error', 'You do not have permission to update this subscription.');
            }

            $newPlan = $this->planRepository->findOrFail($request->input('plan_id'));

            DB::beginTransaction();

            $this->subscriptionService->upgrade($subscription, $newPlan);

            DB::commit();

            return redirect()
                ->route('plans.index')
                ->with('success', "Successfully upgraded to {$newPlan->name} plan!");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Failed to upgrade subscription: '.$e->getMessage());
        }
    }

    /**
     * Downgrade subscription to a new plan.
     */
    public function downgrade(UpdateSubscriptionRequest $request, int $id): RedirectResponse
    {
        try {
            $subscription = $this->subscriptionRepository->find($id);

            if ($subscription->user_id !== $request->user()->id) {
                return redirect()
                    ->back()
                    ->with('error', 'You do not have permission to update this subscription.');
            }

            $newPlan = $this->planRepository->findOrFail($request->input('plan_id'));

            DB::beginTransaction();

            $this->subscriptionService->downgrade($subscription, $newPlan);

            DB::commit();

            return redirect()
                ->route('plans.index')
                ->with('success', "Successfully downgraded to {$newPlan->name} plan!");
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Failed to downgrade subscription: '.$e->getMessage());
        }
    }

    /**
     * Cancel a subscription.
     */
    public function cancel(CancelSubscriptionRequest $request, int $id): RedirectResponse
    {
        try {
            $subscription = $this->subscriptionRepository->find($id);

            if ($subscription->user_id !== $request->user()->id) {
                return redirect()
                    ->back()
                    ->with('error', 'You do not have permission to cancel this subscription.');
            }

            DB::beginTransaction();

            $this->subscriptionService->cancel($subscription, $request->input('reason'));

            DB::commit();

            return redirect()
                ->route('plans.index')
                ->with('success', 'Your subscription has been cancelled successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Failed to cancel subscription: '.$e->getMessage());
        }
    }
}
