<?php

declare(strict_types=1);

namespace App\Modules\Orders;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Jobs\OrderCreatedJob;
use App\Jobs\OrderStatusChangedJob;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class OrderService
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway,
        private RazorpayService $razorpay,
    ) {}

    /**
     * @param  array{per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $query = Order::query()->with(['items.product', 'user']);

        if ($user->isCustomer()) {
            $query->where('user_id', $user->id);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->latest()->paginate($perPage);
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    public function create(User $user, array $items): Order
    {
        if ($this->usesRazorpayCheckout()) {
            throw ValidationException::withMessages([
                'payment' => ['Razorpay payments must be completed through the checkout page.'],
            ]);
        }

        $order = DB::transaction(function () use ($user, $items): Order {
            $order = $this->createPendingOrder($user, $items);

            $payment = $this->paymentGateway->charge($order, (float) $order->total);

            if (! $payment->successful) {
                Log::warning('Order payment failed.', [
                    'order_id' => $order->id,
                    'message' => $payment->message,
                ]);

                throw ValidationException::withMessages([
                    'payment' => [$payment->message ?? 'Payment failed.'],
                ]);
            }

            $order->update([
                'status' => OrderStatus::Confirmed,
                'payment_gateway' => config('eventflow.payments.driver', 'fake'),
                'payment_transaction_id' => $payment->transactionId,
            ]);

            return $order->refresh()->load(['items.product', 'user']);
        });

        OrderCreatedJob::dispatch($order->id);

        return $order;
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    public function prepareRazorpayCheckout(User $user, array $items): Order
    {
        if (! $this->usesRazorpayCheckout()) {
            throw ValidationException::withMessages([
                'payment' => ['Razorpay checkout is not enabled.'],
            ]);
        }

        if (! $this->razorpay->isConfigured()) {
            throw ValidationException::withMessages([
                'payment' => ['Razorpay credentials are not configured.'],
            ]);
        }

        return DB::transaction(function () use ($user, $items): Order {
            $order = $this->createPendingOrder($user, $items);

            $payment = $this->razorpay->createCheckoutOrder($order, (float) $order->total);

            if (! $payment->successful) {
                throw ValidationException::withMessages([
                    'payment' => [$payment->message ?? 'Payment gateway unavailable.'],
                ]);
            }

            $order->update([
                'payment_gateway' => 'razorpay',
                'razorpay_order_id' => $payment->transactionId,
            ]);

            return $order->refresh()->load(['items.product', 'user']);
        });
    }

    public function confirmRazorpayPayment(
        Order $order,
        string $razorpayPaymentId,
        string $razorpayOrderId,
        string $signature,
    ): Order {
        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages([
                'payment' => ['This order has already been processed.'],
            ]);
        }

        if ($order->razorpay_order_id !== $razorpayOrderId) {
            throw ValidationException::withMessages([
                'payment' => ['Payment order mismatch.'],
            ]);
        }

        if (! $this->razorpay->verifyPaymentSignature($razorpayOrderId, $razorpayPaymentId, $signature)) {
            Log::warning('Razorpay signature verification failed.', [
                'order_id' => $order->id,
                'razorpay_order_id' => $razorpayOrderId,
            ]);

            throw ValidationException::withMessages([
                'payment' => ['Payment verification failed.'],
            ]);
        }

        $order->update([
            'status' => OrderStatus::Confirmed,
            'payment_transaction_id' => $razorpayPaymentId,
        ]);

        $order = $order->refresh()->load(['items.product', 'user']);

        OrderCreatedJob::dispatch($order->id);

        return $order;
    }

    public function cancelPendingOrder(Order $order): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            return $order->refresh()->load(['items.product', 'user']);
        }

        DB::transaction(function () use ($order): void {
            $order->loadMissing('items.product');

            foreach ($order->items as $item) {
                $item->product?->increment('stock', $item->quantity);
            }

            $order->update(['status' => OrderStatus::Cancelled]);
        });

        return $order->refresh()->load(['items.product', 'user']);
    }

    public function updateStatus(Order $order, OrderStatus $status): Order
    {
        $previousStatus = $order->status;

        $order->update(['status' => $status]);

        $order = $order->refresh()->load(['items.product', 'user']);

        if ($previousStatus !== $status) {
            OrderStatusChangedJob::dispatch($order->id, $previousStatus->value);
        }

        return $order;
    }

    public function usesRazorpayCheckout(): bool
    {
        return config('eventflow.payments.driver') === 'razorpay';
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    private function createPendingOrder(User $user, array $items): Order
    {
        $lineItems = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $product = Product::query()
                ->whereKey($item['product_id'])
                ->lockForUpdate()
                ->first();

            if ($product === null) {
                throw ValidationException::withMessages([
                    'items' => ['One or more products are invalid.'],
                ]);
            }

            if ($product->status !== ProductStatus::Active) {
                throw ValidationException::withMessages([
                    'items' => ["Product {$product->name} is not available."],
                ]);
            }

            if ($product->stock < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => ["Insufficient stock for {$product->name}."],
                ]);
            }

            $lineTotal = round((float) $product->price * $item['quantity'], 2);
            $subtotal += $lineTotal;

            $lineItems[] = [
                'product' => $product,
                'quantity' => $item['quantity'],
                'price' => (float) $product->price,
                'total' => $lineTotal,
            ];
        }

        $taxRate = (float) config('eventflow.tax_rate');
        $tax = round($subtotal * $taxRate, 2);
        $total = round($subtotal + $tax, 2);

        $order = Order::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);

        foreach ($lineItems as $lineItem) {
            $order->items()->create([
                'product_id' => $lineItem['product']->id,
                'quantity' => $lineItem['quantity'],
                'price' => $lineItem['price'],
                'total' => $lineItem['total'],
            ]);

            $lineItem['product']->decrement('stock', $lineItem['quantity']);
        }

        return $order->load(['items.product', 'user']);
    }
}
