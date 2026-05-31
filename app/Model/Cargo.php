<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends Model
{
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
