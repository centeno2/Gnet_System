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

    protected $fillable = [
        'Id_Persona',
        'Estado',
        'Nacionalidad',
        'Codigo_Ruc',
        'Salario',
    ];

    protected $casts = [
        'Id_Proveedor' => 'integer',
        'Id_Persona' => 'integer',
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
}
