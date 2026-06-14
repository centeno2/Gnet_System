<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DimTiempo extends Model
{
    protected $table = 'dim_tiempo';

    protected $primaryKey = 'Id_Tiempo';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'Id_Tiempo',
        'Fecha',
        'Anio',
        'Mes',
        'Dia',
        'Trimestre',
        'Semana',
        'Dia_Semana',
        'Nombre_Mes',
        'Nombre_Dia',
        'Es_Fin_Semana',
    ];

    protected $casts = [
        'Id_Tiempo' => 'integer',
        'Fecha' => 'date',
        'Anio' => 'integer',
        'Mes' => 'integer',
        'Dia' => 'integer',
        'Trimestre' => 'integer',
        'Semana' => 'integer',
        'Dia_Semana' => 'integer',
        'Es_Fin_Semana' => 'boolean',
    ];
}
