<?php

declare(strict_types=1);

namespace App\Modules\Orders;

use App\Models\Order;

interface PaymentGatewayInterface
{
    public function charge(Order $order, float $amount): PaymentResult;
}
