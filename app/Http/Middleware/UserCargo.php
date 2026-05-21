<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserCargo
{
    /**
     * Validar acceso por cargo del usuario
     */
    public function handle(Request $request, Closure $next, ...$cargos): Response
    {
        $user = $request->user();

        if (!$user || !$user->trabajador || !$user->trabajador->cargo) {
            abort(403, 'Usuario sin cargo asignado');
        }

        $cargo = $user->trabajador->cargo->Id_Cargo;

        if (!in_array($cargo, $cargos)) {
            abort(403, 'No tienes permisos para acceder a esta ruta');
        }

        return $next($request);
    }
}