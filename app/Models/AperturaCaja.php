<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AperturaCaja extends Model
{
    protected $table = 'apertura_caja';

    protected $primaryKey = 'Id_Apertura_Caja';

    public $timestamps = false;

    public const ABIERTO = 1;
    public const CERRADO = 2;

    protected $fillable = [
        'Id_Usuario',
        'Monto_Apertura',
        'Fecha_Apertura',
        'Estado_Apertura',
    ];

    protected $casts = [
        'Id_Apertura_Caja' => 'integer',
        'Id_Usuario' => 'integer',
        'Monto_Apertura' => 'decimal:2',
        'Fecha_Apertura' => 'datetime',
        'Estado_Apertura' => 'integer',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function scopeAbierta(Builder $query): Builder
    {
        return $query->where('Estado_Apertura', self::ABIERTO);
    }

    public function scopeDeHoy(Builder $query): Builder
    {
        return $query->whereDate('Fecha_Apertura', Carbon::today()->toDateString());
    }

    public static function actualCajaAbiertaHoy(): ?self
    {
        return self::query()
            ->abierta()
            ->deHoy()
            ->orderByDesc('Id_Apertura_Caja')
            ->first();
    }

    public static function actualcaja(): ?self
    {
        return self::actualCajaAbiertaHoy();
    }

    protected static function booted(): void
    {
        static::creating(function (AperturaCaja $aperturaCaja): void {
            if (empty($aperturaCaja->Fecha_Apertura)) {
                $aperturaCaja->Fecha_Apertura = Carbon::now();
            }
        });
    }
}