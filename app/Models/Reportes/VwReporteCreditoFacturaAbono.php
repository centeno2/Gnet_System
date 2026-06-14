<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteCreditoFacturaAbono extends Model
{
    protected $table = 'vw_reporte_credito_factura_abonos';

    protected $primaryKey = 'Id_Abono_Reporte';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Abono_Reporte' => 'integer',
        'Id_Abono_Credito' => 'integer',
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
        'Fecha_Abono' => 'datetime',
        'Monto' => 'decimal:2',
        'Tipo_Cambio' => 'decimal:4',
        'Monto_Equivalente_Cordobas' => 'decimal:2',
    ];
}
