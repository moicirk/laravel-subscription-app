<?php

namespace App\Services\Payment;

readonly class PaymentResult
{
    public function __construct(
        public bool    $success,
        public ?string $transactionId = null,
        public ?string $errorMessage = null,
        public array   $metadata = []
    ) {}

    /**
     * Create a successful payment result.
     *
     * @param string $transactionId The transaction ID from the payment gateway
     * @param array $metadata Additional metadata about the transaction
     * @return self
     */
    public static function success(string $transactionId, array $metadata = []): self
    {
        return new self(
            success: true,
            transactionId: $transactionId,
            metadata: $metadata
        );
    }

    /**
     * Create a failed payment result.
     *
     * @param string $errorMessage The error message describing the failure
     * @param array $metadata Additional metadata about the failed transaction
     * @return self
     */
    public static function failure(string $errorMessage, array $metadata = []): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            metadata: $metadata
        );
    }
}
