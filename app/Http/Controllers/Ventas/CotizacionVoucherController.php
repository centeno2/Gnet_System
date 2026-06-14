<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Models\CotizacionVenta;
use App\Services\Ventas\CotizacionVentaPdfService;
use Symfony\Component\HttpFoundation\Response;

class CotizacionVoucherController extends Controller
{
    public function show(string $key, CotizacionVentaPdfService $service): Response
    {
        $cotizacion = CotizacionVenta::query()
            ->with('detalles')
            ->where('Token_Publico', $key)
            ->firstOrFail();

        if ($cotizacion->Estado === CotizacionVenta::ESTADO_VIGENTE && $cotizacion->Fecha_Vencimiento?->lt(now())) {
            $cotizacion->forceFill(['Estado' => CotizacionVenta::ESTADO_VENCIDA])->save();
        }

        abort_if($cotizacion->Estado === CotizacionVenta::ESTADO_ANULADA, 404);

        $filename = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $cotizacion->Numero_Cotizacion) ?: 'cotizacion';

        return response($service->generarDesdeCotizacion($cotizacion), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
