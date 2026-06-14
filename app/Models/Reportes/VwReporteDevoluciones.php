<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteDevoluciones extends Model
{
    protected $table = 'vw_reporte_devoluciones';

    protected $primaryKey = 'Id_Detalle_Devolucion';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Detalle_Devolucion' => 'integer',
        'Id_Devolucion' => 'integer',
        'Id_Venta' => 'integer',
        'Id_Cliente' => 'integer',
        'Id_Usuario' => 'integer',
        'Fecha_Devolucion' => 'datetime',
        'Fecha' => 'date',
        'Cantidad' => 'decimal:2',
        'Monto_Devuelto' => 'decimal:2',
        'Monto_Cambio' => 'decimal:2',
        'Total_Devolucion' => 'decimal:2',
    ];
}
