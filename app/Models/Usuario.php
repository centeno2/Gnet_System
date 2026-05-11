<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Usuario extends Authenticatable
{
    public function getAuthIdentifierName()
    {
        return 'Id_Usuario';
    }

    protected $table = 'usuario';
    protected $primaryKey = 'Id_Usuario';
    public $timestamps = false;

    protected $fillable = [
        'Id_Persona',
        'Nombre_Usuario',
        'Contraseña_Usuario',
        'Rol',
        'Estado',
        'Token_Recuperacion',
        'Fecha_Recuperacion',
        'Intentos_Fallidos',
    ];

    protected $casts = [
        'Id_Usuario' => 'integer',
        'Id_Persona' => 'integer',
        'Estado' => 'integer',
        'Fecha_Recuperacion' => 'datetime',
        'Intentos_Fallidos' => 'integer',
    ];

    // RELACIONES 

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'Id_Persona', 'Id_Persona');
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

    //MÉTODOS DE SEGURIDAD 

    /**
     * Valida contraseña del usuario
     */
    public function validarContraseña(string $contraseña): bool
    {
        return Hash::check($contraseña, $this->Contraseña_Usuario);
    }

    /**
     * Verifica si el usuario está activo
     */
    public function estaActivo(): bool
    {
        return $this->Estado === 1;
    }

    /**
     * Incrementa intentos fallidos
     */
    public function incrementarIntentosFallidos(): void
    {
        $intentos = ($this->Intentos_Fallidos ?? 0) + 1;

        // Bloquear después de 10 intentos
        if ($intentos >= 10)
        {
            $this->update([
                'Intentos_Fallidos' => $intentos,
                'Estado' => 0,  // Bloqueado
            ]);
        } 
        else 
        {
            $this->update(['Intentos_Fallidos' => $intentos]);
        }
    }

    /**
     * Resetea intentos fallidos después de login
     */
    public function resetearIntentosFallidos(): void
    {
        $this->update(['Intentos_Fallidos' => 0]);
    }

    /**
     * Genera token de recuperación de contraseña
     */
    public function generarTokenRecuperacion(): string
    {
        $token = Str::random(60);
        $this->update([
            'Token_Recuperacion' => Hash::make($token),
            'Fecha_Recuperacion' => now()->addHours(2),
        ]);
        return $token;
    }

    /**
     * Valida token de recuperación
     */
    public function validarTokenRecuperacion(string $token): bool
    {
        if (!$this->Token_Recuperacion || !$this->Fecha_Recuperacion) 
        {
            return false;
        }

        if (now()->isAfter($this->Fecha_Recuperacion)) 
        {
            return false;
        }

        return Hash::check($token, $this->Token_Recuperacion);
    }

    /**
     * Limpia datos de recuperación
     */
    public function limpiarRecuperacion(): void
    {
        $this->update([
            'Token_Recuperacion' => null,
            'Fecha_Recuperacion' => null,
        ]);
    }
}
