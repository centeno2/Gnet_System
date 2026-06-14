<?php

namespace App\Http\Controllers\Creditos;

use App\Http\Controllers\Controller;
use App\Services\Creditos\CreditoVoucherPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CreditoVoucherController extends Controller
{
    public function show(string $recibo, Request $request, CreditoVoucherPdfService $service): Response
    {
        $ancho = (int) $request->query('ancho', 80);

        return response($service->generar($recibo, $ancho), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="voucher-credito-' . $recibo . '.pdf"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
