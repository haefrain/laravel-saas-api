<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Http\Middleware\ResolveTeamContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => ResolveTeamContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        /**
         * One JSON error envelope for the whole API:
         * { "message": "...", "error": { "code": "...", "details": {...} } }.
         * Closures are matched by type-hint in registration order, so the
         * specific ones come first and the Throwable catch-all last.
         *
         * @param  array<string, mixed>  $details
         * @param  array<string, string>  $headers
         */
        $envelope = function (string $message, string $code, int $status, array $details = [], array $headers = []) {
            $error = ['code' => $code];
            if ($details !== []) {
                $error['details'] = $details;
            }

            return response()->json(['message' => $message, 'error' => $error], $status, $headers);
        };

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            // errors stays top-level (standard Laravel shape) with the
            // machine-readable code alongside.
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'error' => ['code' => 'validation_error', 'details' => $e->errors()],
            ], $e->status);
        });

        $exceptions->render(function (DomainException $e, Request $request) use ($envelope) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $envelope($e->getMessage(), $e->errorCode(), $e->status(), $e->details());
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($envelope) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $envelope('Unauthenticated.', 'unauthenticated', 401);
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($envelope) {
            if (! $request->is('api/*')) {
                return null;
            }

            return $envelope($e->getMessage() !== '' ? $e->getMessage() : 'Forbidden.', 'forbidden', 403);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($envelope) {
            if (! $request->is('api/*')) {
                return null;
            }

            $headers = $e->getHeaders();
            $retryAfter = isset($headers['Retry-After']) ? (int) $headers['Retry-After'] : null;

            return $envelope(
                'Too many requests.',
                'rate_limited',
                429,
                $retryAfter !== null ? ['retry_after' => $retryAfter] : [],
                $headers,
            );
        });

        $exceptions->render(function (RecordsNotFoundException $e, Request $request) use ($envelope) {
            if (! $request->is('api/*')) {
                return null;
            }

            // Static message only — never echo the requested id, so a
            // cross-tenant probe learns nothing about existence.
            return $envelope('Resource not found.', 'not_found', 404);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($envelope) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();

            [$code, $message] = match (true) {
                $status === 403 && $e->getMessage() === 'team_forbidden' => ['team_forbidden', 'team_forbidden'],
                $status === 401 => ['unauthenticated', 'Unauthenticated.'],
                $status === 403 => ['forbidden', $e->getMessage() !== '' ? $e->getMessage() : 'Forbidden.'],
                $status === 404 => ['not_found', 'Resource not found.'],
                $status === 405 => ['method_not_allowed', 'Method not allowed.'],
                $status === 429 => ['rate_limited', 'Too many requests.'],
                default => ['http_error', $e->getMessage() !== '' ? $e->getMessage() : 'HTTP error.'],
            };

            $details = $e instanceof MethodNotAllowedHttpException
                ? ['allowed' => array_values(array_filter(explode(', ', $e->getHeaders()['Allow'] ?? '')))]
                : [];

            return $envelope($message, $code, $status, $details, $e->getHeaders());
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            // A trace id correlates the client-visible 500 with the log line
            // without leaking internals in production.
            $traceId = (string) Str::ulid();
            Log::error('unhandled api exception', ['trace_id' => $traceId, 'exception' => $e]);

            return response()->json([
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error.',
                'trace_id' => $traceId,
                'error' => ['code' => 'server_error'],
            ], 500);
        });
    })->create();
