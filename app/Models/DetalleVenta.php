<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetalleVenta extends Model
{
    protected $table = 'detalle_venta';

    protected $primaryKey = 'Id_Detalle_Venta';

    public $timestamps = false;

    public const TIPO_PRODUCTO = 'PRODUCTO';
    public const TIPO_COPIA = 'COPIA';

    protected $fillable = [
        'Id_Venta',
        'Tipo_Detalle',
        'Id_Producto',
        'Id_Producto_serie',
        'Id_Servicio',
        'Id_Tarifa_Copia',
        'Nombre_Formato',
        'Formato_Copia',
        'Lados_Copia',
        'Cantidad',
        'Precio_Unitario',
        'Subtotal',
        'Descuento',
        'Observacion',
    ];

    protected $casts = [
        'Id_Detalle_Venta' => 'integer',
        'Id_Venta' => 'integer',
        'Id_Producto' => 'integer',
        'Id_Producto_serie' => 'integer',
        'Id_Servicio' => 'integer',
        'Id_Tarifa_Copia' => 'integer',
        'Formato_Copia' => 'integer',
        'Lados_Copia' => 'integer',
        'Cantidad' => 'decimal:2',
        'Precio_Unitario' => 'decimal:2',
        'Subtotal' => 'decimal:2',
        'Descuento' => 'decimal:2',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Id_Producto', 'Id_Producto');
    }

    public function productoSerie(): BelongsTo
    {
        return $this->belongsTo(ProductoSerie::class, 'Id_Producto_serie', 'id_producto_serie');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'Id_Servicio', 'Id_Servicio');
    }

    public function tarifaCopia(): BelongsTo
    {
        return $this->belongsTo(TarifaCopia::class, 'Id_Tarifa_Copia', 'Id_Tarifa_Copia');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'Id_Venta', 'Id_Venta');
    }

    public function detallesDevolucion(): HasMany
    {
        return $this->hasMany(DetalleDevolucion::class, 'Id_Detalle_Venta', 'Id_Detalle_Venta');
    }
}
