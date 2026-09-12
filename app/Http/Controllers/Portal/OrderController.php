<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Modules\Orders\OrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): View
    {
        $orders = $this->orders->paginate($request->user(), ['per_page' => 10]);

        return view('portal.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->loadMissing(['items.product', 'user']);

        return view('portal.orders.show', compact('order'));
    }
}
