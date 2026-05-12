<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteCreditoMovimiento extends Model
{
    protected $table = 'cliente_credito_movimiento';

    protected $primaryKey = 'Id_Movimiento';

    public $timestamps = false;

    public const TIPO_CARGO = 'CARGO';
    public const TIPO_ABONO = 'ABONO';
    public const TIPO_AJUSTE = 'AJUSTE';

    protected $fillable = [
        'Id_Cliente_Credito',
        'Id_Cliente',
        'Id_Venta',
        'Id_Credito',
        'Tipo_Movimiento',
        'Monto',
        'Saldo_Anterior',
        'Saldo_Despues',
        'Fecha_Movimiento',
        'Observacion',
    ];

    protected $casts = [
        'Id_Movimiento' => 'integer',
        'Id_Cliente_Credito' => 'integer',
        'Id_Cliente' => 'integer',
        'Id_Venta' => 'integer',
        'Id_Credito' => 'integer',
        'Monto' => 'decimal:2',
        'Saldo_Anterior' => 'decimal:2',
        'Saldo_Despues' => 'decimal:2',
        'Fecha_Movimiento' => 'datetime',
    ];

    public function clienteCredito(): BelongsTo
    {
        return $this->belongsTo(ClienteCredito::class, 'Id_Cliente_Credito', 'Id_Cliente_Credito');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'Id_Venta', 'Id_Venta');
    }

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class, 'Id_Credito', 'Id_Credito');
    }
}
