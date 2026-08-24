<?php

use App\Http\Middleware\BlockUnwantedBots;
use App\Http\Middleware\CaptureSiteVisit;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\ProtectFormsFromBots;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            BlockUnwantedBots::class,
        ]);

        $middleware->web(append: [
            CaptureSiteVisit::class,
        ]);

        $middleware->alias([
            'super.admin' => EnsureSuperAdmin::class,
            'protect.bots' => ProtectFormsFromBots::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
