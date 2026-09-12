<?php

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Orders\RazorpayPaymentGateway;
use App\Modules\Orders\RazorpayService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'eventflow.payments.razorpay.key_id' => 'rzp_test_key',
        'eventflow.payments.razorpay.key_secret' => 'rzp_test_secret',
        'eventflow.payments.razorpay.currency' => 'INR',
    ]);
});

it('creates a razorpay test order and returns the transaction id', function () {
    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response([
            'id' => 'order_test_abc123',
            'amount' => 2200,
            'currency' => 'INR',
        ], 201),
    ]);

    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $order = Order::factory()->for($tenant)->for($customer)->create(['total' => 22.00]);

    $result = (new RazorpayPaymentGateway(new RazorpayService))->charge($order, 22.00);

    expect($result->successful)->toBeTrue()
        ->and($result->transactionId)->toBe('order_test_abc123');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.razorpay.com/v1/orders'
            && $request['amount'] === 2200
            && $request['currency'] === 'INR';
    });
});

it('returns a failure when razorpay credentials are missing', function () {
    config([
        'eventflow.payments.razorpay.key_id' => null,
        'eventflow.payments.razorpay.key_secret' => null,
    ]);

    $order = Order::factory()->create();

    $result = (new RazorpayPaymentGateway(new RazorpayService))->charge($order, 10.00);

    expect($result->successful)->toBeFalse()
        ->and($result->message)->toBe('Razorpay credentials are not configured.');
});

it('returns a failure when razorpay api responds with an error', function () {
    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response([
            'error' => ['description' => 'Authentication failed'],
        ], 401),
    ]);

    $order = Order::factory()->create();

    $result = (new RazorpayPaymentGateway(new RazorpayService))->charge($order, 10.00);

    expect($result->successful)->toBeFalse()
        ->and($result->message)->toBe('Authentication failed');
});
