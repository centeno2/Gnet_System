<?php

namespace App\Http\Controllers\Reportes;

use App\Services\Reportes\Base\BaseExcelReporteService;
use App\Services\Reportes\Base\BasePdfReporteService;
use App\Services\Reportes\Base\BaseReporteService;
use App\Services\Reportes\Base\BaseWordReporteService;
use App\Services\Reportes\FacturaContadoReporteService;

class FacturaContadoReporteController extends BaseReporteController
{
    public function __construct(
        BasePdfReporteService $pdfService,
        BaseExcelReporteService $excelService,
        BaseWordReporteService $wordService,
        private readonly FacturaContadoReporteService $facturaContadoReporteService,
    ) {
        parent::__construct($pdfService, $excelService, $wordService);
    }

    protected function reporte(): BaseReporteService
    {
        return $this->facturaContadoReporteService;
    }
}
