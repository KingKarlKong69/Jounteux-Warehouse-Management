<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_ALL
        );

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ─── Global Exception Handler for API (JSON) Requests ───
        // Only intercepts requests that want JSON (Accept: application/json
        // or routes under api/ prefix). Inertia/web requests fall through
        // to Laravel's default HTML exception rendering.

        $exceptions->renderable(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'       => false,
                    'data'          => $e->errors(),
                    'error_message' => $e->getMessage(),
                    'error_code'    => 422,
                ], 422);
            }
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'       => false,
                    'data'          => null,
                    'error_message' => 'Unauthenticated.',
                    'error_code'    => 401,
                ], 401);
            }
        });

        $exceptions->renderable(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'       => false,
                    'data'          => null,
                    'error_message' => $e->getMessage() ?: 'Forbidden.',
                    'error_code'    => 403,
                ], 403);
            }
        });

        $exceptions->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success'       => false,
                    'data'          => null,
                    'error_message' => "{$model} not found.",
                    'error_code'    => 404,
                ], 404);
            }
        });

        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'       => false,
                    'data'          => null,
                    'error_message' => 'Endpoint not found.',
                    'error_code'    => 404,
                ], 404);
            }
        });

        $exceptions->renderable(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'       => false,
                    'data'          => null,
                    'error_message' => 'Too many requests. Please slow down.',
                    'error_code'    => 429,
                ], 429);
            }
        });

        $exceptions->renderable(function (QueryException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Log the real error, return safe message
                report($e);
                return response()->json([
                    'success'       => false,
                    'data'          => null,
                    'error_message' => 'A database error occurred. Please try again.',
                    'error_code'    => 500,
                ], 500);
            }
        });

        $exceptions->renderable(function (HttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success'       => false,
                    'data'          => null,
                    'error_message' => $e->getMessage() ?: 'An error occurred.',
                    'error_code'    => $e->getStatusCode(),
                ], $e->getStatusCode());
            }
        });

        // Catch-all for unhandled exceptions on JSON requests
        $exceptions->renderable(function (\Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                report($e);
                return response()->json([
                    'success'       => false,
                    'data'          => null,
                    'error_message' => app()->hasDebugModeEnabled()
                        ? $e->getMessage()
                        : 'An unexpected error occurred.',
                    'error_code'    => 500,
                ], 500);
            }
        });

    })->create();
