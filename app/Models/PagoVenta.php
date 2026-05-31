<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoVenta extends Model
{
    protected $table = 'pago_venta';

    protected $primaryKey = 'Id_Pago_Venta';

    public $timestamps = false;

    public const MONEDA_CORDOBA = 0;

    public const MONEDA_DOLAR = 1;

    public const TIPO_EFECTIVO = 'EFECTIVO';

    public const TIPO_TRANSFERENCIA = 'TRANSFERENCIA';

    public const TIPO_TARJETA = 'TARJETA';

    protected $fillable = [
        'Id_Venta',
        'Fecha_Pago',
        'Moneda',
        'Tipo_Pago',
        'Numero_Referencia',
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
        return (int) $this->Moneda === self::MONEDA_DOLAR ? 'USD' : 'NIO';
    }

    public function requiereReferencia(): bool
    {
        return in_array($this->Tipo_Pago, [
            self::TIPO_TRANSFERENCIA,
            self::TIPO_TARJETA,
        ], true);
    }

    public function getTipoPagoNombreAttribute(): string
    {
        return match ($this->Tipo_Pago) {
            self::TIPO_TRANSFERENCIA => 'Transferencia',
            self::TIPO_TARJETA => 'Tarjeta',
            default => 'Efectivo',
        };
    }
}
