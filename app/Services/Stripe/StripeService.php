<?php

namespace App\Services\Stripe;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserPaymentMethod;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

readonly class StripeService implements PaymentGatewayInterface
{
    public function __construct(
        private StripeClient $stripe
    ) {}

    /**
     * @throws \Exception
     */
    public function createCustomer(User $user): void
    {
        try {
            if (! $user->stripe_customer_id) {
                $customer = $this->stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => ['user_id' => $user->id],
                ]);

                $user->update(['stripe_customer_id' => $customer->id]);
            }
        } catch (ApiErrorException $e) {
            throw new \Exception("Failed to create Stripe customer: {$e->getMessage()}", 0, $e);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Charge a payment using Stripe PaymentIntent.
     *
     * Creates a payment intent with the specified amount and payment method,
     * then confirms the payment automatically.
     *
     * @param  float  $amount  The amount to charge in the base currency
     * @param  UserPaymentMethod  $method  The payment method to use
     * @return PaymentResult The result of the charge operation
     */
    public function charge(float $amount, UserPaymentMethod $method): PaymentResult
    {
        try {
            $amountInCents = (int) ($amount * 100);
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amountInCents,
                'currency' => 'eur',
                'customer' => $method->user->stripe_customer_id,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            if ($paymentIntent->status === 'succeeded') {
                return PaymentResult::success(
                    transactionId: $paymentIntent->id,
                    metadata: [
                        'amount' => $amount,
                        'currency' => $paymentIntent->currency,
                        'status' => $paymentIntent->status,
                    ]
                );
            }

            return PaymentResult::failure(
                errorMessage: "Payment failed with status: {$paymentIntent->status}",
                metadata: ['payment_intent_id' => $paymentIntent->id]
            );
        } catch (ApiErrorException $e) {
            return PaymentResult::failure(
                errorMessage: $e->getMessage(),
                metadata: ['error_code' => $e->getStripeCode()]
            );
        } catch (\Exception $e) {
            return PaymentResult::failure(
                errorMessage: $e->getMessage()
            );
        }
    }

    /**
     * Refund a payment through Stripe.
     *
     * Creates a refund for the specified payment amount.
     * If the amount is less than the original payment, creates a partial refund.
     *
     * @param  Payment  $payment  The payment to refund
     * @param  float  $amount  The amount to refund
     * @return PaymentResult The result of the refund operation
     */
    public function refund(Payment $payment, float $amount): PaymentResult
    {
        try {
            $amountInCents = (int) ($amount * 100);

            $refund = $this->stripe->refunds->create([
                'payment_intent' => $payment->transaction_id,
                'amount' => $amountInCents,
            ]);

            if ($refund->status === 'succeeded') {
                return PaymentResult::success(
                    transactionId: $refund->id,
                    metadata: [
                        'amount' => $amount,
                        'currency' => $refund->currency,
                        'status' => $refund->status,
                        'original_payment_id' => $payment->id,
                    ]
                );
            }

            return PaymentResult::failure(
                errorMessage: "Refund failed with status: {$refund->status}",
                metadata: ['refund_id' => $refund->id]
            );
        } catch (ApiErrorException $e) {
            return PaymentResult::failure(
                errorMessage: $e->getMessage(),
                metadata: ['error_code' => $e->getStripeCode()]
            );
        } catch (\Exception $e) {
            return PaymentResult::failure(
                errorMessage: $e->getMessage()
            );
        }
    }

    /**
     * Create a subscription in Stripe.
     *
     * Creates a Stripe subscription for the customer based on the plan
     * associated with the subscription model.
     *
     * @param  Subscription  $subscription  The subscription to create in Stripe
     * @return string The Stripe subscription ID
     *
     * @throws \Exception If subscription creation fails
     */
    public function createSubscription(Subscription $subscription): string
    {
        try {
            $subscription->load(['user', 'plan']);

            $stripeSubscription = $this->stripe->subscriptions->create([
                'customer' => $this->getOrCreateStripeCustomer($subscription->user),
                'items' => [
                    ['price' => $this->getStripePriceId($subscription->plan)],
                ],
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->plan_id,
                ],
            ]);

            return $stripeSubscription->id;
        } catch (ApiErrorException $e) {
            throw new \Exception("Failed to create Stripe subscription: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Cancel a subscription in Stripe.
     *
     * Cancels the Stripe subscription immediately.
     *
     * @param  string  $externalId  The Stripe subscription ID
     *
     * @throws \Exception If cancellation fails
     */
    public function cancelSubscription(string $externalId): void
    {
        try {
            $this->stripe->subscriptions->cancel($externalId);
        } catch (ApiErrorException $e) {
            throw new \Exception("Failed to cancel Stripe subscription: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get or create a Stripe customer for the user.
     *
     * Retrieves the Stripe customer ID from user metadata or creates a new customer.
     *
     * @param  \App\Models\User  $user  The user to get/create customer for
     * @return string The Stripe customer ID
     *
     * @throws ApiErrorException
     */
    protected function getOrCreateStripeCustomer(\App\Models\User $user): string
    {
        $stripeCustomerId = $user->stripe_customer_id ?? null;

        if ($stripeCustomerId) {
            return $stripeCustomerId;
        }

        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    /**
     * Get the Stripe price ID for a plan.
     *
     * Retrieves the Stripe price ID from the plan metadata.
     * In production, this should be stored in the database.
     *
     * @param  \App\Models\Plan  $plan  The plan to get price ID for
     * @return string The Stripe price ID
     *
     * @throws \Exception If price ID is not configured
     */
    protected function getStripePriceId(\App\Models\Plan $plan): string
    {
        $stripePriceId = $plan->stripe_price_id ?? null;

        if (! $stripePriceId) {
            throw new \Exception("Stripe price ID not configured for plan {$plan->id}");
        }

        return $stripePriceId;
    }
}
