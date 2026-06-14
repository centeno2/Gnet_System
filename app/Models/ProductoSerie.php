<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoSerie extends Model
{
    protected $table = 'producto_serie';

    protected $primaryKey = 'id_producto_serie';

    public $timestamps = false;

    public const ESTADO_DISPONIBLE = 'DISPONIBLE';
    public const ESTADO_RESERVADO = 'RESERVADO';
    public const ESTADO_DANADO = 'DANADO';
    public const ESTADO_VENDIDO = 'VENDIDO';

    // Estados agregados para salidas manuales de inventario
    public const ESTADO_RETIRADO_INVENTARIO = 'RETIRADO_INVENTARIO';
    public const ESTADO_USO_INTERNO = 'USO_INTERNO';
    public const ESTADO_PERDIDO = 'PERDIDO';

    protected $fillable = [
        'Id_Producto',
        'Numero_Serie',
        'Fecha_Ingreso',
        'Estado',
        'Observacion',
    ];

    protected $casts = [
        'id_producto_serie' => 'integer',
        'Id_Producto' => 'integer',
        'Fecha_Ingreso' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Id_Producto', 'Id_Producto');
    }

    public function productosContrato(): HasMany
    {
        return $this->hasMany(ContratoInstalacionCamaraProducto::class, 'Id_Producto_Serie', 'id_producto_serie');
    }

    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'Id_Producto_serie', 'id_producto_serie');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'Id_Producto_Serie', 'id_producto_serie');
    }

    public function servicioTecnicoProductos(): HasMany
    {
        return $this->hasMany(ServicioTecnicoProducto::class, 'Id_Producto_Serie', 'id_producto_serie');
    }

    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('Estado', self::ESTADO_DISPONIBLE);
    }

    public function scopeNoVendidas(Builder $query): Builder
    {
        return $query->where('Estado', '<>', self::ESTADO_VENDIDO);
    }
}
