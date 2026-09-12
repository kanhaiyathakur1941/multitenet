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
            return $this->deny($request, 'Tenant context is required.');
        }

        if (! $user->tenant->is_active) {
            return $this->deny($request, 'This tenant is inactive.');
        }

        $this->currentTenant->set($user->tenant);

        return $next($request);
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiResponse::error($message, 403);
        }

        abort(403, $message);
    }
}
