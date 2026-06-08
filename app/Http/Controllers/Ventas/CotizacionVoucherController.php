<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Services\Ventas\CotizacionVentaPdfService;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CotizacionVoucherController extends Controller
{
    public function show(string $key, CotizacionVentaPdfService $service): Response
    {
        $payload = Cache::get('cotizacion_venta_' . $key);

        abort_if(! is_array($payload), 404);

        $filename = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($payload['numero'] ?? 'proforma')) ?: 'proforma';

        return response($service->generar($payload), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
