<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServicioTecnico extends Model
{
    protected $table = 'servicio_tecnico';

    protected $primaryKey = 'Id_Servicio_Tecnico';

    public $timestamps = false;

    protected $fillable = [
        'Numero_Orden',
        'Fecha_Ingreso',
        'Id_Cliente',
        'Id_Usuario',
        'Id_Servicio',
        'Id_Trabajador',
        'Tipo_Equipo',
        'Marca',
        'Modelo',
        'Numero_Serie',
        'Problema_Reportado',
        'Detalle_Descriptivo',
        'Estado_Servicio',
        'Costo_Estimado',
        'Fecha_Estimada_Entrega',
        'Observacion_Tecnica',
        'Total_Repuestos',
        'Total_Servicio',
    ];

    protected $casts = [
        'Id_Servicio_Tecnico' => 'integer',
        'Fecha_Ingreso' => 'datetime',
        'Id_Cliente' => 'integer',
        'Id_Usuario' => 'integer',
        'Id_Servicio' => 'integer',
        'Id_Trabajador' => 'integer',
        'Costo_Estimado' => 'decimal:2',
        'Fecha_Estimada_Entrega' => 'date',
        'Total_Repuestos' => 'decimal:2',
        'Total_Servicio' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'Id_Servicio', 'Id_Servicio');
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function checklist(): HasOne
    {
        return $this->hasOne(ServicioTecnicoChecklist::class, 'Id_Servicio_Tecnico', 'Id_Servicio_Tecnico');
    }

    public function productosUsados(): HasMany
    {
        return $this->hasMany(ServicioTecnicoProducto::class, 'Id_Servicio_Tecnico', 'Id_Servicio_Tecnico');
    }
}
