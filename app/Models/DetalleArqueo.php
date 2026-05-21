<?php 

 namespace App\Models;

 use Illuminate\Database\Eloquent\Model;
 use Illuminate\Database\Eloquent\Relations\BelongsTo;
 use Illuminate\Database\Eloquent\Relations\HasMany;


 class DetalleArqueo extends Model
 {
    protected $table = "detalle_arqueo";

    protected $primaryKey = "Id_Detalle_Arqueo";

    public $timestamps = false;

    protected $fillable = 
    [
      "Id_Arqueo",
      "Faltante_Cordoba",
      "Faltante_Dolar",
      "Sobrante_Cordoba",
      "Sobrante_Dolar",
      "Estado_Arqueo",
    ];

    protected $casts = 
    [
      "Id_Detalle_Arqueo"=> "integer",
      "Id_Arqueo"=> "integer",
      "Faltante_Cordoba"=> "decimal:2",
      "Faltante_Dolar"=> "decimal:2",
      "Sobrante_Cordoba"=> "decimal:2",
      "Sobrante_Dolar"=> "decimal:2",
      "Estado_Arqueo"=> "string",
    ];


     public function Arqueo(): BelongsTo
    {
        return $this->belongsTo(ArqueoCaja::class,"Id_Usuario");
    }


 }