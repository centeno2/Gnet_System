<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteOtrasSalidas extends Model
{
    protected $table = 'vw_reporte_otras_salidas';

    protected $primaryKey = 'Id_Movimiento_Inventario';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Movimiento_Inventario' => 'integer',
        'Fecha_Movimiento' => 'datetime',
        'Fecha' => 'date',
        'Id_Producto' => 'integer',
        'Id_Producto_Serie' => 'integer',
        'Cantidad' => 'integer',
        'Precio_Venta' => 'decimal:2',
        'Valor_Estimado' => 'decimal:2',
    ];
}
