<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Egresos extends Model
{
    protected $table = 'egreso';

    protected $primaryKey = 'Id_Egreso';

    public $timestamps = false;

    protected $fillable = [
        'Id_Apertura_Caja',
        'Id_Usuario',
        'Monto_Egresado_Cordoba',
        'Monto_Egresado_Dolar',
        'Motivo_Egreso',
        'Descripcion_Egreso',
        'Fecha_Egreso',
    ];

    protected $casts = [
        'Id_Egreso' => 'integer',
        'Id_Apertura_Caja' => 'integer',
        'Id_Usuario' => 'integer',
        'Monto_Egresado_Cordoba' => 'decimal:2',
        'Monto_Egresado_Dolar' => 'decimal:2',
        'Fecha_Egreso' => 'datetime',
    ];

    public function aperturaCaja(): BelongsTo
    {
        return $this->belongsTo(AperturaCaja::class, 'Id_Apertura_Caja', 'Id_Apertura_Caja');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function scopeDeApertura(Builder $query, int $aperturaCajaId): Builder
    {
        return $query->where('Id_Apertura_Caja', $aperturaCajaId);
    }

    public function scopeDeUsuario(Builder $query, int $usuarioId): Builder
    {
        return $query->where('Id_Usuario', $usuarioId);
    }
}