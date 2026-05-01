<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioTecnicoChecklist extends Model
{
    protected $table = 'servicio_tecnico_checklist';

    protected $primaryKey = 'Id_Servicio_Tecnico_Checklist';

    public $timestamps = false;

    protected $fillable = [
        'Id_Servicio_Tecnico',
        'Enciende',
        'Lleva_Cargador',
        'Lleva_Bateria',
        'Pantalla_Sana',
        'Teclado_Completo',
        'Touchpad_Funcional',
        'Tiene_Golpes_Visibles',
        'Tiene_Humedad',
        'Tiene_Sello_Roto',
        'Lleva_Cable_Poder',
        'Lleva_Cartucho_Toner',
        'Lleva_Mouse_Accesorios',
        'Observacion_Checklist',
    ];

    protected $casts = [
        'Id_Servicio_Tecnico_Checklist' => 'integer',
        'Id_Servicio_Tecnico' => 'integer',
        'Enciende' => 'boolean',
        'Lleva_Cargador' => 'boolean',
        'Lleva_Bateria' => 'boolean',
        'Pantalla_Sana' => 'boolean',
        'Teclado_Completo' => 'boolean',
        'Touchpad_Funcional' => 'boolean',
        'Tiene_Golpes_Visibles' => 'boolean',
        'Tiene_Humedad' => 'boolean',
        'Tiene_Sello_Roto' => 'boolean',
        'Lleva_Cable_Poder' => 'boolean',
        'Lleva_Cartucho_Toner' => 'boolean',
        'Lleva_Mouse_Accesorios' => 'boolean',
    ];

    public function servicioTecnico(): BelongsTo
    {
        return $this->belongsTo(ServicioTecnico::class, 'Id_Servicio_Tecnico', 'Id_Servicio_Tecnico');
    }
}
