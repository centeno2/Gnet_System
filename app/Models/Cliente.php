<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'cliente';

    protected $primaryKey = 'Id_Cliente';

    public $timestamps = false;

    protected $fillable = [
        'Id_Persona',
        'Tipo_Cliente',
        'Institucion',
        'Municipio',
        'Estado',
        'Tipo_pago',
    ];

    protected $casts = [
        'Id_Cliente' => 'integer',  
        'Id_Persona' => 'integer',
        'Tipo_Cliente' => 'integer',
        'Estado' => 'boolean',
        'Tipo_pago' => 'integer',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'Id_Persona', 'Id_Persona');
    }

    public function contratosInstalacionCamara(): HasMany
    {
        return $this->hasMany(ContratoInstalacionCamara::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function devoluciones(): HasMany
    {
        return $this->hasMany(Devolucion::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function serviciosTecnicos(): HasMany
    {
        return $this->hasMany(ServicioTecnico::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'Id_Cliente', 'Id_Cliente');
    }
}
