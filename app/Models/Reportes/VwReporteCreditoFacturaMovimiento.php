<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteCreditoFacturaMovimiento extends Model
{
    protected $table = 'vw_reporte_credito_factura_movimientos';

    protected $primaryKey = 'Id_Movimiento_Reporte';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Movimiento_Reporte' => 'integer',
        'Id_Movimiento' => 'integer',
        'Id_Venta' => 'integer',
        'Fecha_Venta' => 'datetime',
        'Total_Factura' => 'decimal:2',
        'Id_Credito' => 'integer',
        'Fecha_Credito' => 'date',
        'Abono_Inicial' => 'decimal:2',
        'Saldo_Credito' => 'decimal:2',
        'Id_Cliente_Credito' => 'integer',
        'Saldo_Cliente_Credito' => 'decimal:2',
        'Total_Abonos_Cordobas' => 'decimal:2',
        'Abonos_Registrados' => 'integer',
        'Ultimo_Abono' => 'datetime',
        'Fecha_Movimiento' => 'datetime',
        'Monto_Movimiento' => 'decimal:2',
        'Saldo_Anterior' => 'decimal:2',
        'Saldo_Despues' => 'decimal:2',
    ];
}
