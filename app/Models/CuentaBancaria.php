<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaBancaria extends Model
{
    public const TIPO_AHORRO = 'CUENTA_AHORRO';
    public const TIPO_CORRIENTE = 'CUENTA_CORRIENTE';
    public const TIPO_TARJETA_DEBITO = 'TARJETA_DEBITO';
    public const TIPO_TARJETA_CREDITO = 'TARJETA_CREDITO';

    public const MONEDA_CORDOBAS = 'CORDOBAS';
    public const MONEDA_DOLARES = 'DOLARES';

    protected $table = 'cuenta_bancaria';

    protected $primaryKey = 'Id_Cuenta_Bancaria';

    public $timestamps = false;

    protected $fillable = [
        'Id_Banco',
        'Nombre_Titular',
        'Numero_Cuenta',
        'Ultimos_Digitos',
        'Tipo_Cuenta',
        'Moneda',
        'Estado',
    ];

    protected $casts = [
        'Id_Cuenta_Bancaria' => 'integer',
        'Id_Banco' => 'integer',
        'Numero_Cuenta' => 'encrypted',
        'Estado' => 'boolean',
    ];

    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class, 'Id_Banco', 'Id_Banco');
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'Id_Cuenta_Bancaria', 'Id_Cuenta_Bancaria');
    }

    public function numeroEnmascarado(): string
    {
        $limpio = preg_replace('/\D+/', '', (string) $this->Numero_Cuenta);

        if ($limpio === '') {
            return '*** **** ****';
        }

        $primeros = substr($limpio, 0, 3);
        $ultimos = strlen($limpio) >= 4 ? substr($limpio, -4) : substr($limpio, -2);

        return $primeros . ' **** ' . $ultimos;
    }
}
