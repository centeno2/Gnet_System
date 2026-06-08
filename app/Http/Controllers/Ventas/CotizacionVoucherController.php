<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Services\Ventas\CotizacionVentaPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CotizacionVoucherController extends Controller
{
    public function show(string $key, Request $request, CotizacionVentaPdfService $service): Response
    {
        $payload = Cache::get('cotizacion_venta_' . $key);

        abort_if(! is_array($payload), 404);

        return response($service->generar($payload), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="proforma.pdf"',
        ]);
    }
}
