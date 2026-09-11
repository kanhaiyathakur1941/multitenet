<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\IndexOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Modules\Orders\OrderService;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

final class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(IndexOrderRequest $request): JsonResponse
    {
        $paginator = $this->orders->paginate($request->user(), $request->validated());

        return ApiResponse::paginated(
            'Orders retrieved.',
            $paginator,
            OrderResource::collection($paginator->getCollection())->resolve(),
        );
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orders->create($request->user(), $request->validated('items'));

        return ApiResponse::success('Order created successfully.', OrderResource::make($order)->resolve(), 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->loadMissing(['items.product', 'user']);

        return ApiResponse::success('Order retrieved.', OrderResource::make($order)->resolve());
    }
}
