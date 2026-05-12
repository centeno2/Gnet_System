<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trabajador extends Model
{
    protected $table = 'trabajador';

    protected $primaryKey = 'Id_Trabajador';

    public $timestamps = false;

    protected $fillable = [
        'Id_Persona',
        'Fecha_Ingreso',
        'Estado',
        'Id_Cargo',
        'Cedula',
        'Salario',
    ];

    protected $casts = [
        'Id_Trabajador' => 'integer',
        'Id_Persona' => 'integer',
        'Fecha_Ingreso' => 'date',
        'Estado' => 'integer',
        'Id_Cargo' => 'integer',
        'Salario' => 'decimal:6',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'Id_Persona', 'Id_Persona');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'Id_Cargo', 'Id_Cargo');
    }

    public function contratosInstalacionCamara(): HasMany
    {
        return $this->hasMany(ContratoInstalacionCamara::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function movimientosVacacion(): HasMany
    {
        return $this->hasMany(MovimientoVacacion::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function planillas(): HasMany
    {
        return $this->hasMany(Planilla::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function serviciosTecnicos(): HasMany
    {
        return $this->hasMany(ServicioTecnico::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function vacaciones(): HasMany
    {
        return $this->hasMany(Vacaciones::class, 'Id_Trabajador', 'Id_Trabajador');
    }
}