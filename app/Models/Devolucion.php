<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Devolucion extends Model
{
    protected $table = 'devolucion';

    protected $primaryKey = 'Id_Devolucion';

    public $timestamps = false;

    protected $fillable = [
        'Id_Venta',
        'Id_Cliente',
        'Fecha_Devolucion',
        'Con_Factura',
        'Motivo',
        'Observacion',
    ];

    protected $casts = [
        'Id_Devolucion' => 'integer',
        'Id_Venta' => 'integer',
        'Id_Cliente' => 'integer',
        'Fecha_Devolucion' => 'datetime',
        'Con_Factura' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'Id_Venta', 'Id_Venta');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleDevolucion::class, 'Id_Devolucion', 'Id_Devolucion');
    }
}
