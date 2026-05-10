<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    protected $table = 'persona';

    protected $primaryKey = 'Id_Persona';

    public $timestamps = false;

    protected $fillable = [
        'Primer_Nombre',
        'Segundo_Nombre',
        'Primer_Apellido',
        'Segundo_Apellido',
        'Direccion',
        'Telefono',
    ];

    protected $casts = [
        'Id_Persona' => 'integer',
    ];

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'Id_Persona', 'Id_Persona');
    }

    public function proveedor(): HasOne
    {
        return $this->hasOne(Proveedor::class, 'Id_Persona', 'Id_Persona');
    }

    public function trabajador(): HasOne
    {
        return $this->hasOne(Trabajador::class, 'Id_Persona', 'Id_Persona');
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class, 'Id_Persona', 'Id_Persona');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->Primer_Nombre,
            $this->Segundo_Nombre,
            $this->Primer_Apellido,
            $this->Segundo_Apellido,
        ])));
    }
}

