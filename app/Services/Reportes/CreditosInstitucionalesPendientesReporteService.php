<?php

namespace App\Services\Reportes;

class CreditosInstitucionalesPendientesReporteService extends CreditosInstitucionalesReporteService
{
    protected function resolverSoloPendientes(): bool
    {
        return true;
    }

    protected function resolverUsarRangoFechas(): bool
    {
        return false;
    }
}
