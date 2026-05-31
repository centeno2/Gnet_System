<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaProducto extends Model
{
    protected $table = 'categoria_producto';

    protected $primaryKey = 'Id_Categoria';

    public $timestamps = false;

    protected $fillable = [
        'Nombre_Categoria',
    ];

    protected $casts = [
        'Id_Categoria' => 'integer',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'Id_Categoria', 'Id_Categoria');
    }
}