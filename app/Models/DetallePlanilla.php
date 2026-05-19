<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePlanilla extends Model
{
    protected $table = 'detalle_planilla';

    protected $primaryKey = 'Id_Detalle_Planilla';

    public $timestamps = false;

    protected $fillable = [
        'Id_Planilla',
    ];

    protected $casts = [
        'Id_Detalle_Planilla' => 'integer',
        'Id_Planilla' => 'integer',
    ];

    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class, 'Id_Planilla', 'Id_Planilla');
    }
}
