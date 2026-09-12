<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Modules\Orders\OrderService;
use App\Modules\Portal\PortalCart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CartController extends Controller
{
    public function __construct(
        private PortalCart $cart,
        private OrderService $orders,
    ) {}

    public function index(): View
    {
        $products = Product::query()
            ->whereIn('id', array_keys($this->cart->items()))
            ->get()
            ->keyBy('id');

        $lines = collect($this->cart->items())
            ->map(function (int $quantity, int $productId) use ($products): ?array {
                $product = $products->get($productId);

                if ($product === null) {
                    return null;
                }

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => round((float) $product->price * $quantity, 2),
                ];
            })
            ->filter()
            ->values();

        $subtotal = round($lines->sum('line_total'), 2);
        $tax = round($subtotal * (float) config('eventflow.tax_rate'), 2);

        return view('portal.cart.index', [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
            'usesRazorpay' => $this->orders->usesRazorpayCheckout(),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $this->cart->add($product->id, (int) $validated['quantity']);

        return redirect()
            ->route('portal.cart.index')
            ->with('success', "{$product->name} added to your cart.");
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $this->cart->update($product->id, (int) $validated['quantity']);

        return redirect()
            ->route('portal.cart.index')
            ->with('success', 'Cart updated.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        if ($this->cart->count() === 0) {
            return redirect()
                ->route('portal.cart.index')
                ->with('error', 'Your cart is empty.');
        }

        try {
            if ($this->orders->usesRazorpayCheckout()) {
                $order = $this->orders->prepareRazorpayCheckout(
                    $request->user(),
                    $this->cart->toOrderItems(),
                );

                return redirect()
                    ->route('portal.checkout.show', $order)
                    ->with('success', 'Complete payment to confirm your order.');
            }

            $order = $this->orders->create($request->user(), $this->cart->toOrderItems());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('portal.cart.index')
                ->withErrors($exception->errors());
        }

        $this->cart->clear();

        return redirect()
            ->route('portal.orders.show', $order)
            ->with('success', 'Order placed successfully.');
    }
}
