<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends Model
{
    public const ADMINISTRADOR = 1;
    public const GERENTE = 2;
    public const CAJERA = 3;
    public const TECNICO = 4;
    public const SUPER_USUARIO = 5;

    protected $table = 'cargo';

    protected $primaryKey = 'Id_Cargo';

    public $timestamps = false;

    protected $fillable = [
        'Cargo_Asignado',
    ];

    protected $casts = [
        'Id_Cargo' => 'integer',
    ];

    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class, 'Id_Cargo', 'Id_Cargo');
    }
}
