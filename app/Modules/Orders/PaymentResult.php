<?php

declare(strict_types=1);

namespace App\Modules\Orders;

final class PaymentResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly ?string $transactionId,
        public readonly ?string $message,
    ) {}

    public static function successful(string $transactionId): self
    {
        return new self(true, $transactionId, null);
    }

    public static function failed(string $message): self
    {
        return new self(false, null, $message);
    }
}
