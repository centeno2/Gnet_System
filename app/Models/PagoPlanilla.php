<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoPlanilla extends Model
{
    protected $table = 'pago_planilla';

    protected $primaryKey = 'Id_Pago_Planilla';

    public $timestamps = false;

    public const METODO_EFECTIVO = 'EFECTIVO';
    public const METODO_TRANSFERENCIA = 'TRANSFERENCIA';
    public const METODO_CHEQUE = 'CHEQUE';
    public const METODO_OTRO = 'OTRO';

    protected $fillable = [
        'Id_Detalle_Planilla',
        'Fecha_Pago',
        'Monto_Pagado',
        'Metodo_Pago',
        'Observacion',
    ];

    protected $casts = [
        'Id_Pago_Planilla' => 'integer',
        'Id_Detalle_Planilla' => 'integer',
        'Fecha_Pago' => 'datetime',
        'Monto_Pagado' => 'decimal:2',
    ];

    public function detallePlanilla(): BelongsTo
    {
        return $this->belongsTo(DetallePlanilla::class, 'Id_Detalle_Planilla', 'Id_Detalle_Planilla');
    }
}