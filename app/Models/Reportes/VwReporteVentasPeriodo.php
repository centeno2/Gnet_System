<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteVentasPeriodo extends Model
{
    protected $table = 'vw_reporte_ventas_periodo';

    protected $primaryKey = 'Id_Venta';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Venta' => 'integer',
        'Fecha_Venta' => 'datetime',
        'Fecha' => 'date',
        'Id_Usuario' => 'integer',
        'Id_Cliente' => 'integer',
        'Descuento' => 'decimal:2',
        'Total' => 'decimal:2',
        'Tipo_Cambio' => 'decimal:4',
        'Cambio_Entregado_Cordobas' => 'decimal:2',
        'Total_Pagado_Cordobas' => 'decimal:2',
    ];
}
