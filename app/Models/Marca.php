<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    protected $table = 'marca';
    protected $primaryKey = 'Id_Marca';
    public $timestamps = false;

    protected $fillable = [
        'Nombre_Marca',
        'Estado',
    ];

    protected $casts = [
        'Id_Marca' => 'integer',
        'Estado' => 'boolean',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'Id_Marca', 'Id_Marca');
    }
}