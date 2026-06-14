<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContratoInstalacionCamara extends Model
{
    protected $table = 'contrato_instalacion_camara';

    protected $primaryKey = 'Id_Contrato_Instalacion_Camara';

    public $timestamps = false;

    protected $fillable = [
        'Id_Venta',
        'Numero_Contrato',
        'Fecha_Contrato',
        'Id_Cliente',
        'Id_Usuario',
        'Id_Servicio',
        'Id_Trabajador',
        'Municipio',
        'Direccion_Instalacion',
        'Cantidad_Camaras',
        'Metros_Cableado',
        'Costo_Mano_Obra',
        'Porcentaje_Anticipo',
        'Monto_Anticipo',
        'Tipo_Cambio',
        'Monto_Pagado',
        'Cambio_Entregado_Cordobas',
        'Fecha_Estimada',
        'Detalle_Contrato',
        'Estado_Contrato',
        'Total_Materiales',
        'Total_Contrato',
        'Saldo_Pendiente',
    ];

    protected $casts = [
        'Id_Contrato_Instalacion_Camara' => 'integer',
        'Id_Venta' => 'integer',
        'Fecha_Contrato' => 'datetime',
        'Id_Cliente' => 'integer',
        'Id_Usuario' => 'integer',
        'Id_Servicio' => 'integer',
        'Id_Trabajador' => 'integer',
        'Cantidad_Camaras' => 'integer',
        'Metros_Cableado' => 'decimal:2',
        'Costo_Mano_Obra' => 'decimal:2',
        'Porcentaje_Anticipo' => 'decimal:2',
        'Monto_Anticipo' => 'decimal:2',
        'Tipo_Cambio' => 'decimal:4',
        'Monto_Pagado' => 'decimal:2',
        'Cambio_Entregado_Cordobas' => 'decimal:2',
        'Fecha_Estimada' => 'date',
        'Total_Materiales' => 'decimal:2',
        'Total_Contrato' => 'decimal:2',
        'Saldo_Pendiente' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'Id_Venta', 'Id_Venta');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'Id_Cliente', 'Id_Cliente');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'Id_Servicio', 'Id_Servicio');
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class, 'Id_Trabajador', 'Id_Trabajador');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Id_Usuario', 'Id_Usuario');
    }

    public function checklist(): HasOne
    {
        return $this->hasOne(
            ContratoInstalacionCamaraChecklist::class,
            'Id_Contrato_Instalacion_Camara',
            'Id_Contrato_Instalacion_Camara'
        );
    }

    public function productosContrato(): HasMany
    {
        return $this->hasMany(
            ContratoInstalacionCamaraProducto::class,
            'Id_Contrato_Instalacion_Camara',
            'Id_Contrato_Instalacion_Camara'
        );
    }
}
