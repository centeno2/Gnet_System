<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'Correo',
        'Direccion',
        'Telefono',
    ];

    protected $casts = [
        'Id_Persona' => 'integer',
    ];

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'Id_Persona', 'Id_Persona');
    }

    public function proveedores(): HasMany
    {
        return $this->hasMany(Proveedor::class, 'Id_Persona', 'Id_Persona');
    }

    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class, 'Id_Persona', 'Id_Persona');
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class, 'Id_Persona', 'Id_Persona');
    }
}
