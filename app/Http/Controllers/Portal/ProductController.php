<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Modules\Products\ProductService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request): View
    {
        $products = $this->products->paginate($request->user(), [
            'search' => $request->string('search')->toString(),
            'per_page' => 12,
        ]);

        return view('portal.products.index', compact('products'));
    }
}
