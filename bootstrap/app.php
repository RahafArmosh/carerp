<?php

use App\Http\Middleware\CheckCurrentToken;
use App\Http\Middleware\pusherConfig;
use App\Http\Middleware\RevalidateBackHistory;
use App\Http\Middleware\XSS;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Laravel 11 reads aliases from here; string names like 'XSS' in
        // config/chatify.php and route middleware() must map to real classes.
        $middleware->alias([
            'XSS' => XSS::class,
            'revalidate' => RevalidateBackHistory::class,
            'pusher' => pusherConfig::class,
            'check.current.token' => CheckCurrentToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
