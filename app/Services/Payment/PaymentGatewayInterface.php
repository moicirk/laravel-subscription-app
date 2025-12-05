<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\UserPaymentMethod;

interface PaymentGatewayInterface
{
    public function charge(float $amount, UserPaymentMethod $method): PaymentResult;

    public function refund(Payment $payment, float $amount): PaymentResult;

    public function createSubscription(Subscription $subscription): string;

    public function cancelSubscription(string $externalId): void;
}
