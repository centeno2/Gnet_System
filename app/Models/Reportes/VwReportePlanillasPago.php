<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReportePlanillasPago extends Model
{
    protected $table = 'vw_reporte_planillas_pago';

    protected $primaryKey = 'Id_Planilla';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Planilla' => 'integer',
        'Fecha_Inicio_Corte' => 'datetime',
        'Fecha_Fin_Corte' => 'datetime',
        'Fecha_Corte' => 'date',
        'Fecha_Generacion' => 'datetime',
        'Total_Bruto' => 'decimal:2',
        'Total_Incentivos' => 'decimal:2',
        'Total_Vacaciones' => 'decimal:2',
        'Total_Aguinaldo' => 'decimal:2',
        'Total_Indemnizacion' => 'decimal:2',
        'Total_Deducciones' => 'decimal:2',
        'Total_Neto' => 'decimal:2',
        'Total_Trabajadores' => 'integer',
        'Trabajadores_Pagados' => 'integer',
        'Trabajadores_Pendientes' => 'integer',
        'Trabajadores_Anulados' => 'integer',
    ];
}
