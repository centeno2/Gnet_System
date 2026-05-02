<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoVacacion extends Model
{
    protected $table = 'movimiento_vacacion';

    protected $primaryKey = 'Id_Movimiento_Vacacion';

    public $timestamps = false;

    protected $fillable = [
        'Id_Trabajador',
        'Fecha_Movimiento',
        'Tipo_Movimiento',
        'Dias',
        'Observacion',
    ];

    protected $casts = [
        'Id_Movimiento_Vacacion' => 'integer',
        'Id_Trabajador' => 'integer',
        'Fecha_Movimiento' => 'date',
        'Dias' => 'decimal:2',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }
}
