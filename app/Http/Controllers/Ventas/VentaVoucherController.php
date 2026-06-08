<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\Ventas\ThermalVoucherPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VentaVoucherController extends Controller
{
    public function show(Venta $venta, Request $request, ThermalVoucherPdfService $service): Response
    {
        $ancho = (int) $request->query('ancho', 80);

        return response($service->venta($venta, $ancho), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="voucher-' . $venta->Numero_Factura . '.pdf"',
        ]);
    }
}
