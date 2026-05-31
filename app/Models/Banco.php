<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Banco extends Model
{
    protected $table = 'banco';

    protected $primaryKey = 'Id_Banco';

    public $timestamps = false;

    protected $fillable = [
        'Nombre_Banco',
        'Estado',
    ];

    protected $casts = [
        'Id_Banco' => 'integer',
        'Estado' => 'boolean',
    ];

    public function cuentasBancarias(): HasMany
    {
        return $this->hasMany(CuentaBancaria::class, 'Id_Banco', 'Id_Banco');
    }
}
