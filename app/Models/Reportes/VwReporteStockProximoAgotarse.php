<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteStockProximoAgotarse extends Model
{
    protected $table = 'vw_reporte_stock_proximo_agotarse';

    protected $primaryKey = 'Id_Producto';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Producto' => 'integer',
        'Stock_Actual' => 'integer',
        'Stock_Minimo' => 'integer',
        'Unidades_Faltantes' => 'integer',
        'Precio_Venta' => 'decimal:2',
        'Series_Disponibles' => 'integer',
        'Valor_Estimado' => 'decimal:2',
        'Stock_Bajo' => 'integer',
    ];
}
