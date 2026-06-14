<?php

use App\Models\AperturaCaja;
use App\Models\DetalleDevolucion;
use App\Models\DetalleVenta;
use App\Models\Devolucion;
use App\Models\Egresos;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public const MONTO_LIMITE_DEVOLUCION_MONETARIA = 2000.00;

    public const TIPO_LINEA_MONETARIA = 'MONETARIA';
    public const TIPO_LINEA_CAMBIO = 'CAMBIO';

    public const ESTADO_PRODUCTO_BUENO = 1;
    public const ESTADO_PRODUCTO_DANADO = 2;
    public const ESTADO_PRODUCTO_REVISION = 3;
    public const ESTADO_PRODUCTO_GARANTIA = 4;

    public const TIPO_MOV_CAMBIO_PRODUCTO = 'SALIDA_CAMBIO_PRODUCTO';
    public const MOTIVO_MOV_CAMBIO_PRODUCTO = 2;

    public const MOTIVO_EGRESO_DEVOLUCION = 'DEVOLUCION_CLIENTE';

    public string $busqueda = '';
    public array $resultadosVentas = [];

    public ?int $ventaSeleccionadaId = null;

    public array $venta = [
        'numero_factura' => '',
        'cliente' => '',
        'fecha' => '',
        'tipo_venta' => '',
        'total' => 0,
    ];

    public array $detalle = [];

    public string $observacion = '';
    public string $mensajeExito = '';
    public string $mensajeError = '';

    public array $headers = [
        ['key' => 'producto', 'label' => 'Producto'],
        ['key' => 'garantia', 'label' => 'Garantía'],
        ['key' => 'precio', 'label' => 'Precio'],
        ['key' => 'disponible', 'label' => 'Disp.'],
        ['key' => 'aplica', 'label' => 'Aplica'],
        ['key' => 'cantidad', 'label' => 'Cant.'],
        ['key' => 'tipo', 'label' => 'Tipo'],
        ['key' => 'estado', 'label' => 'Estado'],
        ['key' => 'cambio', 'label' => 'Cambio'],
        ['key' => 'motivo', 'label' => 'Motivo'],
        ['key' => 'total', 'label' => 'Total'],
    ];

    public function mount(): void
    {
        $this->limpiarFormulario();
    }

    protected function notificarExito(string $mensaje): void
    {
        $this->mensajeExito = $mensaje;
        $this->mensajeError = '';

        $this->success(
            title: $mensaje,
            position: 'toast-top toast-end',
            timeout: 3500
        );
    }

    protected function notificarError(string $mensaje): void
    {
        $this->mensajeError = $mensaje;
        $this->mensajeExito = '';

        $this->error(
            title: $mensaje,
            position: 'toast-top toast-end',
            timeout: 5000
        );
    }

    public function updatedBusqueda(): void
    {
        $this->mensajeError = '';
        $this->mensajeExito = '';

        $termino = trim($this->busqueda);

        if (mb_strlen($termino) < 2) {
            $this->resultadosVentas = [];
            return;
        }

        $this->resultadosVentas = Venta::query()
            ->with(['cliente.persona'])
            ->where('Estado', Venta::ESTADO_ACTIVA)
            ->where(function ($query) use ($termino) {
                $query->where('Numero_Factura', 'like', "%{$termino}%")
                    ->orWhereHas('cliente', function ($clienteQuery) use ($termino) {
                        $clienteQuery->where('Institucion', 'like', "%{$termino}%")
                            ->orWhereHas('persona', function ($personaQuery) use ($termino) {
                                $personaQuery->where('Primer_Nombre', 'like', "%{$termino}%")
                                    ->orWhere('Segundo_Nombre', 'like', "%{$termino}%")
                                    ->orWhere('Primer_Apellido', 'like', "%{$termino}%")
                                    ->orWhere('Segundo_Apellido', 'like', "%{$termino}%");
                            });
                    });
            })
            ->latest('Fecha_venta')
            ->limit(8)
            ->get()
            ->map(fn (Venta $venta) => [
                'id' => (int) $venta->Id_Venta,
                'factura' => $venta->Numero_Factura,
                'cliente' => $this->nombreCliente($venta->cliente),
                'fecha' => optional($venta->Fecha_venta)->format('d/m/Y'),
                'tipo_venta' => $venta->Tipo_Venta,
                'total' => (float) $venta->Total,
            ])
            ->values()
            ->toArray();
    }

    public function seleccionarVenta(int $ventaId): void
    {
        $this->mensajeError = '';
        $this->mensajeExito = '';

        $venta = Venta::query()
            ->with([
                'cliente.persona',
                'detalles.producto',
                'detalles.productoSerie',
                'detalles.detallesDevolucion.devolucion',
            ])
            ->where('Estado', Venta::ESTADO_ACTIVA)
            ->find($ventaId);

        if (! $venta) {
            $this->notificarError('No se encontró la venta seleccionada.');
            return;
        }

        $this->ventaSeleccionadaId = (int) $venta->Id_Venta;
        $this->busqueda = $venta->Numero_Factura;
        $this->resultadosVentas = [];

        $this->venta = [
            'numero_factura' => $venta->Numero_Factura,
            'cliente' => $this->nombreCliente($venta->cliente),
            'fecha' => optional($venta->Fecha_venta)->format('d/m/Y'),
            'tipo_venta' => $venta->Tipo_Venta,
            'total' => (float) $venta->Total,
        ];

        $this->detalle = $venta->detalles
            ->filter(fn (DetalleVenta $detalle) => $detalle->Tipo_Detalle === DetalleVenta::TIPO_PRODUCTO)
            ->map(fn (DetalleVenta $detalle) => $this->mapearDetalleVenta($detalle, $venta))
            ->values()
            ->map(function (array $linea, int $index) {
                $linea['index'] = $index;
                return $linea;
            })
            ->toArray();

        if (! count($this->detalle)) {
            $this->notificarError('Esta venta no tiene productos disponibles para devolución.');
        }

        $this->recalcularDevolucion();
    }

    public function limpiarFormulario(): void
    {
        $this->busqueda = '';
        $this->resultadosVentas = [];
        $this->ventaSeleccionadaId = null;

        $this->venta = [
            'numero_factura' => '',
            'cliente' => '',
            'fecha' => '',
            'tipo_venta' => '',
            'total' => 0,
        ];

        $this->detalle = [];
        $this->observacion = '';
        $this->mensajeError = '';

        $this->resetErrorBag();
    }

    public function updated($property): void
    {
        if (! str_starts_with($property, 'detalle.')) {
            return;
        }

        $partes = explode('.', $property);
        $index = isset($partes[1]) ? (int) $partes[1] : null;
        $campo = $partes[2] ?? null;

        if ($index === null || ! isset($this->detalle[$index])) {
            return;
        }

        if ($campo === 'aplica') {
            $this->validarSeleccionLinea($index);
        }

        if ($campo === 'tipo_devolucion_linea') {
            if ((bool) ($this->detalle[$index]['forzar_cambio'] ?? false)) {
                $this->detalle[$index]['tipo_devolucion_linea'] = self::TIPO_LINEA_CAMBIO;
            }

            if (($this->detalle[$index]['tipo_devolucion_linea'] ?? self::TIPO_LINEA_MONETARIA) === self::TIPO_LINEA_MONETARIA) {
                $this->limpiarCambioLinea($index);
            } else {
                $this->sugerirProductosCambio($index);
            }
        }

        if ($campo === 'cantidad_devuelve') {
            $this->recalcularDevolucion();

            if ($this->lineaEsCambio($this->detalle[$index]) && empty($this->detalle[$index]['producto_cambio_id'])) {
                $this->sugerirProductosCambio($index);
            }

            return;
        }

        if ($campo === 'estado_producto') {
            $estadoProducto = (int) ($this->detalle[$index]['estado_producto'] ?? self::ESTADO_PRODUCTO_BUENO);

            if ($estadoProducto !== self::ESTADO_PRODUCTO_BUENO) {
                $this->detalle[$index]['reintegra_inventario'] = false;
            }
        }

        if ($campo === 'busqueda_cambio') {
            $this->sugerirProductosCambio($index);
        }

        $this->recalcularDevolucion();
    }

    public function seleccionarProductoCambio(int $index, int $productoId): void
    {
        if (! isset($this->detalle[$index])) {
            return;
        }

        $categoriaOriginalId = $this->detalle[$index]['id_categoria'] ?? null;

        $producto = Producto::query()
            ->where('Estado', true)
            ->where('Stock_Actual', '>', 0)
            ->when($categoriaOriginalId, fn ($query) => $query->where('Id_Categoria', $categoriaOriginalId))
            ->find($productoId);

        if (! $producto) {
            $this->notificarError('El producto de cambio seleccionado no está disponible o no pertenece a la misma categoría.');
            return;
        }

        $tieneSeries = ProductoSerie::query()
            ->where('Id_Producto', $producto->Id_Producto)
            ->exists();

        $seriesDisponibles = ProductoSerie::query()
            ->where('Id_Producto', $producto->Id_Producto)
            ->where('Estado', ProductoSerie::ESTADO_DISPONIBLE)
            ->orderBy('Numero_Serie')
            ->get()
            ->map(fn (ProductoSerie $serie) => [
                'id' => (int) $serie->id_producto_serie,
                'name' => $serie->Numero_Serie,
            ])
            ->values()
            ->toArray();

        if ($tieneSeries && ! count($seriesDisponibles)) {
            $this->notificarError('El producto seleccionado usa serie, pero no tiene series disponibles.');
            return;
        }

        $this->detalle[$index]['tipo_devolucion_linea'] = self::TIPO_LINEA_CAMBIO;
        $this->detalle[$index]['es_cambio_producto'] = true;
        $this->detalle[$index]['producto_cambio_id'] = (int) $producto->Id_Producto;
        $this->detalle[$index]['producto_cambio_nombre'] = trim($producto->Nombre_Producto . ' ' . ($producto->Modelo ?? ''));
        $this->detalle[$index]['producto_cambio_precio'] = (float) $producto->Precio_Venta;
        $this->detalle[$index]['producto_cambio_tiene_series'] = $tieneSeries;
        $this->detalle[$index]['series_cambio'] = $seriesDisponibles;
        $this->detalle[$index]['producto_serie_cambio_id'] = count($seriesDisponibles) ? $seriesDisponibles[0]['id'] : null;
        $this->detalle[$index]['busqueda_cambio'] = $this->detalle[$index]['producto_cambio_nombre'];
        $this->detalle[$index]['resultados_cambio'] = [];

        $this->mensajeError = '';
        $this->recalcularDevolucion();
    }

    public function quitarProductoCambio(int $index): void
    {
        if (! isset($this->detalle[$index])) {
            return;
        }

        $this->limpiarCambioLinea($index);

        if ((bool) ($this->detalle[$index]['forzar_cambio'] ?? false)) {
            $this->detalle[$index]['tipo_devolucion_linea'] = self::TIPO_LINEA_CAMBIO;
            $this->detalle[$index]['es_cambio_producto'] = true;
            $this->sugerirProductosCambio($index);
        }

        $this->recalcularDevolucion();
    }

    public function confirmarDevolucion(): void
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
        $this->resetErrorBag();

        if (! $this->ventaSeleccionadaId) {
            $this->notificarError('Selecciona una venta antes de registrar la devolución.');
            return;
        }

        $itemsSeleccionados = collect($this->detalle)
            ->filter(fn ($item) => (bool) ($item['aplica'] ?? false) && (float) ($item['cantidad_devuelve'] ?? 0) > 0)
            ->values();

        if ($itemsSeleccionados->isEmpty()) {
            $this->notificarError('Selecciona al menos un producto y una cantidad válida.');
            return;
        }

        foreach ($itemsSeleccionados as $item) {
            if (! (bool) ($item['en_garantia'] ?? false)) {
                $this->notificarError('Hay productos seleccionados fuera de garantía.');
                return;
            }

            if ((bool) ($item['forzar_cambio'] ?? false) && ! $this->lineaEsCambio($item)) {
                $this->notificarError('Los productos mayores a C$ 2,000.00 solo pueden procesarse como cambio de producto.');
                return;
            }

            if ($this->lineaEsCambio($item) && empty($item['producto_cambio_id'])) {
                $this->notificarError('Selecciona el producto de cambio para cada línea marcada como cambio.');
                return;
            }

            if (
                $this->lineaEsCambio($item)
                && (bool) ($item['producto_cambio_tiene_series'] ?? false)
                && empty($item['producto_serie_cambio_id'])
            ) {
                $this->notificarError('Selecciona la serie del producto de cambio.');
                return;
            }
        }

        try {
            DB::transaction(function () use ($itemsSeleccionados) {
                $venta = Venta::query()
                    ->where('Estado', Venta::ESTADO_ACTIVA)
                    ->lockForUpdate()
                    ->findOrFail($this->ventaSeleccionadaId);

                $usuarioId = $this->obtenerUsuarioId();

                if (! $usuarioId) {
                    throw new RuntimeException('No se pudo identificar el usuario de la sesión.');
                }

                $hayCambio = $itemsSeleccionados->contains(fn ($item) => $this->lineaEsCambio($item));

                $devolucion = Devolucion::create([
                    'Id_Venta' => $venta->Id_Venta,
                    'Id_Cliente' => $venta->Id_Cliente,
                    'Id_Usuario' => $usuarioId,
                    'Fecha_Devolucion' => now(),
                    'Con_Factura' => true,
                    'Observacion' => trim($this->observacion) ?: null,
                    'Estado_Devolucion' => Devolucion::ESTADO_REGISTRADA,
                    'Tipo_Devolucion' => $hayCambio
                        ? Devolucion::TIPO_CAMBIO_PRODUCTO
                        : Devolucion::TIPO_DEVOLUCION_DINERO,
                    'Total_Devolucion' => 0,
                ]);

                $totalDevolucion = 0;
                $totalEgresoCordoba = 0;
                $descripcionesEgreso = [];

                foreach ($itemsSeleccionados as $item) {
                    $detalleVenta = DetalleVenta::query()
                        ->with(['producto', 'productoSerie'])
                        ->where('Id_Venta', $venta->Id_Venta)
                        ->where('Id_Detalle_Venta', $item['id_detalle_venta'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $cantidadVendida = (float) $detalleVenta->Cantidad;

                    $cantidadYaDevuelta = (float) DetalleDevolucion::query()
                        ->where('Id_Detalle_Venta', $detalleVenta->Id_Detalle_Venta)
                        ->whereHas('devolucion', function ($query) {
                            $query->where('Estado_Devolucion', Devolucion::ESTADO_REGISTRADA);
                        })
                        ->sum('Cantidad');

                    $cantidadDisponible = max(0, $cantidadVendida - $cantidadYaDevuelta);
                    $cantidadDevuelve = min((float) $item['cantidad_devuelve'], $cantidadDisponible);

                    if ($cantidadDevuelve <= 0) {
                        throw new RuntimeException('Uno de los productos ya no tiene cantidad disponible para devolución.');
                    }

                    if (! $this->detalleVentaEnGarantia($detalleVenta, $venta)) {
                        throw new RuntimeException('Uno de los productos ya no cumple garantía para devolución.');
                    }

                    $precioDevolucion = $this->precioUnitarioNeto($detalleVenta);
                    $montoDevuelto = round($cantidadDevuelve * $precioDevolucion, 2);
                    $totalDevolucion += $montoDevuelto;

                    $estadoProducto = (int) ($item['estado_producto'] ?? self::ESTADO_PRODUCTO_BUENO);
                    $reintegraInventario = (bool) ($item['reintegra_inventario'] ?? false);

                    $cambioProcesado = null;

                    if ($this->lineaEsCambio($item)) {
                        $cambioProcesado = $this->procesarSalidaProductoCambio($item, $cantidadDevuelve, $montoDevuelto);

                        if ((float) $cambioProcesado['cliente_recibe'] > 0) {
                            $totalEgresoCordoba += (float) $cambioProcesado['cliente_recibe'];
                            $descripcionesEgreso[] = 'Diferencia por cambio: ' . $cambioProcesado['producto']
                                . ' C$ ' . number_format((float) $cambioProcesado['cliente_recibe'], 2);
                        }
                    } else {
                        $totalEgresoCordoba += $montoDevuelto;
                        $descripcionesEgreso[] = 'Devolución monetaria: '
                            . ($detalleVenta->producto?->Nombre_Producto ?? 'Producto')
                            . ' C$ ' . number_format($montoDevuelto, 2);
                    }

                    $motivo = $this->construirMotivoDevolucion($item, $montoDevuelto, $cambioProcesado);

                    DetalleDevolucion::create([
                        'Id_Devolucion' => $devolucion->Id_Devolucion,
                        'Id_Detalle_Venta' => $detalleVenta->Id_Detalle_Venta,
                        'Cantidad' => $cantidadDevuelve,
                        'Monto_Devuelto' => $montoDevuelto,
                        'Motivo_Devolucion' => $motivo,
                        'Estado_Producto_Devolucion' => $estadoProducto,
                        'Reintegra_Inventario' => $reintegraInventario,
                    ]);

                    $this->procesarProductoDevuelto(
                        detalleVenta: $detalleVenta,
                        cantidadDevuelve: $cantidadDevuelve,
                        estadoProducto: $estadoProducto,
                        reintegraInventario: $reintegraInventario,
                    );

                    $this->marcarDetalleVentaComoDevuelto(
                        detalleVenta: $detalleVenta,
                        devolucion: $devolucion,
                        cantidadDevuelve: $cantidadDevuelve,
                        montoDevuelto: $montoDevuelto,
                        cambioProcesado: $cambioProcesado,
                    );
                }

                $devolucion->update([
                    'Total_Devolucion' => $totalDevolucion,
                ]);

                $this->registrarEgresoSiAplica(
                    venta: $venta,
                    devolucion: $devolucion,
                    usuarioId: $usuarioId,
                    montoCordoba: $totalEgresoCordoba,
                    descripciones: $descripcionesEgreso,
                );
            });

            $this->limpiarFormulario();
            $this->notificarExito('Devolución registrada correctamente.');
        } catch (Throwable $exception) {
            report($exception);

            $this->notificarError(
                $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'No se pudo registrar la devolución. Revisa los datos e inténtalo nuevamente.'
            );
        }
    }

    protected function mapearDetalleVenta(DetalleVenta $detalle, Venta $venta): array
    {
        $producto = $detalle->producto;
        $serie = $detalle->productoSerie;

        $cantidadVendida = (float) $detalle->Cantidad;

        $cantidadYaDevuelta = (float) $detalle->detallesDevolucion
            ->filter(function ($detalleDevolucion) {
                $devolucion = $detalleDevolucion->devolucion;

                if (! $devolucion) {
                    return true;
                }

                return (int) ($devolucion->Estado_Devolucion ?? Devolucion::ESTADO_REGISTRADA) === Devolucion::ESTADO_REGISTRADA;
            })
            ->sum('Cantidad');

        $cantidadDisponible = max(0, $cantidadVendida - $cantidadYaDevuelta);
        $precioDevolucion = $this->precioUnitarioNeto($detalle);
        $garantia = $this->calcularGarantia($detalle, $venta);

        $forzarCambio = $precioDevolucion > self::MONTO_LIMITE_DEVOLUCION_MONETARIA;
        $puedeDevolver = $cantidadDisponible > 0 && $garantia['en_garantia'];

        $bloqueo = '';

        if ($cantidadDisponible <= 0) {
            $bloqueo = 'Ya fue devuelto completamente.';
        } elseif (! $garantia['en_garantia']) {
            $bloqueo = 'Fuera de garantía.';
        }

        $linea = [
            'index' => 0,
            'id_detalle_venta' => (int) $detalle->Id_Detalle_Venta,
            'id_producto' => $producto?->Id_Producto,
            'id_categoria' => $producto?->Id_Categoria,
            'id_producto_serie' => $serie?->id_producto_serie,

            'producto' => $producto?->Nombre_Producto ?? 'Producto no disponible',
            'modelo' => $producto?->Modelo ?? '',
            'serie' => $serie?->Numero_Serie ?? 'Sin serie',

            'precio' => $precioDevolucion,
            'cantidad_vendida' => $cantidadVendida,
            'cantidad_ya_devuelta' => $cantidadYaDevuelta,
            'cantidad_disponible' => $cantidadDisponible,
            'cantidad_devuelve' => 0,
            'monto_devuelve' => 0,

            'aplica' => false,
            'motivo' => '',
            'estado_producto' => self::ESTADO_PRODUCTO_BUENO,
            'reintegra_inventario' => true,

            'garantia_base' => $garantia['fecha_base_texto'],
            'garantia_hasta' => $garantia['vence_texto'],
            'garantia_meses' => $garantia['meses'],
            'en_garantia' => $garantia['en_garantia'],

            'puede_devolver' => $puedeDevolver,
            'bloqueo' => $bloqueo,

            'forzar_cambio' => $forzarCambio,
            'tipo_devolucion_linea' => $forzarCambio ? self::TIPO_LINEA_CAMBIO : self::TIPO_LINEA_MONETARIA,
            'es_cambio_producto' => $forzarCambio,

            'busqueda_cambio' => '',
            'resultados_cambio' => [],
            'producto_cambio_id' => null,
            'producto_cambio_nombre' => '',
            'producto_cambio_precio' => 0,
            'producto_cambio_tiene_series' => false,
            'producto_serie_cambio_id' => null,
            'series_cambio' => [],

            'monto_cambio' => 0,
            'diferencia_cambio' => 0,
            'cliente_paga' => 0,
            'cliente_recibe' => 0,
        ];

        if ($forzarCambio && $producto) {
            $linea = $this->autoseleccionarCambioMismoProducto($linea, $producto);
        }

        return $linea;
    }

    protected function autoseleccionarCambioMismoProducto(array $linea, Producto $producto): array
    {
        if ((int) $producto->Stock_Actual <= 0) {
            return $linea;
        }

        $tieneSeries = ProductoSerie::query()
            ->where('Id_Producto', $producto->Id_Producto)
            ->exists();

        if ($tieneSeries) {
            $series = ProductoSerie::query()
                ->where('Id_Producto', $producto->Id_Producto)
                ->where('Estado', ProductoSerie::ESTADO_DISPONIBLE)
                ->orderBy('Numero_Serie')
                ->get()
                ->map(fn (ProductoSerie $serie) => [
                    'id' => (int) $serie->id_producto_serie,
                    'name' => $serie->Numero_Serie,
                ])
                ->values()
                ->toArray();

            if (! count($series)) {
                return $linea;
            }

            $linea['series_cambio'] = $series;
            $linea['producto_serie_cambio_id'] = $series[0]['id'];
        }

        $linea['tipo_devolucion_linea'] = self::TIPO_LINEA_CAMBIO;
        $linea['es_cambio_producto'] = true;
        $linea['producto_cambio_id'] = (int) $producto->Id_Producto;
        $linea['producto_cambio_nombre'] = trim($producto->Nombre_Producto . ' ' . ($producto->Modelo ?? ''));
        $linea['producto_cambio_precio'] = (float) $producto->Precio_Venta;
        $linea['producto_cambio_tiene_series'] = $tieneSeries;
        $linea['busqueda_cambio'] = $linea['producto_cambio_nombre'];

        return $linea;
    }

    protected function sugerirProductosCambio(int $index): void
    {
        if (! isset($this->detalle[$index])) {
            return;
        }

        if (! $this->lineaEsCambio($this->detalle[$index])) {
            $this->detalle[$index]['resultados_cambio'] = [];
            return;
        }

        $termino = trim((string) ($this->detalle[$index]['busqueda_cambio'] ?? ''));
        $categoriaOriginalId = $this->detalle[$index]['id_categoria'] ?? null;
        $precioReferencia = (float) ($this->detalle[$index]['precio'] ?? 0);

        if (! $categoriaOriginalId) {
            $this->detalle[$index]['resultados_cambio'] = [];
            $this->notificarError('El producto original no tiene categoría asignada para sugerir cambios.');
            return;
        }

        $this->detalle[$index]['resultados_cambio'] = Producto::query()
            ->where('Estado', true)
            ->where('Stock_Actual', '>', 0)
            ->where('Id_Categoria', $categoriaOriginalId)
            ->where(function ($query) {
                $query->doesntHave('series')
                    ->orWhereHas('series', function ($serieQuery) {
                        $serieQuery->where('Estado', ProductoSerie::ESTADO_DISPONIBLE);
                    });
            })
            ->when(mb_strlen($termino) >= 2, function ($query) use ($termino) {
                $query->where(function ($subQuery) use ($termino) {
                    $subQuery->where('Nombre_Producto', 'like', "%{$termino}%")
                        ->orWhere('Modelo', 'like', "%{$termino}%");
                });
            })
            ->orderByRaw('ABS(Precio_Venta - ?) ASC', [$precioReferencia])
            ->orderBy('Nombre_Producto')
            ->limit(8)
            ->get()
            ->map(function (Producto $producto) use ($precioReferencia) {
                $diferencia = round((float) $producto->Precio_Venta - $precioReferencia, 2);

                return [
                    'id' => (int) $producto->Id_Producto,
                    'nombre' => trim($producto->Nombre_Producto . ' ' . ($producto->Modelo ?? '')),
                    'stock' => (int) $producto->Stock_Actual,
                    'precio' => (float) $producto->Precio_Venta,
                    'diferencia' => $diferencia,
                    'distancia' => abs($diferencia),
                    'texto_diferencia' => $diferencia > 0
                        ? 'Cliente paga C$ ' . number_format($diferencia, 2)
                        : ($diferencia < 0
                            ? 'Cliente recibe C$ ' . number_format(abs($diferencia), 2)
                            : 'Sin diferencia'),
                ];
            })
            ->values()
            ->toArray();
    }

    protected function validarSeleccionLinea(int $index): void
    {
        if (! (bool) ($this->detalle[$index]['aplica'] ?? false)) {
            $this->detalle[$index]['cantidad_devuelve'] = 0;
            return;
        }

        if (! (bool) ($this->detalle[$index]['puede_devolver'] ?? false)) {
            $this->detalle[$index]['aplica'] = false;
            $this->detalle[$index]['cantidad_devuelve'] = 0;
            $this->notificarError($this->detalle[$index]['bloqueo'] ?? 'El producto no puede devolverse.');
            return;
        }

        $this->detalle[$index]['cantidad_devuelve'] = max(1, (float) ($this->detalle[$index]['cantidad_devuelve'] ?? 1));

        if ((bool) ($this->detalle[$index]['forzar_cambio'] ?? false)) {
            $this->detalle[$index]['tipo_devolucion_linea'] = self::TIPO_LINEA_CAMBIO;
            $this->detalle[$index]['es_cambio_producto'] = true;
        }

        if ($this->lineaEsCambio($this->detalle[$index])) {
            $this->sugerirProductosCambio($index);
        }
    }

    protected function recalcularDevolucion(): void
    {
        foreach ($this->detalle as $index => $item) {
            $aplica = (bool) ($item['aplica'] ?? false);
            $puedeDevolver = (bool) ($item['puede_devolver'] ?? false);
            $forzarCambio = (bool) ($item['forzar_cambio'] ?? false);

            if ($forzarCambio) {
                $this->detalle[$index]['tipo_devolucion_linea'] = self::TIPO_LINEA_CAMBIO;
            }

            $esCambio = $forzarCambio
                || (($this->detalle[$index]['tipo_devolucion_linea'] ?? self::TIPO_LINEA_MONETARIA) === self::TIPO_LINEA_CAMBIO);

            $cantidadDisponible = (float) ($item['cantidad_disponible'] ?? 0);
            $cantidadDevuelve = (float) ($item['cantidad_devuelve'] ?? 0);
            $precio = (float) ($item['precio'] ?? 0);

            if (! $aplica || ! $puedeDevolver) {
                $cantidadDevuelve = 0;
            }

            $cantidadDevuelve = max(0, min($cantidadDisponible, $cantidadDevuelve));
            $montoDevuelve = $aplica ? round($cantidadDevuelve * $precio, 2) : 0;

            $precioCambio = (float) ($item['producto_cambio_precio'] ?? 0);
            $montoCambio = $aplica && $esCambio && ! empty($item['producto_cambio_id'])
                ? round($cantidadDevuelve * $precioCambio, 2)
                : 0;

            $diferenciaCambio = round($montoCambio - $montoDevuelve, 2);

            $this->detalle[$index]['index'] = $index;
            $this->detalle[$index]['es_cambio_producto'] = $esCambio;
            $this->detalle[$index]['cantidad_devuelve'] = $cantidadDevuelve;
            $this->detalle[$index]['monto_devuelve'] = $montoDevuelve;
            $this->detalle[$index]['monto_cambio'] = $montoCambio;
            $this->detalle[$index]['diferencia_cambio'] = $diferenciaCambio;
            $this->detalle[$index]['cliente_paga'] = max(0, $diferenciaCambio);
            $this->detalle[$index]['cliente_recibe'] = max(0, abs(min(0, $diferenciaCambio)));
        }
    }

    protected function limpiarCambioLinea(int $index): void
    {
        $this->detalle[$index]['es_cambio_producto'] = false;
        $this->detalle[$index]['producto_cambio_id'] = null;
        $this->detalle[$index]['producto_cambio_nombre'] = '';
        $this->detalle[$index]['producto_cambio_precio'] = 0;
        $this->detalle[$index]['producto_cambio_tiene_series'] = false;
        $this->detalle[$index]['producto_serie_cambio_id'] = null;
        $this->detalle[$index]['series_cambio'] = [];
        $this->detalle[$index]['busqueda_cambio'] = '';
        $this->detalle[$index]['resultados_cambio'] = [];
        $this->detalle[$index]['monto_cambio'] = 0;
        $this->detalle[$index]['diferencia_cambio'] = 0;
        $this->detalle[$index]['cliente_paga'] = 0;
        $this->detalle[$index]['cliente_recibe'] = 0;
    }

    protected function lineaEsCambio(array $item): bool
    {
        return (bool) ($item['forzar_cambio'] ?? false)
            || (($item['tipo_devolucion_linea'] ?? self::TIPO_LINEA_MONETARIA) === self::TIPO_LINEA_CAMBIO);
    }

    protected function calcularGarantia(DetalleVenta $detalle, Venta $venta): array
    {
        $producto = $detalle->producto;
        $serie = $detalle->productoSerie;

        $mesesGarantia = (int) ($producto?->Meses_Garantia_Nuevo ?? 0);

        $fechaBase = $serie?->Fecha_Ingreso
            ? Carbon::parse($serie->Fecha_Ingreso)
            : ($venta->Fecha_venta ? Carbon::parse($venta->Fecha_venta) : null);

        $vence = $fechaBase && $mesesGarantia > 0
            ? $fechaBase->copy()->addMonthsNoOverflow($mesesGarantia)
            : null;

        $enGarantia = $vence
            ? now()->startOfDay()->lte($vence->copy()->endOfDay())
            : false;

        return [
            'meses' => $mesesGarantia,
            'fecha_base_texto' => $fechaBase ? $fechaBase->format('d/m/Y') : 'Sin fecha',
            'vence_texto' => $vence ? $vence->format('d/m/Y') : 'Sin garantía',
            'en_garantia' => $enGarantia,
        ];
    }

    protected function detalleVentaEnGarantia(DetalleVenta $detalle, Venta $venta): bool
    {
        return (bool) $this->calcularGarantia($detalle, $venta)['en_garantia'];
    }

    protected function precioUnitarioNeto(DetalleVenta $detalle): float
    {
        $cantidad = (float) $detalle->Cantidad;

        if ($cantidad <= 0) {
            return 0;
        }

        $subtotal = (float) $detalle->Subtotal;
        $descuento = (float) ($detalle->Descuento ?? 0);

        return round(max(0, $subtotal - $descuento) / $cantidad, 2);
    }

    protected function procesarProductoDevuelto(
        DetalleVenta $detalleVenta,
        float $cantidadDevuelve,
        int $estadoProducto,
        bool $reintegraInventario
    ): void {
        $producto = $detalleVenta->producto;

        if (! $producto) {
            return;
        }

        if ($detalleVenta->productoSerie) {
            $nuevoEstadoSerie = match ($estadoProducto) {
                self::ESTADO_PRODUCTO_BUENO => $reintegraInventario
                    ? ProductoSerie::ESTADO_DISPONIBLE
                    : ProductoSerie::ESTADO_RESERVADO,
                self::ESTADO_PRODUCTO_DANADO => ProductoSerie::ESTADO_DANADO,
                self::ESTADO_PRODUCTO_REVISION,
                self::ESTADO_PRODUCTO_GARANTIA => ProductoSerie::ESTADO_RESERVADO,
                default => ProductoSerie::ESTADO_RESERVADO,
            };

            $detalleVenta->productoSerie->update([
                'Estado' => $nuevoEstadoSerie,
            ]);
        }

        if ($reintegraInventario) {
            $producto->increment('Stock_Actual', (int) $cantidadDevuelve);
        }
    }

    protected function procesarSalidaProductoCambio(array $item, float $cantidadCambio, float $montoDevuelto): array
    {
        $productoCambio = Producto::query()
            ->where('Estado', true)
            ->where('Stock_Actual', '>=', (int) $cantidadCambio)
            ->lockForUpdate()
            ->find((int) $item['producto_cambio_id']);

        if (! $productoCambio) {
            throw new RuntimeException('El producto de cambio no tiene stock suficiente.');
        }

        $serieCambio = null;

        if ((bool) ($item['producto_cambio_tiene_series'] ?? false)) {
            if ((float) $cantidadCambio !== 1.0) {
                throw new RuntimeException('Los productos con serie deben cambiarse de uno en uno.');
            }

            $serieCambio = ProductoSerie::query()
                ->where('Id_Producto', $productoCambio->Id_Producto)
                ->where('id_producto_serie', (int) $item['producto_serie_cambio_id'])
                ->where('Estado', ProductoSerie::ESTADO_DISPONIBLE)
                ->lockForUpdate()
                ->first();

            if (! $serieCambio) {
                throw new RuntimeException('La serie seleccionada para cambio ya no está disponible.');
            }

            $serieCambio->update([
                'Estado' => ProductoSerie::ESTADO_VENDIDO,
            ]);
        }

        $productoCambio->decrement('Stock_Actual', (int) $cantidadCambio);

        MovimientoInventario::create([
            'Id_Producto' => $productoCambio->Id_Producto,
            'Id_Producto_Serie' => $serieCambio?->id_producto_serie,
            'Fecha_Movimiento' => now(),
            'Tipo_Movimiento' => self::TIPO_MOV_CAMBIO_PRODUCTO,
            'Cantidad' => (int) $cantidadCambio,
            'Motivo_Movimiento' => self::MOTIVO_MOV_CAMBIO_PRODUCTO,
        ]);

        $montoCambio = round((float) $productoCambio->Precio_Venta * $cantidadCambio, 2);
        $diferencia = round($montoCambio - $montoDevuelto, 2);

        return [
            'producto_id' => (int) $productoCambio->Id_Producto,
            'producto' => trim($productoCambio->Nombre_Producto . ' ' . ($productoCambio->Modelo ?? '')),
            'serie_id' => $serieCambio?->id_producto_serie,
            'serie' => $serieCambio?->Numero_Serie,
            'cantidad' => $cantidadCambio,
            'precio' => (float) $productoCambio->Precio_Venta,
            'monto_cambio' => $montoCambio,
            'diferencia' => $diferencia,
            'cliente_paga' => max(0, $diferencia),
            'cliente_recibe' => max(0, abs(min(0, $diferencia))),
        ];
    }

    protected function registrarEgresoSiAplica(
        Venta $venta,
        Devolucion $devolucion,
        int $usuarioId,
        float $montoCordoba,
        array $descripciones
    ): void {
        $montoCordoba = round($montoCordoba, 2);

        if ($montoCordoba <= 0) {
            return;
        }

        $aperturaCaja = AperturaCaja::query()
            ->where('Id_Usuario', $usuarioId)
            ->where('Estado_Apertura', 1)
            ->whereDate('Fecha_Apertura', now()->toDateString())
            ->latest('Fecha_Apertura')
            ->lockForUpdate()
            ->first();

        if (! $aperturaCaja) {
            throw new RuntimeException('No hay una apertura de caja abierta para registrar el egreso de la devolución.');
        }

        Egresos::create([
            'Id_Apertura_Caja' => $aperturaCaja->Id_Apertura_Caja,
            'Id_Usuario' => $usuarioId,
            'Monto_Egresado_Cordoba' => $montoCordoba,
            'Monto_Egresado_Dolar' => 0,
            'Motivo_Egreso' => self::MOTIVO_EGRESO_DEVOLUCION,
            'Descripcion_Egreso' => Str::limit(
                'DEV-' . $devolucion->Id_Devolucion
                . ' | Factura: ' . $venta->Numero_Factura
                . ' | ' . implode(' / ', $descripciones),
                255,
                ''
            ),
            'Fecha_Egreso' => now(),
        ]);
    }

    protected function construirMotivoDevolucion(array $item, float $montoDevuelto, ?array $cambioProcesado): string
    {
        $partes = [];

        $motivo = trim((string) ($item['motivo'] ?? ''));

        if ($motivo !== '') {
            $partes[] = $motivo;
        }

        $partes[] = 'Monto ref.: C$ ' . number_format($montoDevuelto, 2);

        if ($cambioProcesado) {
            $partes[] = 'Cambio: ' . $cambioProcesado['producto'];

            if (! empty($cambioProcesado['serie'])) {
                $partes[] = 'Serie cambio: ' . $cambioProcesado['serie'];
            }

            $partes[] = 'Monto cambio: C$ ' . number_format((float) $cambioProcesado['monto_cambio'], 2);

            if ((float) $cambioProcesado['cliente_paga'] > 0) {
                $partes[] = 'Cliente paga: C$ ' . number_format((float) $cambioProcesado['cliente_paga'], 2);
            }

            if ((float) $cambioProcesado['cliente_recibe'] > 0) {
                $partes[] = 'Cliente recibe: C$ ' . number_format((float) $cambioProcesado['cliente_recibe'], 2);
            }

            if ((float) $cambioProcesado['cliente_paga'] === 0.0 && (float) $cambioProcesado['cliente_recibe'] === 0.0) {
                $partes[] = 'Sin diferencia.';
            }
        }

        return Str::limit(implode(' | ', $partes), 200, '');
    }

    protected function marcarDetalleVentaComoDevuelto(
        DetalleVenta $detalleVenta,
        Devolucion $devolucion,
        float $cantidadDevuelve,
        float $montoDevuelto,
        ?array $cambioProcesado
    ): void {
        $nota = 'DEV-' . $devolucion->Id_Devolucion
            . ': afectado por devolución. Cantidad: '
            . number_format($cantidadDevuelve, 2)
            . '. Monto ref.: C$ '
            . number_format($montoDevuelto, 2);

        if ($cambioProcesado) {
            $nota .= '. Cambio: ' . $cambioProcesado['producto'];

            if (! empty($cambioProcesado['serie'])) {
                $nota .= ' / Serie: ' . $cambioProcesado['serie'];
            }

            if ((float) $cambioProcesado['cliente_paga'] > 0) {
                $nota .= '. Cliente paga C$ ' . number_format((float) $cambioProcesado['cliente_paga'], 2);
            }

            if ((float) $cambioProcesado['cliente_recibe'] > 0) {
                $nota .= '. Cliente recibe C$ ' . number_format((float) $cambioProcesado['cliente_recibe'], 2);
            }
        }

        $observacionActual = trim((string) ($detalleVenta->Observacion ?? ''));
        $nuevaObservacion = trim($observacionActual . ($observacionActual ? ' | ' : '') . $nota);

        $detalleVenta->update([
            'Observacion' => Str::limit($nuevaObservacion, 250, ''),
        ]);
    }

    protected function obtenerUsuarioId(): ?int
    {
        $usuarioAutenticado = auth()->user();

        if ($usuarioAutenticado && isset($usuarioAutenticado->Id_Usuario)) {
            return (int) $usuarioAutenticado->Id_Usuario;
        }

        if (auth()->id()) {
            return (int) auth()->id();
        }

        return Usuario::query()
            ->orderBy('Id_Usuario')
            ->value('Id_Usuario');
    }

    protected function nombreCliente($cliente): string
    {
        if (! $cliente) {
            return 'Cliente no disponible';
        }

        if (! empty($cliente->Institucion)) {
            return $cliente->Institucion;
        }

        $persona = $cliente->persona ?? null;

        if (! $persona) {
            return 'Cliente sin nombre';
        }

        return collect([
            $persona->Primer_Nombre ?? null,
            $persona->Segundo_Nombre ?? null,
            $persona->Primer_Apellido ?? null,
            $persona->Segundo_Apellido ?? null,
        ])
            ->filter()
            ->implode(' ') ?: 'Cliente sin nombre';
    }

    public function obtenerTotalDevolucion(): float
    {
        return collect($this->detalle)->sum(fn ($item) => (float) ($item['monto_devuelve'] ?? 0));
    }

    public function obtenerCantidadTotalDevuelta(): float
    {
        return collect($this->detalle)->sum(fn ($item) => (float) ($item['cantidad_devuelve'] ?? 0));
    }

    public function obtenerClientePagaTotal(): float
    {
        return collect($this->detalle)->sum(fn ($item) => (float) ($item['cliente_paga'] ?? 0));
    }

    public function obtenerClienteRecibeTotal(): float
    {
        return collect($this->detalle)->sum(fn ($item) => (float) ($item['cliente_recibe'] ?? 0));
    }

    public function obtenerEgresoEstimadoTotal(): float
    {
        return collect($this->detalle)
            ->filter(fn ($item) => (bool) ($item['aplica'] ?? false))
            ->sum(function ($item) {
                if ($this->lineaEsCambio($item)) {
                    return (float) ($item['cliente_recibe'] ?? 0);
                }

                return (float) ($item['monto_devuelve'] ?? 0);
            });
    }

    public function haySeleccion(): bool
    {
        return collect($this->detalle)
            ->contains(fn ($item) => (bool) ($item['aplica'] ?? false) && (float) ($item['cantidad_devuelve'] ?? 0) > 0);
    }

    public function tipoDevolucionTexto(): string
    {
        $seleccionados = collect($this->detalle)
            ->filter(fn ($item) => (bool) ($item['aplica'] ?? false) && (float) ($item['cantidad_devuelve'] ?? 0) > 0);

        if ($seleccionados->isEmpty()) {
            return 'Sin selección';
        }

        $hayCambio = $seleccionados->contains(fn ($item) => $this->lineaEsCambio($item));
        $hayMonetaria = $seleccionados->contains(fn ($item) => ! $this->lineaEsCambio($item));

        if ($hayCambio && $hayMonetaria) {
            return 'Mixta';
        }

        return $hayCambio ? 'Cambio' : 'Monetaria';
    }
};
?>

<div class="min-h-screen w-full max-w-full overflow-x-hidden bg-[#F0F3F7] px-3 py-4 md:px-5">
    <div class="mx-auto flex w-full max-w-[1180px] min-w-0 flex-col gap-4 overflow-x-hidden">
        <x-toast />

        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#1A2B42] md:text-3xl">Proceso de devoluciones</h1>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    Busca una venta, valida garantía y registra devolución monetaria o cambio de producto.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-2 lg:w-[520px]">
                <x-card shadow class="border border-[#D7E4F3] !bg-white">
                    <p class="text-[11px] font-bold uppercase text-[#5F6B7A]">Tipo</p>
                    <p class="text-base font-bold text-[#1A2B42]">{{ $this->tipoDevolucionTexto() }}</p>
                </x-card>

                <x-card shadow class="border border-[#D7E4F3] !bg-white">
                    <p class="text-[11px] font-bold uppercase text-[#5F6B7A]">Referencia</p>
                    <p class="text-lg font-bold text-[#2E8BC0]">
                        C$ {{ number_format($this->obtenerTotalDevolucion(), 2) }}
                    </p>
                </x-card>

                <x-card shadow class="border border-[#D7E4F3] !bg-white">
                    <p class="text-[11px] font-bold uppercase text-[#5F6B7A]">Egreso</p>
                    <p class="text-lg font-bold text-red-600">
                        C$ {{ number_format($this->obtenerEgresoEstimadoTotal(), 2) }}
                    </p>
                </x-card>
            </div>
        </div>

        <x-card
            title="Buscar venta"
            subtitle="Selecciona la factura correcta para cargar los productos vendidos."
            shadow
            separator
            class="border border-[#D7E4F3] !bg-white text-[#1A2B42] shadow-sm
            [&_.card-title]:text-[#1A2B42]
            [&_.text-base-content\/70]:text-[#5F6B7A]
            [&_label]:text-[#1A2B42]
            [&_.fieldset-legend]:text-[#1A2B42]"
        >
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="relative lg:col-span-4">
                    <x-input
                        label="Factura o cliente"
                        wire:model.live.debounce.350ms="busqueda"
                        placeholder="Ej. FAC-000245 o María López"
                        icon="o-magnifying-glass"
                        class="h-10 rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#5F6B7A]"
                    />

                    @if (count($resultadosVentas))
                        <x-card shadow class="absolute z-30 mt-2 max-h-72 w-full overflow-auto border border-[#D7E4F3] !bg-white p-2">
                            <div class="flex flex-col gap-2">
                                @foreach ($resultadosVentas as $resultado)
                                    <x-button
                                        type="button"
                                        wire:click="seleccionarVenta({{ $resultado['id'] }})"
                                        class="!h-auto !justify-start !border !border-[#E6EEF8] !bg-white !px-3 !py-2 !text-left !text-[#1A2B42] hover:!bg-[#F8FBFF] hover:!text-[#1A2B42]"
                                    >
                                        <div class="flex w-full flex-col gap-1">
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="font-bold text-[#1A2B42]">{{ $resultado['factura'] }}</span>
                                                <span class="rounded-full bg-[#EAF5FB] px-2.5 py-1 text-xs font-bold text-[#2E8BC0]">
                                                    {{ $resultado['tipo_venta'] }}
                                                </span>
                                            </div>

                                            <span class="text-sm text-[#5F6B7A]">{{ $resultado['cliente'] }}</span>

                                            <span class="text-xs text-[#5F6B7A]">
                                                {{ $resultado['fecha'] }} · C$ {{ number_format((float) $resultado['total'], 2) }}
                                            </span>
                                        </div>
                                    </x-button>
                                @endforeach
                            </div>
                        </x-card>
                    @endif
                </div>

                <div class="lg:col-span-2">
                    <x-input label="Factura" wire:model="venta.numero_factura" readonly class="h-10 rounded-xl border-[#D7E4F3] bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                </div>

                <div class="lg:col-span-3">
                    <x-input label="Cliente" wire:model="venta.cliente" readonly class="h-10 rounded-xl border-[#D7E4F3] bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                </div>

                <div class="lg:col-span-1">
                    <x-input label="Fecha" wire:model="venta.fecha" readonly class="h-10 rounded-xl border-[#D7E4F3] bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                </div>

                <div class="lg:col-span-1">
                    <x-input label="Tipo" wire:model="venta.tipo_venta" readonly class="h-10 rounded-xl border-[#D7E4F3] bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                </div>

                <div class="lg:col-span-1">
                    <x-input
                        label="Total"
                        value="C$ {{ number_format((float) ($venta['total'] ?? 0), 2) }}"
                        readonly
                        class="h-10 rounded-xl border-[#D7E4F3] bg-[#F7F9FC] text-sm text-[#1A2B42]"
                    />
                </div>

                <div class="lg:col-span-12">
                    <x-input
                        label="Observación general"
                        wire:model.blur="observacion"
                        placeholder="Opcional. Ej. Producto presenta falla al encender."
                        class="h-10 rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#5F6B7A]"
                    />
                </div>
            </div>
        </x-card>

        <x-form wire:submit="confirmarDevolucion" no-separator>
            <div class="flex flex-col gap-3 overflow-hidden">
                <x-card
                    title="Productos de la venta"
                    subtitle="Hasta C$ 2,000.00 permite monetaria o cambio. Mayor a C$ 2,000.00 solo cambio."
                    shadow
                    separator
                    class="w-full max-w-full overflow-hidden border border-[#D7E4F3] !bg-white text-[#1A2B42] shadow-sm
                    [&_.card-body]:!overflow-hidden
                    [&_.card-body]:!p-3
                    [&_.card-title]:text-[#1A2B42]
                    [&_.text-base-content\/70]:text-[#5F6B7A]
                    [&_label]:text-[#1A2B42]
                    [&_.fieldset-legend]:text-[#1A2B42]"
                >
                    <div
                        class="w-full max-w-full min-w-0 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white
                        [&_.overflow-x-auto]:!overflow-visible"
                    >
                        <div
                            class="max-h-[430px] w-full max-w-full min-w-0"
                            style="overflow-x: auto; overflow-y: auto;"
                        >
                            <div class="w-[1180px] max-w-none">
                                <x-table
                                    :headers="$headers"
                                    :rows="$detalle"
                                    class="w-full table-xs
                                    [&_thead]:sticky [&_thead]:top-0 [&_thead]:z-20
                                    [&_thead_th]:!bg-[#2E8BC0] [&_thead_th]:!px-2 [&_thead_th]:!py-2
                                    [&_thead_th]:!text-[10px] [&_thead_th]:!font-bold [&_thead_th]:!uppercase [&_thead_th]:!text-white
                                    [&_tbody_tr]:!bg-white
                                    [&_tbody_tr:hover]:!bg-[#F8FBFF]
                                    [&_tbody_tr:hover>td]:!bg-[#F8FBFF]
                                    [&_tbody_tr:hover>td]:!text-[#1A2B42]
                                    [&_td]:!px-2 [&_td]:!py-2
                                    [&_td]:!align-top [&_td]:!text-xs [&_td]:!text-[#1A2B42]"
                                >
                                    @scope('cell_producto', $item)
                                        <div class="w-[170px]">
                                            <div class="truncate text-xs font-bold text-[#1A2B42]" title="{{ data_get($item, 'producto') }}">
                                                {{ data_get($item, 'producto') }}
                                            </div>

                                            <div class="truncate text-[11px] text-[#5F6B7A]">
                                                {{ data_get($item, 'modelo') ?: 'Sin modelo' }} · {{ data_get($item, 'serie') }}
                                            </div>

                                            @if (data_get($item, 'forzar_cambio'))
                                                <x-badge value="Solo cambio" class="mt-1 !bg-[#EAF5FB] !text-[#2E8BC0]" />
                                            @else
                                                <x-badge value="Flexible" class="mt-1 !bg-green-50 !text-green-700" />
                                            @endif

                                            @if (! data_get($item, 'puede_devolver'))
                                                <div class="mt-1 text-[11px] font-semibold text-red-600">
                                                    {{ data_get($item, 'bloqueo') }}
                                                </div>
                                            @endif
                                        </div>
                                    @endscope

                                    @scope('cell_garantia', $item)
                                        <div class="w-[100px]">
                                            @if (data_get($item, 'en_garantia'))
                                                <x-badge value="Vigente" class="!bg-green-50 !text-green-700" />
                                            @else
                                                <x-badge value="Vencida" class="!bg-red-50 !text-red-700" />
                                            @endif

                                            <div class="mt-1 text-[10px] leading-tight text-[#5F6B7A]">
                                                Base: {{ data_get($item, 'garantia_base') }}
                                            </div>
                                            <div class="text-[10px] leading-tight text-[#5F6B7A]">
                                                Hasta: {{ data_get($item, 'garantia_hasta') }}
                                            </div>
                                        </div>
                                    @endscope

                                    @scope('cell_precio', $item)
                                        <div class="w-[75px] text-right text-xs font-bold text-[#1A2B42]">
                                            C$ {{ number_format((float) data_get($item, 'precio'), 2) }}
                                        </div>
                                    @endscope

                                    @scope('cell_disponible', $item)
                                        <div class="w-[45px] text-center text-xs font-semibold text-[#1A2B42]">
                                            {{ number_format((float) data_get($item, 'cantidad_disponible'), 2) }}
                                        </div>
                                    @endscope

                                    @scope('cell_aplica', $item)
                                        @php($i = data_get($item, 'index'))

                                        <div class="flex w-[45px] justify-center">
                                            <x-checkbox
                                                wire:model.live="detalle.{{ $i }}.aplica"
                                                :disabled="! data_get($item, 'puede_devolver')"
                                                class="checkbox-sm border-[#2E8BC0] checked:border-[#2E8BC0] checked:bg-[#2E8BC0]"
                                            />
                                        </div>
                                    @endscope

                                    @scope('cell_cantidad', $item)
                                        @php($i = data_get($item, 'index'))

                                        <div class="w-[55px]">
                                            <x-input
                                                type="number"
                                                step="1"
                                                min="0"
                                                max="{{ data_get($item, 'cantidad_disponible') }}"
                                                wire:model.live="detalle.{{ $i }}.cantidad_devuelve"
                                                :disabled="! data_get($item, 'aplica') || ! data_get($item, 'puede_devolver')"
                                                class="!h-8 !min-h-8 rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-center text-xs text-[#1A2B42]"
                                            />
                                        </div>
                                    @endscope

                                    @scope('cell_tipo', $item)
                                        @php($i = data_get($item, 'index'))

                                        <div class="w-[105px]">
                                            <x-select
                                                wire:model.live="detalle.{{ $i }}.tipo_devolucion_linea"
                                                :options="[
                                                    ['id' => 'MONETARIA', 'name' => 'Monetaria'],
                                                    ['id' => 'CAMBIO', 'name' => 'Cambio'],
                                                ]"
                                                option-value="id"
                                                option-label="name"
                                                :disabled="! data_get($item, 'aplica') || data_get($item, 'forzar_cambio')"
                                                class="!h-8 !min-h-8 rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-xs text-[#1A2B42]"
                                            />

                                            @if (data_get($item, 'forzar_cambio'))
                                                <div class="mt-1 text-[10px] font-semibold text-[#2E8BC0]">
                                                    Forzado
                                                </div>
                                            @endif
                                        </div>
                                    @endscope

                                    @scope('cell_estado', $item)
                                        @php($i = data_get($item, 'index'))

                                        <div class="w-[115px]">
                                            <x-select
                                                wire:model.live="detalle.{{ $i }}.estado_producto"
                                                :options="[
                                                    ['id' => 1, 'name' => 'Bueno'],
                                                    ['id' => 2, 'name' => 'Dañado'],
                                                    ['id' => 3, 'name' => 'En revisión'],
                                                    ['id' => 4, 'name' => 'Garantía'],
                                                ]"
                                                option-value="id"
                                                option-label="name"
                                                :disabled="! data_get($item, 'aplica')"
                                                class="!h-8 !min-h-8 rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-xs text-[#1A2B42]"
                                            />

                                            <div class="mt-1">
                                                <x-checkbox
                                                    label="Reintegra"
                                                    wire:model.live="detalle.{{ $i }}.reintegra_inventario"
                                                    :disabled="! data_get($item, 'aplica') || (int) data_get($item, 'estado_producto') !== 1"
                                                    class="checkbox-xs border-[#2E8BC0] checked:border-[#2E8BC0] checked:bg-[#2E8BC0] [&_span]:text-[11px] [&_span]:text-[#1A2B42]"
                                                />
                                            </div>
                                        </div>
                                    @endscope

                                    @scope('cell_cambio', $item)
                                        @php($i = data_get($item, 'index'))

                                        <div class="w-[250px]">
                                            @if (data_get($item, 'es_cambio_producto'))
                                                <x-input
                                                    wire:model.live.debounce.350ms="detalle.{{ $i }}.busqueda_cambio"
                                                    :disabled="! data_get($item, 'aplica')"
                                                    placeholder="Buscar similar"
                                                    icon="o-magnifying-glass"
                                                    class="!h-8 !min-h-8 rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-xs text-[#1A2B42] placeholder:text-[#5F6B7A]"
                                                />

                                                @if (count(data_get($item, 'resultados_cambio', [])))
                                                    <x-card
                                                        shadow
                                                        class="mt-2 max-h-36 overflow-auto border border-[#D7E4F3] !bg-white p-0 text-[#1A2B42]
                                                        [&_.card-body]:!p-2"
                                                    >
                                                        <div class="flex flex-col gap-1.5">
                                                            @foreach (data_get($item, 'resultados_cambio', []) as $productoCambio)
                                                                <x-button
                                                                    type="button"
                                                                    wire:click="seleccionarProductoCambio({{ $i }}, {{ $productoCambio['id'] }})"
                                                                    class="!h-auto !min-h-0 !justify-start !border !border-[#E6EEF8] !bg-white !px-2 !py-1.5 !text-left !text-[#1A2B42] hover:!bg-[#F8FBFF] hover:!text-[#1A2B42]"
                                                                >
                                                                    <div class="flex w-full flex-col gap-0.5 overflow-hidden">
                                                                        <span class="truncate text-[11px] font-bold text-[#1A2B42]">
                                                                            {{ $productoCambio['nombre'] }}
                                                                        </span>
                                                                        <span class="text-[10px] text-[#5F6B7A]">
                                                                            Stock: {{ $productoCambio['stock'] }} · C$ {{ number_format((float) $productoCambio['precio'], 2) }}
                                                                        </span>
                                                                        <span class="text-[10px] font-bold {{ $productoCambio['diferencia'] > 0 ? 'text-amber-600' : ($productoCambio['diferencia'] < 0 ? 'text-green-700' : 'text-[#2E8BC0]') }}">
                                                                            {{ $productoCambio['texto_diferencia'] }}
                                                                        </span>
                                                                    </div>
                                                                </x-button>
                                                            @endforeach
                                                        </div>
                                                    </x-card>
                                                @endif

                                                @if (! empty(data_get($item, 'producto_cambio_id')))
                                                    <x-card
                                                        class="mt-2 border border-[#D7E4F3] !bg-[#F8FBFF] text-[#1A2B42]
                                                        [&_.card-body]:!p-2"
                                                    >
                                                        <div class="flex flex-col gap-1.5">
                                                            <div class="flex items-start justify-between gap-2">
                                                                <div class="min-w-0">
                                                                    <div class="truncate text-[11px] font-bold text-[#1A2B42]" title="{{ data_get($item, 'producto_cambio_nombre') }}">
                                                                        {{ data_get($item, 'producto_cambio_nombre') }}
                                                                    </div>
                                                                    <div class="text-[10px] text-[#5F6B7A]">
                                                                        C$ {{ number_format((float) data_get($item, 'producto_cambio_precio'), 2) }}
                                                                    </div>
                                                                </div>

                                                                <x-button
                                                                    label="Quitar"
                                                                    type="button"
                                                                    wire:click="quitarProductoCambio({{ $i }})"
                                                                    class="!h-6 !min-h-6 shrink-0 !border-0 !bg-red-50 !px-2 !text-[10px] !font-bold !text-red-600 hover:!bg-red-100 hover:!text-red-700"
                                                                />
                                                            </div>

                                                            @if (data_get($item, 'producto_cambio_tiene_series'))
                                                                <x-select
                                                                    wire:model.live="detalle.{{ $i }}.producto_serie_cambio_id"
                                                                    :options="data_get($item, 'series_cambio', [])"
                                                                    option-value="id"
                                                                    option-label="name"
                                                                    class="!h-8 !min-h-8 rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-xs text-[#1A2B42]"
                                                                />
                                                            @endif

                                                            <div>
                                                                @if ((float) data_get($item, 'cliente_paga') > 0)
                                                                    <x-badge
                                                                        value="{{ 'Paga C$ ' . number_format((float) data_get($item, 'cliente_paga'), 2) }}"
                                                                        class="!bg-amber-50 !text-amber-700"
                                                                    />
                                                                @elseif ((float) data_get($item, 'cliente_recibe') > 0)
                                                                    <x-badge
                                                                        value="{{ 'Recibe C$ ' . number_format((float) data_get($item, 'cliente_recibe'), 2) }}"
                                                                        class="!bg-green-50 !text-green-700"
                                                                    />
                                                                @else
                                                                    <x-badge value="Sin diferencia" class="!bg-[#EAF5FB] !text-[#2E8BC0]" />
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </x-card>
                                                @else
                                                    <div class="mt-1 text-[11px] font-semibold text-amber-600">
                                                        Selecciona producto de cambio.
                                                    </div>
                                                @endif
                                            @else
                                                <x-alert class="border border-green-100 !bg-green-50 !py-1 text-xs !text-green-700">
                                                    Monetaria.
                                                </x-alert>
                                            @endif
                                        </div>
                                    @endscope

                                    @scope('cell_motivo', $item)
                                        @php($i = data_get($item, 'index'))

                                        <div class="w-[140px]">
                                            <x-input
                                                maxlength="200"
                                                wire:model.blur="detalle.{{ $i }}.motivo"
                                                :disabled="! data_get($item, 'aplica')"
                                                placeholder="Motivo"
                                                class="!h-8 !min-h-8 rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-xs text-[#1A2B42] placeholder:text-[#5F6B7A]"
                                            />
                                        </div>
                                    @endscope

                                    @scope('cell_total', $item)
                                        <div class="w-[95px] text-right">
                                            <div class="text-xs font-bold text-[#2E8BC0]">
                                                C$ {{ number_format((float) data_get($item, 'monto_devuelve'), 2) }}
                                            </div>

                                            @if (data_get($item, 'es_cambio_producto') && ! empty(data_get($item, 'producto_cambio_id')))
                                                <div class="mt-1 text-[10px] text-[#5F6B7A]">
                                                    Cambio: C$ {{ number_format((float) data_get($item, 'monto_cambio'), 2) }}
                                                </div>
                                            @endif
                                        </div>
                                    @endscope
                                </x-table>
                            </div>
                        </div>
                    </div>
                </x-card>

                <x-card shadow class="sticky bottom-0 z-30 w-full max-w-full overflow-hidden border border-[#D7E4F3] !bg-white/95 backdrop-blur">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm md:grid-cols-4">
                            <div>
                                <span class="font-bold text-[#1A2B42]">Cantidad:</span>
                                <span class="text-[#5F6B7A]">{{ number_format($this->obtenerCantidadTotalDevuelta(), 2) }}</span>
                            </div>

                            <div>
                                <span class="font-bold text-[#1A2B42]">Egreso:</span>
                                <span class="text-red-600">C$ {{ number_format($this->obtenerEgresoEstimadoTotal(), 2) }}</span>
                            </div>

                            <div>
                                <span class="font-bold text-[#1A2B42]">Cliente paga:</span>
                                <span class="text-amber-700">C$ {{ number_format($this->obtenerClientePagaTotal(), 2) }}</span>
                            </div>

                            <div>
                                <span class="font-bold text-[#1A2B42]">Cliente recibe:</span>
                                <span class="text-green-700">C$ {{ number_format($this->obtenerClienteRecibeTotal(), 2) }}</span>
                            </div>
                        </div>

                        <div class="flex shrink-0 justify-end">
                            <x-button
                                label="Registrar devolución"
                                type="submit"
                                icon="o-check-circle"
                                spinner="confirmarDevolucion"
                                :disabled="! $ventaSeleccionadaId || ! $this->haySeleccion()"
                                class="!h-10 !min-w-52 !border-0 !bg-[#2E8BC0] !px-6 !font-bold !text-white hover:!bg-[#0B6FE4] hover:!text-white disabled:!bg-[#9ABBD3] disabled:!text-white"
                            />
                        </div>
                    </div>
                </x-card>
            </div>
        </x-form>
    </div>
</div>