<?php

use App\Http\Middleware\EnsureCustomer;
use App\Http\Middleware\SetCurrentTenant;
use App\Shared\Exceptions\ApiExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => SetCurrentTenant::class,
            'customer' => EnsureCustomer::class,
        ]);

        $middleware->throttleApi('api');

        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            SetCurrentTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return false;
            }

            return app(ApiExceptionRenderer::class)->render($e);
        });
    })->create();
