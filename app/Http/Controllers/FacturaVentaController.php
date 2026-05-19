<?php

namespace App\Http\Controllers;

use App\Models\Venta;

class FacturaVentaController extends Controller
{
    public function show(Venta $venta)
    {
        $venta->load([
            'cliente.persona',
            'usuario',
            'detalles.producto',
            'detalles.productoSerie',
            'detalles.tarifaCopia',
            'detalles.servicio',
            'pagos',
            'credito',
        ]);

        $cliente = $venta->cliente;
        $persona = $cliente?->persona;

        $nombreCliente = 'Consumidor final';

        if ($cliente) {
            $nombrePersona = trim(implode(' ', array_filter([
                $persona?->Primer_Nombre,
                $persona?->Segundo_Nombre,
                $persona?->Primer_Apellido,
                $persona?->Segundo_Apellido,
            ])));

            $nombreCliente = $cliente->Institucion ?: $nombrePersona ?: 'Cliente';
        }

        $municipio = session('venta_municipio_'.$venta->Id_Venta) ?: ($cliente->Municipio ?? '');

        return view('pages.ventas.factura', [
            'venta' => $venta,
            'nombreCliente' => $nombreCliente,
            'municipio' => $municipio,
        ]);
    }
}
