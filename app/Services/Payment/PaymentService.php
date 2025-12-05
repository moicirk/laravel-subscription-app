<?php

namespace App\Services\Payment;

use App\Enums\InvoiceStatusEnum;
use App\Enums\UserPaymentMethodTypeEnum;
use App\Models\Invoice;
use App\Models\User;
use App\Models\UserPaymentMethod;
use App\Services\Stripe\StripeService;

readonly class PaymentService
{
    public function __construct(
        private StripeService $stripeService
    ) {}

    /**
     * @param User $user
     * @param UserPaymentMethodTypeEnum $type
     * @return void
     * @throws \Exception
     */
    public function createCustomer(User $user, UserPaymentMethodTypeEnum $type): void
    {
        match ($type) {
            UserPaymentMethodTypeEnum::STRIPE => $this->stripeService->createCustomer($user),
            default => throw new \Exception('To be implemented')
        };
    }

    /**
     * @param Invoice $invoice
     * @param UserPaymentMethod $method
     * @return PaymentResult
     * @throws \Exception
     */
    public function charge(Invoice $invoice, UserPaymentMethod $method): PaymentResult
    {
        if ($invoice->status !== InvoiceStatusEnum::PENDING) {
            throw new \Exception('Invoice status is not pending');
        }

        return match($method->type) {
            UserPaymentMethodTypeEnum::STRIPE => $this->stripeService->charge(
                $invoice->price + $invoice->tax,
                $method
            ),
            default => throw new \Exception('To be implemented')
        };
    }
}
