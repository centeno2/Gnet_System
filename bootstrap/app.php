<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Usuario no auntenticado intenta ingresar a rutas 
         */
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));

        /*
         * Usuario autenticado edita rutas 
         */
        $middleware->redirectUsersTo(fn (Request $request) => route('main'));

        /*
         * Middleware personalizado por cargo.
         */
        $middleware->alias([
            'cargo' => \App\Http\Middleware\UserCargo::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();