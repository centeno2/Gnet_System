<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    public const TIPO_CONTADO = 'CONTADO';
    public const TIPO_CREDITO = 'CREDITO';

    public const MEDIO_TRANSFERENCIA = 'TRANSFERENCIA';
    public const MEDIO_PAGO_FISICO = 'PAGO_FISICO';

    protected $table = 'compra';

    protected $primaryKey = 'Id_Compra';

    public $timestamps = false;

    protected $fillable = [
        'Numero_Compra',
        'Fecha_Compra',
        'Id_Proveedor',
        'Id_Usuario',
        'Tipo_Compra',
        'Fecha_Limite_Credito',
        'Medio_Pago',
        'Id_Cuenta_Bancaria',
        'Numero_Referencia_Transferencia',
        'Total',
        'Observacion',
        'Id_producto',
        'Retencion',
        'Iva',
    ];

    protected $casts = [
        'Id_Compra' => 'integer',
        'Fecha_Compra' => 'datetime',
        'Fecha_Limite_Credito' => 'date',
        'Id_Proveedor' => 'integer',
        'Id_Usuario' => 'integer',
        'Id_Cuenta_Bancaria' => 'integer',
        'Total' => 'decimal:2',
        'Id_producto' => 'integer',
        'Retencion' => 'decimal:2',
        'Iva' => 'decimal:2',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'Id_Proveedor', 'Id_Proveedor');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Id_producto', 'Id_Producto');
    }

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'Id_Cuenta_Bancaria', 'Id_Cuenta_Bancaria');
    }

    public function detallesCompra(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'Id_Compra', 'Id_Compra');
    }

    public function esCredito(): bool
    {
        return $this->Tipo_Compra === self::TIPO_CREDITO;
    }

    public function esTransferencia(): bool
    {
        return $this->Medio_Pago === self::MEDIO_TRANSFERENCIA;
    }
}
