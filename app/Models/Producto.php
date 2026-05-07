<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'producto';

    protected $primaryKey = 'Id_Producto';

    public $timestamps = false;

    protected $fillable = [
        'Id_Categoria',
        'Id_Marca',
        'Nombre_Producto',
        'Modelo',
        'Stock_Actual',
        'Stock_Minimo',

        'Precio_Venta',
        'Fecha_Vencimiento',
        'Meses_Garantia_Nuevo',
        'Meses_Garantia_Usado',
        'Estado',
    ];

    protected $casts = [
        'Id_Producto' => 'integer',
        'Id_Categoria' => 'integer',
        'Id_Marca' => 'integer',
        'Stock_Actual' => 'integer',
        'Stock_Minimo' => 'integer',

        'Precio_Venta' => 'decimal:2',
        'Fecha_Vencimiento' => 'datetime',
        'Meses_Garantia_Nuevo' => 'integer',
        'Meses_Garantia_Usado' => 'integer',
        'Estado' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'Id_Categoria', 'Id_Categoria');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'Id_Marca', 'Id_Marca');
    }

    public function series(): HasMany
    {
        return $this->hasMany(ProductoSerie::class, 'Id_Producto', 'Id_Producto');
    }

    public function productosContrato(): HasMany
    {
        return $this->hasMany(ContratoInstalacionCamaraProducto::class, 'Id_Producto', 'Id_Producto');
    }

    public function detallesCompra(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'Id_Producto', 'Id_Producto');
    }

    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'Id_Producto', 'Id_Producto');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'Id_Producto', 'Id_Producto');
    }

    public function servicioTecnicoProductos(): HasMany
    {
        return $this->hasMany(ServicioTecnicoProducto::class, 'Id_Producto', 'Id_Producto');
    }
}
