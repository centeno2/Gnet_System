<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoVenta extends Model
{
    protected $table = 'pago_venta';

    protected $primaryKey = 'Id_Pago_Venta';

    public $timestamps = false;

    protected $fillable = [
        'Id_Venta',
        'Fecha_Pago',
        'Moneda',
        'Tipo_Pago',
        'Monto',
        'Tipo_Cambio',
        'Monto_Equivalente_Cordobas',
    ];

    protected $casts = [
        'Id_Pago_Venta' => 'integer',
        'Id_Venta' => 'integer',
        'Fecha_Pago' => 'datetime',
        'Moneda' => 'integer',
        'Monto' => 'decimal:2',
        'Tipo_Cambio' => 'decimal:4',
        'Monto_Equivalente_Cordobas' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'Id_Venta', 'Id_Venta');
    }

    public function getMonedaNombreAttribute(): string
    {
        return (int) $this->Moneda === 1 ? 'USD' : 'NIO';
    }
}
