<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteFacturaContadoDetalle extends Model
{
    protected $table = 'vw_reporte_factura_contado_detalle';

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
        'Total_Pagado_Cordobas' => 'decimal:2',
        'Pagos_Registrados' => 'integer',
        'Ultimo_Pago' => 'datetime',
        'Cantidad' => 'decimal:2',
        'Precio_Unitario' => 'decimal:2',
        'Descuento_Detalle' => 'decimal:2',
        'Subtotal' => 'decimal:2',
    ];
}
