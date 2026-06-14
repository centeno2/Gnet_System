<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleDevolucion extends Model
{
    protected $table = 'detalle_devolucion';

    protected $primaryKey = 'Id_Detalle_Devolucion';

    public $timestamps = false;

    public const ESTADO_PRODUCTO_BUENO = 1;
    public const ESTADO_PRODUCTO_DANADO = 2;
    public const ESTADO_PRODUCTO_REVISION = 3;
    public const ESTADO_PRODUCTO_GARANTIA = 4;

    protected $fillable = [
        'Id_Devolucion',
        'Id_Detalle_Venta',
        'Cantidad',
        'Monto_Devuelto',
        'Motivo_Devolucion',
        'Estado_Producto_Devolucion',
        'Reintegra_Inventario',

        // Producto entregado en cambio
        'Id_Producto_Cambio',
        'Id_Producto_Serie_Cambio',
        'Cantidad_Cambio',
        'Monto_Cambio',
    ];

    protected $casts = [
        'Id_Detalle_Devolucion' => 'integer',
        'Id_Devolucion' => 'integer',
        'Id_Detalle_Venta' => 'integer',
        'Cantidad' => 'decimal:2',
        'Monto_Devuelto' => 'decimal:2',
        'Estado_Producto_Devolucion' => 'integer',
        'Reintegra_Inventario' => 'boolean',
        'Id_Producto_Cambio' => 'integer',
        'Id_Producto_Serie_Cambio' => 'integer',
        'Cantidad_Cambio' => 'decimal:2',
        'Monto_Cambio' => 'decimal:2',
    ];

    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(Devolucion::class, 'Id_Devolucion', 'Id_Devolucion');
    }

    public function detalleVenta(): BelongsTo
    {
        return $this->belongsTo(DetalleVenta::class, 'Id_Detalle_Venta', 'Id_Detalle_Venta');
    }

    public function productoCambio(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Id_Producto_Cambio', 'Id_Producto');
    }

    public function productoSerieCambio(): BelongsTo
    {
        return $this->belongsTo(ProductoSerie::class, 'Id_Producto_Serie_Cambio', 'id_producto_serie');
    }

    public function productoEnBuenEstado(): bool
    {
        return (int) $this->Estado_Producto_Devolucion === self::ESTADO_PRODUCTO_BUENO;
    }

    public function debeReintegrarInventario(): bool
    {
        return (bool) $this->Reintegra_Inventario;
    }

    public function tieneCambioProducto(): bool
    {
        return ! empty($this->Id_Producto_Cambio);
    }
}