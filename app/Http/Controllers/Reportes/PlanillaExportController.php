<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\Reportes\Base\BaseExcelReporteService;
use App\Services\Reportes\Base\BasePdfReporteService;
use App\Services\Reportes\Base\BaseReporteService;
use App\Services\Reportes\Base\BaseWordReporteService;
use App\Services\PlanillaPago\PlanillaAnualReporteService;
use App\Services\PlanillaPago\PlanillaComprobanteReporteService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanillaExportController extends Controller
{
    public function comprobante(
        int $planilla,
        string $formato,
        BasePdfReporteService $pdfService,
        BaseExcelReporteService $excelService,
        BaseWordReporteService $wordService
    ) {
        $reporte = new PlanillaComprobanteReporteService($planilla);

        return $this->exportar($reporte, $formato, $pdfService, $excelService, $wordService);
    }

    public function anual(
        int $year,
        string $formato,
        BasePdfReporteService $pdfService,
        BaseExcelReporteService $excelService,
        BaseWordReporteService $wordService
    ) {
        $year = max(2000, min(2100, $year));
        $reporte = new PlanillaAnualReporteService($year);

        return $this->exportar($reporte, $formato, $pdfService, $excelService, $wordService);
    }

    private function exportar(
        BaseReporteService $reporte,
        string $formato,
        BasePdfReporteService $pdfService,
        BaseExcelReporteService $excelService,
        BaseWordReporteService $wordService
    ) {
        $formato = mb_strtolower(trim($formato));

        return match ($formato) {
            'pdf' => $this->pdf($reporte, $pdfService),
            'excel', 'xlsx' => $this->excel($reporte, $excelService),
            'word', 'docx' => $this->word($reporte, $wordService),
            default => abort(404),
        };
    }

    private function pdf(BaseReporteService $reporte, BasePdfReporteService $pdfService)
    {
        $filename = $reporte->nombreArchivo() . '.pdf';

        return response($pdfService->generar($reporte), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function excel(BaseReporteService $reporte, BaseExcelReporteService $excelService): StreamedResponse
    {
        $writer = $excelService->generar($reporte);
        $filename = $reporte->nombreArchivo() . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function word(BaseReporteService $reporte, BaseWordReporteService $wordService): StreamedResponse
    {
        $writer = $wordService->generar($reporte);
        $filename = $reporte->nombreArchivo() . '.docx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
