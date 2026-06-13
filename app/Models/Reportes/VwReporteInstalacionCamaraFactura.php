<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteInstalacionCamaraFactura extends Model
{
    protected $table = 'vw_reporte_instalacion_camara_factura';

    protected $primaryKey = 'Id_Fila_Reporte';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Fila_Reporte' => 'integer',
        'Id_Detalle_Venta' => 'integer',
        'Id_Contrato_Instalacion_Camara' => 'integer',
        'Fecha_Contrato' => 'datetime',
        'Id_Cliente' => 'integer',
        'Id_Usuario' => 'integer',
        'Id_Trabajador' => 'integer',
        'Cantidad_Camaras' => 'integer',
        'Metros_Cableado' => 'decimal:2',
        'Costo_Mano_Obra' => 'decimal:2',
        'Porcentaje_Anticipo' => 'decimal:2',
        'Monto_Anticipo' => 'decimal:2',
        'Tipo_Cambio_Contrato' => 'decimal:4',
        'Monto_Pagado_Contrato' => 'decimal:2',
        'Cambio_Entregado_Contrato' => 'decimal:2',
        'Fecha_Estimada' => 'date',
        'Total_Materiales' => 'decimal:2',
        'Total_Contrato' => 'decimal:2',
        'Saldo_Pendiente_Contrato' => 'decimal:2',
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