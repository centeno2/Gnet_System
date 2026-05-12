<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vacaciones extends Model
{
    protected $table = 'vacaciones';

    protected $primaryKey = 'Id_Vacacion';

    public $timestamps = false;

    protected $fillable = [
        'Id_Trabajador',
        'Fecha_Inicio',
        'Fecha_Fin',
        'Dias_Tomados',
        'Estado',
        'Observacion',
    ];

    protected $casts = [
        'Id_Vacacion' => 'integer',
        'Id_Trabajador' => 'integer',
        'Fecha_Inicio' => 'date',
        'Fecha_Fin' => 'date',
        'Dias_Tomados' => 'integer',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }
}
