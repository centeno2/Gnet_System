<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimiento_inventario';

    protected $primaryKey = 'Id_Movimiento_inventario';

    public $timestamps = false;

    protected $fillable = [
        'Id_Producto',
        'Id_Producto_Serie',
        'Fecha_Movimiento',
        'Tipo_Movimiento',
        'Cantidad',
        'Motivo_Movimiento',
    ];

    protected $casts = [
        'Id_Movimiento_inventario' => 'integer',
        'Id_Producto' => 'integer',
        'Id_Producto_Serie' => 'integer',
        'Fecha_Movimiento' => 'datetime',
        'Cantidad' => 'integer',
        'Motivo_Movimiento' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Id_Producto', 'Id_Producto');
    }

    public function productoSerie(): BelongsTo
    {
        return $this->belongsTo(ProductoSerie::class, 'Id_Producto_Serie', 'id_producto_serie');
    }
}
