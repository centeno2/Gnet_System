<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cliente extends Model
{
    protected $table = 'cliente';

    protected $primaryKey = 'Id_Cliente';

    public $timestamps = false;

    public const TIPO_NATURAL = 1;
    public const TIPO_INSTITUCION = 2;

    public const TIPO_PAGO_CONTADO = 1;
    public const TIPO_PAGO_CREDITO = 2;

    public const ESTADO_INACTIVO = 0;
    public const ESTADO_ACTIVO = 1;

    protected $fillable = [
        'Id_Persona',
        'Tipo_Cliente',
        'Institucion',
        'Telefono_Institucion',
        'Direccion_Institucion',
        'Correo_Institucion',
        'Municipio',
        'Estado',
        'Tipo_pago',
        'RUC',
    ];

    protected $casts = [
        'Id_Cliente' => 'integer',
        'Id_Persona' => 'integer',
        'Tipo_Cliente' => 'integer',
        'Estado' => 'boolean',
        'Tipo_pago' => 'integer',
    ];


    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'Id_Persona', 'Id_Persona');
    }

    public function contratosInstalacionCamara(): HasMany
    {
        return $this->hasMany(ContratoInstalacionCamara::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function devoluciones(): HasMany
    {
        return $this->hasMany(Devolucion::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function serviciosTecnicos(): HasMany
    {
        return $this->hasMany(ServicioTecnico::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function creditoGeneral(): HasOne
    {
        return $this->hasOne(ClienteCredito::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function movimientosCredito(): HasMany
    {
        return $this->hasMany(ClienteCreditoMovimiento::class, 'Id_Cliente', 'Id_Cliente');
    }

 

    public function esNatural(): bool
    {
        return (int) $this->Tipo_Cliente === self::TIPO_NATURAL;
    }

    public function esInstitucion(): bool
    {
        return (int) $this->Tipo_Cliente === self::TIPO_INSTITUCION;
    }

    public function esCredito(): bool
    {
        return (int) $this->Tipo_pago === self::TIPO_PAGO_CREDITO;
    }

    public function esContado(): bool
    {
        return (int) $this->Tipo_pago === self::TIPO_PAGO_CONTADO;
    }

   

    public function getNombreFacturacionAttribute(): string
    {
        if ($this->esInstitucion()) {
            return $this->Institucion ?: 'Institución';
        }

        $nombre = trim(implode(' ', array_filter([
            $this->persona?->Primer_Nombre,
            $this->persona?->Segundo_Nombre,
            $this->persona?->Primer_Apellido,
            $this->persona?->Segundo_Apellido,
        ])));

        return $nombre !== '' ? $nombre : 'Cliente';
    }

    public function getTelefonoFacturacionAttribute(): string
    {
        if ($this->esInstitucion()) {
            return $this->Telefono_Institucion ?: 'Sin teléfono';
        }

        return $this->persona?->Telefono ?: 'Sin teléfono';
    }

    public function getCorreoFacturacionAttribute(): string
    {
        if ($this->esInstitucion()) {
            return $this->Correo_Institucion ?: 'Sin correo';
        }

        return $this->persona?->Correo ?: 'Sin correo';
    }

    public function getDireccionFacturacionAttribute(): string
    {
        if ($this->esInstitucion()) {
            return $this->Direccion_Institucion ?: 'Sin dirección';
        }

        return $this->persona?->Direccion ?: 'Sin dirección';
    }

    public function getRucFacturacionAttribute(): string
    {
        foreach (['RUC', 'Codigo_RUC', 'Codigo_Ruc', 'Ruc', 'Cedula_RUC', 'Cedula'] as $campo) {
            $valor = trim((string) ($this->getAttribute($campo) ?? ''));

            if ($valor !== '') {
                return $valor;
            }
        }

        foreach (['RUC', 'Codigo_RUC', 'Codigo_Ruc', 'Ruc', 'Cedula_RUC', 'Cedula'] as $campo) {
            $valor = trim((string) ($this->persona?->getAttribute($campo) ?? ''));

            if ($valor !== '') {
                return $valor;
            }
        }

        return '';
    }

    public function getTipoClienteNombreAttribute(): string
    {
        return $this->esInstitucion() ? 'Institucional' : 'Natural';
    }

    public function getTipoPagoNombreAttribute(): string
    {
        return $this->esCredito() ? 'Crédito' : 'Contado';
    }

    public function getEstadoNombreAttribute(): string
    {
        return $this->Estado ? 'Activo' : 'Inactivo';
    }
}
