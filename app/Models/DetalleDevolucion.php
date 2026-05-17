<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleDevolucion extends Model
{
    protected $table = 'detalle_devolucion';

    protected $primaryKey = 'Id_Detalle_Devolucion';

    public $timestamps = false;

    protected $fillable = [
        'Id_Devolucion',
        'Id_Detalle_Venta',
        'Cantidad',
        'Monto_Devuelto',
    ];

    protected $casts = [
        'Id_Detalle_Devolucion' => 'integer',
        'Id_Devolucion' => 'integer',
        'Id_Detalle_Venta' => 'integer',
        'Cantidad' => 'decimal:2',
        'Monto_Devuelto' => 'decimal:2',
    ];

    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(Devolucion::class, 'Id_Devolucion', 'Id_Devolucion');
    }

    public function detalleVenta(): BelongsTo
    {
        return $this->belongsTo(DetalleVenta::class, 'Id_Detalle_Venta', 'Id_Detalle_Venta');
    }
}
