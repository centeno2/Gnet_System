<?php 

 namespace App\Models;

 use Illuminate\Database\Eloquent\Model;
 use Illuminate\Database\Eloquent\Relations\BelongsTo;
 use Illuminate\Database\Eloquent\Relations\HasMany;

 Class AperturaCaja extends Model
 {
    protected $table = "apertura_caja";

    protected $primary_key = "Id_Apertura_Caja";

    public $timestamps = false;

    public const Abierto = 1;
    public const Cerrado = 2;


    protected $fillable = 
    [
    "Id_Usuario",
    "Monto_Apertura",
    "Fecha_Apertura",
    "Estado_Apertura",
    ];

    PROTECTED $casts = 
    [
    "Id_Apertura_Caja"=> "integer",
    "Id_Usuario"=> "integer",
    "Estado_Apertura"=> "boolean",
    ];

    public function Usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class,"Id_Usuario");
    }

 }