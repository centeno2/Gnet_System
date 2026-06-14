<?php

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use App\Services\Ventas\CreditoEntregaReciboPdfService;
use Symfony\Component\HttpFoundation\Response;

class CreditoEntregaReciboController extends Controller
{
    public function show(int $entrega, CreditoEntregaReciboPdfService $service): Response
    {
        $pdf = $service->generar($entrega);
        $filename = 'recibo-entrega-credito-' . str_pad((string) $entrega, 6, '0', STR_PAD_LEFT) . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
