<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCaja extends Model
{
    protected $table = 'movimiento_caja';

    protected $primaryKey = 'Id_Movimiento_caja';

    public $timestamps = false;

    protected $fillable = [
        'Fecha_Movimiento',
        'Id_Usuario',
        'Tipo_Movimiento',
        'Moneda',
        'Monto',
        'Motivo',
    ];

    protected $casts = [
        'Id_Movimiento_caja' => 'integer',
        'Fecha_Movimiento' => 'datetime',
        'Id_Usuario' => 'integer',
        'Monto' => 'decimal:2',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Id_Usuario', 'Id_Usuario');
    }
}
