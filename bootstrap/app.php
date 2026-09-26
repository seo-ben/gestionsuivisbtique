<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$cacheDir = __DIR__.'/cache';
if (file_exists($cacheDir.'/packages.php')) {
    $packagesContent = @file_get_contents($cacheDir.'/packages.php');
    if ($packagesContent && str_contains($packagesContent, 'PailServiceProvider')) {
        @unlink($cacheDir.'/packages.php');
        @unlink($cacheDir.'/services.php');
    }
}
if (file_exists($cacheDir.'/services.php')) {
    $servicesContent = @file_get_contents($cacheDir.'/services.php');
    if ($servicesContent && str_contains($servicesContent, 'PailServiceProvider')) {
        @unlink($cacheDir.'/services.php');
        @unlink($cacheDir.'/packages.php');
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
