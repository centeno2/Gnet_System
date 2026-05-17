<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $table = 'proveedor';

    protected $primaryKey = 'Id_Proveedor';

    public $timestamps = false;

    public const TIPO_NATURAL = 1;
    public const TIPO_EMPRESA = 2;

    protected $fillable = [
        'Id_Persona',
        'Tipo_Proveedor',
        'Empresa',
        'Telefono_Empresa',
        'Direccion_Empresa',
        'Correo_Empresa',
        'Estado',
        'Nacionalidad',
        'Codigo_Ruc',
    ];

    protected $casts = [
        'Id_Proveedor' => 'integer',
        'Id_Persona' => 'integer',
        'Tipo_Proveedor' => 'integer',
        'Estado' => 'boolean',
        'Codigo_Ruc' => 'string',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'Id_Persona', 'Id_Persona');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'Id_Proveedor', 'Id_Proveedor');
    }

    public function esNatural(): bool
    {
        return $this->Tipo_Proveedor === self::TIPO_NATURAL;
    }

    public function esEmpresa(): bool
    {
        return $this->Tipo_Proveedor === self::TIPO_EMPRESA;
    }
}
