<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credito extends Model
{
    protected $table = 'credito';

    protected $primaryKey = 'Id_Credito';

    public $timestamps = false;

    protected $fillable = [
        'Id_Cliente_Credito',
        'Id_Venta',
        'Fecha_Credito',
        'Abono_Inicial',
        'Saldo_Actual',
        'Firma_Recibido',
        'Estado',
    ];

    protected $casts = [
        'Id_Credito' => 'integer',
        'Id_Cliente_Credito' => 'integer',
        'Id_Venta' => 'integer',
        'Fecha_Credito' => 'date',
        'Abono_Inicial' => 'decimal:2',
        'Saldo_Actual' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'Id_Venta', 'Id_Venta');
    }

    public function clienteCredito(): BelongsTo
    {
        return $this->belongsTo(ClienteCredito::class, 'Id_Cliente_Credito', 'Id_Cliente_Credito');
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(AbonoCredito::class, 'Id_Credito', 'Id_Credito');
    }

    public function movimientosCreditoGeneral(): HasMany
    {
        return $this->hasMany(ClienteCreditoMovimiento::class, 'Id_Credito', 'Id_Credito');
    }
}
