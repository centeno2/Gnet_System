<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacaciones extends Model
{
    protected $table = 'vacaciones';

    protected $primaryKey = 'Id_Vacacion';

    public $timestamps = false;

    public const ESTADO_SOLICITADA = 'SOLICITADA';
    public const ESTADO_APROBADA = 'APROBADA';
    public const ESTADO_PAGADA = 'PAGADA';
    public const ESTADO_ANULADA = 'ANULADA';
    public const ESTADO_RECHAZADA = 'RECHAZADA';

    protected $fillable = [
        'Id_Trabajador',
        'Id_Detalle_Planilla',
        'Fecha_Inicio',
        'Fecha_Fin',
        'Dias_Tomados',
        'Estado',
        'Observacion',
    ];

    protected $casts = [
        'Id_Vacacion' => 'integer',
        'Id_Trabajador' => 'integer',
        'Id_Detalle_Planilla' => 'integer',
        'Fecha_Inicio' => 'date',
        'Fecha_Fin' => 'date',
        'Dias_Tomados' => 'integer',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function detallePlanilla(): BelongsTo
    {
        return $this->belongsTo(DetallePlanilla::class, 'Id_Detalle_Planilla', 'Id_Detalle_Planilla');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoVacacion::class, 'Id_Vacacion', 'Id_Vacacion');
    }
}