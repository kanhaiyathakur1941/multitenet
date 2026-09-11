<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\DashboardService;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->canViewDashboard()) {
            abort(403);
        }

        return ApiResponse::success(
            'Dashboard statistics retrieved.',
            $this->dashboard->forTenant($user),
        );
    }
}
