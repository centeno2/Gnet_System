<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetallePlanilla extends Model
{
    protected $table = 'detalle_planilla';

    protected $primaryKey = 'Id_Detalle_Planilla';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_PAGADO = 'PAGADO';
    public const ESTADO_ANULADO = 'ANULADO';

    protected $fillable = [
        'Id_Planilla',
        'Id_Trabajador',
        'Salario_Base',
        'Dias_Trabajados',
        'Dias_Vacaciones',
        'Monto_Vacaciones',
        'Monto_Incentivo',
        'Monto_Aguinaldo',
        'Monto_Indemnizacion',
        'Monto_Deduccion',
        'Total_Bruto',
        'Total_Neto',
        'Estado_Pago',
        'Fecha_Pago',
        'Observacion',
    ];

    protected $casts = [
        'Id_Detalle_Planilla' => 'integer',
        'Id_Planilla' => 'integer',
        'Id_Trabajador' => 'integer',
        'Salario_Base' => 'decimal:2',
        'Dias_Trabajados' => 'decimal:2',
        'Dias_Vacaciones' => 'decimal:2',
        'Monto_Vacaciones' => 'decimal:2',
        'Monto_Incentivo' => 'decimal:2',
        'Monto_Aguinaldo' => 'decimal:2',
        'Monto_Indemnizacion' => 'decimal:2',
        'Monto_Deduccion' => 'decimal:2',
        'Total_Bruto' => 'decimal:2',
        'Total_Neto' => 'decimal:2',
        'Fecha_Pago' => 'datetime',
    ];

    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class, 'Id_Planilla', 'Id_Planilla');
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoPlanilla::class, 'Id_Detalle_Planilla', 'Id_Detalle_Planilla');
    }

    public function incentivos(): HasMany
    {
        return $this->hasMany(IncentivoTrabajador::class, 'Id_Detalle_Planilla', 'Id_Detalle_Planilla');
    }

    public function deducciones(): HasMany
    {
        return $this->hasMany(DeduccionTrabajador::class, 'Id_Detalle_Planilla', 'Id_Detalle_Planilla');
    }

    public function vacaciones(): HasMany
    {
        return $this->hasMany(Vacaciones::class, 'Id_Detalle_Planilla', 'Id_Detalle_Planilla');
    }

    public function movimientosVacacion(): HasMany
    {
        return $this->hasMany(MovimientoVacacion::class, 'Id_Detalle_Planilla', 'Id_Detalle_Planilla');
    }
}