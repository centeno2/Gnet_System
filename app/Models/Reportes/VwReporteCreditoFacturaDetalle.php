<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteCreditoFacturaDetalle extends Model
{
    protected $table = 'vw_reporte_credito_factura_detalle';

    protected $primaryKey = 'Id_Detalle_Venta';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Detalle_Venta' => 'integer',
        'Id_Venta' => 'integer',
        'Fecha_Venta' => 'datetime',
        'Id_Cliente' => 'integer',
        'Id_Usuario' => 'integer',
        'Descuento_Venta' => 'decimal:2',
        'Total_Factura' => 'decimal:2',
        'Tipo_Cambio_Venta' => 'decimal:4',
        'Cambio_Entregado_Cordobas' => 'decimal:2',
        'Id_Credito' => 'integer',
        'Fecha_Credito' => 'date',
        'Abono_Inicial' => 'decimal:2',
        'Saldo_Credito' => 'decimal:2',
        'Id_Cliente_Credito' => 'integer',
        'Saldo_Cliente_Credito' => 'decimal:2',
        'Fecha_Registro_Cliente_Credito' => 'datetime',
        'Total_Pagado_Cordobas' => 'decimal:2',
        'Pagos_Registrados' => 'integer',
        'Ultimo_Pago' => 'datetime',
        'Total_Abonos_Cordobas' => 'decimal:2',
        'Abonos_Registrados' => 'integer',
        'Ultimo_Abono' => 'datetime',
        'Total_Cargos_Movimiento' => 'decimal:2',
        'Total_Abonos_Movimiento' => 'decimal:2',
        'Total_Ajustes_Movimiento' => 'decimal:2',
        'Movimientos_Registrados' => 'integer',
        'Ultimo_Movimiento' => 'datetime',
        'Cantidad' => 'decimal:2',
        'Precio_Unitario' => 'decimal:2',
        'Descuento_Detalle' => 'decimal:2',
        'Subtotal' => 'decimal:2',
    ];
}
