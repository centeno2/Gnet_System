<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venta extends Model
{
    protected $table = 'venta';

    protected $primaryKey = 'Id_Venta';

    public $timestamps = false;

    protected $fillable = [
        'Numero_Factura',
        'Fecha_venta',
        'Id_Cliente',
        'Id_Usuario',
        'Tipo_Venta',
        'Estado',
        'Descuento',
        'Total',
    ];

    protected $casts = [
        'Id_Venta' => 'integer',
        'Fecha_venta' => 'datetime',
        'Id_Cliente' => 'integer',
        'Id_Usuario' => 'integer',
        'Estado' => 'integer',
        'Descuento' => 'decimal:2',
        'Total' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function credito(): HasOne
    {
        return $this->hasOne(Credito::class, 'Id_Venta', 'Id_Venta');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'Id_Venta', 'Id_Venta');
    }

    public function devoluciones(): HasMany
    {
        return $this->hasMany(Devolucion::class, 'Id_Venta', 'Id_Venta');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoVenta::class, 'Id_Venta', 'Id_Venta');
    }
}
