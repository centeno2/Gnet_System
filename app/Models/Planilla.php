<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planilla extends Model
{
    protected $table = 'planilla';

    protected $primaryKey = 'Id_Planilla';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    public const TIPO_NORMAL = 'NORMAL';
    public const TIPO_AGUINALDO = 'AGUINALDO';
    public const TIPO_VACACIONES = 'VACACIONES';
    public const TIPO_LIQUIDACION = 'LIQUIDACION';

    public const ESTADO_BORRADOR = 'BORRADOR';
    public const ESTADO_CALCULADA = 'CALCULADA';
    public const ESTADO_PAGADA = 'PAGADA';
    public const ESTADO_ANULADA = 'ANULADA';

    protected $fillable = [
        'Fecha_Inicio_Corte',
        'Fecha_Fin_Corte',
        'Fecha_Generacion',
        'Tipo_Planilla',
        'Estado',
        'Total_Bruto',
        'Total_Incentivos',
        'Total_Vacaciones',
        'Total_Aguinaldo',
        'Total_Indemnizacion',
        'Total_Deducciones',
        'Total_Neto',
        'Observacion',
    ];

    protected $casts = [
        'Id_Planilla' => 'integer',
        'Fecha_Inicio_Corte' => 'datetime',
        'Fecha_Fin_Corte' => 'datetime',
        'Fecha_Generacion' => 'datetime',
        'Total_Bruto' => 'decimal:2',
        'Total_Incentivos' => 'decimal:2',
        'Total_Vacaciones' => 'decimal:2',
        'Total_Aguinaldo' => 'decimal:2',
        'Total_Indemnizacion' => 'decimal:2',
        'Total_Deducciones' => 'decimal:2',
        'Total_Neto' => 'decimal:2',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePlanilla::class, 'Id_Planilla', 'Id_Planilla');
    }
}