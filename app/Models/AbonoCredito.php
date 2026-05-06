<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbonoCredito extends Model
{
    protected $table = 'abono_credito';

    protected $primaryKey = 'Id_Abono_Credito';

    public $timestamps = false;

    protected $fillable = [
        'Id_Credito',
        'Fecha_Abono',
        'Moneda',
        'Monto',
        'Tipo_Cambio',
        'Monto_Equivalente_Cordobas',
        'Numero_Transferencia',
        'Observacion',
    ];

    protected $casts = [
        'Id_Abono_Credito' => 'integer',
        'Id_Credito' => 'integer',
        'Fecha_Abono' => 'datetime',
        'Monto' => 'decimal:2',
        'Tipo_Cambio' => 'decimal:4',
        'Monto_Equivalente_Cordobas' => 'decimal:2',
    ];

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class, 'Id_Credito', 'Id_Credito');
    }

    public function getMonedaNombreAttribute(): string
    {
        return strtoupper((string) $this->Moneda) === 'USD' ? 'USD' : 'NIO';
    }
}
