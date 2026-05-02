<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planilla extends Model
{
    protected $table = 'planilla';

    protected $primaryKey = 'Id_Planilla';

    public $timestamps = false;

    protected $fillable = [
        'Id_Trabajador',
        'Fecha_Inicio_Corte',
        'Fecha_Fin_Corte',
        'Total',
    ];

    protected $casts = [
        'Id_Planilla' => 'integer',
        'Id_Trabajador' => 'integer',
        'Fecha_Inicio_Corte' => 'datetime',
        'Fecha_Fin_Corte' => 'datetime',
        'Total' => 'decimal:6',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePlanilla::class, 'Id_Planilla', 'Id_Planilla');
    }
}
