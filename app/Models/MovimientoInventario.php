<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimiento_inventario';

    protected $primaryKey = 'Id_Movimiento_inventario';

    public $timestamps = false;

    public const TIPO_ENTRADA = 'ENTRADA';

    public const TIPO_SALIDA_INSTALACION = 'SALIDA_INSTALACION';
    public const TIPO_SALIDA_SERVICIO_TECNICO = 'SALIDA_SERVICIO_TECNICO';
    public const TIPO_SALIDA_CAMBIO_PRODUCTO = 'SALIDA_CAMBIO_PRODUCTO';

    public const TIPO_SALIDA_AJUSTE = 'SALIDA_AJUSTE';
    public const TIPO_SALIDA_DANO = 'SALIDA_DANO';
    public const TIPO_SALIDA_DEFECTO = 'SALIDA_DEFECTO';
    public const TIPO_SALIDA_USO_PERSONAL = 'SALIDA_USO_PERSONAL';
    public const TIPO_SALIDA_PERDIDA = 'SALIDA_PERDIDA';
    public const TIPO_SALIDA_MERMA = 'SALIDA_MERMA';

    public const TIPO_SALIDA_VENTA = 'SALIDA_VENTA';
    public const TIPO_ENTRADA_DEVOLUCION_CLIENTE = 'ENTRADA_DEVOLUCION_CLIENTE';
    public const TIPO_SALIDA_DEVOLUCION_PROVEEDOR = 'SALIDA_DEVOLUCION_PROVEEDOR';

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
        'Motivo_Movimiento' => 'string',
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


