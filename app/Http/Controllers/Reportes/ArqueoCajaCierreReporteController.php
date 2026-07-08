<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\Reportes\ArqueoCajaCierrePdfService;
use Symfony\Component\HttpFoundation\Response;

class ArqueoCajaCierreReporteController extends Controller
{
    public function show(int $arqueo, ArqueoCajaCierrePdfService $service): Response
    {
        $filename = 'cierre-caja-' . str_pad((string) $arqueo, 6, '0', STR_PAD_LEFT) . '.pdf';

        return response($service->generar($arqueo), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
