<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'staff' => \App\Http\Middleware\EnsureStaffUser::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'user_type' => \App\Http\Middleware\EnsureUserType::class,
        ]);

        // Job applications posted from the careers widget embedded on other websites:
        // a cross-site iframe cannot carry the session cookie, so CSRF cannot apply.
        // The endpoint is throttled and has honeypot / timing spam traps instead.
        $middleware->validateCsrfTokens(except: ['careers/*/apply']);

        $middleware->web(
            append: [
                \App\Http\Middleware\DynamicMaintenanceMode::class,
            ]
        );

        // Allow same-origin web (PWA) requests to authenticate against the
        // `auth:sanctum` API routes using the session, so the participant
        // dashboard's sync button works alongside the mobile app's bearer
        // token flow.
        $middleware->api(
            prepend: [
                \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            ]
        );

        $middleware->append(
            \App\Http\Middleware\SecurityHeaders::class
        );
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        // Every api/* request gets JSON errors (never HTML or a redirect to the web login),
        // even when the client forgets the Accept: application/json header.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // Uploads bigger than PHP's post_max_size arrive with no fields at all. Runs before
        // the session starts, so explain on a plain page instead of a flash message.
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            $limitMb = max(1, (int) floor(\App\Services\Learning\LearningFileService::iniBytes((string) ini_get('post_max_size')) / 1048576));
            $back = e(url()->previous() ?: url('/'));

            return response(
                '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
                .'<title>Upload too large</title></head><body style="font-family:system-ui,sans-serif;max-width:560px;margin:60px auto;padding:0 16px;line-height:1.5">'
                .'<h1 style="font-size:1.4rem">The upload was too large</h1>'
                ."<p>The files you selected add up to more than the server accepts in one go ({$limitMb} MB). Nothing was saved.</p>"
                .'<p>Go back and upload fewer or smaller files at a time (for big videos, paste a video link instead).</p>'
                ."<p><a href=\"{$back}\">&larr; Go back</a></p></body></html>",
                413
            );
        });

        // Human-readable "message" for HTTP errors on api/* routes. Validation (422) and
        // authentication (401) errors keep Laravel's default JSON: {"message": ..., "errors": {...}}.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $previous = $e->getPrevious();

                if ($previous instanceof ModelNotFoundException) {
                    $message = Str::headline(class_basename($previous->getModel())).' not found.';
                } elseif ($e instanceof NotFoundHttpException && ! $request->route()) {
                    $message = 'The requested API endpoint does not exist.';
                } else {
                    $message = trim((string) $e->getMessage());
                }

                if ($message === '') {
                    $message = match ($status) {
                        400 => 'The request could not be understood.',
                        401 => 'Unauthenticated.',
                        403 => 'You do not have permission to perform this action.',
                        404 => 'The requested resource was not found.',
                        405 => 'This HTTP method is not supported for this endpoint.',
                        419 => 'Your session has expired. Please sign in again.',
                        429 => 'Too many requests. Please wait a moment and try again.',
                        503 => 'The service is temporarily unavailable. Please try again later.',
                        default => SymfonyResponse::$statusTexts[$status] ?? 'An error occurred.',
                    };
                }

                return response()->json(['message' => $message], $status, $e->getHeaders());
            }

            if ($e instanceof AuthenticationException || $e instanceof ValidationException) {
                return null;
            }

            // Unexpected server errors: keep Laravel's detailed debug output when APP_DEBUG=true.
            if (config('app.debug')) {
                return null;
            }

            return response()->json([
                'message' => 'Something went wrong on the server. Please try again later.',
            ], 500);
        });
    })

    ->create();
