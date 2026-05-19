<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Usuario extends Model
{
    protected $table = 'usuario';

    protected $primaryKey = 'Id_Usuario';

    public $timestamps = false;

    protected $fillable = [
        'Id_Trabajador',
        'Nombre_Usuario',
        'Contraseña_Usuario',
        'Estado',
        'Token_Recuperacion',
        'Fecha_Recuperacion',
        'Intentos_Fallidos',
        'Correo',
    ];

    protected $casts = [
        'Id_Usuario' => 'integer',
        'Id_Trabajador' => 'integer',
        'Estado' => 'integer',
        'Fecha_Recuperacion' => 'datetime',
        'Intentos_Fallidos' => 'integer',
    ];

    protected $hidden = [
        'Contraseña_Usuario',
        'Token_Recuperacion',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function contratosInstalacionCamara(): HasMany
    {
        return $this->hasMany(ContratoInstalacionCamara::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function movimientosCaja(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function serviciosTecnicos(): HasMany
    {
        return $this->hasMany(ServicioTecnico::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'Id_Usuario', 'Id_Usuario');
    }
}