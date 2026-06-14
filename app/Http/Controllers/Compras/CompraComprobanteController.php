<?php

namespace App\Http\Controllers\Compras;

use App\Http\Controllers\Controller;
use App\Services\Compras\CompraComprobantePdfService;
use Symfony\Component\HttpFoundation\Response;

class CompraComprobanteController extends Controller
{
    public function show(int $compra, CompraComprobantePdfService $service): Response
    {
        $resultado = $service->generar($compra);
        $filename = preg_replace('/[^A-Za-z0-9_.-]/', '-', (string) ($resultado['filename'] ?? 'compra.pdf')) ?: 'compra.pdf';

        return response($resultado['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
