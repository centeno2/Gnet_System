<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    protected $table = 'servicio';

    protected $primaryKey = 'Id_Servicio';

    public $timestamps = false;

    protected $fillable = [
        'Nombre_Servicio',
        'Descripcion',
        'Precio_Base',
        'Requiere_Contrato',
        'Requiere_Anticipo',
        'Porcentaje_Anticipo',
        'Garantia',
        'Estado',
    ];

    protected $casts = [
        'Id_Servicio' => 'integer',
        'Precio_Base' => 'decimal:2',
        'Requiere_Contrato' => 'boolean',
        'Requiere_Anticipo' => 'boolean',
        'Porcentaje_Anticipo' => 'decimal:2',
        'Garantia' => 'boolean',
        'Estado' => 'boolean',
    ];

    public function contratosInstalacionCamara(): HasMany
    {
        return $this->hasMany(ContratoInstalacionCamara::class, 'Id_Servicio', 'Id_Servicio');
    }

    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'Id_Servicio', 'Id_Servicio');
    }

    public function serviciosTecnicos(): HasMany
    {
        return $this->hasMany(ServicioTecnico::class, 'Id_Servicio', 'Id_Servicio');
    }
}
