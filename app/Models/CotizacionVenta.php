<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CotizacionVenta extends Model
{
    protected $table = 'cotizacion_venta';

    protected $primaryKey = 'Id_Cotizacion';

    public $timestamps = false;

    public const ESTADO_VIGENTE = 'VIGENTE';
    public const ESTADO_VENCIDA = 'VENCIDA';
    public const ESTADO_CONVERTIDA = 'CONVERTIDA';
    public const ESTADO_ANULADA = 'ANULADA';

    protected $fillable = [
        'Numero_Cotizacion',
        'Token_Publico',
        'Fecha_Cotizacion',
        'Fecha_Vencimiento',
        'Plazo_Validez_Dias',
        'Id_Tiempo_Cotizacion',
        'Id_Tiempo_Vencimiento',
        'Id_Cliente',
        'Id_Usuario',
        'Tipo_Venta',
        'Cliente_Nombre',
        'Municipio',
        'Tipo_Cambio',
        'Subtotal',
        'Descuento',
        'Total',
        'Observacion',
        'Estado',
        'Id_Venta_Convertida',
        'Fecha_Registro',
    ];

    protected $casts = [
        'Fecha_Cotizacion' => 'datetime',
        'Fecha_Vencimiento' => 'datetime',
        'Fecha_Registro' => 'datetime',
        'Plazo_Validez_Dias' => 'integer',
        'Id_Tiempo_Cotizacion' => 'integer',
        'Id_Tiempo_Vencimiento' => 'integer',
        'Id_Cliente' => 'integer',
        'Id_Usuario' => 'integer',
        'Tipo_Cambio' => 'float',
        'Subtotal' => 'float',
        'Descuento' => 'float',
        'Total' => 'float',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCotizacionVenta::class, 'Id_Cotizacion', 'Id_Cotizacion')
            ->orderBy('Id_Detalle_Cotizacion');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function ventaConvertida(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'Id_Venta_Convertida', 'Id_Venta');
    }

    public function estaVigente(): bool
    {
        return $this->Estado === self::ESTADO_VIGENTE
            && $this->Fecha_Vencimiento
            && $this->Fecha_Vencimiento->greaterThanOrEqualTo(now());
    }
}
