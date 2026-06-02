<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\Reportes\Base\BaseExcelReporteService;
use App\Services\Reportes\Base\BasePdfReporteService;
use App\Services\Reportes\Base\BaseReporteService;
use App\Services\Reportes\Base\BaseWordReporteService;

abstract class BaseReporteController extends Controller
{
    public function __construct(
        protected readonly BasePdfReporteService $pdfService,
        protected readonly BaseExcelReporteService $excelService,
        protected readonly BaseWordReporteService $wordService,
    ) {}

    abstract protected function reporte(): BaseReporteService;

    public function pdf()
    {
        $reporte = $this->reporte();

        $directorio = storage_path('app/reportes');

        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $archivo = $directorio . '/' . $reporte->cacheKey() . '.pdf';

        if (file_exists($archivo) && filemtime($archivo) >= now()->subMinutes(2)->timestamp) {
            return response()->file($archivo, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $reporte->nombreArchivo() . '.pdf"',
                'Cache-Control' => 'public, max-age=120',
            ]);
        }

        $contenido = $this->pdfService->generar($reporte);

        file_put_contents($archivo, $contenido);

        return response()->file($archivo, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $reporte->nombreArchivo() . '.pdf"',
            'Cache-Control' => 'public, max-age=120',
        ]);
    }

    public function excel()
    {
        $reporte = $this->reporte();

        $writer = $this->excelService->generar($reporte);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $reporte->nombreArchivo() . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function word()
    {
        $reporte = $this->reporte();

        $writer = $this->wordService->generar($reporte);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $reporte->nombreArchivo() . '.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
