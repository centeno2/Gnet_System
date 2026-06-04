<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteCreditosInstitucionales extends Model
{
    protected $table = 'vw_reporte_creditos_institucionales';

    protected $primaryKey = 'Id_Detalle_Venta';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Detalle_Venta' => 'integer',
        'Id_Credito' => 'integer',
        'Id_Venta' => 'integer',
        'Id_Cliente' => 'integer',
        'Fecha_Venta' => 'datetime',
        'Fecha_Credito' => 'date',
        'Cantidad' => 'decimal:2',
        'Precio_Unitario' => 'decimal:2',
        'Subtotal' => 'decimal:2',
        'Descuento' => 'decimal:2',
        'Total_Linea' => 'decimal:2',
        'Total_Venta' => 'decimal:2',
        'Abono_Inicial' => 'decimal:2',
        'Saldo_Actual' => 'decimal:2',
    ];
}
