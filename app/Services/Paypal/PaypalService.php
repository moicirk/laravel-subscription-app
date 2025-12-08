<?php

namespace App\Services\Paypal;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\UserPaymentMethod;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;
use PayPalHttp\HttpException;

class PaypalService implements PaymentGatewayInterface
{
    public function __construct(
        private PayPalHttpClient $client
    ) {}

    /**
     * Charge a payment using PayPal Order.
     *
     * Creates a PayPal order with the specified amount and captures it immediately.
     *
     * @param float $amount The amount to charge
     * @param UserPaymentMethods $method The payment method to use
     * @return PaymentResult The result of the charge operation
     */
    public function charge(float $amount, UserPaymentMethod $method): PaymentResult
    {
        try {
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                    ],
                ],
                'application_context' => [
                    'return_url' => config('services.paypal.return_url'),
                    'cancel_url' => config('services.paypal.cancel_url'),
                ],
            ];

            $response = $this->client->execute($request);

            if ($response->statusCode !== 201) {
                return PaymentResult::failure('Failed to create PayPal order');
            }

            $orderId = $response->result->id;

            $captureRequest = new OrdersCaptureRequest($orderId);
            $captureResponse = $this->client->execute($captureRequest);

            if ($captureResponse->statusCode !== 201) {
                return PaymentResult::failure('Failed to capture PayPal order');
            }

            $captureId = $captureResponse->result->purchase_units[0]->payments->captures[0]->id ?? null;
            $status = $captureResponse->result->status ?? 'UNKNOWN';

            if ($status === 'COMPLETED' && $captureId) {
                return PaymentResult::success(
                    transactionId: $captureId,
                    metadata: [
                        'order_id' => $orderId,
                        'amount' => $amount,
                        'currency' => 'USD',
                        'status' => $status,
                    ]
                );
            }

            return PaymentResult::failure(
                errorMessage: "PayPal order status: {$status}",
                metadata: ['order_id' => $orderId]
            );
        } catch (HttpException $e) {
            $error = json_decode($e->getMessage(), true);
            $errorMessage = $error['message'] ?? $e->getMessage();

            return PaymentResult::failure(
                errorMessage: $errorMessage,
                metadata: ['error_details' => $error]
            );
        } catch (\Exception $e) {
            return PaymentResult::failure(
                errorMessage: $e->getMessage()
            );
        }
    }

    /**
     * Refund a payment through PayPal.
     *
     * Creates a refund for the specified captured payment.
     * Supports both full and partial refunds.
     *
     * @param Payment $payment The payment to refund
     * @param float $amount The amount to refund
     * @return PaymentResult The result of the refund operation
     */
    public function refund(Payment $payment, float $amount): PaymentResult
    {
        try {
            $request = new CapturesRefundRequest($payment->transaction_id);
            $request->body = [
                'amount' => [
                    'value' => number_format($amount, 2, '.', ''),
                    'currency_code' => 'USD',
                ],
            ];

            $response = $this->client->execute($request);

            if ($response->statusCode !== 201) {
                return PaymentResult::failure('Failed to create PayPal refund');
            }

            $refundId = $response->result->id ?? null;
            $status = $response->result->status ?? 'UNKNOWN';

            if ($status === 'COMPLETED' && $refundId) {
                return PaymentResult::success(
                    transactionId: $refundId,
                    metadata: [
                        'amount' => $amount,
                        'currency' => 'USD',
                        'status' => $status,
                        'original_payment_id' => $payment->id,
                        'original_capture_id' => $payment->transaction_id,
                    ]
                );
            }

            return PaymentResult::failure(
                errorMessage: "PayPal refund status: {$status}",
                metadata: ['refund_id' => $refundId]
            );
        } catch (HttpException $e) {
            $error = json_decode($e->getMessage(), true);
            $errorMessage = $error['message'] ?? $e->getMessage();

            return PaymentResult::failure(
                errorMessage: $errorMessage,
                metadata: ['error_details' => $error]
            );
        } catch (\Exception $e) {
            return PaymentResult::failure(
                errorMessage: $e->getMessage()
            );
        }
    }

    /**
     * Create a subscription in PayPal.
     *
     * Creates a PayPal subscription using the PayPal Billing API.
     * Requires a PayPal plan ID to be configured in the plan model.
     *
     * @param Subscription $subscription The subscription to create in PayPal
     * @return string The PayPal subscription ID
     * @throws \Exception If subscription creation fails
     */
    public function createSubscription(Subscription $subscription): string
    {
        try {
            $subscription->load(['user', 'plan']);

            $paypalPlanId = $this->getPayPalPlanId($subscription->plan);

            $subscriptionData = [
                'plan_id' => $paypalPlanId,
                'subscriber' => [
                    'email_address' => $subscription->user->email,
                    'name' => [
                        'given_name' => $subscription->user->name,
                    ],
                ],
                'application_context' => [
                    'return_url' => config('services.paypal.return_url'),
                    'cancel_url' => config('services.paypal.cancel_url'),
                ],
            ];

            $request = new \PayPalHttp\HttpRequest('/v1/billing/subscriptions', 'POST');
            $request->headers = ['Content-Type' => 'application/json'];
            $request->body = $subscriptionData;

            $response = $this->client->execute($request);

            if ($response->statusCode !== 201) {
                throw new \Exception('Failed to create PayPal subscription');
            }

            return $response->result->id;
        } catch (HttpException $e) {
            $error = json_decode($e->getMessage(), true);
            $errorMessage = $error['message'] ?? $e->getMessage();
            throw new \Exception("Failed to create PayPal subscription: {$errorMessage}", 0, $e);
        } catch (\Exception $e) {
            throw new \Exception("Failed to create PayPal subscription: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Cancel a subscription in PayPal.
     *
     * Cancels the PayPal subscription immediately.
     *
     * @param string $externalId The PayPal subscription ID
     * @return void
     * @throws \Exception If cancellation fails
     */
    public function cancelSubscription(string $externalId): void
    {
        try {
            $request = new \PayPalHttp\HttpRequest("/v1/billing/subscriptions/{$externalId}/cancel", 'POST');
            $request->headers = ['Content-Type' => 'application/json'];
            $request->body = [
                'reason' => 'User requested cancellation',
            ];

            $response = $this->client->execute($request);

            if ($response->statusCode !== 204) {
                throw new \Exception('Failed to cancel PayPal subscription');
            }
        } catch (HttpException $e) {
            $error = json_decode($e->getMessage(), true);
            $errorMessage = $error['message'] ?? $e->getMessage();
            throw new \Exception("Failed to cancel PayPal subscription: {$errorMessage}", 0, $e);
        } catch (\Exception $e) {
            throw new \Exception("Failed to cancel PayPal subscription: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get the PayPal plan ID for a subscription plan.
     *
     * Retrieves the PayPal plan ID from the plan model.
     * In production, this should be stored in the database.
     *
     * @param \App\Models\Plan $plan The plan to get PayPal plan ID for
     * @return string The PayPal plan ID
     * @throws \Exception If plan ID is not configured
     */
    protected function getPayPalPlanId(\App\Models\Plan $plan): string
    {
        $paypalPlanId = $plan->paypal_plan_id ?? null;

        if (!$paypalPlanId) {
            throw new \Exception("PayPal plan ID not configured for plan {$plan->id}");
        }

        return $paypalPlanId;
    }

    /**
     * Verify a PayPal webhook signature.
     *
     * Validates that a webhook request actually came from PayPal.
     *
     * @param string $webhookId The PayPal webhook ID
     * @param array $headers The request headers
     * @param string $body The raw request body
     * @return bool True if the signature is valid
     */
    public function verifyWebhookSignature(string $webhookId, array $headers, string $body): bool
    {
        try {
            $request = new \PayPalHttp\HttpRequest('/v1/notifications/verify-webhook-signature', 'POST');
            $request->headers = ['Content-Type' => 'application/json'];
            $request->body = [
                'auth_algo' => $headers['PAYPAL-AUTH-ALGO'] ?? '',
                'cert_url' => $headers['PAYPAL-CERT-URL'] ?? '',
                'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
                'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
                'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
                'webhook_id' => $webhookId,
                'webhook_event' => json_decode($body, true),
            ];

            $response = $this->client->execute($request);

            return ($response->result->verification_status ?? '') === 'SUCCESS';
        } catch (\Exception $e) {
            return false;
        }
    }
}
