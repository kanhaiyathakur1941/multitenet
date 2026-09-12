<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Modules\Orders\OrderService;
use App\Modules\Orders\RazorpayService;
use App\Modules\Portal\PortalCart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private RazorpayService $razorpay,
        private PortalCart $cart,
    ) {}

    public function show(Order $order): View|RedirectResponse
    {
        $this->authorize('pay', $order);

        if (blank($order->razorpay_order_id)) {
            return redirect()
                ->route('portal.cart.index')
                ->with('error', 'This checkout session is invalid.');
        }

        return view('portal.checkout.show', [
            'order' => $order->loadMissing(['items.product']),
            'keyId' => config('eventflow.payments.razorpay.key_id'),
            'currency' => config('eventflow.payments.razorpay.currency', 'INR'),
            'amount' => $this->razorpay->amountInSubunits((float) $order->total),
            'prefill' => [
                'name' => config('eventflow.demo.customer_name'),
                'email' => config('eventflow.demo.customer_email'),
                'contact' => config('eventflow.demo.customer_phone'),
            ],
        ]);
    }

    public function verify(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('pay', $order);

        $validated = $request->validate([
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        try {
            $order = $this->orders->confirmRazorpayPayment(
                $order,
                $validated['razorpay_payment_id'],
                $validated['razorpay_order_id'],
                $validated['razorpay_signature'],
            );
        } catch (ValidationException $exception) {
            return redirect()
                ->route('portal.checkout.show', $order)
                ->withErrors($exception->errors());
        }

        $this->cart->clear();

        return redirect()
            ->route('portal.orders.show', $order)
            ->with('success', 'Payment successful. Your order is confirmed.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->authorize('pay', $order);

        $this->orders->cancelPendingOrder($order);

        return redirect()
            ->route('portal.cart.index')
            ->with('error', 'Payment was cancelled. Your order has been cancelled and stock restored.');
    }
}
