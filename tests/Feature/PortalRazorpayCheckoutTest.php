<?php

use App\Enums\OrderStatus;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'eventflow.payments.driver' => 'razorpay',
        'eventflow.payments.razorpay.key_id' => 'rzp_test_key',
        'eventflow.payments.razorpay.key_secret' => 'rzp_test_secret',
        'eventflow.payments.razorpay.currency' => 'INR',
    ]);
});

it('redirects to razorpay checkout when placing an order from the cart', function () {
    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response([
            'id' => 'order_test_checkout',
            'amount' => 2200,
            'currency' => 'INR',
        ], 201),
    ]);

    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $product = Product::factory()->for($tenant)->create([
        'price' => 10.00,
        'stock' => 5,
    ]);

    $this->actingAs($customer)
        ->post(route('portal.cart.store', $product), ['quantity' => 2])
        ->assertRedirect(route('portal.cart.index'));

    $response = $this->actingAs($customer)
        ->post(route('portal.cart.checkout'));

    $order = $customer->orders()->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->payment_gateway)->toBe('razorpay')
        ->and($order->razorpay_order_id)->toBe('order_test_checkout');

    $response->assertRedirect(route('portal.checkout.show', $order));
});

it('confirms an order after razorpay payment verification', function () {
    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response([
            'id' => 'order_test_verify',
            'amount' => 2200,
            'currency' => 'INR',
        ], 201),
    ]);

    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $product = Product::factory()->for($tenant)->create([
        'price' => 10.00,
        'stock' => 5,
    ]);

    $this->actingAs($customer)
        ->post(route('portal.cart.store', $product), ['quantity' => 2]);

    $this->actingAs($customer)
        ->post(route('portal.cart.checkout'));

    $order = $customer->orders()->first();
    $paymentId = 'pay_test_confirmed';
    $signature = hash_hmac('sha256', 'order_test_verify|'.$paymentId, 'rzp_test_secret');

    $this->actingAs($customer)
        ->post(route('portal.checkout.verify', $order), [
            'razorpay_payment_id' => $paymentId,
            'razorpay_order_id' => 'order_test_verify',
            'razorpay_signature' => $signature,
        ])
        ->assertRedirect(route('portal.orders.show', $order));

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payment_transaction_id)->toBe($paymentId);
});

it('cancels a pending razorpay order and restores stock', function () {
    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response([
            'id' => 'order_test_cancel',
            'amount' => 1100,
            'currency' => 'INR',
        ], 201),
    ]);

    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->for($tenant)->create();
    $product = Product::factory()->for($tenant)->create([
        'price' => 10.00,
        'stock' => 5,
    ]);

    $this->actingAs($customer)
        ->post(route('portal.cart.store', $product), ['quantity' => 1]);

    $this->actingAs($customer)
        ->post(route('portal.cart.checkout'));

    $order = $customer->orders()->first();

    expect($product->fresh()->stock)->toBe(4);

    $this->actingAs($customer)
        ->get(route('portal.checkout.cancel', $order))
        ->assertRedirect(route('portal.cart.index'));

    $order->refresh();
    $product->refresh();

    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($product->stock)->toBe(5);
});
