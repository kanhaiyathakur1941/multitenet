<?php

declare(strict_types=1);

namespace App\Modules\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FakePaymentGateway implements PaymentGatewayInterface
{
    public function charge(Order $order, float $amount): PaymentResult
    {
        if (config('eventflow.payments.simulate_failure')) {
            Log::warning('Fake payment failed.', [
                'order_id' => $order->id,
                'amount' => $amount,
            ]);

            return PaymentResult::failed('Payment was declined by the gateway.');
        }

        return PaymentResult::successful('fake_'.Str::uuid()->toString());
    }
}
