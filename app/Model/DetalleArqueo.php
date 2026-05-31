<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleArqueo extends Model
{
    protected $table = 'detalle_arqueo';

    protected $primaryKey = 'Id_Detalle_Arqueo';

    public $timestamps = false;

    public const ESTADO_CUADRADO = 'CUADRADO';
    public const ESTADO_FALTANTE = 'FALTANTE';
    public const ESTADO_SOBRANTE = 'SOBRANTE';
    public const ESTADO_DIFERENCIA = 'DIFERENCIA';

    protected $fillable = [
        'Id_Arqueo',
        'Faltante_Cordoba',
        'Faltante_Dolar',
        'Sobrante_Cordoba',
        'Sobrante_Dolar',
        'Cantidad_Egresada_Cordoba',
        'Cantidad_Egresada_Dolar',
        'Estado_Arqueo',
    ];

    protected $casts = [
        'Id_Detalle_Arqueo' => 'integer',
        'Id_Arqueo' => 'integer',
        'Faltante_Cordoba' => 'decimal:2',
        'Faltante_Dolar' => 'decimal:2',
        'Sobrante_Cordoba' => 'decimal:2',
        'Sobrante_Dolar' => 'decimal:2',
        'Cantidad_Egresada_Cordoba' => 'decimal:2',
        'Cantidad_Egresada_Dolar' => 'decimal:2',
        'Estado_Arqueo' => 'string',
    ];

    public function arqueo(): BelongsTo
    {
        return $this->belongsTo(ArqueoCaja::class, 'Id_Arqueo', 'Id_Arqueo');
    }
}