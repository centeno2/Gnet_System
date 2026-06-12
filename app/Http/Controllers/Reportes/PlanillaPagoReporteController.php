<?php

namespace App\Http\Controllers\Reportes;

use App\Services\Reportes\Base\BaseExcelReporteService;
use App\Services\Reportes\Base\BasePdfReporteService;
use App\Services\Reportes\Base\BaseReporteService;
use App\Services\Reportes\Base\BaseWordReporteService;
use App\Services\Reportes\PlanillaPagoReporteService;

class PlanillaPagoReporteController extends BaseReporteController
{
    public function __construct(
        BasePdfReporteService $pdfService,
        BaseExcelReporteService $excelService,
        BaseWordReporteService $wordService,
        private readonly PlanillaPagoReporteService $planillaPagoReporteService,
    ) {
        parent::__construct($pdfService, $excelService, $wordService);
    }

    protected function reporte(): BaseReporteService
    {
        return $this->planillaPagoReporteService;
    }
}
