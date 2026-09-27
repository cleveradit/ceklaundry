<?php

use App\Http\Middleware\EnsureBusinessAccess;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\NoStore;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: ['owner.password']);
        $middleware->web(append: [NoStore::class, HandleInertiaRequests::class]);
        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'business.access' => EnsureBusinessAccess::class,
            'password.changed' => RequirePasswordChange::class,
            'role' => RequireRole::class,
        ]);
        $middleware->trustProxies(at: array_values(array_filter(explode(',', (string) env('TRUSTED_PROXIES', '')))));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['password', 'owner.password', 'password_confirmation', 'current_password', 'token']);
        $exceptions->report(function (Throwable $error) {
            Log::error('application_error', ['type' => class_basename($error)]);

            return false;
        });
        $exceptions->respond(function (Response $response) {
            if (in_array($response->getStatusCode(), [403, 404, 409, 419, 423, 429, 500, 503])) {
                if (request()->header('X-Inertia')) {
                    $response = Inertia::render('Error', ['status' => $response->getStatusCode()])->toResponse(request())->setStatusCode($response->getStatusCode());
                } elseif (! request()->expectsJson()) {
                    $response = response()->view('errors.friendly', ['status' => $response->getStatusCode()], $response->getStatusCode());
                }
            }
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Referrer-Policy', 'no-referrer');

            return $response;
        });
    })->create();
