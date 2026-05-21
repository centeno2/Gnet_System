<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArqueoCaja extends Model
{
  protected $table = "arqueo_caja";

  protected $primaryKey = "Id_Arqueo";

  public $timestamps = false;

  protected $fillable =
  [
    "Id_Usuario",
    "Id_Apertura_Caja",
    "Total_Caja_Cordoba",
    "Total_Caja_Dolar",
    "Fecha_Arqueo",
  ];

  protected $casts =
  [
    "Id_Arqueo" => "integer",
    "Id_Usuario" => "integer",
    "Id_Apertura_Caja" => "integer",
    "Total_Caja_Cordoba" => "decimal:2",
    "Total_Caja_Dolar" => "decimal:2",
    "Fecha_Arqueo" => "datetime",
  ];

  public function Usuario(): BelongsTo
  {
    return $this->belongsTo(Usuario::class, "Id_Usuario");
  }

  public function Apertura(): BelongsTo
  {
    return $this->belongsTo(AperturaCaja::class, "Id_Apertura_Caja");
  }
}
