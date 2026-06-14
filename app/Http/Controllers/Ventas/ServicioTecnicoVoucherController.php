<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Services\Ventas\ServicioTecnicoVoucherPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ServicioTecnicoVoucherController extends Controller
{
    public function show(int $servicio, Request $request, ServicioTecnicoVoucherPdfService $service): Response
    {
        $ancho = (int) $request->query('ancho', 80);

        return response($service->generar($servicio, $ancho), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="voucher-servicio-tecnico-' . $servicio . '.pdf"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
