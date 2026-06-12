<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Services\Ventas\ContratoInstalacionCamaraPdfService;
use Symfony\Component\HttpFoundation\Response;

class ContratoInstalacionCamaraController extends Controller
{
    public function show(int $contrato, ContratoInstalacionCamaraPdfService $service): Response
    {
        return response($service->generar($contrato), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="contrato-instalacion-camaras-' . $contrato . '.pdf"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
