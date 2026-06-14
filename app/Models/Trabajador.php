<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trabajador extends Model
{
    protected $table = 'trabajador';

    protected $primaryKey = 'Id_Trabajador';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'Id_Persona',
        'Fecha_Ingreso',
        'Fecha_Salida',
        'Motivo_Salida',
        'Estado',
        'Id_Cargo',
        'Cedula',
        'Salario',
    ];

    protected $casts = [
        'Id_Trabajador' => 'integer',
        'Id_Persona' => 'integer',
        'Fecha_Ingreso' => 'date',
        'Fecha_Salida' => 'date',
        'Estado' => 'integer',
        'Id_Cargo' => 'integer',
        'Salario' => 'decimal:2',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'Id_Persona', 'Id_Persona');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'Id_Cargo', 'Id_Cargo');
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function contratosInstalacionCamara(): HasMany
    {
        return $this->hasMany(ContratoInstalacionCamara::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function serviciosTecnicos(): HasMany
    {
        return $this->hasMany(ServicioTecnico::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function detallesPlanilla(): HasMany
    {
        return $this->hasMany(DetallePlanilla::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function planillas(): BelongsToMany
    {
        return $this->belongsToMany(
            Planilla::class,
            'detalle_planilla',
            'Id_Trabajador',
            'Id_Planilla',
            'Id_Trabajador',
            'Id_Planilla'
        )->withPivot([
            'Id_Detalle_Planilla',
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
        ]);
    }

    public function vacaciones(): HasMany
    {
        return $this->hasMany(Vacaciones::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function movimientosVacacion(): HasMany
    {
        return $this->hasMany(MovimientoVacacion::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function incentivos(): HasMany
    {
        return $this->hasMany(IncentivoTrabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function deducciones(): HasMany
    {
        return $this->hasMany(DeduccionTrabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function pagosPlanilla(): HasManyThrough
    {
        return $this->hasManyThrough(
            PagoPlanilla::class,
            DetallePlanilla::class,
            'Id_Trabajador',
            'Id_Detalle_Planilla',
            'Id_Trabajador',
            'Id_Detalle_Planilla'
        );
    }
}