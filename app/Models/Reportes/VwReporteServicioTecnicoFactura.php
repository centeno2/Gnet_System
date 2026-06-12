<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteServicioTecnicoFactura extends Model
{
    protected $table = 'vw_reporte_servicio_tecnico_factura';

    protected $primaryKey = 'Id_Fila_Reporte';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Fila_Reporte' => 'integer',
        'Id_Detalle_Venta' => 'integer',
        'Id_Servicio_Tecnico' => 'integer',
        'Fecha_Ingreso' => 'datetime',
        'Fecha_Estimada_Entrega' => 'date',
        'Id_Cliente' => 'integer',
        'Id_Usuario' => 'integer',
        'Id_Trabajador' => 'integer',
        'Costo_Estimado' => 'decimal:2',
        'Total_Repuestos' => 'decimal:2',
        'Total_Servicio' => 'decimal:2',
        'Tipo_Cambio_Servicio' => 'decimal:4',
        'Monto_Pagado_Servicio' => 'decimal:2',
        'Saldo_Pendiente_Servicio' => 'decimal:2',
        'Cambio_Entregado_Servicio' => 'decimal:2',
        'Id_Venta' => 'integer',
        'Fecha_Venta' => 'datetime',
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
