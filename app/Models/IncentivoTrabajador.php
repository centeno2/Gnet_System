<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncentivoTrabajador extends Model
{
    protected $table = 'incentivo_trabajador';

    protected $primaryKey = 'Id_Incentivo';

    public $timestamps = false;

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_APLICADO = 'APLICADO';
    public const ESTADO_ANULADO = 'ANULADO';

    protected $fillable = [
        'Id_Trabajador',
        'Id_Detalle_Planilla',
        'Fecha_Incentivo',
        'Concepto',
        'Monto',
        'Estado',
        'Observacion',
    ];

    protected $casts = [
        'Id_Incentivo' => 'integer',
        'Id_Trabajador' => 'integer',
        'Id_Detalle_Planilla' => 'integer',
        'Fecha_Incentivo' => 'datetime',
        'Monto' => 'decimal:2',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function detallePlanilla(): BelongsTo
    {
        return $this->belongsTo(DetallePlanilla::class, 'Id_Detalle_Planilla', 'Id_Detalle_Planilla');
    }
}