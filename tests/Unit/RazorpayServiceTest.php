<?php

use App\Models\Order;
use App\Modules\Orders\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config([
        'eventflow.payments.razorpay.key_id' => 'rzp_test_key',
        'eventflow.payments.razorpay.key_secret' => 'rzp_test_secret',
        'eventflow.payments.razorpay.currency' => 'INR',
    ]);
});

it('creates a razorpay checkout order', function () {
    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response([
            'id' => 'order_test_abc123',
            'amount' => 2200,
            'currency' => 'INR',
        ], 201),
    ]);

    $order = Order::factory()->create(['total' => 22.00]);

    $result = (new RazorpayService)->createCheckoutOrder($order, 22.00);

    expect($result->successful)->toBeTrue()
        ->and($result->transactionId)->toBe('order_test_abc123');
});

it('verifies a valid razorpay payment signature', function () {
    $service = new RazorpayService;

    $orderId = 'order_test_abc123';
    $paymentId = 'pay_test_xyz789';
    $signature = hash_hmac('sha256', $orderId.'|'.$paymentId, 'rzp_test_secret');

    expect($service->verifyPaymentSignature($orderId, $paymentId, $signature))->toBeTrue();
});

it('rejects an invalid razorpay payment signature', function () {
    $service = new RazorpayService;

    expect($service->verifyPaymentSignature('order_a', 'pay_b', 'invalid_signature'))->toBeFalse();
});

it('converts amounts to subunits', function () {
    $service = new RazorpayService;

    expect($service->amountInSubunits(22.00))->toBe(2200)
        ->and($service->amountInSubunits(10.50))->toBe(1050);
});
