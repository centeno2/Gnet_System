<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioTecnicoProducto extends Model
{
    protected $table = 'servicio_tecnico_producto';

    protected $primaryKey = 'Id_Servicio_Tecnico_Producto';

    public $timestamps = false;

    protected $fillable = [
        'Id_Servicio_Tecnico',
        'Id_Producto',
        'Id_Producto_Serie',
        'Cantidad',
        'Precio_Unitario',
        'Subtotal',
        'Observacion',
    ];

    protected $casts = [
        'Id_Servicio_Tecnico_Producto' => 'integer',
        'Id_Servicio_Tecnico' => 'integer',
        'Id_Producto' => 'integer',
        'Id_Producto_Serie' => 'integer',
        'Cantidad' => 'decimal:2',
        'Precio_Unitario' => 'decimal:2',
        'Subtotal' => 'decimal:2',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Id_Producto', 'Id_Producto');
    }

    public function productoSerie(): BelongsTo
    {
        return $this->belongsTo(ProductoSerie::class, 'Id_Producto_Serie', 'id_producto_serie');
    }

    public function servicioTecnico(): BelongsTo
    {
        return $this->belongsTo(ServicioTecnico::class, 'Id_Servicio_Tecnico', 'Id_Servicio_Tecnico');
    }
}
