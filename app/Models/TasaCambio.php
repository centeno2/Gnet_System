<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TasaCambio extends Model
{
    protected $table = 'tasa_cambio';

    protected $primaryKey = 'Id_Tasa_Cambio';

    public $timestamps = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'Valor_Cambio',
        'Fecha_Modificacion',
    ];

    protected $casts = [
        'Id_Tasa_Cambio' => 'integer',
        'Valor_Cambio' => 'decimal:2',
        'Fecha_Modificacion' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (TasaCambio $tazaCambio): void {
            if (empty($tazaCambio->Fecha_Modificacion)) {
                $tazaCambio->Fecha_Modificacion = Carbon::now();
            }
        });

        static::updating(function (TasaCambio $tazaCambio): void {
            $tazaCambio->Fecha_Modificacion = Carbon::now();
        });
    }

    public static function actual(): ?self
    {
        return self::query()
            ->latest('Fecha_Modificacion')
            ->first();
    }

    public static function valorActual(): float
    {
        return (float) (self::actual()?->Valor_Cambio ?? 0);
    }
}