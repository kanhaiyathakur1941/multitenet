<?php

declare(strict_types=1);

namespace App\Modules\Orders;

use App\Models\Order;

final class RazorpayPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private RazorpayService $razorpay) {}

    public function charge(Order $order, float $amount): PaymentResult
    {
        return $this->razorpay->createCheckoutOrder($order, $amount);
    }
}
