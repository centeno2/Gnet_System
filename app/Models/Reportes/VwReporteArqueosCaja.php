<?php

namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class VwReporteArqueosCaja extends Model
{
    protected $table = 'vw_reporte_arqueos_caja';

    protected $primaryKey = 'Id_Arqueo_Caja';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'Id_Arqueo_Caja' => 'integer',
        'Id_Apertura_Caja' => 'integer',
        'Numero_Caja' => 'integer',
        'Id_Usuario' => 'integer',
        'Fecha_Apertura' => 'datetime',
        'Fecha_Arqueo' => 'datetime',
        'Fecha' => 'date',
        'Monto_Apertura' => 'decimal:2',
        'Total_Efectivo_Cordobas' => 'decimal:2',
        'Total_Efectivo_Dolares' => 'decimal:2',
        'Faltante_Cordobas' => 'decimal:2',
        'Faltante_Dolares' => 'decimal:2',
        'Sobrante_Cordobas' => 'decimal:2',
        'Sobrante_Dolares' => 'decimal:2',
        'Cantidad_Egresada_Cordobas' => 'decimal:2',
        'Cantidad_Egresada_Dolares' => 'decimal:2',
    ];
}
