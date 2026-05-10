<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCompra extends Model
{
    protected $table = 'detalle_compra';

    protected $primaryKey = 'Id_Detalle_Compra';

    public $timestamps = false;

    protected $fillable = [
        'Id_Compra',
        'Id_Producto',
        'Cantidad',
        'Precio_Compra',
        'Subtotal',
    ];

    protected $casts = [
        'Id_Detalle_Compra' => 'integer',
        'Id_Compra' => 'integer',
        'Id_Producto' => 'integer',
        'Cantidad' => 'decimal:2',
        'Precio_Compra' => 'decimal:2',
        'Subtotal' => 'decimal:2',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'Id_Compra', 'Id_Compra');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Id_Producto', 'Id_Producto');
    }
}
