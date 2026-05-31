<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratoInstalacionCamaraProducto extends Model
{
    protected $table = 'contrato_instalacion_camara_producto';

    protected $primaryKey = 'Id_Contrato_Instalacion_Camara_Producto';

    public $timestamps = false;

    protected $fillable = [
        'Id_Contrato_Instalacion_Camara',
        'Id_Producto',
        'Id_Producto_Serie',
        'Cantidad',
        'Precio_Unitario',
        'Subtotal',
        'Observacion',
    ];

    protected $casts = [
        'Id_Contrato_Instalacion_Camara_Producto' => 'integer',
        'Id_Contrato_Instalacion_Camara' => 'integer',
        'Id_Producto' => 'integer',
        'Id_Producto_Serie' => 'integer',
        'Cantidad' => 'decimal:2',
        'Precio_Unitario' => 'decimal:2',
        'Subtotal' => 'decimal:2',
    ];

    public function contratoInstalacionCamara(): BelongsTo
    {
        return $this->belongsTo(ContratoInstalacionCamara::class, 'Id_Contrato_Instalacion_Camara', 'Id_Contrato_Instalacion_Camara');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Id_Producto', 'Id_Producto');
    }

    public function productoSerie(): BelongsTo
    {
        return $this->belongsTo(ProductoSerie::class, 'Id_Producto_Serie', 'id_producto_serie');
    }
}
