<?php

declare(strict_types=1);

namespace App\Modules\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class RazorpayPaymentGateway implements PaymentGatewayInterface
{
    public function charge(Order $order, float $amount): PaymentResult
    {
        $keyId = config('eventflow.payments.razorpay.key_id');
        $keySecret = config('eventflow.payments.razorpay.key_secret');

        if (blank($keyId) || blank($keySecret)) {
            return PaymentResult::failed('Razorpay credentials are not configured.');
        }

        $response = Http::withBasicAuth($keyId, $keySecret)
            ->acceptJson()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => (int) round($amount * 100),
                'currency' => config('eventflow.payments.razorpay.currency', 'INR'),
                'receipt' => 'eventflow_order_'.$order->id,
                'notes' => [
                    'eventflow_order_id' => (string) $order->id,
                    'tenant_id' => (string) $order->tenant_id,
                ],
            ]);

        if ($response->failed()) {
            Log::warning('Razorpay order creation failed.', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return PaymentResult::failed(
                $response->json('error.description') ?? 'Payment gateway unavailable.',
            );
        }

        $razorpayOrderId = $response->json('id');

        if (! is_string($razorpayOrderId) || $razorpayOrderId === '') {
            return PaymentResult::failed('Invalid payment gateway response.');
        }

        Log::info('Razorpay test order created.', [
            'order_id' => $order->id,
            'razorpay_order_id' => $razorpayOrderId,
            'amount' => $amount,
        ]);

        return PaymentResult::successful($razorpayOrderId);
    }
}
