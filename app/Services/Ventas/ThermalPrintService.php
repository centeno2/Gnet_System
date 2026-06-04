<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use Symfony\Component\Process\Process;

class ThermalPrintService
{
    public function __construct(
        private readonly ThermalVoucherPdfService $voucherPdfService
    ) {}

    public function imprimirVenta(int $ventaId): void
    {
        $venta = Venta::query()->findOrFail($ventaId);

        $ancho = (int) env('THERMAL_PAPER_WIDTH', 80);
        $ancho = in_array($ancho, [58, 80], true) ? $ancho : 80;

        $printer = trim((string) env('THERMAL_PRINTER_NAME', ''));

        if ($printer === '') {
            throw new \RuntimeException('Configura THERMAL_PRINTER_NAME en el archivo .env.');
        }

        $pdf = $this->voucherPdfService->venta($venta, $ancho);

        $dir = storage_path('app/thermal');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $archivo = $dir . '/voucher-' . $venta->Id_Venta . '-' . now()->format('YmdHis') . '.pdf';

        file_put_contents($archivo, $pdf);

        $process = new Process([
            'lp',
            '-d',
            $printer,
            '-o',
            'fit-to-page',
            $archivo,
        ]);

        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: 'No se pudo enviar el voucher a la impresora.');
        }
    }
}
