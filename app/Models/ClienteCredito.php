<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteCredito extends Model
{
    protected $table = 'cliente_credito';

    protected $primaryKey = 'Id_Cliente_Credito';

    public $timestamps = false;

    protected $fillable = [
        'Id_Cliente',
        'Limite_Credito',
        'Saldo_Actual',
        'Estado',
        'Fecha_Registro',
    ];

    protected $casts = [
        'Id_Cliente_Credito' => 'integer',
        'Id_Cliente' => 'integer',
        'Limite_Credito' => 'decimal:2',
        'Saldo_Actual' => 'decimal:2',
        'Fecha_Registro' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function creditos(): HasMany
    {
        return $this->hasMany(Credito::class, 'Id_Cliente_Credito', 'Id_Cliente_Credito');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(ClienteCreditoMovimiento::class, 'Id_Cliente_Credito', 'Id_Cliente_Credito');
    }
}
