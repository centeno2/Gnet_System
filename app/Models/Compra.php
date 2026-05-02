<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    protected $table = 'compra';

    protected $primaryKey = 'Id_Compra';

    public $timestamps = false;

    protected $fillable = [
        'Numero_Compra',
        'Fecha_Compra',
        'Id_Proveedor',
        'Id_Usuario',
        'Tipo_Compra',
        'Subtotal',
        'Descuento',
        'Total',
        'Observacion',
        'Cantida',
        'Precio_Compra',
        'Id_producto',
        'Retencion',
    ];

    protected $casts = [
        'Id_Compra' => 'integer',
        'Fecha_Compra' => 'datetime',
        'Id_Proveedor' => 'integer',
        'Id_Usuario' => 'integer',
        'Subtotal' => 'decimal:2',
        'Descuento' => 'decimal:2',
        'Total' => 'decimal:2',
        'Cantida' => 'integer',
        'Precio_Compra' => 'integer',
        'Id_producto' => 'integer',
        'Retencion' => 'integer',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'Id_Proveedor', 'Id_Proveedor');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function detallesCompra(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'Id_Compra', 'Id_Compra');
    }
}
