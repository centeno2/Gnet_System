<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TarifaCopia extends Model
{
    protected $table = 'tarifa_copia';

    protected $primaryKey = 'Id_Tarifa_Copia';

    public $timestamps = false;

    protected $fillable = [
        'Id_Servicio',
        'Nombre_Tarifa',
        'Tipo_Color',
        'Formato',
        'Lados',
        'Precio_Unitario',
        'Estado',
        'Fecha_Registro',
    ];

    protected $casts = [
        'Id_Tarifa_Copia' => 'integer',
        'Id_Servicio' => 'integer',
        'Precio_Unitario' => 'decimal:2',
        'Estado' => 'boolean',
        'Fecha_Registro' => 'datetime',
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'Id_Servicio', 'Id_Servicio');
    }

    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'Id_Tarifa_Copia', 'Id_Tarifa_Copia');
    }
}
