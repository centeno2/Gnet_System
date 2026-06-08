<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteComprasProveedor extends Model
{
    protected $table = 'vw_reporte_compras_proveedor';

    protected $primaryKey = 'Id_Detalle_Compra';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Compra' => 'integer',
        'Id_Detalle_Compra' => 'integer',
        'Id_Proveedor' => 'integer',
        'Id_Usuario' => 'integer',
        'Id_Producto' => 'integer',
        'Fecha_Compra' => 'datetime',
        'Fecha' => 'date',
        'Fecha_Limite_Credito' => 'date',
        'Cantidad' => 'decimal:2',
        'Precio_Compra' => 'decimal:2',
        'Subtotal' => 'decimal:2',
        'Iva_Linea' => 'decimal:2',
        'Retencion_Linea' => 'decimal:2',
        'Total_Linea' => 'decimal:2',
        'Total_Compra' => 'decimal:2',
    ];
}
