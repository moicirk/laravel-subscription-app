<?php

namespace App\Http\Controllers;

use App\Repositories\PlanRepository;
use App\Repositories\SubscriptionRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __construct(
        private readonly PlanRepository $planRepository,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {}

    /**
     * Display all available subscription plans.
     *
     * Shows all plans with features to both authenticated and guest users.
     * If user is authenticated, shows their current subscription status.
     */
    public function index(Request $request): View
    {
        $plans = $this->planRepository->allWithFeatures();

        $currentSubscription = null;
        if ($request->user()) {
            try {
                $currentSubscription = $this->subscriptionRepository->currentForUser($request->user());
            } catch (\Exception $e) {
                // No active subscription
            }
        }

        return view('plans.index', [
            'plans' => $plans,
            'currentSubscription' => $currentSubscription,
            'isAuthenticated' => (bool) $request->user(),
        ]);
    }
}
