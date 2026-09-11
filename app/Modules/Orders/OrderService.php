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
    public function __construct(private PaymentGatewayInterface $paymentGateway) {}

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
        $order = DB::transaction(function () use ($user, $items): Order {
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

            $payment = $this->paymentGateway->charge($order, $total);

            if (! $payment->successful) {
                Log::warning('Order payment failed.', [
                    'order_id' => $order->id,
                    'message' => $payment->message,
                ]);

                throw ValidationException::withMessages([
                    'payment' => [$payment->message ?? 'Payment failed.'],
                ]);
            }

            $order->update(['status' => OrderStatus::Confirmed]);

            return $order->refresh()->load(['items.product', 'user']);
        });

        OrderCreatedJob::dispatch($order->id);

        return $order;
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
}
