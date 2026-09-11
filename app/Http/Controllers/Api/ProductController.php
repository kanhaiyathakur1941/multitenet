<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\IndexProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Modules\Products\ProductService;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(IndexProductRequest $request): JsonResponse
    {
        $paginator = $this->products->paginate($request->user(), $request->validated());

        return ApiResponse::paginated(
            'Products retrieved.',
            $paginator,
            ProductResource::collection($paginator->getCollection())->resolve(),
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->products->create($request->validated());

        return ApiResponse::success('Product created successfully.', ProductResource::make($product)->resolve(), 201);
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return ApiResponse::success('Product retrieved.', ProductResource::make($product)->resolve());
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->products->update($product, $request->validated());

        return ApiResponse::success('Product updated successfully.', ProductResource::make($product)->resolve());
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->products->delete($product);

        return ApiResponse::success('Product deleted successfully.');
    }
}
