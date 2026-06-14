<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCotizacionVenta extends Model
{
    protected $table = 'detalle_cotizacion_venta';

    protected $primaryKey = 'Id_Detalle_Cotizacion';

    public $timestamps = false;

    protected $fillable = [
        'Id_Cotizacion',
        'Tipo_Detalle',
        'Id_Producto',
        'Id_Producto_serie',
        'Id_Servicio',
        'Id_Tarifa_Copia',
        'Codigo',
        'Descripcion',
        'Nombre_Formato',
        'Formato_Copia',
        'Lados_Copia',
        'Area',
        'Cantidad',
        'Precio_Unitario_Cotizado',
        'Descuento',
        'Subtotal_Bruto',
        'Subtotal',
        'Fecha_Registro',
    ];

    protected $casts = [
        'Id_Cotizacion' => 'integer',
        'Id_Producto' => 'integer',
        'Id_Producto_serie' => 'integer',
        'Id_Servicio' => 'integer',
        'Id_Tarifa_Copia' => 'integer',
        'Formato_Copia' => 'integer',
        'Lados_Copia' => 'integer',
        'Cantidad' => 'float',
        'Precio_Unitario_Cotizado' => 'float',
        'Descuento' => 'float',
        'Subtotal_Bruto' => 'float',
        'Subtotal' => 'float',
        'Fecha_Registro' => 'datetime',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(CotizacionVenta::class, 'Id_Cotizacion', 'Id_Cotizacion');
    }
}
