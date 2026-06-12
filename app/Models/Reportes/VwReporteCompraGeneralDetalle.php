<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteCompraGeneralDetalle extends Model
{
    protected $table = 'vw_reporte_compra_general';

    protected $primaryKey = 'Id_Detalle_Compra';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Compra' => 'integer',
        'Id_Proveedor' => 'integer',
        'Id_Usuario' => 'integer',
        'Id_Cuenta_Bancaria' => 'integer',
        'Id_Detalle_Compra' => 'integer',
        'Id_Producto' => 'integer',
        'Fecha_Compra' => 'datetime',
        'Fecha_Limite_Credito' => 'date',
        'Total_Compra' => 'decimal:2',
        'Retencion' => 'decimal:2',
        'Iva' => 'decimal:2',
        'Cantidad' => 'decimal:2',
        'Precio_Compra' => 'decimal:2',
        'Subtotal' => 'decimal:2',
        'Meses_Garantia_Proveedor' => 'integer',
        'Precio_Venta' => 'decimal:2',
        'Stock_Actual' => 'integer',
    ];
}
