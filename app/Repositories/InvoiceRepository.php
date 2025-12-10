<?php

namespace App\Repositories;

use App\Enums\InvoiceStatusEnum;
use App\Models\Invoice;

class InvoiceRepository
{
    /**
     * Create a new invoice.
     *
     * @param array $data
     * @return Invoice
     */
    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    /**
     * Cancel all pending invoices for a subscription.
     *
     * @param int $subscriptionId
     * @return int Number of invoices updated
     */
    public function cancelPendingForSubscription(int $subscriptionId): int
    {
        return Invoice::where('subscription_id', $subscriptionId)
            ->where('status', InvoiceStatusEnum::PENDING)
            ->update(['status' => InvoiceStatusEnum::CANCELED]);
    }
}
