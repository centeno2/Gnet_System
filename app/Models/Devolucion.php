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

    public const ESTADO_ANULADA = 0;
    public const ESTADO_REGISTRADA = 1;

    public const TIPO_DEVOLUCION_DINERO = 1;
    public const TIPO_CAMBIO_PRODUCTO = 2;
    public const TIPO_SALDO_FAVOR = 3;
    public const TIPO_AJUSTE_CREDITO = 4;

    protected $fillable = [
        'Id_Venta',
        'Id_Cliente',
        'Id_Usuario',
        'Fecha_Devolucion',
        'Con_Factura',
        'Observacion',
        'Estado_Devolucion',
        'Tipo_Devolucion',
        'Total_Devolucion',
    ];

    protected $casts = [
        'Id_Devolucion' => 'integer',
        'Id_Venta' => 'integer',
        'Id_Cliente' => 'integer',
        'Id_Usuario' => 'integer',
        'Fecha_Devolucion' => 'datetime',
        'Con_Factura' => 'boolean',
        'Estado_Devolucion' => 'integer',
        'Tipo_Devolucion' => 'integer',
        'Total_Devolucion' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'Id_Venta', 'Id_Venta');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleDevolucion::class, 'Id_Devolucion', 'Id_Devolucion');
    }

    public function estaRegistrada(): bool
    {
        return (int) $this->Estado_Devolucion === self::ESTADO_REGISTRADA;
    }

    public function estaAnulada(): bool
    {
        return (int) $this->Estado_Devolucion === self::ESTADO_ANULADA;
    }

    public function esDevolucionDinero(): bool
    {
        return (int) $this->Tipo_Devolucion === self::TIPO_DEVOLUCION_DINERO;
    }

    public function esCambioProducto(): bool
    {
        return (int) $this->Tipo_Devolucion === self::TIPO_CAMBIO_PRODUCTO;
    }

    public function esSaldoFavor(): bool
    {
        return (int) $this->Tipo_Devolucion === self::TIPO_SALDO_FAVOR;
    }

    public function esAjusteCredito(): bool
    {
        return (int) $this->Tipo_Devolucion === self::TIPO_AJUSTE_CREDITO;
    }
}