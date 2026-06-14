<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteInventarioDisponible extends Model
{
    protected $table = 'vw_reporte_inventario_disponible';

    protected $primaryKey = 'Id_Producto';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Producto' => 'integer',
        'Stock_Actual' => 'integer',
        'Stock_Minimo' => 'integer',
        'Precio_Venta' => 'decimal:2',
        'Valor_Estimado' => 'decimal:2',
        'Stock_Bajo' => 'integer',
    ];
}

