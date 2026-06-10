<?php

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\TarifaCopia;
use App\Models\TasaCambio;
use App\Models\Usuario;
use App\Models\Venta;
use App\Services\Ventas\ThermalPrintService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    private const TIPO_CONTADO = 'CONTADO';
    private const TIPO_CREDITO = 'CREDITO';

    private const TIPO_PRODUCTO = 'PRODUCTO';
    private const TIPO_COPIA = 'COPIA';

    private const ESTADO_SERIE_DISPONIBLE = 'DISPONIBLE';
    private const ESTADO_SERIE_VENDIDO = 'VENDIDO';

    private const SERVICIO_COPIA = 'COPIA';

    private const MONEDA_CORDOBA = 0;
    private const MONEDA_DOLAR = 1;

    private const PAGO_EFECTIVO = 'EFECTIVO';
    private const PAGO_TRANSFERENCIA = 'TRANSFERENCIA';
    private const PAGO_TARJETA = 'TARJETA';

    private const ESTADO_CREDITO_PENDIENTE = 'PENDIENTE';

    public bool $modalCobro = false;
    public bool $modalNuevaCopiaRapida = false;
    public bool $modalVoucherVenta = false;
    public bool $modalCotizacionRapida = false;
    public bool $modalEntregasCredito = false;

    public string $tipoVenta = self::TIPO_CONTADO;

    public string $tipoPagoCordobas = self::PAGO_EFECTIVO;
    public string $tipoPagoDolares = self::PAGO_EFECTIVO;
    public string $tipoCambio = '0.00';

    public string $referenciaCordobas = '';
    public string $referenciaDolares = '';

    public ?int $clienteId = null;
    public string $buscarCliente = '';
    public string $clienteNombre = 'Consumidor final';
    public string $departamentoMunicipio = '';
    public array $clientesEncontrados = [];
    public bool $mostrarClientes = false;

    public string $buscarItem = '';
    public array $resultadosItems = [];
    public bool $mostrarItems = false;

    public ?array $itemSeleccionado = null;
    public string $descripcionSeleccionada = '';
    public string $tipoItemSeleccionado = '';
    public string $cantidadItem = '1';
    public string $precioItem = '0';
    public string $descuentoItem = '0';
    public int $stockDisponible = 0;
    public bool $productoUsaSerie = false;

    public $serieProductoId = null;
    public array $seriesDisponibles = [];

    public ?int $copiaRapidaId = null;
    public string $cantidadCopiaRapida = '1';
    public string $precioCopiaRapida = '';
    public array $copiasRapidas = [];

    public string $nuevaCopiaNombre = '';
    public string $nuevaCopiaTipoColor = 'BN';
    public string $nuevaCopiaFormato = 'CARTA';
    public string $nuevaCopiaLados = 'UNA_CARA';

    public array $opcionesTipoColorCopia = [
        ['id' => 'BN', 'name' => 'Blanco y negro'],
        ['id' => 'COLOR', 'name' => 'Color'],
    ];

    public array $opcionesFormatoCopia = [
        ['id' => 'CARTA', 'name' => 'Carta'],
        ['id' => 'OFICIO', 'name' => 'Oficio'],
        ['id' => 'A4', 'name' => 'A4'],
        ['id' => 'LEGAL', 'name' => 'Legal'],
    ];

    public array $opcionesLadosCopia = [
        ['id' => 'UNA_CARA', 'name' => 'Una cara'],
        ['id' => 'DOBLE_CARA', 'name' => 'Doble cara'],
    ];

    public array $detalleVenta = [];
    public string $observacionVenta = '';
    public string $areaItem = '';

    public string $pagoCordobas = '0';
    public string $pagoDolares = '0';

    public float $saldoFavorClienteCredito = 0.00;

    public ?int $ultimaVentaId = null;
    public string $ultimaFacturaNumero = '';
    public string $ultimoTipoVenta = '';

    public ?int $voucherVentaId = null;
    public string $voucherPreviewUrl = '';

    public string $cotizacionPreviewUrl = '';
    public string $cotizacionNumero = '';
    public array $cotizacionPreview = [];

    // MODIFICADO: propiedades para entregar pendientes de crédito institucional.
    public string $entregaMunicipio = '';
    public string $entregaClienteId = '';
    public array $entregaMunicipiosOpciones = [];
    public array $entregaInstitucionesOpciones = [];
    public array $entregasPendientes = [];
    public array $recibidosPendientes = [];

    public function mount(): void
    {
        $this->cargarTasaCambio();
        $this->cargarCopiasRapidas();
    }

    public function cargarTasaCambio(): void
    {
        $tasaActual = TasaCambio::actual();

        $valorActual = $tasaActual
            ? (float) $tasaActual->Valor_Cambio
            : 0;

        $this->tipoCambio = number_format($valorActual, 2, '.', '');
    }

    public function cambiarTipoVenta(string $tipo): void
    {
        if (! in_array($tipo, [self::TIPO_CONTADO, self::TIPO_CREDITO], true)) {
            return;
        }

        $this->tipoVenta = $tipo;
        $this->updatedTipoVenta();
    }

    public function updatedBuscarCliente(): void
    {
        $this->buscarClientes();
    }

    public function updatedBuscarItem(): void
    {
        $this->buscarItems();
    }

    public function updatedPrecioItem($value): void
    {
        $this->precioItem = $this->formatearMonto((string) $value);
    }

    public function updatedDescuentoItem($value): void
    {
        $this->descuentoItem = $this->formatearMonto((string) $value);
    }

    public function updatedPrecioCopiaRapida($value): void
    {
        $this->precioCopiaRapida = $this->formatearMonto((string) $value);
    }

    public function updatedPagoCordobas($value): void
    {
        $this->pagoCordobas = $this->formatearMonto((string) $value);
    }

    public function updatedPagoDolares($value): void
    {
        $this->pagoDolares = $this->formatearDecimal((string) $value);
    }

    public function updatedTipoCambio($value): void
    {
        $this->tipoCambio = $this->formatearDecimal((string) $value);
    }

    public function updatedTipoPagoCordobas(): void
    {
        if (! $this->pagoRequiereReferencia($this->tipoPagoCordobas)) {
            $this->referenciaCordobas = '';
        }
    }

    public function updatedTipoPagoDolares(): void
    {
        if (! $this->pagoRequiereReferencia($this->tipoPagoDolares)) {
            $this->referenciaDolares = '';
        }
    }

    public function updatedTipoVenta(): void
    {
        $this->pagoCordobas = '0';
        $this->pagoDolares = '0';
        $this->referenciaCordobas = '';
        $this->referenciaDolares = '';
        $this->clientesEncontrados = [];
        $this->mostrarClientes = false;

        if ($this->clienteId) {
            $cliente = Cliente::query()
                ->where('Id_Cliente', $this->clienteId)
                ->first();

            if (! $cliente || ! $this->clientePermitidoParaTipoVenta($cliente)) {
                $this->limpiarClienteFacturacion();
            } elseif ($this->tipoVenta === self::TIPO_CREDITO) {
                $this->departamentoMunicipio = $cliente->Municipio ?? '';
                $this->cargarSaldoFavorClienteCredito();
            }
        } else {
            $this->limpiarClienteFacturacion();
        }

        if ($this->tipoVenta === self::TIPO_CONTADO) {
            $this->departamentoMunicipio = '';
        }

        if ($this->tipoVenta === self::TIPO_CREDITO) {
            $this->tipoPagoCordobas = self::PAGO_EFECTIVO;
            $this->tipoPagoDolares = self::PAGO_EFECTIVO;
        }
    }

    public function updatedEntregaMunicipio(): void
    {
        $this->entregaClienteId = '';
        $this->entregasPendientes = [];
        $this->recibidosPendientes = [];
        $this->cargarInstitucionesEntregaCredito();
    }

    public function updatedEntregaClienteId(): void
    {
        $this->entregasPendientes = [];
        $this->recibidosPendientes = [];
    }

    protected function buscarClientes(): void
    {
        $busqueda = trim($this->buscarCliente);

        if (strlen($busqueda) < 2) {
            $this->clientesEncontrados = [];
            $this->mostrarClientes = false;
            return;
        }

        $tipoClientePermitido = $this->tipoClientePermitidoVenta();

        $this->clientesEncontrados = Cliente::query()
            ->with('persona')
            ->where('Estado', true)
            ->where('Tipo_Cliente', $tipoClientePermitido)
            ->where(function ($query) use ($busqueda) {
                $query->where('Institucion', 'like', "%{$busqueda}%")
                    ->orWhere('Telefono_Institucion', 'like', "%{$busqueda}%")
                    ->orWhere('Municipio', 'like', "%{$busqueda}%")
                    ->orWhereHas('persona', function ($persona) use ($busqueda) {
                        $persona->where('Primer_Nombre', 'like', "%{$busqueda}%")
                            ->orWhere('Segundo_Nombre', 'like', "%{$busqueda}%")
                            ->orWhere('Primer_Apellido', 'like', "%{$busqueda}%")
                            ->orWhere('Segundo_Apellido', 'like', "%{$busqueda}%")
                            ->orWhere('Telefono', 'like', "%{$busqueda}%");
                    });
            })
            ->limit(8)
            ->get()
            ->map(function (Cliente $cliente) {
                return [
                    'id' => (int) $cliente->Id_Cliente,
                    'nombre' => $this->nombreClienteFacturacion($cliente),
                    'telefono' => $this->telefonoClienteFacturacion($cliente),
                    'municipio' => $cliente->Municipio ?: '',
                ];
            })
            ->toArray();

        $this->mostrarClientes = count($this->clientesEncontrados) > 0;
    }

    public function seleccionarCliente(int $idCliente): void
    {
        $cliente = Cliente::query()
            ->with('persona')
            ->where('Id_Cliente', $idCliente)
            ->first();

        if (! $cliente) {
            $this->mostrarToast('No se encontró el cliente seleccionado.', 'error');
            return;
        }

        if (! $this->clientePermitidoParaTipoVenta($cliente)) {
            $this->mostrarToast($this->mensajeClienteNoPermitido(), 'error');
            $this->limpiarClienteFacturacion();
            return;
        }

        $this->clienteId = (int) $cliente->Id_Cliente;
        $this->clienteNombre = $this->nombreClienteFacturacion($cliente);
        $this->buscarCliente = $this->clienteNombre;
        $this->departamentoMunicipio = $this->tipoVenta === self::TIPO_CREDITO
            ? (string) ($cliente->Municipio ?? '')
            : '';

        $this->clientesEncontrados = [];
        $this->mostrarClientes = false;

        $this->cargarSaldoFavorClienteCredito();
    }

    public function usarConsumidorFinal(): void
    {
        if ($this->tipoVenta === self::TIPO_CREDITO) {
            $this->mostrarToast('Para crédito debe seleccionar una institución registrada.', 'error');
            return;
        }

        $this->limpiarClienteFacturacion();
    }

    protected function buscarItems(): void
    {
        $busqueda = trim($this->buscarItem);
        $seriesUsadas = $this->seriesUsadasEnDetalle();

        if (strlen($busqueda) < 2) {
            $this->resultadosItems = [];
            $this->mostrarItems = false;
            return;
        }

        $productos = Producto::query()
            ->with('marca')
            ->where('Estado', true)
            ->where('Stock_Actual', '>', 0)
            ->where(function ($query) use ($seriesUsadas) {
                $query->whereDoesntHave('series')
                    ->orWhereHas('series', function ($serie) use ($seriesUsadas) {
                        $serie->where('Estado', self::ESTADO_SERIE_DISPONIBLE)
                            ->when(count($seriesUsadas) > 0, function ($query) use ($seriesUsadas) {
                                $query->whereNotIn('id_producto_serie', $seriesUsadas);
                            });
                    });
            })
            ->where(function ($query) use ($busqueda, $seriesUsadas) {
                $query->where('Nombre_Producto', 'like', "%{$busqueda}%")
                    ->orWhere('Modelo', 'like', "%{$busqueda}%")
                    ->orWhereHas('marca', function ($marca) use ($busqueda) {
                        $marca->where('Nombre_Marca', 'like', "%{$busqueda}%");
                    })
                    ->orWhereHas('series', function ($serie) use ($busqueda, $seriesUsadas) {
                        $serie->where('Estado', self::ESTADO_SERIE_DISPONIBLE)
                            ->where('Numero_Serie', 'like', "%{$busqueda}%")
                            ->when(count($seriesUsadas) > 0, function ($query) use ($seriesUsadas) {
                                $query->whereNotIn('id_producto_serie', $seriesUsadas);
                            });
                    });
            })
            ->orderBy('Nombre_Producto')
            ->limit(8)
            ->get()
            ->map(function (Producto $producto) use ($busqueda, $seriesUsadas) {
                $serie = $producto->series()
                    ->where('Estado', self::ESTADO_SERIE_DISPONIBLE)
                    ->where('Numero_Serie', 'like', "%{$busqueda}%")
                    ->when(count($seriesUsadas) > 0, function ($query) use ($seriesUsadas) {
                        $query->whereNotIn('id_producto_serie', $seriesUsadas);
                    })
                    ->orderBy('Numero_Serie')
                    ->first();

                $titulo = $this->nombreProductoLimpio(
                    $producto->marca?->Nombre_Marca,
                    $producto->Nombre_Producto
                );

                return [
                    'tipo' => self::TIPO_PRODUCTO,
                    'id' => (int) $producto->Id_Producto,
                    'serie_id' => $serie ? (int) $serie->id_producto_serie : null,
                    'titulo' => $titulo,
                    'subtitulo' => trim(
                        ($producto->Modelo ?: 'Sin modelo')
                        . ' · Stock: ' . $producto->Stock_Actual
                        . ($serie ? ' · Serie: ' . $serie->Numero_Serie : '')
                    ),
                    'precio' => (float) $producto->Precio_Venta,
                    'precio_texto' => 'C$ ' . number_format((float) $producto->Precio_Venta, 0, '.', ','),
                ];
            })
            ->toArray();

        $copias = TarifaCopia::query()
            ->with('servicio')
            ->where('Estado', true)
            ->whereHas('servicio', function ($servicio) {
                $servicio->where('Estado', true)
                    ->where('Tipo_Servicio', self::SERVICIO_COPIA);
            })
            ->where(function ($query) use ($busqueda) {
                $query->where('Nombre_Tarifa', 'like', "%{$busqueda}%")
                    ->orWhere('Tipo_Color', 'like', "%{$busqueda}%")
                    ->orWhere('Formato', 'like', "%{$busqueda}%")
                    ->orWhere('Lados', 'like', "%{$busqueda}%");
            })
            ->orderBy('Nombre_Tarifa')
            ->limit(8)
            ->get()
            ->map(function (TarifaCopia $tarifa) {
                return [
                    'tipo' => self::TIPO_COPIA,
                    'id' => (int) $tarifa->Id_Tarifa_Copia,
                    'serie_id' => null,
                    'titulo' => $tarifa->Nombre_Tarifa,
                    'subtitulo' => $tarifa->Tipo_Color . ' · ' . $tarifa->Formato . ' · ' . $tarifa->Lados,
                    'precio' => 0,
                    'precio_texto' => 'Precio manual',
                ];
            })
            ->toArray();

        $this->resultadosItems = array_values(array_merge($productos, $copias));
        $this->mostrarItems = count($this->resultadosItems) > 0;
    }

    protected function cargarCopiasRapidas(): void
    {
        $this->copiasRapidas = TarifaCopia::query()
            ->with('servicio')
            ->where('Estado', true)
            ->whereHas('servicio', function ($servicio) {
                $servicio->where('Estado', true)
                    ->where('Tipo_Servicio', self::SERVICIO_COPIA);
            })
            ->orderBy('Nombre_Tarifa')
            ->get()
            ->map(fn (TarifaCopia $tarifa) => [
                'id' => (int) $tarifa->Id_Tarifa_Copia,
                'name' => trim((string) $tarifa->Nombre_Tarifa),
            ])
            ->toArray();
    }

    public function seleccionarItem(string $tipo, int $id, ?int $serieId = null): void
    {
        $this->descuentoItem = '0';

        if ($tipo === self::TIPO_PRODUCTO) {
            $producto = Producto::query()
                ->with('marca')
                ->where('Id_Producto', $id)
                ->where('Estado', true)
                ->first();

            if (! $producto) {
                $this->mostrarToast('No se encontró el producto seleccionado.', 'error');
                return;
            }

            $seriesUsadas = $this->seriesUsadasEnDetalle((int) $producto->Id_Producto);
            $seriesTotalesDisponibles = $producto->series()->where('Estado', self::ESTADO_SERIE_DISPONIBLE)->count();

            $this->productoUsaSerie = $seriesTotalesDisponibles > 0;
            $this->seriesDisponibles = $producto->series()
                ->where('Estado', self::ESTADO_SERIE_DISPONIBLE)
                ->when(count($seriesUsadas) > 0, function ($query) use ($seriesUsadas) {
                    $query->whereNotIn('id_producto_serie', $seriesUsadas);
                })
                ->orderBy('Numero_Serie')
                ->limit(50)
                ->get()
                ->map(fn (ProductoSerie $serie) => [
                    'id' => (int) $serie->id_producto_serie,
                    'name' => $serie->Numero_Serie,
                ])
                ->toArray();

            $descripcion = $this->nombreProductoLimpio(
                $producto->marca?->Nombre_Marca,
                $producto->Nombre_Producto,
                $producto->Modelo
            );

            $serieIdValida = null;

            if ($serieId) {
                $serieIdValida = collect($this->seriesDisponibles)->firstWhere('id', (int) $serieId)['id'] ?? null;
            }

            $this->itemSeleccionado = [
                'tipo' => self::TIPO_PRODUCTO,
                'id_producto' => (int) $producto->Id_Producto,
                'id_tarifa_copia' => null,
                'id_servicio' => null,
                'descripcion' => $descripcion,
                'formato' => null,
                'lados' => null,
            ];

            $this->descripcionSeleccionada = $descripcion;
            $this->tipoItemSeleccionado = self::TIPO_PRODUCTO;
            $this->stockDisponible = (int) $producto->Stock_Actual;
            $this->precioItem = number_format((float) $producto->Precio_Venta, 0, '.', ',');
            $this->cantidadItem = '1';
            $this->serieProductoId = $serieIdValida;
        }

        if ($tipo === self::TIPO_COPIA) {
            $tarifa = TarifaCopia::query()
                ->where('Id_Tarifa_Copia', $id)
                ->where('Estado', true)
                ->first();

            if (! $tarifa) {
                $this->mostrarToast('No se encontró la copia seleccionada.', 'error');
                return;
            }

            $this->itemSeleccionado = [
                'tipo' => self::TIPO_COPIA,
                'id_producto' => null,
                'id_tarifa_copia' => (int) $tarifa->Id_Tarifa_Copia,
                'id_servicio' => (int) $tarifa->Id_Servicio,
                'descripcion' => $tarifa->Nombre_Tarifa,
                'formato' => $tarifa->Formato,
                'lados' => $tarifa->Lados,
            ];

            $this->descripcionSeleccionada = $tarifa->Nombre_Tarifa;
            $this->tipoItemSeleccionado = self::TIPO_COPIA;
            $this->stockDisponible = 0;
            $this->seriesDisponibles = [];
            $this->serieProductoId = null;
            $this->productoUsaSerie = false;
            $this->precioItem = '';
            $this->cantidadItem = '1';
        }

        $this->buscarItem = '';
        $this->resultadosItems = [];
        $this->mostrarItems = false;
    }

    public function agregarCopiaRapida(): void
    {
        if (! $this->copiaRapidaId) {
            $this->mostrarToast('Seleccione una copia rápida.', 'error');
            return;
        }

        $precioCopiaRapida = $this->limpiarMonto($this->precioCopiaRapida);

        if ($precioCopiaRapida <= 0) {
            $this->mostrarToast('Ingrese el precio unitario de la copia rápida.', 'error');
            return;
        }

        $this->descuentoItem = '0';
        $this->seleccionarItem(self::TIPO_COPIA, (int) $this->copiaRapidaId);
        $this->cantidadItem = (string) max(1, (int) $this->cantidadCopiaRapida);
        $this->precioItem = number_format($precioCopiaRapida, 0, '.', ',');
        $this->agregarItem();

        $this->copiaRapidaId = null;
        $this->cantidadCopiaRapida = '1';
        $this->precioCopiaRapida = '';
    }

    public function abrirModalNuevaCopiaRapida(): void
    {
        $this->limpiarNuevaCopiaRapida();
        $this->modalNuevaCopiaRapida = true;
    }

    public function cerrarModalNuevaCopiaRapida(): void
    {
        $this->modalNuevaCopiaRapida = false;
        $this->limpiarNuevaCopiaRapida();
    }

    public function guardarNuevaCopiaRapida(): void
    {
        $nombre = trim($this->nuevaCopiaNombre);

        if (strlen($nombre) < 3) {
            $this->mostrarToast('Ingrese un nombre válido para la copia rápida.', 'error');
            return;
        }

        if (! in_array($this->nuevaCopiaTipoColor, ['BN', 'COLOR'], true)) {
            $this->mostrarToast('Seleccione un tipo de color válido.', 'error');
            return;
        }

        if (! in_array($this->nuevaCopiaFormato, ['CARTA', 'OFICIO', 'A4', 'LEGAL'], true)) {
            $this->mostrarToast('Seleccione un formato válido.', 'error');
            return;
        }

        if (! in_array($this->nuevaCopiaLados, ['UNA_CARA', 'DOBLE_CARA'], true)) {
            $this->mostrarToast('Seleccione un tipo de lado válido.', 'error');
            return;
        }

        try {
            $idTarifaCopia = DB::transaction(function () use ($nombre) {
                $idServicio = $this->obtenerServicioCopiaId();

                $tarifaExistente = TarifaCopia::query()
                    ->where('Id_Servicio', $idServicio)
                    ->where('Tipo_Color', $this->nuevaCopiaTipoColor)
                    ->where('Formato', $this->nuevaCopiaFormato)
                    ->where('Lados', $this->nuevaCopiaLados)
                    ->lockForUpdate()
                    ->first();

                if ($tarifaExistente) {
                    $tarifaExistente->forceFill([
                        'Nombre_Tarifa' => Str::limit($nombre, 150, ''),
                        'Precio_Unitario' => 0,
                        'Estado' => true,
                    ])->save();

                    return (int) $tarifaExistente->Id_Tarifa_Copia;
                }

                return (int) DB::table('tarifa_copia')->insertGetId([
                    'Id_Servicio' => $idServicio,
                    'Nombre_Tarifa' => Str::limit($nombre, 150, ''),
                    'Tipo_Color' => $this->nuevaCopiaTipoColor,
                    'Formato' => $this->nuevaCopiaFormato,
                    'Lados' => $this->nuevaCopiaLados,
                    'Precio_Unitario' => 0,
                    'Estado' => true,
                    'Fecha_Registro' => now(),
                ]);
            });

            $this->cargarCopiasRapidas();
            $this->copiaRapidaId = $idTarifaCopia;
            $this->modalNuevaCopiaRapida = false;
            $this->limpiarNuevaCopiaRapida();

            $this->mostrarToast('Copia rápida guardada y seleccionada. Recuerde ingresar precio unitario al venderla.');
        } catch (\Throwable $e) {
            $this->mostrarToast('No se pudo guardar la copia rápida: ' . $e->getMessage(), 'error');
        }
    }

    protected function limpiarNuevaCopiaRapida(): void
    {
        $this->nuevaCopiaNombre = '';
        $this->nuevaCopiaTipoColor = 'BN';
        $this->nuevaCopiaFormato = 'CARTA';
        $this->nuevaCopiaLados = 'UNA_CARA';
    }

    protected function obtenerServicioCopiaId(): int
    {
        $idServicio = DB::table('servicio')
            ->where('Tipo_Servicio', self::SERVICIO_COPIA)
            ->where('Estado', true)
            ->value('Id_Servicio');

        if ($idServicio) {
            return (int) $idServicio;
        }

        return (int) DB::table('servicio')->insertGetId([
            'Nombre_Servicio' => 'Fotocopias',
            'Descripcion' => 'Servicio de fotocopias e impresiones por cantidad.',
            'Precio_Base' => 0,
            'Requiere_Contrato' => false,
            'Requiere_Anticipo' => false,
            'Porcentaje_Anticipo' => 0,
            'Garantia' => false,
            'Estado' => true,
            'Tipo_Servicio' => self::SERVICIO_COPIA,
            'Unidad_Medida' => 'COPIA',
            'Permite_Credito' => true,
        ]);
    }

    public function agregarItem(): void
    {
        if (! $this->itemSeleccionado) {
            $this->mostrarToast('Seleccione primero un producto o una copia.', 'error');
            return;
        }

        $precio = $this->limpiarMonto($this->precioItem);
        $cantidad = max(1, (int) $this->cantidadItem);

        if ($precio <= 0) {
            $this->mostrarToast(
                $this->itemSeleccionado['tipo'] === self::TIPO_COPIA
                    ? 'Ingrese el precio unitario de la copia.'
                    : 'El precio debe ser mayor a cero.',
                'error'
            );
            return;
        }

        if ($this->itemSeleccionado['tipo'] === self::TIPO_PRODUCTO) {
            if ($this->productoUsaSerie && ! $this->serieProductoId) {
                $this->mostrarToast('Seleccione una serie disponible para este producto.', 'error');
                return;
            }

            if ($this->serieProductoId) {
                $cantidad = 1;
            }

            $stockUsadoEnDetalle = collect($this->detalleVenta)
                ->where('tipo', self::TIPO_PRODUCTO)
                ->where('id_producto', $this->itemSeleccionado['id_producto'])
                ->sum('cantidad');

            if (($cantidad + $stockUsadoEnDetalle) > $this->stockDisponible) {
                $this->mostrarToast('La cantidad supera el stock disponible.', 'error');
                return;
            }

            if ($this->serieProductoId && in_array((int) $this->serieProductoId, $this->seriesUsadasEnDetalle(), true)) {
                $this->mostrarToast('Esta serie ya fue agregada al detalle.', 'error');
                return;
            }
        }

        $areaItem = $this->areaItemNormalizada();

        if ($this->tipoVenta === self::TIPO_CREDITO && ! $areaItem) {
            $this->mostrarToast('Ingrese el área del item antes de agregarlo al detalle.', 'error');
            return;
        }

        $serieTexto = null;

        if ($this->serieProductoId) {
            $serieTexto = ProductoSerie::query()
                ->where('id_producto_serie', $this->serieProductoId)
                ->value('Numero_Serie');
        }

        $subtotalBruto = $cantidad * $precio;
        $descuentoItem = min($this->limpiarMonto($this->descuentoItem), $subtotalBruto);
        $subtotal = $subtotalBruto - $descuentoItem;

        $indiceDetalleExistente = $this->indiceDetalleExistente(
            $this->itemSeleccionado['tipo'],
            $this->itemSeleccionado['id_producto'],
            $this->itemSeleccionado['id_tarifa_copia'],
            $this->serieProductoId ? (int) $this->serieProductoId : null,
            $precio,
            $areaItem
        );

        if ($indiceDetalleExistente !== null) {
            $cantidadActualizada = (int) $this->detalleVenta[$indiceDetalleExistente]['cantidad'] + $cantidad;
            $subtotalBrutoActualizado = $cantidadActualizada * $precio;
            $descuentoActualizado = min(
                (float) $this->detalleVenta[$indiceDetalleExistente]['descuento_valor'] + $descuentoItem,
                $subtotalBrutoActualizado
            );

            $this->detalleVenta[$indiceDetalleExistente]['cantidad'] = $cantidadActualizada;
            $this->detalleVenta[$indiceDetalleExistente]['subtotal_bruto_valor'] = $subtotalBrutoActualizado;
            $this->detalleVenta[$indiceDetalleExistente]['descuento_valor'] = $descuentoActualizado;
            $this->detalleVenta[$indiceDetalleExistente]['subtotal_valor'] = $subtotalBrutoActualizado - $descuentoActualizado;

            $this->mostrarToast('Item sumado al detalle existente.', 'info');
            $this->limpiarItemSeleccionado();
            return;
        }

        $this->detalleVenta[] = [
            'uid' => uniqid('det_', true),
            'tipo' => $this->itemSeleccionado['tipo'],
            'codigo' => $this->itemSeleccionado['tipo'] === self::TIPO_PRODUCTO
                ? 'P-' . $this->itemSeleccionado['id_producto']
                : 'C-' . $this->itemSeleccionado['id_tarifa_copia'],
            'descripcion' => $this->itemSeleccionado['descripcion'] . ($serieTexto ? ' · Serie: ' . $serieTexto : ''),
            'id_producto' => $this->itemSeleccionado['id_producto'],
            'id_producto_serie' => $this->serieProductoId ? (int) $this->serieProductoId : null,
            'id_servicio' => $this->itemSeleccionado['id_servicio'],
            'id_tarifa_copia' => $this->itemSeleccionado['id_tarifa_copia'],
            'formato' => $this->itemSeleccionado['formato'],
            'lados' => $this->itemSeleccionado['lados'],
            'area' => $areaItem,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal_bruto_valor' => $subtotalBruto,
            'descuento_valor' => $descuentoItem,
            'subtotal_valor' => $subtotal,
        ];

        $this->limpiarItemSeleccionado();
    }

    protected function indiceDetalleExistente(
        string $tipo,
        ?int $idProducto,
        ?int $idTarifaCopia,
        ?int $idProductoSerie,
        float $precioUnitario,
        ?string $areaItem = null
    ): ?int {
        if ($idProductoSerie) {
            return null;
        }

        foreach ($this->detalleVenta as $indice => $item) {
            if ($item['tipo'] !== $tipo) {
                continue;
            }

            if ((float) $item['precio_unitario'] !== (float) $precioUnitario) {
                continue;
            }

            if (trim((string) ($item['area'] ?? '')) !== trim((string) $areaItem)) {
                continue;
            }

            if ($tipo === self::TIPO_PRODUCTO && empty($item['id_producto_serie']) && (int) $item['id_producto'] === (int) $idProducto) {
                return $indice;
            }

            if ($tipo === self::TIPO_COPIA && (int) $item['id_tarifa_copia'] === (int) $idTarifaCopia) {
                return $indice;
            }
        }

        return null;
    }

    public function eliminarDetalle(string $uid): void
    {
        $this->detalleVenta = array_values(array_filter(
            $this->detalleVenta,
            fn ($item) => $item['uid'] !== $uid
        ));
    }

    public function abrirModalCobro(): void
    {
        $this->cargarTasaCambio();

        if (! $this->tasaCambioValida()) {
            $this->mostrarToast('Debe registrar una tasa de cambio válida desde Arqueo de caja antes de facturar.', 'error');
            return;
        }

        if (count($this->detalleVenta) === 0) {
            $this->mostrarToast('Agregue al menos un item a la venta.', 'error');
            return;
        }

        if (! $this->clienteSeleccionadoValidoParaVenta()) {
            $this->mostrarToast($this->mensajeClienteNoPermitido(), 'error');
            return;
        }

        $this->pagoCordobas = '0';
        $this->pagoDolares = '0';
        $this->referenciaCordobas = '';
        $this->referenciaDolares = '';
        $this->cargarSaldoFavorClienteCredito();

        $this->modalCobro = true;
    }

    public function cerrarModalCobro(): void
    {
        $this->modalCobro = false;
    }

    public function generarCotizacion()
    {
        $this->resetErrorBag();
        $this->cargarTasaCambio();

        $this->modalCotizacionRapida = false;
        $this->cotizacionPreviewUrl = '';
        $this->cotizacionNumero = '';
        $this->cotizacionPreview = [];

        if (! $this->tasaCambioValida()) {
            $this->mostrarToast('Debe registrar una tasa de cambio válida antes de generar la cotización.', 'error');
            return null;
        }

        if (count($this->detalleVenta) === 0) {
            $this->mostrarToast('Agregue al menos un item para generar la cotización.', 'error');
            return null;
        }

        if (! $this->clienteSeleccionadoValidoParaVenta()) {
            $this->mostrarToast($this->mensajeClienteNoPermitido(), 'error');
            return null;
        }

        $key = (string) Str::uuid();
        $numeroCotizacion = 'PRO-' . now()->format('Ymd-His');

        Cache::put('cotizacion_venta_' . $key, [
            'numero' => $numeroCotizacion,
            'cliente' => $this->clienteNombre,
            'municipio' => $this->tipoVenta === self::TIPO_CREDITO ? $this->departamentoMunicipio : '',
            'tipo_venta' => $this->tipoVenta,
            'tipo_cambio' => $this->tasaCambio(),
            'subtotal' => $this->subtotalVenta(),
            'descuento' => $this->descuentoVenta(),
            'total' => $this->totalVenta(),
            'observacion' => $this->observacionVentaNormalizada(),
            'items' => array_values($this->detalleVenta),
        ], now()->addMinutes(20));

        $this->cotizacionNumero = $numeroCotizacion;
        $this->cotizacionPreviewUrl = route('ventas.cotizacion', ['key' => $key]);
        $this->modalCotizacionRapida = true;

        $this->mostrarToast('Cotización PDF generada correctamente.', 'info');

        return null;
    }

    public function cerrarModalCotizacionRapida(): void
    {
        $this->modalCotizacionRapida = false;
        $this->cotizacionPreviewUrl = '';
        $this->cotizacionNumero = '';
        $this->cotizacionPreview = [];
    }

    public function guardarVenta()
    {
        $this->resetErrorBag();
        $this->cargarTasaCambio();

        if (! $this->tasaCambioValida()) {
            $this->mostrarToast('Debe registrar una tasa de cambio válida desde Arqueo de caja antes de guardar la venta.', 'error');
            return null;
        }

        $total = $this->totalVenta();
        $descuento = $this->descuentoVenta();

        $pagoCordobas = $this->limpiarMonto($this->pagoCordobas);
        $pagoDolares = $this->limpiarDecimal($this->pagoDolares);
        $equivalenteDolares = round($pagoDolares * $this->tasaCambio(), 2);
        $totalPagado = round($pagoCordobas + $equivalenteDolares, 2);

        $cambioEntregadoCordobas = $this->tipoVenta === self::TIPO_CONTADO
            ? round(max($totalPagado - $total, 0), 2)
            : 0.00;

        if ($this->tipoVenta === self::TIPO_CONTADO && $totalPagado < $total) {
            $this->mostrarToast('El monto recibido no puede ser menor que el total.', 'error');
            return null;
        }

        if (
            $this->tipoVenta === self::TIPO_CONTADO &&
            $pagoCordobas > 0 &&
            $this->pagoRequiereReferencia($this->tipoPagoCordobas) &&
            trim($this->referenciaCordobas) === ''
        ) {
            $this->mostrarToast('Ingrese el número de referencia del pago en córdobas.', 'error');
            return null;
        }

        if (
            $this->tipoVenta === self::TIPO_CONTADO &&
            $pagoDolares > 0 &&
            $this->pagoRequiereReferencia($this->tipoPagoDolares) &&
            trim($this->referenciaDolares) === ''
        ) {
            $this->mostrarToast('Ingrese el número de referencia del pago en dólares.', 'error');
            return null;
        }

        if (! $this->clienteSeleccionadoValidoParaVenta()) {
            $this->mostrarToast($this->mensajeClienteNoPermitido(), 'error');
            return null;
        }

        $ventaIncluyeProductos = $this->ventaTieneProductos();

        try {
            $tipoVentaActual = $this->tipoVenta;

            $resultado = DB::transaction(function () use (
                $total,
                $descuento,
                $pagoCordobas,
                $pagoDolares,
                $equivalenteDolares,
                $cambioEntregadoCordobas
            ) {
                $idUsuario = $this->obtenerUsuarioId();
                $numeroFactura = $this->generarNumeroFactura();

                $venta = new Venta();
                $venta->forceFill([
                    'Numero_Factura' => $numeroFactura,
                    'Fecha_venta' => now(),
                    'Id_Cliente' => $this->clienteId,
                    'Id_Usuario' => $idUsuario,
                    'Tipo_Venta' => $this->tipoVenta,
                    'Estado' => defined(Venta::class . '::ESTADO_ACTIVA') ? Venta::ESTADO_ACTIVA : 1,
                    'Descuento' => $descuento,
                    'Total' => $total,
                    'Tipo_Cambio' => $this->tasaCambio(),
                    'Cambio_Entregado_Cordobas' => $cambioEntregadoCordobas,
                ]);
                $venta->save();

                foreach ($this->detalleVenta as $item) {
                    if ($item['tipo'] === self::TIPO_PRODUCTO) {
                        $producto = Producto::query()
                            ->where('Id_Producto', $item['id_producto'])
                            ->lockForUpdate()
                            ->first();

                        if (! $producto) {
                            throw ValidationException::withMessages([
                                'venta' => 'Uno de los productos ya no existe.',
                            ]);
                        }

                        $cantidad = (int) $item['cantidad'];

                        if ((int) $producto->Stock_Actual < $cantidad) {
                            throw ValidationException::withMessages([
                                'stock' => 'Stock insuficiente para ' . $item['descripcion'],
                            ]);
                        }

                        if ($item['id_producto_serie']) {
                            $serie = ProductoSerie::query()
                                ->where('id_producto_serie', $item['id_producto_serie'])
                                ->where('Estado', self::ESTADO_SERIE_DISPONIBLE)
                                ->lockForUpdate()
                                ->first();

                            if (! $serie) {
                                throw ValidationException::withMessages([
                                    'serie' => 'La serie ya no está disponible: ' . $item['descripcion'],
                                ]);
                            }

                            $serie->Estado = self::ESTADO_SERIE_VENDIDO;
                            $serie->save();
                        }

                        $producto->Stock_Actual = ((int) $producto->Stock_Actual) - $cantidad;
                        $producto->save();
                    }

                    $venta->detalles()->create([
                        'Tipo_Detalle' => $item['tipo'],
                        'Id_Producto' => $item['id_producto'],
                        'Id_Producto_serie' => $item['id_producto_serie'],
                        'Id_Servicio' => $item['id_servicio'],
                        'Id_Tarifa_Copia' => $item['id_tarifa_copia'],
                        'Nombre_Formato' => $item['tipo'] === self::TIPO_COPIA ? $item['descripcion'] : null,
                        'Formato_Copia' => $item['tipo'] === self::TIPO_COPIA ? $this->formatoCopiaValor($item['formato']) : null,
                        'Lados_Copia' => $item['tipo'] === self::TIPO_COPIA ? $this->ladosCopiaValor($item['lados']) : null,
                        'Cantidad' => $item['cantidad'],
                        'Precio_Unitario' => $item['precio_unitario'],
                        'Subtotal' => $item['subtotal_valor'],
                        'Descuento' => $item['descuento_valor'],
                        // MODIFICADO: en crédito la observación del detalle guarda el área específica del item.
                        'Observacion' => $this->tipoVenta === self::TIPO_CREDITO
                            ? ($item['area'] ?? null)
                            : $this->observacionVentaNormalizada(),
                        // MODIFICADO: queda vacío hasta que se entregue desde el modal de pendientes.
                        'Recibido_Por' => null,
                    ]);
                }

                if ($this->tipoVenta === self::TIPO_CONTADO) {
                    if ($pagoCordobas > 0) {
                        $venta->pagos()->create([
                            'Fecha_Pago' => now(),
                            'Moneda' => self::MONEDA_CORDOBA,
                            'Tipo_Pago' => $this->tipoPagoCordobas,
                            'Numero_Referencia' => $this->pagoRequiereReferencia($this->tipoPagoCordobas)
                                ? trim($this->referenciaCordobas)
                                : null,
                            'Monto' => $pagoCordobas,
                            'Tipo_Cambio' => 1,
                            'Monto_Equivalente_Cordobas' => $pagoCordobas,
                        ]);
                    }

                    if ($pagoDolares > 0) {
                        $venta->pagos()->create([
                            'Fecha_Pago' => now(),
                            'Moneda' => self::MONEDA_DOLAR,
                            'Tipo_Pago' => $this->tipoPagoDolares,
                            'Numero_Referencia' => $this->pagoRequiereReferencia($this->tipoPagoDolares)
                                ? trim($this->referenciaDolares)
                                : null,
                            'Monto' => $pagoDolares,
                            'Tipo_Cambio' => $this->tasaCambio(),
                            'Monto_Equivalente_Cordobas' => $equivalenteDolares,
                        ]);
                    }
                }

                if ($this->tipoVenta === self::TIPO_CREDITO) {
                    $clienteCredito = $this->obtenerOcrearClienteCreditoBloqueado((int) $this->clienteId);
                    $saldoAnteriorFavor = round(max((float) $clienteCredito->Saldo_Actual, 0), 2);
                    $saldoFavorAplicado = round(min($saldoAnteriorFavor, $total), 2);
                    $saldoDespuesFavor = round(max($saldoAnteriorFavor - $saldoFavorAplicado, 0), 2);
                    $saldoPendienteCredito = round(max($total - $saldoFavorAplicado, 0), 2);

                    DB::table('cliente_credito')
                        ->where('Id_Cliente_Credito', $clienteCredito->Id_Cliente_Credito)
                        ->update([
                            'Saldo_Actual' => $saldoDespuesFavor,
                        ]);

                    $credito = $venta->credito()->create([
                        'Id_Cliente_Credito' => $clienteCredito->Id_Cliente_Credito,
                        'Fecha_Credito' => now()->toDateString(),
                        'Abono_Inicial' => $saldoFavorAplicado,
                        'Saldo_Actual' => $saldoPendienteCredito,
                        'Firma_Recibido' => null,
                        'Estado' => $saldoPendienteCredito <= 0
                            ? 'CANCELADO'
                            : self::ESTADO_CREDITO_PENDIENTE,
                    ]);

                    if ($saldoFavorAplicado > 0) {
                        DB::table('cliente_credito_movimiento')->insert([
                            'Id_Cliente_Credito' => $clienteCredito->Id_Cliente_Credito,
                            'Id_Cliente' => $this->clienteId,
                            'Id_Venta' => $venta->Id_Venta,
                            'Id_Credito' => $credito->Id_Credito,
                            'Tipo_Movimiento' => 'CARGO',
                            'Monto' => $saldoFavorAplicado,
                            'Saldo_Anterior' => $saldoAnteriorFavor,
                            'Saldo_Despues' => $saldoDespuesFavor,
                            'Fecha_Movimiento' => now(),
                            'Observacion' => 'Saldo a favor aplicado a factura ' . $numeroFactura,
                        ]);
                    }
                }

                return [
                    'id_venta' => (int) $venta->Id_Venta,
                    'numero_factura' => $numeroFactura,
                ];
            });

            if ($tipoVentaActual === self::TIPO_CREDITO) {
                session(['venta_municipio_' . $resultado['id_venta'] => $this->departamentoMunicipio]);
            }

            $this->ultimaVentaId = $resultado['id_venta'];
            $this->ultimaFacturaNumero = $resultado['numero_factura'];
            $this->ultimoTipoVenta = $tipoVentaActual;

            $this->limpiarVentaActual();
            $this->cerrarModalCobro();

            if ($tipoVentaActual === self::TIPO_CONTADO && $ventaIncluyeProductos) {
                $this->prepararVoucherVenta((int) $resultado['id_venta']);
                $this->mostrarToast('Venta guardada. Revise el voucher antes de imprimir. Factura: ' . $resultado['numero_factura'], 'info');
            } elseif ($tipoVentaActual === self::TIPO_CREDITO) {
                $this->mostrarToast('Crédito guardado correctamente. Factura: ' . $resultado['numero_factura']);
            } else {
                $this->mostrarToast('Venta de copias guardada correctamente. Factura: ' . $resultado['numero_factura']);
            }

            return null;
        } catch (ValidationException $e) {
            $mensaje = collect($e->errors())->flatten()->first() ?: 'No se pudo guardar la venta.';
            $this->mostrarToast($mensaje, 'error');
            return null;
        } catch (\Throwable $e) {
            $this->mostrarToast('Error al guardar la venta: ' . $e->getMessage(), 'error');
            return null;
        }
    }

    protected function ventaTieneProductos(): bool
    {
        return collect($this->detalleVenta)
            ->contains(fn ($item) => ($item['tipo'] ?? null) === self::TIPO_PRODUCTO);
    }

    protected function prepararVoucherVenta(int $ventaId): void
    {
        $this->voucherVentaId = $ventaId;
        $this->voucherPreviewUrl = route('ventas.voucher', ['venta' => $ventaId, 'ancho' => 80]);
        $this->modalVoucherVenta = true;
    }

    public function cerrarModalVoucherVenta(): void
    {
        $this->modalVoucherVenta = false;
        $this->voucherVentaId = null;
        $this->voucherPreviewUrl = '';
    }

    public function imprimirVoucherVenta(): void
    {
        if (! $this->voucherVentaId) {
            $this->mostrarToast('No hay una venta seleccionada para imprimir.', 'error');
            return;
        }

        if (! $this->impresionTermicaActiva()) {
            $this->mostrarToast('La impresión térmica está desactivada en el archivo .env.', 'warning');
            return;
        }

        try {
            app(ThermalPrintService::class)->imprimirVenta((int) $this->voucherVentaId);
            $this->mostrarToast('Voucher enviado a impresión correctamente.');
            $this->cerrarModalVoucherVenta();
        } catch (\Throwable $e) {
            $this->mostrarToast('No se pudo imprimir el voucher: ' . $e->getMessage(), 'error');
        }
    }

    public function observacionVentaNormalizada(): ?string
    {
        $observacion = trim($this->observacionVenta);

        return $observacion !== '' ? Str::limit($observacion, 255, '') : null;
    }

    public function areaItemNormalizada(): ?string
    {
        $area = trim($this->areaItem);

        return $area !== '' ? Str::limit($area, 255, '') : null;
    }

    protected function impresionTermicaActiva(): bool
    {
        return filter_var(env('THERMAL_PRINT_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

    protected function limpiarVentaActual(): void
    {
        $this->tipoVenta = self::TIPO_CONTADO;
        $this->tipoPagoCordobas = self::PAGO_EFECTIVO;
        $this->tipoPagoDolares = self::PAGO_EFECTIVO;
        $this->cargarTasaCambio();
        $this->usarConsumidorFinal();
        $this->detalleVenta = [];
        $this->pagoCordobas = '0';
        $this->pagoDolares = '0';
        $this->referenciaCordobas = '';
        $this->referenciaDolares = '';
        $this->limpiarItemSeleccionado();
        $this->copiaRapidaId = null;
        $this->cantidadCopiaRapida = '1';
        $this->precioCopiaRapida = '';
        $this->modalNuevaCopiaRapida = false;
        $this->limpiarNuevaCopiaRapida();
        $this->observacionVenta = '';
        $this->modalCotizacionRapida = false;
        $this->cotizacionPreviewUrl = '';
        $this->cotizacionNumero = '';
        $this->cotizacionPreview = [];
    }

    public function cancelarVenta(): void
    {
        $this->limpiarVentaActual();
        $this->ultimaVentaId = null;
        $this->ultimaFacturaNumero = '';
        $this->ultimoTipoVenta = '';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    protected function limpiarItemSeleccionado(): void
    {
        $this->buscarItem = '';
        $this->resultadosItems = [];
        $this->mostrarItems = false;
        $this->itemSeleccionado = null;
        $this->descripcionSeleccionada = '';
        $this->tipoItemSeleccionado = '';
        $this->cantidadItem = '1';
        $this->precioItem = '0';
        $this->descuentoItem = '0';
        $this->areaItem = '';
        $this->stockDisponible = 0;
        $this->serieProductoId = null;
        $this->seriesDisponibles = [];
        $this->productoUsaSerie = false;
    }

    public function subtotalVenta(): float
    {
        return collect($this->detalleVenta)->sum('subtotal_bruto_valor');
    }

    public function descuentoVenta(): float
    {
        return collect($this->detalleVenta)->sum('descuento_valor');
    }

    public function totalVenta(): float
    {
        return max($this->subtotalVenta() - $this->descuentoVenta(), 0);
    }

    public function totalPagadoCordobas(): float
    {
        return $this->limpiarMonto($this->pagoCordobas)
            + ($this->limpiarDecimal($this->pagoDolares) * $this->tasaCambio());
    }

    public function cambioVenta(): float
    {
        if ($this->tipoVenta !== self::TIPO_CONTADO) {
            return 0;
        }

        return $this->totalPagadoCordobas() - $this->totalVenta();
    }

    public function cambioEntregadoCordobas(): float
    {
        return round(max($this->cambioVenta(), 0), 2);
    }

    public function saldoFavorAplicable(): float
    {
        if ($this->tipoVenta !== self::TIPO_CREDITO) {
            return 0;
        }

        return round(min($this->saldoFavorClienteCredito, $this->totalVenta()), 2);
    }

    public function saldoCredito(): float
    {
        if ($this->tipoVenta !== self::TIPO_CREDITO) {
            return 0;
        }

        return round(max($this->totalVenta() - $this->saldoFavorAplicable(), 0), 2);
    }

    protected function limpiarMonto(?string $valor): float
    {
        $valor = str_replace(',', '', $valor ?? '');
        $limpio = preg_replace('/[^\d.]/', '', $valor);

        return $limpio === '' ? 0 : (float) $limpio;
    }

    protected function limpiarDecimal(?string $valor): float
    {
        $valor = str_replace(',', '.', $valor ?? '');
        $limpio = preg_replace('/[^\d.]/', '', $valor);

        return $limpio === '' ? 0 : (float) $limpio;
    }

    protected function formatearMonto(?string $valor): string
    {
        $limpio = preg_replace('/[^\d]/', '', $valor ?? '');

        if ($limpio === '') {
            return '';
        }

        return number_format((int) $limpio, 0, '.', ',');
    }

    protected function formatearDecimal(?string $valor): string
    {
        $valor = str_replace(',', '.', $valor ?? '');
        $valor = preg_replace('/[^\d.]/', '', $valor);

        if ($valor === '') {
            return '';
        }

        $partes = explode('.', $valor, 2);
        $entero = $partes[0] === '' ? '0' : $partes[0];
        $decimal = $partes[1] ?? '';

        if ($decimal !== '') {
            return $entero . '.' . substr($decimal, 0, 2);
        }

        return $entero;
    }

    public function tasaCambio(): float
    {
        $tasa = $this->limpiarDecimal($this->tipoCambio);

        return $tasa > 0 ? $tasa : 0;
    }

    protected function tasaCambioValida(): bool
    {
        return $this->tasaCambio() > 0;
    }

    protected function pagoRequiereReferencia(string $tipoPago): bool
    {
        return in_array($tipoPago, [self::PAGO_TRANSFERENCIA, self::PAGO_TARJETA], true);
    }

    protected function seriesUsadasEnDetalle(?int $idProducto = null): array
    {
        return collect($this->detalleVenta)
            ->filter(fn ($item) => $item['tipo'] === self::TIPO_PRODUCTO)
            ->filter(fn ($item) => ! empty($item['id_producto_serie']))
            ->when($idProducto, fn ($items) => $items->filter(fn ($item) => (int) $item['id_producto'] === $idProducto))
            ->pluck('id_producto_serie')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();
    }

    protected function nombreProductoLimpio(?string $marca, ?string $nombre, ?string $modelo = null): string
    {
        $marca = trim((string) $marca);
        $nombre = trim((string) $nombre);
        $modelo = trim((string) $modelo);
        $nombreLower = strtolower($nombre);
        $marcaLower = strtolower($marca);
        $base = $nombre;

        if ($marca !== '' && ! str_starts_with($nombreLower, $marcaLower)) {
            $base = trim($marca . ' ' . $nombre);
        }

        if ($modelo !== '') {
            $base .= ' - ' . $modelo;
        }

        return trim($base);
    }

    protected function cargarSaldoFavorClienteCredito(): void
    {
        $this->saldoFavorClienteCredito = 0.00;

        if ($this->tipoVenta !== self::TIPO_CREDITO || ! $this->clienteId) {
            return;
        }

        $saldo = DB::table('cliente_credito')
            ->where('Id_Cliente', $this->clienteId)
            ->value('Saldo_Actual');

        $this->saldoFavorClienteCredito = round(max((float) ($saldo ?? 0), 0), 2);
    }

    protected function tipoClientePermitidoVenta(): int
    {
        return $this->tipoVenta === self::TIPO_CREDITO
            ? Cliente::TIPO_INSTITUCION
            : Cliente::TIPO_NATURAL;
    }

    protected function clientePermitidoParaTipoVenta(Cliente $cliente): bool
    {
        return (int) $cliente->Tipo_Cliente === $this->tipoClientePermitidoVenta();
    }

    protected function clienteSeleccionadoValidoParaVenta(): bool
    {
        if (! $this->clienteId) {
            return $this->tipoVenta === self::TIPO_CONTADO;
        }

        $cliente = Cliente::query()
            ->where('Id_Cliente', $this->clienteId)
            ->first();

        return $cliente && $this->clientePermitidoParaTipoVenta($cliente);
    }

    protected function mensajeClienteNoPermitido(): string
    {
        return $this->tipoVenta === self::TIPO_CREDITO
            ? 'Para crédito solo puede seleccionar clientes institucionales.'
            : 'Para contado solo puede seleccionar clientes naturales o consumidor final.';
    }

    protected function limpiarClienteFacturacion(): void
    {
        $this->clienteId = null;
        $this->buscarCliente = '';
        $this->clienteNombre = $this->tipoVenta === self::TIPO_CREDITO
            ? 'Seleccione institución'
            : 'Consumidor final';
        $this->departamentoMunicipio = '';
        $this->saldoFavorClienteCredito = 0.00;
        $this->clientesEncontrados = [];
        $this->mostrarClientes = false;
    }

    protected function nombreClienteFacturacion(Cliente $cliente): string
    {
        if ((int) $cliente->Tipo_Cliente === Cliente::TIPO_INSTITUCION) {
            return $cliente->Institucion ?: 'Institución';
        }

        $nombre = trim(implode(' ', array_filter([
            $cliente->persona?->Primer_Nombre,
            $cliente->persona?->Segundo_Nombre,
            $cliente->persona?->Primer_Apellido,
            $cliente->persona?->Segundo_Apellido,
        ])));

        return $nombre !== '' ? $nombre : 'Cliente';
    }

    protected function telefonoClienteFacturacion(Cliente $cliente): string
    {
        if ((int) $cliente->Tipo_Cliente === Cliente::TIPO_INSTITUCION) {
            return $cliente->Telefono_Institucion ?: 'Sin teléfono';
        }

        return $cliente->persona?->Telefono ?: 'Sin teléfono';
    }

    protected function formatoCopiaValor(?string $formato): ?int
    {
        return match ($formato) {
            'CARTA' => 1,
            'OFICIO' => 2,
            'A4' => 3,
            'LEGAL' => 4,
            default => null,
        };
    }

    protected function ladosCopiaValor(?string $lados): ?int
    {
        return match ($lados) {
            'UNA_CARA' => 1,
            'DOBLE_CARA' => 2,
            default => null,
        };
    }

    protected function obtenerOcrearClienteCreditoBloqueado(int $clienteId): object
    {
        $clienteCredito = DB::table('cliente_credito')
            ->where('Id_Cliente', $clienteId)
            ->lockForUpdate()
            ->first();

        if ($clienteCredito) {
            if ((string) $clienteCredito->Estado !== 'ACTIVO') {
                throw ValidationException::withMessages([
                    'cliente_credito' => 'El cliente crédito no está activo.',
                ]);
            }

            return $clienteCredito;
        }

        $idClienteCredito = DB::table('cliente_credito')->insertGetId([
            'Id_Cliente' => $clienteId,
            'Saldo_Actual' => 0,
            'Estado' => 'ACTIVO',
            'Fecha_Registro' => now(),
        ]);

        return DB::table('cliente_credito')
            ->where('Id_Cliente_Credito', $idClienteCredito)
            ->lockForUpdate()
            ->first();
    }

    protected function obtenerUsuarioId(): int
    {
        $authUser = auth()->user();

        if ($authUser && isset($authUser->Id_Usuario)) {
            return (int) $authUser->Id_Usuario;
        }

        $sessionId = session('Id_Usuario')
            ?? session('id_usuario')
            ?? session('usuario_id');

        if ($sessionId) {
            return (int) $sessionId;
        }

        $idUsuario = Usuario::query()->value('Id_Usuario');

        if (! $idUsuario) {
            throw ValidationException::withMessages([
                'usuario' => 'No hay usuario disponible para registrar la venta.',
            ]);
        }

        return (int) $idUsuario;
    }

    protected function generarNumeroFactura(): string
    {
        do {
            $numero = 'F-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
        } while (Venta::query()->where('Numero_Factura', $numero)->exists());

        return $numero;
    }

    // MODIFICADO: abre el modal de pendientes desde facturación.
    public function abrirModalEntregasCredito(): void
    {
        $this->modalEntregasCredito = true;
        $this->entregaMunicipio = '';
        $this->entregaClienteId = '';
        $this->entregasPendientes = [];
        $this->recibidosPendientes = [];
        $this->cargarMunicipiosEntregaCredito();
        $this->cargarInstitucionesEntregaCredito();
    }

    public function cerrarModalEntregasCredito(): void
    {
        $this->modalEntregasCredito = false;
        $this->entregaMunicipio = '';
        $this->entregaClienteId = '';
        $this->entregasPendientes = [];
        $this->recibidosPendientes = [];
    }

    private function cargarMunicipiosEntregaCredito(): void
    {
        // MODIFICADO: se cargan municipios desde clientes institucionales activos,
        // no solo desde pendientes. Así una institución nueva aparece aunque todavía no cargues la tabla.
        $municipios = DB::table('cliente as c')
            ->where('c.Estado', 1)
            ->where('c.Tipo_Cliente', Cliente::TIPO_INSTITUCION)
            ->whereNotNull('c.Municipio')
            ->whereRaw("TRIM(c.Municipio) <> ''")
            ->selectRaw('TRIM(c.Municipio) as municipio')
            ->distinct()
            ->orderBy('municipio')
            ->get()
            ->map(fn ($fila) => [
                'id' => (string) $fila->municipio,
                'name' => (string) $fila->municipio,
            ])
            ->values()
            ->toArray();

        array_unshift($municipios, ['id' => '', 'name' => 'Todos los municipios']);

        $this->entregaMunicipiosOpciones = $municipios;
    }

    private function cargarInstitucionesEntregaCredito(): void
    {
        // MODIFICADO: se cargan todas las instituciones activas del municipio seleccionado.
        // Antes solo salían instituciones con pendientes y podía ocultar clientes nuevos.
        $instituciones = DB::table('cliente as c')
            ->where('c.Estado', 1)
            ->where('c.Tipo_Cliente', Cliente::TIPO_INSTITUCION)
            ->when(trim($this->entregaMunicipio) !== '', function ($query) {
                $query->whereRaw('TRIM(c.Municipio) = ?', [trim($this->entregaMunicipio)]);
            })
            ->selectRaw("c.Id_Cliente as id, COALESCE(NULLIF(TRIM(c.Institucion), ''), CONCAT('Institución #', c.Id_Cliente)) as name")
            ->orderBy('name')
            ->get()
            ->map(fn ($fila) => [
                'id' => (string) $fila->id,
                'name' => (string) $fila->name,
            ])
            ->values()
            ->toArray();

        array_unshift($instituciones, ['id' => '', 'name' => 'Todas las instituciones']);

        $this->entregaInstitucionesOpciones = $instituciones;
    }

    public function buscarPendientesEntregaCredito(): void
    {
        $municipio = trim($this->entregaMunicipio);
        $clienteId = trim($this->entregaClienteId);

        if ($municipio === '' && $clienteId === '') {
            $this->mostrarToast('Seleccione al menos un municipio o una institución para cargar pendientes.', 'error');
            return;
        }

        if ($clienteId !== '' && ! ctype_digit($clienteId)) {
            $this->mostrarToast('La institución seleccionada no es válida.', 'error');
            return;
        }

        $pendientes = DB::table('detalle_venta as dv')
            ->join('venta as v', 'v.Id_Venta', '=', 'dv.Id_Venta')
            ->join('credito as cr', 'cr.Id_Venta', '=', 'v.Id_Venta')
            ->join('cliente as c', 'c.Id_Cliente', '=', 'v.Id_Cliente')
            ->leftJoin('producto as p', 'p.Id_Producto', '=', 'dv.Id_Producto')
            ->leftJoin('servicio as s', 's.Id_Servicio', '=', 'dv.Id_Servicio')
            ->leftJoin('tarifa_copia as tc', 'tc.Id_Tarifa_Copia', '=', 'dv.Id_Tarifa_Copia')
            ->where('v.Tipo_Venta', self::TIPO_CREDITO)
            ->where('v.Estado', 1)
            ->where('c.Estado', 1)
            ->where('c.Tipo_Cliente', Cliente::TIPO_INSTITUCION)
            ->where(function ($query) {
                $query->whereNull('dv.Recibido_Por')
                    ->orWhereRaw("TRIM(COALESCE(dv.Recibido_Por, '')) = ''");
            })
            ->when($municipio !== '', function ($query) use ($municipio) {
                $query->whereRaw('TRIM(c.Municipio) = ?', [$municipio]);
            })
            ->when($clienteId !== '', function ($query) use ($clienteId) {
                $query->where('c.Id_Cliente', (int) $clienteId);
            })
            // MODIFICADO: primero lo más reciente para que las ventas nuevas no queden escondidas por datos demo.
            ->orderByDesc('v.Fecha_venta')
            ->orderByDesc('v.Id_Venta')
            ->orderBy('c.Institucion')
            ->orderBy('dv.Id_Detalle_Venta')
            ->limit(120)
            ->select([
                'dv.Id_Detalle_Venta',
                'v.Numero_Factura',
                'v.Fecha_venta',
                'c.Institucion',
                'c.Municipio',
                'dv.Tipo_Detalle',
                'dv.Nombre_Formato',
                'dv.Formato_Copia',
                'dv.Cantidad',
                'dv.Precio_Unitario',
                'dv.Subtotal',
                'dv.Descuento',
                'dv.Observacion',
                'p.Nombre_Producto',
                'p.Modelo',
                's.Nombre_Servicio',
                'tc.Nombre_Tarifa',
            ])
            ->get()
            ->map(function ($fila) {
                $id = (int) $fila->Id_Detalle_Venta;
                $this->recibidosPendientes[$id] = $this->recibidosPendientes[$id] ?? '';

                return [
                    'id' => $id,
                    'fecha' => $fila->Fecha_venta ? \Illuminate\Support\Carbon::parse($fila->Fecha_venta)->format('d/m/Y') : '',
                    'factura' => (string) $fila->Numero_Factura,
                    'institucion' => (string) ($fila->Institucion ?: 'Institución'),
                    'municipio' => (string) ($fila->Municipio ?: '—'),
                    'tipo' => (string) $fila->Tipo_Detalle,
                    'item' => $this->nombreItemPendiente($fila),
                    'area' => (string) ($fila->Observacion ?: '—'),
                    'formato' => $this->formatoPendienteNombre($fila),
                    'cantidad' => (float) $fila->Cantidad,
                    'precio' => (float) $fila->Precio_Unitario,
                    'monto' => (float) $fila->Subtotal,
                ];
            })
            ->values()
            ->toArray();

        $this->entregasPendientes = $pendientes;

        if (count($this->entregasPendientes) === 0) {
            $this->mostrarToast('No hay pendientes de entrega para el filtro seleccionado.', 'info');
        }
    }

    private function nombreItemPendiente(object $fila): string
    {
        if ((string) $fila->Tipo_Detalle === self::TIPO_COPIA) {
            return (string) ($fila->Nombre_Formato ?: $fila->Nombre_Tarifa ?: 'Copia');
        }

        if ((string) $fila->Tipo_Detalle === self::TIPO_PRODUCTO) {
            return trim('Producto: ' . ($fila->Nombre_Producto ?: 'Producto') . ($fila->Modelo ? ' - ' . $fila->Modelo : ''));
        }

        return trim('Servicio: ' . ($fila->Nombre_Servicio ?: 'Servicio'));
    }

    private function formatoPendienteNombre(object $fila): string
    {
        if ((string) $fila->Tipo_Detalle !== self::TIPO_COPIA) {
            return '—';
        }

        return match ((int) $fila->Formato_Copia) {
            1 => 'Carta',
            2 => 'Oficio',
            3 => 'A4',
            4 => 'Legal',
            default => '—',
        };
    }


    public function guardarRecibidoPendiente(int $detalleVentaId): void
    {
        $recibidoPor = trim((string) ($this->recibidosPendientes[$detalleVentaId] ?? ''));

        if (mb_strlen($recibidoPor) < 3) {
            $this->mostrarToast('Ingrese el nombre de quien recibe.', 'error');
            return;
        }

        $existePendiente = DB::table('detalle_venta as dv')
            ->join('venta as v', 'v.Id_Venta', '=', 'dv.Id_Venta')
            ->join('credito as cr', 'cr.Id_Venta', '=', 'v.Id_Venta')
            ->join('cliente as c', 'c.Id_Cliente', '=', 'v.Id_Cliente')
            ->where('dv.Id_Detalle_Venta', $detalleVentaId)
            ->where('v.Tipo_Venta', self::TIPO_CREDITO)
            ->where('v.Estado', 1)
            ->where('c.Tipo_Cliente', Cliente::TIPO_INSTITUCION)
            ->where(function ($query) {
                $query->whereNull('dv.Recibido_Por')
                    ->orWhereRaw("TRIM(COALESCE(dv.Recibido_Por, '')) = ''");
            })
            ->exists();

        if (! $existePendiente) {
            $this->mostrarToast('Este pendiente ya fue entregado o no existe.', 'warning');
            $this->buscarPendientesEntregaCredito();
            return;
        }

        DB::table('detalle_venta')
            ->where('Id_Detalle_Venta', $detalleVentaId)
            ->update([
                'Recibido_Por' => Str::limit($recibidoPor, 150, ''),
            ]);

        unset($this->recibidosPendientes[$detalleVentaId]);
        $this->mostrarToast('Pendiente marcado como entregado correctamente.');
        $this->buscarPendientesEntregaCredito();
        $this->cargarMunicipiosEntregaCredito();
        $this->cargarInstitucionesEntregaCredito();
    }

    protected function mostrarToast(string $mensaje, string $tipo = 'success'): void
    {
        match ($tipo) {
            'error' => $this->error($mensaje, position: 'toast-top toast-end', timeout: 3500),
            'warning' => $this->warning($mensaje, position: 'toast-top toast-end', timeout: 3000),
            'info' => $this->info($mensaje, position: 'toast-top toast-end', timeout: 2500),
            default => $this->success($mensaje, position: 'toast-top toast-end', timeout: 2500),
        };
    }
};
?>

<div class="min-h-[calc(100vh-3rem)] w-full overflow-x-hidden bg-[#F0F3F7] px-2 py-3 md:px-3 md:py-4">
    <div class="mx-0 flex w-full max-w-none flex-col gap-4">

        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold leading-tight text-[#1A2B42] md:text-3xl">Facturación</h1>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    {{ $tipoVenta === 'CONTADO' ? 'Venta al contado con tasa única y referencias de pago.' : 'Venta al
                    crédito sin cobro inicial.' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button icon="o-document-text" label="Cotización" wire:click="generarCotizacion"
                    spinner="generarCotizacion"
                    class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] shadow-sm hover:bg-[#F8FAFC]" />

                <x-button icon="o-truck" label="Entregar pendientes" wire:click="abrirModalEntregasCredito"
                    spinner="abrirModalEntregasCredito"
                    class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] shadow-sm hover:bg-[#F8FAFC]" />

                <button type="button" wire:click="cambiarTipoVenta('CONTADO')"
                    class="{{ $tipoVenta === 'CONTADO' ? 'bg-[#0B6FE4] text-white shadow-sm' : 'bg-white text-[#1A2B42]' }} inline-flex h-10 items-center justify-center rounded-xl border border-[#D7E4F3] px-5 text-sm font-semibold transition">
                    Contado
                </button>

                <button type="button" wire:click="cambiarTipoVenta('CREDITO')"
                    class="{{ $tipoVenta === 'CREDITO' ? 'bg-[#0B6FE4] text-white shadow-sm' : 'bg-white text-[#1A2B42]' }} inline-flex h-10 items-center justify-center rounded-xl border border-[#D7E4F3] px-5 text-sm font-semibold transition">
                    Crédito
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_280px]">
            <div class="min-w-0 space-y-4">

                <x-card class="rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
                    <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-[#1A2B42]">Datos de la venta</h2>
                        </div>

                        <span
                            class="{{ $tipoVenta === 'CREDITO' ? 'bg-[#EAF2FB] text-[#0B6FE4]' : 'bg-emerald-50 text-emerald-700' }} w-fit rounded-full px-3 py-1 text-xs font-bold">
                            {{ $tipoVenta === 'CREDITO' ? 'Crédito institucional' : 'Venta contado' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                        <div
                            class="relative min-w-0 {{ $tipoVenta === 'CREDITO' ? 'lg:col-span-5' : 'lg:col-span-7' }}">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">
                                {{ $tipoVenta === 'CREDITO' ? 'Institución' : 'Cliente natural' }}
                            </label>

                            <div class="flex gap-2">
                                <x-input wire:model.live.debounce.250ms="buscarCliente" type="text" autocomplete="off"
                                    placeholder="{{ $tipoVenta === 'CREDITO' ? 'Buscar institución' : 'Buscar cliente natural' }}"
                                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

                                @if ($tipoVenta === 'CONTADO')
                                <button type="button" wire:click="usarConsumidorFinal"
                                    class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] shadow-sm transition hover:bg-[#F8FAFC]">
                                    Final
                                </button>
                                @endif
                            </div>

                            @if ($mostrarClientes)
                            <div
                                class="absolute left-0 right-0 top-full z-50 mt-1 max-h-64 overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                                @foreach ($clientesEncontrados as $cliente)
                                <button type="button" wire:key="cliente-{{ $cliente['id'] }}"
                                    wire:click="seleccionarCliente({{ $cliente['id'] }})"
                                    class="flex w-full flex-col border-b border-[#EAF2FB] px-4 py-3 text-left transition hover:bg-[#EAF4FD] last:border-b-0">
                                    <span class="truncate text-sm font-semibold text-[#1A2B42]">
                                        {{ $cliente['nombre'] }}
                                    </span>
                                    <span class="text-xs text-[#5F6B7A]">
                                        {{ $cliente['telefono'] }}
                                        @if($cliente['municipio']) · {{ $cliente['municipio'] }} @endif
                                    </span>
                                </button>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <div class="min-w-0 {{ $tipoVenta === 'CREDITO' ? 'lg:col-span-3' : 'lg:col-span-5' }}">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">
                                Cliente seleccionado
                            </label>
                            <div
                                class="flex h-11 items-center rounded-xl border border-[#D7E4F3] bg-[#EAF2FB] px-3 text-sm font-semibold text-[#1A2B42]">
                                <span class="truncate">{{ $clienteNombre }}</span>
                            </div>
                        </div>

                        @if ($tipoVenta === 'CREDITO')
                        <div class="min-w-0 lg:col-span-4">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">
                                Departamento / municipio
                            </label>
                            <x-input wire:model="departamentoMunicipio" type="text" placeholder="Ingrese municipio"
                                class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                        </div>
                        @endif
                    </div>
                </x-card>

                <x-card class="rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
                    <div class="mb-4">
                        <h2 class="text-lg font-bold text-[#1A2B42]">Agregar a la venta</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
                        <div
                            class="relative min-w-0 {{ $tipoVenta === 'CONTADO' ? 'xl:col-span-3' : 'xl:col-span-4' }}">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Buscar producto</label>
                            <x-input wire:model.live.debounce.250ms="buscarItem" type="text" autocomplete="off"
                                placeholder="Producto, serie o copia"
                                class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

                            @if ($mostrarItems)
                            <div
                                class="absolute left-0 right-0 top-full z-50 mt-1 max-h-72 overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                                @foreach ($resultadosItems as $item)
                                <button type="button"
                                    wire:key="item-{{ $item['tipo'] }}-{{ $item['id'] }}-{{ $item['serie_id'] ?? 0 }}"
                                    wire:click="seleccionarItem('{{ $item['tipo'] }}', {{ $item['id'] }}, {{ $item['serie_id'] ?? 'null' }})"
                                    class="flex w-full items-center justify-between gap-3 border-b border-[#EAF2FB] px-4 py-3 text-left hover:bg-[#EAF4FD] last:border-b-0">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-[#1A2B42]">{{
                                            $item['titulo'] }}</span>
                                        <span class="block truncate text-xs text-[#5F6B7A]">{{ $item['subtitulo']
                                            }}</span>
                                    </span>
                                    <span
                                        class="shrink-0 rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-bold text-[#0B6FE4]">
                                        {{ $item['precio_texto'] }}
                                    </span>
                                </button>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <div class="min-w-0 xl:col-span-3">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Seleccionado</label>
                            <div
                                class="flex h-11 items-center rounded-xl bg-[#EAF2FB] px-3 text-sm font-semibold text-[#1A2B42]">
                                <span class="truncate">{{ $descripcionSeleccionada ?: 'Ningún item' }}</span>
                            </div>
                        </div>


                        <div class="min-w-0 xl:col-span-1">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Stock</label>
                            <x-input wire:model="stockDisponible" readonly
                                class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#EAF2FB] text-center text-sm text-[#1A2B42]" />
                        </div>

                        <div class="min-w-0 xl:col-span-1">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Cant.</label>
                            <x-input wire:model="cantidadItem" type="number" min="1"
                                class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-center text-sm text-[#1A2B42]" />
                        </div>

                        <div class="min-w-0 xl:col-span-2">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Precio</label>
                            <x-input wire:model.live.debounce.250ms="precioItem" type="text" inputmode="numeric"
                                placeholder="{{ $tipoItemSeleccionado === 'COPIA' ? 'Manual' : '0' }}"
                                class="h-11 min-h-11 w-full min-w-30 rounded-xl border-0 bg-[#F0F3F7] px-3 text-right text-sm font-semibold text-[#1A2B42] placeholder:text-left placeholder:font-normal placeholder:text-[#7B8794]" />
                        </div>

                        <div class="min-w-0 xl:col-span-1">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Desc.</label>
                            <x-input wire:model.live.debounce.250ms="descuentoItem" type="text" inputmode="numeric"
                                placeholder="0"
                                class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        </div>

                        @if ($tipoVenta === 'CONTADO')
                        <div class="xl:col-span-1">
                            <label class="mb-1.5 block text-sm font-semibold text-transparent">Acción</label>
                            <x-button label="+" wire:click="agregarItem"
                                class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#2E8BC0] text-lg font-bold text-white shadow-sm hover:bg-[#0B6FE4]" />
                        </div>
                        @endif
                    </div>

                    @if (count($seriesDisponibles) > 0)
                    <div class="mt-3 grid grid-cols-1 gap-3 xl:grid-cols-12">
                        <div class="min-w-0 xl:col-span-4">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Serie</label>
                            <x-select wire:model="serieProductoId" :options="$seriesDisponibles" option-value="id"
                                option-label="name" placeholder="Seleccione serie"
                                class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        </div>
                    </div>
                    @endif

                    <div
                        class="mt-4 rounded-2xl border border-[#D7E4F3] bg-linear-to-br from-[#F8FBFF] to-white p-3 shadow-[0_8px_24px_rgba(11,111,228,0.04)]">
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wide text-[#1A2B42]">
                                    Copias rápidas
                                </h3>
                            </div>

                            <span class="w-fit rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-bold text-[#0B6FE4]">
                                Precio manual
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-3 xl:grid-cols-12 xl:items-end">
                            <div class="min-w-0 {{ $tipoVenta === 'CREDITO' ? 'xl:col-span-6' : 'xl:col-span-5' }}">
                                <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">
                                    Copia rápida
                                </label>

                                <div class="flex gap-2">
                                    <div class="min-w-0 flex-1">
                                        <x-select wire:model.live="copiaRapidaId" :options="$copiasRapidas"
                                            option-value="id" option-label="name" placeholder="Seleccione una copia"
                                            class="h-11 min-h-11 w-full rounded-xl border-0 bg-white text-sm text-[#1A2B42]" />
                                    </div>

                                    <x-button icon="o-plus" label="Nueva" wire:click="abrirModalNuevaCopiaRapida"
                                        class="h-11 min-h-11 shrink-0 rounded-xl border border-[#D7E4F3] bg-white px-3 text-sm font-semibold text-[#0E48A1] shadow-sm transition hover:bg-[#EAF4FD]" />
                                </div>
                            </div>

                            <div class="min-w-0 xl:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">
                                    Cantidad
                                </label>
                                <x-input wire:model="cantidadCopiaRapida" type="number" min="1"
                                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-white text-center text-sm font-semibold text-[#1A2B42]" />
                            </div>

                            <div class="min-w-0 {{ $tipoVenta === 'CREDITO' ? 'xl:col-span-4' : 'xl:col-span-2' }}">
                                <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">
                                    Precio unit.
                                </label>
                                <x-input wire:model.live.debounce.250ms="precioCopiaRapida" type="text"
                                    inputmode="numeric" placeholder="Ej. 2"
                                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-white text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                            </div>

                            @if ($tipoVenta === 'CONTADO')
                            <div class="xl:col-span-3">
                                <x-button icon="o-plus-circle" label="Agregar copia" wire:click="agregarCopiaRapida"
                                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#0E48A1] text-sm font-semibold text-white shadow-sm transition hover:bg-[#0B6FE4]" />
                            </div>
                            @endif
                        </div>
                    </div>

                    @if ($tipoVenta === 'CREDITO')
                    <div class="mt-4 rounded-2xl border border-[#D7E4F3] bg-white p-3 shadow-sm">
                        <div class="grid grid-cols-1 gap-3 xl:grid-cols-12 xl:items-end">
                            <div class="min-w-0 xl:col-span-6">
                                <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Área del item</label>
                                <x-input wire:model.live.debounce.250ms="areaItem" type="text" maxlength="255"
                                    placeholder="Ej. Contabilidad"
                                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                            </div>

                            <div class="xl:col-span-3">
                                <x-button icon="o-plus" label="Agregar item" wire:click="agregarItem"
                                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#2E8BC0] text-sm font-semibold text-white shadow-sm transition hover:bg-[#0B6FE4]" />
                            </div>

                            <div class="xl:col-span-3">
                                <x-button icon="o-plus-circle" label="Agregar copia" wire:click="agregarCopiaRapida"
                                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#0E48A1] text-sm font-semibold text-white shadow-sm transition hover:bg-[#0B6FE4]" />
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="mt-4 rounded-2xl border border-[#D7E4F3] bg-white p-3 shadow-sm">
                        <h3 class="mb-2 text-sm font-black uppercase tracking-wide text-[#1A2B42]">
                            Observación general
                        </h3>

                        <x-textarea wire:model.live.debounce.300ms="observacionVenta" rows="2" maxlength="255"
                            placeholder="Ejemplo: entregar por la tarde, nota interna o comentario general"
                            class="min-h-20 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                    </div>
                </x-card>

                <x-card class="rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-[#1A2B42]">Detalle de venta</h2>
                        <span class="rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-semibold text-[#0B6FE4]">
                            {{ count($detalleVenta) }} items
                        </span>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-[#D7E4F3] bg-white">
                        <div class="w-full overflow-x-auto">
                            <table class="min-w-225 w-full border-separate border-spacing-0 text-[13px] text-[#1A2B42]">
                                <thead>
                                    <tr>
                                        <th
                                            class="rounded-tl-xl bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">
                                            Código</th>
                                        <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">
                                            Descripción</th>
                                        <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">
                                            Área</th>
                                        <th class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">Tipo
                                        </th>
                                        <th class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">Cant.
                                        </th>
                                        <th class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white">Precio
                                        </th>
                                        <th class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white">Desc.
                                        </th>
                                        <th class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white">Subtotal
                                        </th>
                                        <th
                                            class="rounded-tr-xl bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">
                                            Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($detalleVenta as $item)
                                    <tr class="odd:bg-white even:bg-[#F8FBFF]">
                                        <td class="whitespace-nowrap px-3 py-3 font-semibold">{{ $item['codigo'] }}</td>
                                        <td class="px-3 py-3">{{ $item['descripcion'] }}</td>
                                        <td class="min-w-36 px-3 py-3 text-sm text-[#1A2B42]">
                                            {{ $item['area'] ?? '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <span
                                                class="{{ $item['tipo'] === 'COPIA' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }} rounded-full px-2.5 py-1 text-xs font-semibold">
                                                {{ $item['tipo'] }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 text-center">{{
                                            number_format($item['cantidad'], 0, '.', ',') }}</td>
                                        <td class="whitespace-nowrap px-3 py-3 text-right">C$ {{
                                            number_format($item['precio_unitario'], 0, '.', ',') }}</td>
                                        <td class="whitespace-nowrap px-3 py-3 text-right text-red-600">C$ {{
                                            number_format($item['descuento_valor'] ?? 0, 0, '.', ',') }}</td>
                                        <td class="whitespace-nowrap px-3 py-3 text-right font-semibold">C$ {{
                                            number_format($item['subtotal_valor'], 0, '.', ',') }}</td>
                                        <td class="px-3 py-3 text-center">
                                            <button type="button" wire:click="eliminarDetalle('{{ $item['uid'] }}')"
                                                class="rounded-lg bg-red-50 px-3 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-100">
                                                Quitar
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-10 text-center text-sm text-[#7B8794]">
                                            No hay items agregados.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-card>
            </div>

            <div class="min-w-0 xl:sticky xl:top-4 xl:self-start">
                <x-card class="rounded-2xl border border-[#D7E4F3] bg-white p-3 shadow-sm">
                    <div class="mb-3">
                        <h2 class="text-lg font-bold text-[#1A2B42]">Resumen</h2>
                        <p class="text-xs text-[#5F6B7A]">Totales de la venta.</p>
                    </div>

                    <div class="space-y-2">
                        <div class="rounded-xl bg-[#F0F3F7] px-3 py-2 text-[#1A2B42]">
                            <span class="block text-xs text-[#5F6B7A]">Subtotal</span>
                            <strong class="block text-base">C$ {{ number_format($this->subtotalVenta(), 0, '.', ',')
                                }}</strong>
                        </div>

                        <div class="rounded-xl bg-red-50 px-3 py-2 text-red-700">
                            <span class="block text-xs">Descuentos</span>
                            <strong class="block text-base">C$ {{ number_format($this->descuentoVenta(), 0, '.', ',')
                                }}</strong>
                        </div>

                        <div class="rounded-xl bg-[#EAF2FB] px-3 py-3 text-[#0B6FE4]">
                            <span class="block text-xs font-semibold">Total</span>
                            <strong class="block text-2xl">C$ {{ number_format($this->totalVenta(), 0, '.', ',')
                                }}</strong>
                        </div>

                        <div class="rounded-xl bg-[#F8FBFF] px-3 py-2 text-[#1A2B42]">
                            <span class="block text-xs text-[#5F6B7A]">Tasa de cambio</span>
                            <strong class="block text-base">TC {{ number_format($this->tasaCambio(), 2, '.', ',')
                                }}</strong>
                        </div>

                        <div class="grid grid-cols-1 gap-2 pt-1">
                            <x-button :label="$tipoVenta === 'CONTADO' ? 'Cobrar' : 'Guardar crédito'"
                                wire:click="abrirModalCobro"
                                class="h-10 min-h-10 rounded-xl border-0 bg-[#2E8BC0] px-3 text-sm font-semibold text-white shadow-sm hover:bg-[#0B6FE4]" />

                            <x-button label="Cancelar" wire:click="cancelarVenta"
                                class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white px-3 text-sm font-semibold text-[#1A2B42] hover:bg-[#F5F9FC]" />
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    <x-modal wire:model="modalEntregasCredito" class="backdrop-blur-sm"
        box-class="w-[96vw] max-w-7xl max-h-[92vh] overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">

        <div class="flex max-h-[88vh] flex-col">
            <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0">
                    <h3 class="text-2xl font-bold text-[#1A2B42]">Entregar pendientes de crédito</h3>
                    <p class="mt-1 text-sm text-[#5F6B7A]">
                        Filtre por municipio o institución y registre el nombre de quien recibe cada pendiente.
                    </p>
                </div>

                <span class="w-fit rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-bold text-[#0B6FE4]">
                    {{ count($entregasPendientes) }} pendientes cargados
                </span>
            </div>

            <div class="mb-4 rounded-2xl border border-[#D7E4F3] bg-[#F8FBFF] p-3">
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-4">
                        <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Municipio</label>
                        <x-select wire:model.live="entregaMunicipio" :options="$entregaMunicipiosOpciones"
                            option-value="id" option-label="name"
                            class="h-11 min-h-11 rounded-xl bg-white text-sm text-[#1A2B42]" />
                    </div>

                    <div class="lg:col-span-5">
                        <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Institución</label>
                        <x-select wire:model.live="entregaClienteId" :options="$entregaInstitucionesOpciones"
                            option-value="id" option-label="name"
                            class="h-11 min-h-11 rounded-xl bg-white text-sm text-[#1A2B42]" />
                    </div>

                    <div class="lg:col-span-3">
                        <x-button icon="o-magnifying-glass" label="Cargar pendientes"
                            wire:click="buscarPendientesEntregaCredito" spinner="buscarPendientesEntregaCredito"
                            class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#2E8BC0] text-sm font-semibold text-white shadow-sm hover:bg-[#0B6FE4]" />
                    </div>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto rounded-2xl border border-[#D7E4F3] bg-[#F8FBFF] p-3">
                <div class="space-y-3">
                    @forelse ($entregasPendientes as $pendiente)
                    <div wire:key="pendiente-entrega-{{ $pendiente['id'] }}"
                        class="rounded-2xl border border-[#D7E4F3] bg-white p-3 shadow-sm">
                        <div class="grid grid-cols-1 gap-3 xl:grid-cols-12 xl:items-center">
                            <div class="xl:col-span-2">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-[#5F6B7A]">Factura</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">{{ $pendiente['factura'] }}</p>
                                <p class="mt-1 text-xs font-semibold text-[#5F6B7A]">{{ $pendiente['fecha'] }}</p>
                            </div>

                            <div class="xl:col-span-3">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-[#5F6B7A]">Institución</p>
                                <p class="mt-1 text-sm font-bold text-[#1A2B42]">{{ $pendiente['institucion'] }}</p>
                                <p class="mt-1 text-xs font-semibold text-[#5F6B7A]">{{ $pendiente['municipio'] }}</p>
                            </div>

                            <div class="xl:col-span-3">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-[#5F6B7A]">Nombre del
                                    formato</p>
                                <p class="mt-1 text-sm font-bold leading-5 text-[#1A2B42]">{{ $pendiente['item'] }}</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <span
                                        class="rounded-full bg-[#EAF2FB] px-2 py-1 text-[11px] font-black text-[#0B6FE4]">
                                        {{ $pendiente['tipo'] }}
                                    </span>
                                    <span
                                        class="rounded-full bg-[#F0F3F7] px-2 py-1 text-[11px] font-black text-[#1A2B42]">
                                        Cant: {{ number_format($pendiente['cantidad'], 0, '.', ',') }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs text-[#5F6B7A]">
                                    Área: <span class="font-semibold text-[#1A2B42]">{{ $pendiente['area'] }}</span>
                                </p>
                            </div>

                            <div class="xl:col-span-2">
                                <div class="grid grid-cols-2 gap-2 text-center sm:grid-cols-4 xl:grid-cols-2">
                                    <div class="rounded-xl bg-[#F0F3F7] px-2 py-2">
                                        <p class="text-[10px] font-bold uppercase text-[#5F6B7A]">Tamaño</p>
                                        <p class="text-sm font-black text-[#1A2B42]">{{ $pendiente['formato'] }}</p>
                                    </div>
                                    <div class="rounded-xl bg-[#F0F3F7] px-2 py-2">
                                        <p class="text-[10px] font-bold uppercase text-[#5F6B7A]">Cant.</p>
                                        <p class="text-sm font-black text-[#1A2B42]">{{
                                            number_format($pendiente['cantidad'], 0, '.', ',') }}</p>
                                    </div>
                                    <div class="rounded-xl bg-[#F0F3F7] px-2 py-2">
                                        <p class="text-[10px] font-bold uppercase text-[#5F6B7A]">P/Unit</p>
                                        <p class="text-sm font-black text-[#1A2B42]">C$ {{
                                            number_format($pendiente['precio'], 0, '.', ',') }}</p>
                                    </div>
                                    <div class="rounded-xl bg-[#EAF2FB] px-2 py-2">
                                        <p class="text-[10px] font-bold uppercase text-[#0B6FE4]">Monto</p>
                                        <p class="text-sm font-black text-[#1A2B42]">C$ {{
                                            number_format($pendiente['monto'], 0, '.', ',') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="xl:col-span-2">
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-[#5F6B7A]">
                                    Recibí conforme
                                </label>
                                <div class="flex flex-col gap-2 sm:flex-row xl:flex-col 2xl:flex-row">
                                    <x-input wire:model.live.debounce.250ms="recibidosPendientes.{{ $pendiente['id'] }}"
                                        type="text" placeholder="Nombre de quien recibe"
                                        class="h-10 min-h-10 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />

                                    <x-button label="Guardar"
                                        wire:click="guardarRecibidoPendiente({{ $pendiente['id'] }})"
                                        spinner="guardarRecibidoPendiente({{ $pendiente['id'] }})"
                                        class="h-10 min-h-10 shrink-0 rounded-xl border-0 bg-[#0E48A1] px-4 text-xs font-semibold text-white hover:bg-[#0B6FE4]" />
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="rounded-2xl border border-dashed border-[#D7E4F3] bg-white px-4 py-14 text-center">
                        <p class="text-sm font-bold text-[#1A2B42]">No hay pendientes cargados.</p>
                        <p class="mt-1 text-sm text-[#7B8794]">
                            Seleccione un municipio o institución y presione “Cargar pendientes”.
                        </p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" type="button" wire:click="cerrarModalEntregasCredito"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalNuevaCopiaRapida" class="backdrop-blur-sm"
        box-class="w-full max-w-xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">

        <div class="mb-5">
            <h3 class="text-2xl font-bold text-[#1A2B42]">Nueva copia rápida</h3>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Se guardará en la base de datos para volver a usarla después. El precio se ingresa manualmente al
                vender.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Nombre</label>
                <x-input wire:model.live.debounce.250ms="nuevaCopiaNombre" type="text" maxlength="150"
                    placeholder="Ej. Copia tamaño carta"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Color</label>
                <x-select wire:model="nuevaCopiaTipoColor" :options="$opcionesTipoColorCopia" option-value="id"
                    option-label="name"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Formato</label>
                <x-select wire:model="nuevaCopiaFormato" :options="$opcionesFormatoCopia" option-value="id"
                    option-label="name"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Lados</label>
                <x-select wire:model="nuevaCopiaLados" :options="$opcionesLadosCopia" option-value="id"
                    option-label="name"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                <p class="mt-2 rounded-xl bg-[#F8FBFF] px-3 py-2 text-xs text-[#5F6B7A]">
                    Si ya existe una copia con el mismo color, formato y lados, se actualizará su nombre y quedará
                    activa.
                </p>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" type="button" wire:click="cerrarModalNuevaCopiaRapida"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />

            <x-button label="Guardar copia" type="button" wire:click="guardarNuevaCopiaRapida"
                class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalCotizacionRapida" class="backdrop-blur-sm"
        box-class="w-full max-w-6xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">

        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div class="min-w-0">
                <h3 class="text-2xl font-bold text-[#1A2B42]">Vista previa de cotización</h3>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    {{ $cotizacionNumero ?: 'Cotización' }} · Revise antes de compartir o imprimir.
                </p>
            </div>

            @if ($cotizacionPreviewUrl !== '')
            <a href="{{ $cotizacionPreviewUrl }}" target="_blank"
                class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] shadow-sm hover:bg-[#F8FAFC]">
                Abrir en pestaña
            </a>
            @endif
        </div>

        <div class="overflow-hidden rounded-2xl border border-[#D7E4F3] bg-[#F8FBFF]">
            @if ($cotizacionPreviewUrl !== '')
            <iframe src="{{ $cotizacionPreviewUrl }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" loading="eager"
                class="h-[76vh] w-full bg-white"></iframe>
            @else
            <div class="px-4 py-16 text-center text-sm text-[#7B8794]">No hay cotización para mostrar.</div>
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" type="button" wire:click="cerrarModalCotizacionRapida"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalVoucherVenta" class="backdrop-blur-sm"
        box-class="w-full max-w-md rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">

        <div class="mb-4">
            <h3 class="text-2xl font-bold text-[#1A2B42]">Voucher de venta</h3>
            <p class="mt-1 text-sm text-[#5F6B7A]">Revise el voucher. Solo se imprimirá si confirma la impresión.</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-[#D7E4F3] bg-[#F8FBFF]">
            @if ($voucherPreviewUrl !== '')
            <iframe src="{{ $voucherPreviewUrl }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" loading="eager"
                class="h-[68vh] w-full bg-white"></iframe>
            @else
            <div class="px-4 py-12 text-center text-sm text-[#7B8794]">No hay voucher para mostrar.</div>
            @endif
        </div>

        <x-slot:actions>
            <x-button label="No imprimir" type="button" wire:click="cerrarModalVoucherVenta"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />

            <x-button label="Imprimir voucher" type="button" wire:click="imprimirVoucherVenta"
                class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalCobro" class="backdrop-blur-sm"
        box-class="w-full max-w-2xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">

        <div class="mb-5">
            <h3 class="text-2xl font-bold text-[#1A2B42]">
                {{ $tipoVenta === 'CONTADO' ? 'Finalizar venta' : 'Guardar venta al crédito' }}
            </h3>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                {{ $tipoVenta === 'CONTADO' ? 'Confirme el cobro antes de guardar.' : 'Esta venta se guardará como
                crédito completo. El cobro se realizará desde abonos.' }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            @if ($tipoVenta === 'CONTADO')
            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tasa de cambio oficial</label>
                <x-input wire:model="tipoCambio" type="text" inputmode="decimal" readonly
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#EAF2FB] text-sm font-semibold text-[#1A2B42]" />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cambio</label>
                <div
                    class="{{ $this->cambioVenta() < 0 ? 'bg-red-50 text-red-700' : 'bg-[#EAF2FB] text-[#1A2B42]' }} flex h-11 items-center rounded-xl px-3 text-sm font-bold">
                    C$ {{ number_format($this->cambioVenta(), 0, '.', ',') }}
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo pago C$</label>
                <select wire:model.live="tipoPagoCordobas"
                    class="h-11 w-full rounded-xl border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                    <option value="EFECTIVO">Efectivo</option>
                    <option value="TRANSFERENCIA">Transferencia</option>
                    <option value="TARJETA">Tarjeta</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Pago C$</label>
                <x-input wire:model.live.debounce.250ms="pagoCordobas" type="text" inputmode="numeric"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
            </div>

            @if (in_array($tipoPagoCordobas, ['TRANSFERENCIA', 'TARJETA'], true))
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Referencia C$</label>
                <x-input wire:model.live.debounce.250ms="referenciaCordobas" type="text"
                    placeholder="{{ $tipoPagoCordobas === 'TRANSFERENCIA' ? 'Número de transferencia' : 'Número de autorización / voucher' }}"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
            </div>
            @endif

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo pago US$</label>
                <select wire:model.live="tipoPagoDolares"
                    class="h-11 w-full rounded-xl border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                    <option value="EFECTIVO">Efectivo</option>
                    <option value="TRANSFERENCIA">Transferencia</option>
                    <option value="TARJETA">Tarjeta</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Pago US$</label>
                <x-input wire:model.live.debounce.250ms="pagoDolares" type="text" inputmode="decimal"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
            </div>

            @if (in_array($tipoPagoDolares, ['TRANSFERENCIA', 'TARJETA'], true))
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Referencia US$</label>
                <x-input wire:model.live.debounce.250ms="referenciaDolares" type="text"
                    placeholder="{{ $tipoPagoDolares === 'TRANSFERENCIA' ? 'Número de transferencia' : 'Número de autorización / voucher' }}"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
            </div>
            @endif

            <div
                class="md:col-span-2 grid grid-cols-1 gap-2 rounded-xl bg-[#F8FBFF] px-4 py-3 text-sm text-[#1A2B42] md:grid-cols-3">
                <div><span class="block text-xs text-[#5F6B7A]">Recibido C$</span><strong>C$ {{ $pagoCordobas !== '' ?
                        $pagoCordobas : '0' }}</strong></div>
                <div><span class="block text-xs text-[#5F6B7A]">Recibido US$</span><strong>US$ {{ $pagoDolares !== '' ?
                        $pagoDolares : '0' }}</strong></div>
                <div><span class="block text-xs text-[#5F6B7A]">Cambio entregado</span><strong>C$ {{
                        number_format($this->cambioEntregadoCordobas(), 0, '.', ',') }}</strong></div>
            </div>
            @endif

            @if ($tipoVenta === 'CREDITO')
            <div class="md:col-span-2 rounded-xl border border-[#B7D6F2] bg-[#EAF4FD] px-4 py-4 text-sm text-[#1A2B42]">
                Se aplicará automáticamente el saldo a favor disponible del cliente crédito. Si cubre todo el total, el
                crédito quedará cancelado.
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Saldo a favor</label>
                <div class="flex h-11 items-center rounded-xl bg-[#F0F3F7] px-3 text-sm font-semibold text-[#1A2B42]">
                    C$ {{ number_format($saldoFavorClienteCredito, 0, '.', ',') }}
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Aplicado</label>
                <div class="flex h-11 items-center rounded-xl bg-[#EAF2FB] px-3 text-sm font-semibold text-[#0B6FE4]">
                    C$ {{ number_format($this->saldoFavorAplicable(), 0, '.', ',') }}
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Saldo crédito</label>
                <div class="flex h-11 items-center rounded-xl bg-[#EAF2FB] px-3 text-sm font-semibold text-[#1A2B42]">
                    C$ {{ number_format($this->saldoCredito(), 0, '.', ',') }}
                </div>
            </div>
            @endif

            <div class="md:col-span-2">
                <div class="rounded-xl bg-[#F8FBFF] px-4 py-3 text-sm text-[#1A2B42]">
                    Cliente: <strong>{{ $clienteNombre }}</strong><br>

                    @if ($this->observacionVentaNormalizada())
                    Observación: <strong>{{ $this->observacionVentaNormalizada() }}</strong><br>
                    @endif

                    @if ($tipoVenta === 'CREDITO')
                    Municipio: <strong>{{ $departamentoMunicipio ?: 'No especificado' }}</strong><br>
                    @endif

                    Subtotal: <strong>C$ {{ number_format($this->subtotalVenta(), 0, '.', ',') }}</strong><br>
                    Descuentos: <strong>C$ {{ number_format($this->descuentoVenta(), 0, '.', ',') }}</strong><br>
                    Total venta: <strong>C$ {{ number_format($this->totalVenta(), 0, '.', ',') }}</strong>

                    @if ($tipoVenta === 'CREDITO')
                    <br>
                    Saldo a favor aplicado: <strong>C$ {{ number_format($this->saldoFavorAplicable(), 0, '.', ',')
                        }}</strong><br>
                    Saldo pendiente: <strong>C$ {{ number_format($this->saldoCredito(), 0, '.', ',') }}</strong>
                    @endif

                    @if ($tipoVenta === 'CONTADO')
                    <br>
                    Tipo cambio: <strong>C$ {{ number_format($this->tasaCambio(), 2, '.', ',') }}</strong>
                    @endif
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Volver" type="button" wire:click="cerrarModalCobro"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />

            <x-button :label="$tipoVenta === 'CONTADO' ? 'Guardar venta' : 'Guardar crédito'" type="button"
                wire:click="guardarVenta" class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]" />
        </x-slot:actions>
    </x-modal>
</div>
