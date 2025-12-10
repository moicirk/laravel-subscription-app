<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelSubscriptionRequest;
use App\Http\Requests\SubscribeRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Repositories\PlanRepository;
use App\Repositories\PromoCodeRepository;
use App\Repositories\SubscriptionRepository;
use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

final class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly PlanRepository $planRepository,
        private readonly PromoCodeRepository $promoCodeRepository
    ) {}

    /**
     * Get all subscriptions for the authenticated user.
     *
     * Retrieves all subscriptions (active and inactive) for the currently
     * authenticated user with related plan and promo code information.
     */
    public function index(Request $request): JsonResource
    {
        return SubscriptionResource::collection(
            $this->subscriptionRepository->allForUser($request->user())
        );
    }

    /**
     * Get the current active subscription for the authenticated user.
     *
     * Returns the user's active subscription if one exists.
     */
    public function current(Request $request): JsonResource
    {
        return new SubscriptionResource(
            $this->subscriptionRepository->currentForUser($request->user())
        );
    }

    /**
     * Subscribe to a plan.
     *
     * Creates a new subscription for the authenticated user with the specified
     * plan and optional promo code.
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function subscribe(SubscribeRequest $request): JsonResource
    {
        DB::beginTransaction();

        try {
            $user = $request->user();
            $plan = $this->planRepository->findOrFail($request->input('plan_id'));
            $promoCode = $request->filled('promo_code')
                ? $this->promoCodeRepository->findByCode($request->input('promo_code')) : null;

            $subscription = $this->subscriptionService->subscribe($user, $plan, $promoCode);

            DB::commit();

            return new SubscriptionResource($subscription);
        } catch (\Exception $e) {
            DB::rollBack();

            throw new \Exception('Failed to subscribe: '.$e->getMessage());
        }
    }

    /**
     * Cancel a subscription.
     *
     * Cancels the user's active subscription and sets the end date to now.
     * Also cancels any pending invoices associated with the subscription.
     *
     * @throws
     */
    public function cancel(CancelSubscriptionRequest $request, int $id): JsonResource
    {
        DB::beginTransaction();

        try {
            $subscription = $this->subscriptionRepository->find($id);
            if ($subscription->user_id !== $request->user()->id) {
                throw new AuthorizationException('You do not have permission to cancel this subscription.');
            }

            if ($subscription->end_date < now()) {
                throw new ModelNotFoundException('This subscription is already cancelled or expired.');
            }

            $this->subscriptionService->cancel($subscription, $request->input('reason'));
            $subscription->fresh();
            DB::commit();

            return new SubscriptionResource($subscription);
        } catch (\Exception $e) {
            DB::rollBack();

            throw new \Exception('Failed to cancel subscription: '.$e->getMessage());
        }
    }

    /**
     * Renew a subscription.
     *
     * Renews an existing subscription for another billing period.
     *
     * @throws
     */
    public function renew(Request $request, int $id): JsonResource
    {
        DB::beginTransaction();

        try {
            $subscription = $this->subscriptionRepository->find($id);
            if ($subscription->user_id !== $request->user()->id) {
                throw new AuthorizationException('You do not have permission to cancel this subscription.');
            }

            $this->subscriptionService->renew($subscription);
            $subscription->fresh();
            DB::commit();

            return new SubscriptionResource($subscription);
        } catch (\Exception $e) {
            DB::rollBack();

            throw new \Exception('Failed to renew subscription: '.$e->getMessage());
        }
    }
}
