<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Shared\CurrentTenant;
use App\Shared\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetCurrentTenant
{
    public function __construct(private CurrentTenant $currentTenant) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            $this->currentTenant->clear();

            return $next($request);
        }

        $user->loadMissing(['tenant', 'role']);

        if ($user->tenant_id === null || $user->tenant === null) {
            return ApiResponse::error('Tenant context is required.', 403);
        }

        if (! $user->tenant->is_active) {
            return ApiResponse::error('This tenant is inactive.', 403);
        }

        $this->currentTenant->set($user->tenant);

        return $next($request);
    }
}
