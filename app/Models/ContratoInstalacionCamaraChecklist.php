<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratoInstalacionCamaraChecklist extends Model
{
    protected $table = 'contrato_instalacion_camara_checklist';

    protected $primaryKey = 'Id_Contrato_Instalacion_Camara_Checklist';

    public $timestamps = false;

    protected $fillable = [
        'Id_Contrato_Instalacion_Camara',
        'Incluye_Instalacion_Fisica',
        'Incluye_Configuracion_App',
        'Incluye_Pruebas_Sistema',
        'Incluye_Capacitacion_Basica',
        'Incluye_Garantia',
        'Anticipo_Recibido',
        'Contrato_Firmado',
        'Cliente_Aprueba_Recorrido',
        'Sistema_Energizado',
        'Observacion_Checklist',
    ];

    protected $casts = [
        'Id_Contrato_Instalacion_Camara_Checklist' => 'integer',
        'Id_Contrato_Instalacion_Camara' => 'integer',
        'Incluye_Instalacion_Fisica' => 'boolean',
        'Incluye_Configuracion_App' => 'boolean',
        'Incluye_Pruebas_Sistema' => 'boolean',
        'Incluye_Capacitacion_Basica' => 'boolean',
        'Incluye_Garantia' => 'boolean',
        'Anticipo_Recibido' => 'boolean',
        'Contrato_Firmado' => 'boolean',
        'Cliente_Aprueba_Recorrido' => 'boolean',
        'Sistema_Energizado' => 'boolean',
    ];

    public function contratoInstalacionCamara(): BelongsTo
    {
        return $this->belongsTo(ContratoInstalacionCamara::class, 'Id_Contrato_Instalacion_Camara', 'Id_Contrato_Instalacion_Camara');
    }
}
