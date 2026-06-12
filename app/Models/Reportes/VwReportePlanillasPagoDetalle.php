<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReportePlanillasPagoDetalle extends Model
{
    protected $table = 'vw_reporte_planillas_pago_detalle';

    protected $primaryKey = 'Id_Detalle_Planilla';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Detalle_Planilla' => 'integer',
        'Id_Planilla' => 'integer',
        'Fecha_Inicio_Corte' => 'datetime',
        'Fecha_Fin_Corte' => 'datetime',
        'Fecha_Corte' => 'date',
        'Fecha_Generacion' => 'datetime',
        'Id_Trabajador' => 'integer',
        'Fecha_Ingreso' => 'date',
        'Salario_Base' => 'decimal:2',
        'Dias_Trabajados' => 'decimal:2',
        'Dias_Vacaciones' => 'decimal:2',
        'Monto_Vacaciones' => 'decimal:2',
        'Monto_Incentivo' => 'decimal:2',
        'Monto_Aguinaldo' => 'decimal:2',
        'Monto_Indemnizacion' => 'decimal:2',
        'Monto_Deduccion' => 'decimal:2',
        'Total_Bruto' => 'decimal:2',
        'Total_Neto' => 'decimal:2',
        'Fecha_Pago' => 'datetime',
        'Dias_Vacaciones_Registradas' => 'decimal:2',
        'Vacaciones_Pagadas' => 'integer',
    ];
}
