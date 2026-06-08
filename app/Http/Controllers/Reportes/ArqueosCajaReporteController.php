<?php

namespace App\Http\Controllers\Reportes;

use App\Services\Reportes\ArqueosCajaReporteService;
use App\Services\Reportes\Base\BaseExcelReporteService;
use App\Services\Reportes\Base\BasePdfReporteService;
use App\Services\Reportes\Base\BaseReporteService;
use App\Services\Reportes\Base\BaseWordReporteService;

class ArqueosCajaReporteController extends BaseReporteController
{
    public function __construct(
        BasePdfReporteService $pdfService,
        BaseExcelReporteService $excelService,
        BaseWordReporteService $wordService,
        private readonly ArqueosCajaReporteService $arqueosCajaReporteService,
    ) {
        parent::__construct($pdfService, $excelService, $wordService);
    }

    protected function reporte(): BaseReporteService
    {
        return $this->arqueosCajaReporteService;
    }
}
