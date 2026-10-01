<?php

use App\Models\Redirect;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // De installatiepagina draait zonder sessies, want de database bestaat dan nog niet
        then: fn () => Route::group([], base_path('routes/install.php')),
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Mollie kan geen CSRF-token meesturen
        $middleware->preventRequestForgery(except: ['webhooks/*']);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('beheer*') ? route('filament.admin.auth.login') : route('account.login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('beheer*') ? '/beheer' : route('account.dashboard'));
        // Hostingpartijen zetten de winkel vaak achter een proxy
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Nog niet geïnstalleerd: stuur door naar de installatiepagina
        $exceptions->render(function (QueryException $e, Request $request) {
            try {
                if (filled(config('app.install_token')) && ! $request->is('install') && ! Schema::hasTable('users')) {
                    return redirect('/install');
                }
            } catch (Throwable) {
            }

            return null;
        });

        // Oude Shopify-adressen en handmatige doorverwijzingen uit /beheer
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('beheer*')) {
                return null;
            }
            try {
                $path = '/'.ltrim($request->path(), '/');
                $redirect = Redirect::where('from_path', $path)->orWhere('from_path', rtrim($path, '/'))->first();
                if ($redirect) {
                    $redirect->increment('hits');

                    return redirect($redirect->to_path, 301);
                }
            } catch (Throwable) {
            }

            return response()->view('shop.errors.404', [], 404);
        });
    })->create();

// Op gedeelde hosting (DirectAdmin) staat de map public vaak als public_html naast de app
if (! is_dir($app->basePath('public')) && is_dir(dirname($app->basePath()).'/public_html')) {
    $app->usePublicPath(dirname($app->basePath()).'/public_html');
}

return $app;
