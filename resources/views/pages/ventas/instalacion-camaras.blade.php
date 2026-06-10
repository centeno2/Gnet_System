<?php

use Livewire\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mary\Traits\Toast;

use App\Models\Cliente;
use App\Models\Trabajador;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Servicio;
use App\Models\Usuario;
use App\Models\MovimientoInventario;
use App\Models\Venta;
use App\Models\ContratoInstalacionCamara;
use App\Models\ContratoInstalacionCamaraChecklist;
use App\Models\ContratoInstalacionCamaraProducto;

new class extends Component
{
    // MODIFICADO: usamos los toast temporales nativos de MaryUI.
    use Toast;

    private const MONEDA_CORDOBA = 'NIO';
    private const MONEDA_DOLAR = 'USD';

    private const PAGO_EFECTIVO = 'EFECTIVO';
    private const PAGO_TRANSFERENCIA = 'TRANSFERENCIA';
    private const PAGO_TARJETA = 'TARJETA';

    private const TIPO_CONTADO = 'CONTADO';
    private const TIPO_CREDITO = 'CREDITO';

    // MODIFICADO: se consulta toda la base por búsqueda, pero solo se renderiza un bloque pequeño.
    private const TIPO_CLIENTE_NATURAL = 1;
    private const TIPO_CLIENTE_INSTITUCION = 2;
    private const RESULTADOS_BUSQUEDA_SELECT = 75;
    private const CLIENTES_POR_PAGINA = 15;
    private const PRODUCTOS_POR_PAGINA = 15;
    private const PENDIENTES_POR_PAGINA = 12;

    private const ESTADO_CREDITO_PENDIENTE = 'PENDIENTE';
    private const ESTADO_CREDITO_CANCELADO = 'CANCELADO';

    public array $clientes = [];
    public array $tecnicos = [];
    public array $productosDisponibles = [];
    public array $seriesDisponibles = [];
    public array $productosUsados = [];
    public array $contratosPendientes = [];

    // MODIFICADO: búsqueda dinámica de clientes. No se cargan miles de registros al snapshot.
    public string $filtroCliente = '';
    public bool $mostrarBusquedaClientes = false;
    public bool $hayMasClientes = false;
    public int $paginaBusquedaClientes = 1;
    public int $totalClientesBusqueda = 0;
    public string $clienteSeleccionadoNombre = '';

    // MODIFICADO: productos filtrados desde base de datos para evitar payloads pesados.
    public string $filtroProducto = '';

    public int $paginaPendientes = 1;
    public int $totalPendientes = 0;
    public int $totalPaginasPendientes = 1;

    public ?int $contratoInstalacionIdSeleccionado = null;

    public bool $modalPendientes = false;
    public string $filtroPendientes = '';

    public string $tipoOperacion = self::TIPO_CONTADO;

    public ?int $clienteId = null;
    public string $telefonoCliente = '';
    public string $municipio = '';
    public ?int $tecnicoId = null;

    public $cantidadCamaras = 0;
    public $metrosCableado = 0;
    public $costoManoObra = 0;
    public $porcentajeAnticipo = 30;

    public ?string $fechaEstimada = null;
    public string $direccionInstalacion = '';
    public string $detalleContrato = '';
    public string $estadoContrato = 'PENDIENTE';

    public string $tipoCambio = '36.50';
    public string $tipoPagoCordobas = self::PAGO_EFECTIVO;
    public string $tipoPagoDolares = self::PAGO_EFECTIVO;
    public string $pagoCordobas = '0';
    public string $pagoDolares = '0';
    public string $referenciaCordobas = '';
    public string $referenciaDolares = '';

    public ?int $productoId = null;
    public ?int $productoSerieId = null;
    public $productoCantidad = 1;
    public $productoPrecio = 0;
    public bool $productoTieneSeries = false;

    public array $checklist = [
        'incluye_instalacion_fisica' => true,
        'incluye_configuracion_app' => false,
        'incluye_pruebas_sistema' => false,
        'incluye_capacitacion_basica' => false,
        'incluye_garantia' => false,
        'anticipo_recibido' => false,
        'contrato_firmado' => false,
        'cliente_aprueba_recorrido' => false,
        'sistema_energizado' => false,
        'observacion_checklist' => '',
    ];

    public array $condicionesChecklist = [
        'incluye_instalacion_fisica' => 'Instalación física',
        'incluye_configuracion_app' => 'Configuración en app',
        'incluye_pruebas_sistema' => 'Pruebas del sistema',
        'incluye_capacitacion_basica' => 'Capacitación básica',
        'incluye_garantia' => 'Incluye garantía',
        'anticipo_recibido' => 'Anticipo recibido',
        'contrato_firmado' => 'Contrato firmado',
        'cliente_aprueba_recorrido' => 'Cliente aprueba recorrido',
        'sistema_energizado' => 'Sistema energizado',
    ];

    public array $estadosContrato = [
        ['id' => 'PENDIENTE', 'name' => 'Pendiente'],
        ['id' => 'EN_PROCESO', 'name' => 'En proceso'],
        ['id' => 'FINALIZADO', 'name' => 'Finalizado'],
        ['id' => 'CANCELADO', 'name' => 'Cancelado'],
    ];

    public array $headers = [
        ['key' => 'codigo', 'label' => 'Código'],
        ['key' => 'descripcion', 'label' => 'Descripción'],
        ['key' => 'serie', 'label' => 'Serie'],
        ['key' => 'cantidad', 'label' => 'Cantidad'],
        ['key' => 'precio', 'label' => 'Precio'],
        ['key' => 'subtotal', 'label' => 'Subtotal'],
        ['key' => 'acciones', 'label' => ''],
    ];

    public function mount(): void
    {
        // MODIFICADO: usa la tasa vigente registrada en tasa_cambio, no un valor fijo.
        $this->tipoCambio = $this->tipoCambioActualFormateada();

        $this->cargarCombos();
        $this->cargarPendientes();
    }

    // MODIFICADO: validación reactiva para que las alertas se limpien al corregir el campo,
    // sin esperar otro intento de guardado.
    public function updated(string $campo): void
    {
        $this->validarCampoEnTiempoReal($campo);
    }

    // MODIFICADO: permite que las alertas visuales desaparezcan automáticamente luego de unos segundos.
    public function limpiarErrorCampo(string $campo): void
    {
        if (in_array($campo, $this->camposConValidacion(), true)) {
            $this->resetErrorBag($campo);
        }
    }

    private function validarCampoEnTiempoReal(string $campo): void
    {
        if (! in_array($campo, $this->camposConValidacion(), true)) {
            return;
        }

        $this->resetErrorBag($campo);

        $reglasContrato = $this->reglasContrato();

        if (array_key_exists($campo, $reglasContrato)) {
            $this->validateOnly($campo, $reglasContrato, $this->mensajesValidacionContrato());
            return;
        }

        $reglasProducto = $this->reglasProducto();

        if (array_key_exists($campo, $reglasProducto)) {
            $this->validateOnly($campo, $reglasProducto, $this->mensajesValidacionProducto());
        }
    }

    private function camposConValidacion(): array
    {
        return [
            'clienteId',
            'tecnicoId',
            'municipio',
            'direccionInstalacion',
            'cantidadCamaras',
            'metrosCableado',
            'costoManoObra',
            'porcentajeAnticipo',
            'fechaEstimada',
            'detalleContrato',
            'estadoContrato',
            'productoId',
            'productoSerieId',
            'productoCantidad',
            'productoPrecio',
        ];
    }

    private function reglasContrato(): array
    {
        return [
            'clienteId' => ['required', 'integer'],
            'tecnicoId' => ['nullable', 'integer'],
            'municipio' => ['nullable', 'string', 'max:100'],
            'direccionInstalacion' => ['required', 'string', 'max:255'],
            'cantidadCamaras' => ['required', 'integer', 'min:1'],
            'metrosCableado' => ['required', 'numeric', 'min:0'],
            'costoManoObra' => ['required', 'numeric', 'min:0'],
            'porcentajeAnticipo' => ['required', 'numeric', 'min:0', 'max:100'],
            'fechaEstimada' => ['nullable', 'date'],
            'detalleContrato' => ['nullable', 'string', 'max:1000'],
            'estadoContrato' => ['required', 'in:PENDIENTE,EN_PROCESO,FINALIZADO,CANCELADO'],
        ];
    }

    private function mensajesValidacionContrato(): array
    {
        return [
            'clienteId.required' => 'Seleccione el cliente.',
            'clienteId.integer' => 'Seleccione un cliente válido.',
            'tecnicoId.integer' => 'Seleccione un técnico válido.',
            'municipio.max' => 'El municipio no debe superar los 100 caracteres.',
            'direccionInstalacion.required' => 'Ingrese la dirección de instalación.',
            'direccionInstalacion.max' => 'La dirección no debe superar los 255 caracteres.',
            'cantidadCamaras.required' => 'Ingrese la cantidad de cámaras.',
            'cantidadCamaras.integer' => 'La cantidad de cámaras debe ser un número entero.',
            'cantidadCamaras.min' => 'Ingrese al menos una cámara.',
            'metrosCableado.required' => 'Ingrese los metros de cableado.',
            'metrosCableado.numeric' => 'Los metros de cableado deben ser numéricos.',
            'metrosCableado.min' => 'Los metros de cableado no pueden ser negativos.',
            'costoManoObra.required' => 'Ingrese el costo de mano de obra.',
            'costoManoObra.numeric' => 'La mano de obra debe ser numérica.',
            'costoManoObra.min' => 'La mano de obra no puede ser negativa.',
            'porcentajeAnticipo.required' => 'Ingrese el porcentaje de anticipo.',
            'porcentajeAnticipo.numeric' => 'El anticipo debe ser numérico.',
            'porcentajeAnticipo.min' => 'El anticipo no puede ser negativo.',
            'porcentajeAnticipo.max' => 'El anticipo no puede superar el 100%.',
            'fechaEstimada.date' => 'Ingrese una fecha estimada válida.',
            'detalleContrato.max' => 'El detalle no debe superar los 1000 caracteres.',
            'estadoContrato.required' => 'Seleccione el estado del contrato.',
            'estadoContrato.in' => 'Seleccione un estado válido.',
        ];
    }

    private function reglasProducto(): array
    {
        return [
            'productoId' => ['required', 'integer'],
            'productoSerieId' => $this->productoTieneSeries ? ['required', 'integer'] : ['nullable', 'integer'],
            'productoCantidad' => ['required', 'numeric', 'min:0.01'],
            'productoPrecio' => ['required', 'numeric', 'min:0'],
        ];
    }

    private function mensajesValidacionProducto(): array
    {
        return [
            'productoId.required' => 'Seleccione un producto.',
            'productoId.integer' => 'Seleccione un producto válido.',
            'productoSerieId.required' => 'Seleccione la serie del producto.',
            'productoSerieId.integer' => 'Seleccione una serie válida.',
            'productoCantidad.required' => 'Ingrese la cantidad.',
            'productoCantidad.numeric' => 'La cantidad debe ser numérica.',
            'productoCantidad.min' => 'La cantidad debe ser mayor a cero.',
            'productoPrecio.required' => 'Ingrese el precio.',
            'productoPrecio.numeric' => 'El precio debe ser numérico.',
            'productoPrecio.min' => 'El precio no puede ser negativo.',
        ];
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

    public function cambiarTipoOperacion(string $tipo): void
    {
        if (! in_array($tipo, [self::TIPO_CONTADO, self::TIPO_CREDITO], true)) {
            return;
        }

        $cambioTipo = $this->tipoOperacion !== $tipo;
        $this->tipoOperacion = $tipo;
        $this->limpiarCobroContrato();

        if ($cambioTipo) {
            $this->clienteId = null;
            $this->telefonoCliente = '';
            $this->municipio = '';
            $this->filtroCliente = '';
            $this->clienteSeleccionadoNombre = '';
        }

        $this->paginaBusquedaClientes = 1;
        $this->mostrarBusquedaClientes = false;
        $this->cargarClientes();
    }

    public function cargarCombos(): void
    {
        $this->cargarClientes();
        $this->cargarTecnicos();
        $this->cargarProductosDisponibles();
    }

    private function cargarClientes(): void
    {
        $filtro = trim($this->filtroCliente);

        if ($this->clienteId && $filtro === trim($this->clienteSeleccionadoNombre)) {
            $filtro = '';
        }

        $limite = max(1, $this->paginaBusquedaClientes) * self::CLIENTES_POR_PAGINA;
        $query = $this->consultaClientesBase($filtro);

        $this->totalClientesBusqueda = (clone $query)->count();

        $clientes = $query
            ->select($this->columnasClienteSelect())
            ->orderByRaw('CASE WHEN cliente.Tipo_Cliente = ? THEN cliente.Institucion ELSE p.Primer_Nombre END ASC', [self::TIPO_CLIENTE_INSTITUCION])
            ->orderBy('p.Primer_Apellido')
            ->limit($limite)
            ->get()
            ->map(fn ($item) => $this->clienteOpcion($item))
            ->values();

        if ($this->clienteId && ! $clientes->contains(fn ($item) => (int) $item['id'] === (int) $this->clienteId)) {
            $seleccionado = $this->buscarClienteOpcionPorId((int) $this->clienteId);

            if ($seleccionado) {
                $clientes->prepend($seleccionado);
            }
        }

        $this->hayMasClientes = $this->totalClientesBusqueda > $clientes->count();
        $this->clientes = $clientes->toArray();
    }

    private function columnasClienteSelect(): array
    {
        return [
            'cliente.Id_Cliente as id',
            'cliente.Institucion',
            'cliente.Tipo_Cliente',
            'cliente.Telefono_Institucion',
            'cliente.Municipio',
            'p.Primer_Nombre',
            'p.Segundo_Nombre',
            'p.Primer_Apellido',
            'p.Segundo_Apellido',
            'p.Telefono',
        ];
    }

    private function clienteOpcion(object $item): array
    {
        $esInstitucion = (int) ($item->Tipo_Cliente ?? 0) === self::TIPO_CLIENTE_INSTITUCION;

        $nombrePersona = trim(
            ($item->Primer_Nombre ?? '') . ' ' .
            ($item->Segundo_Nombre ?? '') . ' ' .
            ($item->Primer_Apellido ?? '') . ' ' .
            ($item->Segundo_Apellido ?? '')
        );

        $nombre = $esInstitucion
            ? (string) ($item->Institucion ?: 'Institución sin nombre')
            : (string) ($nombrePersona ?: 'Cliente sin nombre');

        $telefono = $esInstitucion
            ? (string) ($item->Telefono_Institucion ?? '')
            : (string) ($item->Telefono ?? '');

        return [
            'id' => (int) $item->id,
            'name' => $this->limpiarTexto(trim($nombre . ($telefono !== '' ? ' | Tel: ' . $telefono : ''))),
            'telefono' => $telefono,
            'municipio' => (string) ($item->Municipio ?? ''),
            'tipo_cliente' => $esInstitucion ? self::TIPO_CLIENTE_INSTITUCION : self::TIPO_CLIENTE_NATURAL,
        ];
    }

    private function buscarClienteOpcionPorId(int $id): ?array
    {
        $cliente = Cliente::query()
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'cliente.Id_Persona')
            ->where('cliente.Id_Cliente', $id)
            ->where('cliente.Estado', 1)
            ->select($this->columnasClienteSelect())
            ->first();

        return $cliente ? $this->clienteOpcion($cliente) : null;
    }

    private function consultaClientesBase(string $filtro)
    {
        return Cliente::query()
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'cliente.Id_Persona')
            ->where('cliente.Estado', 1)
            ->when($this->tipoOperacion === self::TIPO_CREDITO, function ($query) {
                $query->where('cliente.Tipo_Cliente', self::TIPO_CLIENTE_INSTITUCION);
            }, function ($query) {
                $query->where('cliente.Tipo_Cliente', self::TIPO_CLIENTE_NATURAL);
            })
            ->when($filtro !== '', function ($query) use ($filtro) {
                $query->where(function ($q) use ($filtro) {
                    if ($this->tipoOperacion === self::TIPO_CREDITO) {
                        $q->where('cliente.Institucion', 'like', '%' . $filtro . '%')
                            ->orWhere('cliente.Telefono_Institucion', 'like', '%' . $filtro . '%');

                        return;
                    }

                    $q->where('p.Telefono', 'like', '%' . $filtro . '%')
                        ->orWhere('p.Primer_Nombre', 'like', '%' . $filtro . '%')
                        ->orWhere('p.Segundo_Nombre', 'like', '%' . $filtro . '%')
                        ->orWhere('p.Primer_Apellido', 'like', '%' . $filtro . '%')
                        ->orWhere('p.Segundo_Apellido', 'like', '%' . $filtro . '%');
                });
            });
    }

    private function cargarTecnicos(): void
    {
        $query = Trabajador::query()
            ->join('persona as p', 'p.Id_Persona', '=', 'trabajador.Id_Persona')
            ->leftJoin('cargo as cg', 'cg.Id_Cargo', '=', 'trabajador.Id_Cargo')
            ->where('trabajador.Estado', 1)
            ->select([
                'trabajador.Id_Trabajador as id',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido',
                'cg.Cargo_Asignado',
            ])
            ->orderBy('p.Primer_Nombre')
            ->orderBy('p.Primer_Apellido')
            ->limit(self::RESULTADOS_BUSQUEDA_SELECT);

        $tecnicos = $query->get()->map(fn ($item) => $this->tecnicoOpcion($item))->values();

        if ($this->tecnicoId && ! $tecnicos->contains(fn ($item) => (int) $item['id'] === (int) $this->tecnicoId)) {
            $seleccionado = Trabajador::query()
                ->join('persona as p', 'p.Id_Persona', '=', 'trabajador.Id_Persona')
                ->leftJoin('cargo as cg', 'cg.Id_Cargo', '=', 'trabajador.Id_Cargo')
                ->where('trabajador.Id_Trabajador', $this->tecnicoId)
                ->select([
                    'trabajador.Id_Trabajador as id',
                    'p.Primer_Nombre',
                    'p.Segundo_Nombre',
                    'p.Primer_Apellido',
                    'p.Segundo_Apellido',
                    'cg.Cargo_Asignado',
                ])
                ->first();

            if ($seleccionado) {
                $tecnicos->prepend($this->tecnicoOpcion($seleccionado));
            }
        }

        $this->tecnicos = $tecnicos->toArray();
    }

    private function tecnicoOpcion(object $item): array
    {
        return [
            'id' => (int) $item->id,
            'name' => $this->limpiarTexto(
                trim(
                    ($item->Primer_Nombre ?? '') . ' ' .
                    ($item->Segundo_Nombre ?? '') . ' ' .
                    ($item->Primer_Apellido ?? '') . ' ' .
                    ($item->Segundo_Apellido ?? '')
                ) . ' - ' . ($item->Cargo_Asignado ?: 'Trabajador')
            ),
        ];
    }

    private function cargarProductosDisponibles(): void
    {
        $query = $this->consultaProductosBase(trim($this->filtroProducto))
            ->select([
                'producto.Id_Producto as id',
                'producto.Nombre_Producto',
                'producto.Modelo',
                'producto.Precio_Venta as precio',
                'producto.Stock_Actual',
                'm.Nombre_Marca',
            ])
            ->orderBy('producto.Nombre_Producto')
            ->limit(self::RESULTADOS_BUSQUEDA_SELECT);

        $productos = $query->get();

        if ($this->productoId && ! $productos->contains(fn ($item) => (int) $item->id === (int) $this->productoId)) {
            $seleccionado = Producto::query()
                ->leftJoin('marca as m', 'm.Id_Marca', '=', 'producto.Id_Marca')
                ->where('producto.Id_Producto', $this->productoId)
                ->select([
                    'producto.Id_Producto as id',
                    'producto.Nombre_Producto',
                    'producto.Modelo',
                    'producto.Precio_Venta as precio',
                    'producto.Stock_Actual',
                    'm.Nombre_Marca',
                ])
                ->first();

            if ($seleccionado) {
                $productos->prepend($seleccionado);
            }
        }

        $ids = $productos->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        $seriesDisponiblesPorProducto = empty($ids)
            ? collect()
            : ProductoSerie::query()
                ->whereIn('Id_Producto', $ids)
                ->where('Estado', 'DISPONIBLE')
                ->select('Id_Producto', DB::raw('COUNT(*) as total'))
                ->groupBy('Id_Producto')
                ->pluck('total', 'Id_Producto');

        $this->productosDisponibles = $productos
            ->map(fn ($item) => $this->productoOpcion($item, (int) ($seriesDisponiblesPorProducto[$item->id] ?? 0)))
            ->values()
            ->toArray();
    }

    private function consultaProductosBase(string $filtro)
    {
        return Producto::query()
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'producto.Id_Marca')
            ->where('producto.Estado', 1)
            ->where('producto.Stock_Actual', '>', 0)
            ->when($filtro !== '', function ($query) use ($filtro) {
                $query->where(function ($q) use ($filtro) {
                    $q->where('producto.Nombre_Producto', 'like', '%' . $filtro . '%')
                        ->orWhere('producto.Modelo', 'like', '%' . $filtro . '%')
                        ->orWhere('m.Nombre_Marca', 'like', '%' . $filtro . '%')
                        ->orWhere('producto.Id_Producto', 'like', '%' . $filtro . '%');
                });
            });
    }

    private function productoOpcion(object $item, int $seriesDisponibles): array
    {
        $nombre = $this->limpiarTexto(
            trim(
                ($item->Nombre_Marca ? $item->Nombre_Marca . ' ' : '') .
                $item->Nombre_Producto . ' ' .
                ($item->Modelo ?? '')
            )
        );

        return [
            'id' => (int) $item->id,
            'name' => $nombre .
                ' - Stock: ' . (int) $item->Stock_Actual .
                ($seriesDisponibles > 0 ? ' | Series: ' . $seriesDisponibles : ''),
            'precio' => (float) $item->precio,
            'series_disponibles' => $seriesDisponibles,
        ];
    }

    public function cargarPendientes(): void
    {
        $tablaContrato = (new ContratoInstalacionCamara())->getTable();

        $query = ContratoInstalacionCamara::query()
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', $tablaContrato . '.Id_Cliente')
            ->leftJoin('persona as pc', 'pc.Id_Persona', '=', 'c.Id_Persona')
            ->leftJoin('trabajador as t', 't.Id_Trabajador', '=', $tablaContrato . '.Id_Trabajador')
            ->leftJoin('persona as pt', 'pt.Id_Persona', '=', 't.Id_Persona')
            ->whereNotIn($tablaContrato . '.Estado_Contrato', ['FINALIZADO', 'CANCELADO']);

        $filtro = trim($this->filtroPendientes);

        if ($filtro !== '') {
            $query->where(function ($q) use ($filtro, $tablaContrato) {
                $q->where($tablaContrato . '.Numero_Contrato', 'like', '%' . $filtro . '%')
                    ->orWhere($tablaContrato . '.Municipio', 'like', '%' . $filtro . '%')
                    ->orWhere($tablaContrato . '.Direccion_Instalacion', 'like', '%' . $filtro . '%')
                    ->orWhere('pc.Primer_Nombre', 'like', '%' . $filtro . '%')
                    ->orWhere('pc.Primer_Apellido', 'like', '%' . $filtro . '%')
                    ->orWhere('c.Institucion', 'like', '%' . $filtro . '%');
            });
        }

        $this->totalPendientes = (clone $query)->count();
        $this->totalPaginasPendientes = max(1, (int) ceil($this->totalPendientes / self::PENDIENTES_POR_PAGINA));

        if ($this->paginaPendientes > $this->totalPaginasPendientes) {
            $this->paginaPendientes = $this->totalPaginasPendientes;
        }

        if ($this->paginaPendientes < 1) {
            $this->paginaPendientes = 1;
        }

        $this->contratosPendientes = $query
            ->select([
                $tablaContrato . '.Id_Contrato_Instalacion_Camara as id',
                $tablaContrato . '.Numero_Contrato as numero',
                $tablaContrato . '.Fecha_Contrato as fecha',
                $tablaContrato . '.Municipio as municipio',
                $tablaContrato . '.Direccion_Instalacion as direccion',
                $tablaContrato . '.Cantidad_Camaras as camaras',
                $tablaContrato . '.Estado_Contrato as estado',
                $tablaContrato . '.Total_Contrato as total',
                $tablaContrato . '.Saldo_Pendiente as saldo',
                'c.Institucion as cliente_institucion',
                'pc.Primer_Nombre as cliente_primer_nombre',
                'pc.Segundo_Nombre as cliente_segundo_nombre',
                'pc.Primer_Apellido as cliente_primer_apellido',
                'pc.Segundo_Apellido as cliente_segundo_apellido',
                'pt.Primer_Nombre as tecnico_primer_nombre',
                'pt.Primer_Apellido as tecnico_primer_apellido',
            ])
            ->orderByDesc($tablaContrato . '.Fecha_Contrato')
            ->forPage($this->paginaPendientes, self::PENDIENTES_POR_PAGINA)
            ->get()
            ->map(function ($item) {
                $cliente = $this->limpiarTexto(
                    trim(
                        ($item->cliente_institucion ? $item->cliente_institucion . ' - ' : '') .
                        ($item->cliente_primer_nombre ?? '') . ' ' .
                        ($item->cliente_segundo_nombre ?? '') . ' ' .
                        ($item->cliente_primer_apellido ?? '') . ' ' .
                        ($item->cliente_segundo_apellido ?? '')
                    )
                );

                $tecnico = $this->limpiarTexto(
                    trim(
                        ($item->tecnico_primer_nombre ?? '') . ' ' .
                        ($item->tecnico_primer_apellido ?? '')
                    )
                );

                return [
                    'id' => (int) $item->id,
                    'numero' => $item->numero,
                    'fecha' => $item->fecha,
                    'cliente' => $cliente ?: 'Cliente no especificado',
                    'ubicacion' => $this->limpiarTexto(
                        trim(($item->municipio ?? '') . ' ' . ($item->direccion ?? ''))
                    ) ?: 'Sin ubicación',
                    'tecnico' => $tecnico ?: 'Sin técnico',
                    'camaras' => (int) $item->camaras,
                    'estado' => $item->estado,
                    'total' => (float) $item->total,
                    'saldo' => (float) $item->saldo,
                ];
            })
            ->toArray();
    }

    public function abrirPendientes(): void
    {
        $this->paginaPendientes = 1;
        $this->cargarPendientes();
        $this->modalPendientes = true;
    }

    public function paginaAnteriorPendientes(): void
    {
        if ($this->paginaPendientes > 1) {
            $this->paginaPendientes--;
            $this->cargarPendientes();
        }
    }

    public function paginaSiguientePendientes(): void
    {
        if ($this->paginaPendientes < $this->totalPaginasPendientes) {
            $this->paginaPendientes++;
            $this->cargarPendientes();
        }
    }

    public function updatedFiltroCliente(): void
    {
        if ($this->clienteId && trim($this->filtroCliente) !== trim($this->clienteSeleccionadoNombre)) {
            $this->clienteId = null;
            $this->telefonoCliente = '';
            $this->municipio = '';
            $this->clienteSeleccionadoNombre = '';
        }

        $this->paginaBusquedaClientes = 1;
        $this->mostrarBusquedaClientes = true;
        $this->cargarClientes();
    }

    public function abrirBusquedaClientes(): void
    {
        $this->paginaBusquedaClientes = 1;
        $this->mostrarBusquedaClientes = true;
        $this->cargarClientes();
    }

    public function cerrarBusquedaClientes(): void
    {
        $this->mostrarBusquedaClientes = false;
    }

    public function cargarMasClientes(): void
    {
        if (! $this->hayMasClientes) {
            return;
        }

        $this->paginaBusquedaClientes++;
        $this->mostrarBusquedaClientes = true;
        $this->cargarClientes();
    }

    public function seleccionarCliente(int $id): void
    {
        $cliente = $this->buscarClienteOpcionPorId($id);

        if (! $cliente) {
            $this->mostrarMensaje('error', 'Cliente no encontrado', 'El cliente seleccionado ya no está activo.');
            $this->cargarClientes();
            return;
        }

        if ($this->tipoOperacion === self::TIPO_CREDITO && (int) $cliente['tipo_cliente'] !== self::TIPO_CLIENTE_INSTITUCION) {
            $this->mostrarMensaje('error', 'Cliente no permitido', 'El crédito solo se puede registrar a clientes institucionales.');
            return;
        }

        if ($this->tipoOperacion === self::TIPO_CONTADO && (int) $cliente['tipo_cliente'] !== self::TIPO_CLIENTE_NATURAL) {
            $this->mostrarMensaje('error', 'Cliente no permitido', 'El contado solo se registra a clientes normales.');
            return;
        }

        $this->clienteId = (int) $cliente['id'];
        $this->telefonoCliente = (string) ($cliente['telefono'] ?? '');
        $this->municipio = (string) ($cliente['municipio'] ?? '');
        $this->clienteSeleccionadoNombre = (string) $cliente['name'];
        $this->filtroCliente = (string) $cliente['name'];
        $this->mostrarBusquedaClientes = false;
        $this->resetErrorBag('clienteId');
        $this->cargarClientes();
    }

    public function updatedFiltroProducto(): void
    {
        $this->cargarProductosDisponibles();
    }

    public function updatedFiltroPendientes(): void
    {
        $this->paginaPendientes = 1;
        $this->cargarPendientes();
    }

    public function cargarPendiente(int $id, bool $cerrarModal = true): void
    {
        $contrato = ContratoInstalacionCamara::query()
            ->where('Id_Contrato_Instalacion_Camara', $id)
            ->first();

        if (!$contrato) {
            $this->mostrarMensaje('error', 'No encontrado', 'El contrato seleccionado ya no existe.');
            return;
        }

        $this->contratoInstalacionIdSeleccionado = (int) $contrato->Id_Contrato_Instalacion_Camara;
        $this->clienteId = $contrato->Id_Cliente ? (int) $contrato->Id_Cliente : null;
        $this->updatedClienteId($this->clienteId);

        $this->tecnicoId = $contrato->Id_Trabajador ? (int) $contrato->Id_Trabajador : null;
        $this->municipio = (string) ($contrato->Municipio ?? $this->municipio);
        $this->direccionInstalacion = (string) $contrato->Direccion_Instalacion;
        $this->cantidadCamaras = (int) $contrato->Cantidad_Camaras;
        $this->metrosCableado = (float) $contrato->Metros_Cableado;
        $this->costoManoObra = (float) $contrato->Costo_Mano_Obra;
        $this->porcentajeAnticipo = (float) $contrato->Porcentaje_Anticipo;
        $this->fechaEstimada = $this->normalizarFechaInput($contrato->Fecha_Estimada);
        $this->detalleContrato = (string) ($contrato->Detalle_Contrato ?? '');
        $this->estadoContrato = (string) $contrato->Estado_Contrato;
        // MODIFICADO: si el contrato aún no tiene venta o fue cargado antes, tomamos Tipo_Venta del contrato primero.
        $tipoVentaGuardada = strtoupper((string) ($contrato->Tipo_Venta ?? ''));

        if ($tipoVentaGuardada === '' && $contrato->Id_Venta) {
            $tipoVentaGuardada = strtoupper((string) DB::table('venta')
                ->where('Id_Venta', $contrato->Id_Venta)
                ->value('Tipo_Venta'));
        }

        $this->tipoOperacion = $tipoVentaGuardada === self::TIPO_CREDITO
            ? self::TIPO_CREDITO
            : self::TIPO_CONTADO;
        $this->filtroCliente = '';
        $this->clienteSeleccionadoNombre = '';
        $this->paginaBusquedaClientes = 1;
        $this->mostrarBusquedaClientes = false;
        $this->filtroProducto = '';
        $this->cargarCombos();
        $this->updatedClienteId($this->clienteId);
        // MODIFICADO: al actualizar/cobrar pendientes se muestra la tasa vigente, no un fallback viejo.
        $this->tipoCambio = $this->tipoCambioActualFormateada();
        $this->limpiarCobroContrato();

        $this->cargarChecklistContrato((int) $contrato->Id_Contrato_Instalacion_Camara);
        $this->cargarProductosDelContrato((int) $contrato->Id_Contrato_Instalacion_Camara);
        $this->resetProductoForm();
        $this->resetErrorBag();

        if ($cerrarModal) {
            $this->modalPendientes = false;
        }

        $this->mostrarMensaje('success', 'Pendiente cargado', 'Ya podés actualizar el contrato o agregar materiales.');
    }

    public function nuevoContrato(): void
    {
        $this->limpiarFormulario();
        $this->cargarCombos();
        $this->cargarPendientes();

        $this->mostrarMensaje(
            'success',
            'Formulario limpio',
            'Listo para registrar un nuevo contrato de instalación.'
        );
    }

    public function updatedClienteId($value): void
    {
        $this->telefonoCliente = '';
        $this->municipio = '';
        $this->clienteSeleccionadoNombre = '';

        if (!$value) {
            return;
        }

        $cliente = $this->buscarClienteOpcionPorId((int) $value);

        if (! $cliente) {
            return;
        }

        $this->clienteId = (int) $cliente['id'];
        $this->telefonoCliente = (string) ($cliente['telefono'] ?? '');
        $this->municipio = (string) ($cliente['municipio'] ?? '');
        $this->clienteSeleccionadoNombre = (string) $cliente['name'];
        $this->filtroCliente = (string) $cliente['name'];
        $this->mostrarBusquedaClientes = false;
        $this->cargarClientes();
    }

    public function updatedProductoId($value): void
    {
        $this->productoSerieId = null;
        $this->seriesDisponibles = [];
        $this->productoTieneSeries = false;
        $this->productoCantidad = 1;
        $this->productoPrecio = 0;

        if (!$value) {
            return;
        }

        $producto = Producto::query()
            ->where('Id_Producto', $value)
            ->first();

        if (!$producto) {
            return;
        }

        $this->productoPrecio = (float) $producto->Precio_Venta;

        $seriesUsadasEnPantalla = collect($this->productosUsados)
            ->pluck('producto_serie_id')
            ->filter()
            ->values()
            ->all();

        $query = ProductoSerie::query()
            ->where('Id_Producto', $value)
            ->where('Estado', 'DISPONIBLE');

        if (!empty($seriesUsadasEnPantalla)) {
            $query->whereNotIn('id_producto_serie', $seriesUsadasEnPantalla);
        }

        $this->seriesDisponibles = $query
            ->orderBy('Numero_Serie')
            ->get(['id_producto_serie', 'Numero_Serie'])
            ->map(fn ($item) => [
                'id' => (int) $item->id_producto_serie,
                'name' => $item->Numero_Serie,
            ])
            ->toArray();

        $this->productoTieneSeries = ProductoSerie::query()
            ->where('Id_Producto', $value)
            ->exists();

        if ($this->productoTieneSeries) {
            $this->productoCantidad = 1;
        }
    }

    public function agregarProducto(): void
    {
        // MODIFICADO: reglas centralizadas para que la validación normal y la validación en vivo usen los mismos mensajes.
        $this->validate($this->reglasProducto(), $this->mensajesValidacionProducto());

        $producto = Producto::query()
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'producto.Id_Marca')
            ->where('producto.Id_Producto', $this->productoId)
            ->select([
                'producto.*',
                'm.Nombre_Marca',
            ])
            ->first();

        if (!$producto || (int) $producto->Estado !== 1 || (int) $producto->Stock_Actual <= 0) {
            $this->addError('productoId', 'El producto no está disponible.');
            return;
        }

        $serie = null;
        $cantidad = (float) $this->productoCantidad;

        if ($this->productoTieneSeries) {
            if (!$this->productoSerieId) {
                $this->addError('productoSerieId', 'Seleccione la serie del producto.');
                return;
            }

            $serie = ProductoSerie::query()
                ->where('id_producto_serie', $this->productoSerieId)
                ->where('Id_Producto', $this->productoId)
                ->where('Estado', 'DISPONIBLE')
                ->first();

            if (!$serie) {
                $this->addError('productoSerieId', 'La serie seleccionada ya no está disponible.');
                return;
            }

            $cantidad = 1;
        }

        $cantidadYaAgregada = collect($this->productosUsados)
            ->where('producto_id', $this->productoId)
            ->sum('cantidad');

        if (!$this->productoTieneSeries && ($cantidadYaAgregada + $cantidad) > (float) $producto->Stock_Actual) {
            $this->addError('productoCantidad', 'La cantidad supera el stock disponible.');
            return;
        }

        if ($serie && collect($this->productosUsados)->contains('producto_serie_id', (int) $serie->id_producto_serie)) {
            $this->addError('productoSerieId', 'Esta serie ya fue agregada.');
            return;
        }

        $precio = round((float) $this->productoPrecio, 2);
        $subtotal = round($cantidad * $precio, 2);

        $this->productosUsados[] = [
            'tmp_id' => uniqid('prod_', true),
            'ya_guardado' => false,
            'producto_id' => (int) $producto->Id_Producto,
            'producto_serie_id' => $serie?->id_producto_serie ? (int) $serie->id_producto_serie : null,
            'codigo' => (string) $producto->Id_Producto,
            'descripcion' => $this->limpiarTexto(
                trim(
                    ($producto->Nombre_Marca ? $producto->Nombre_Marca . ' ' : '') .
                    $producto->Nombre_Producto . ' ' .
                    ($producto->Modelo ?? '')
                )
            ),
            'serie' => $serie->Numero_Serie ?? 'N/A',
            'cantidad' => $cantidad,
            'precio' => $precio,
            'subtotal' => $subtotal,
            'acciones' => '',
        ];

        $this->resetProductoForm();
        $this->cargarCombos();

        $this->mostrarMensaje(
            'success',
            'Producto agregado',
            'El material quedó listo para guardarse con el contrato.'
        );
    }

    public function quitarProducto(string $tmpId): void
    {
        $producto = collect($this->productosUsados)->firstWhere('tmp_id', $tmpId);

        if ($producto && !empty($producto['ya_guardado'])) {
            $this->mostrarMensaje('error', 'No permitido', 'Este material ya fue descontado del inventario. Para revertirlo hay que hacer una devolución o ajuste de inventario.');
            return;
        }

        $this->productosUsados = array_values(array_filter(
            $this->productosUsados,
            fn ($item) => $item['tmp_id'] !== $tmpId
        ));

        $this->cargarCombos();

        $this->mostrarMensaje(
            'success',
            'Producto quitado',
            'El material fue removido del contrato.'
        );
    }

    public function guardar(): void
    {
        // MODIFICADO: reglas centralizadas para permitir alertas dinámicas y limpieza automática.
        $this->validate($this->reglasContrato(), $this->mensajesValidacionContrato());

        if ($this->tipoOperacion === self::TIPO_CREDITO && ! $this->clienteEsInstitucion((int) $this->clienteId)) {
            $this->mostrarMensaje('error', 'Cliente no permitido', 'El crédito solo se puede registrar a clientes institucionales.');
            return;
        }

        if ($this->limpiarMonto($this->pagoCordobas) > 0 && $this->pagoRequiereReferencia($this->tipoPagoCordobas) && trim($this->referenciaCordobas) === '') {
            $this->mostrarMensaje('error', 'Referencia requerida', 'Ingrese la referencia del pago en córdobas.');
            return;
        }

        if ($this->limpiarDecimal($this->pagoDolares) > 0 && $this->pagoRequiereReferencia($this->tipoPagoDolares) && trim($this->referenciaDolares) === '') {
            $this->mostrarMensaje('error', 'Referencia requerida', 'Ingrese la referencia del pago en dólares.');
            return;
        }

        try {
            if ($this->contratoInstalacionIdSeleccionado) {
                $this->actualizarContratoInstalacion();

                $id = $this->contratoInstalacionIdSeleccionado;

                $this->cargarCombos();
                $this->cargarPendientes();
                $this->cargarPendiente($id, false);

                $this->mostrarMensaje('success', 'Contrato actualizado', 'La instalación de cámaras se actualizó correctamente.');
                return;
            }

            $contratoId = $this->crearContratoInstalacion();

            $this->limpiarFormulario();
            $this->cargarCombos();
            $this->cargarPendientes();

            $this->mostrarMensaje(
                'success',
                'Contrato guardado',
                'La instalación de cámaras se registró correctamente. Contrato #' . $contratoId . '.'
            );
        } catch (\Throwable $e) {
            report($e);

            $this->mostrarMensaje(
                'error',
                'No se pudo guardar',
                $e->getMessage()
            );
        }
    }

    public function estadoNombre(?string $estado): string
    {
        return match ($estado) {
            'PENDIENTE' => 'Pendiente',
            'EN_PROCESO' => 'En proceso',
            'FINALIZADO' => 'Finalizado',
            'CANCELADO' => 'Cancelado',
            default => str_replace('_', ' ', (string) $estado),
        };
    }

    private function crearContratoInstalacion(): int
    {
        return DB::transaction(function () {
            $usuarioId = $this->usuarioActualId();

            $numeroContrato = $this->generarNumeroUnico(
                'IC',
                ContratoInstalacionCamara::class,
                'Numero_Contrato'
            );

            $servicioId = $this->servicioPorTipo('INSTALACION');

            $totalMateriales = round(collect($this->productosUsados)
                ->sum(fn ($item) => $this->numeroSeguro($item['subtotal'] ?? 0)), 2);
            $totalContrato = round($totalMateriales + $this->numeroSeguro($this->costoManoObra), 2);
            $anticipoEsperado = round($totalContrato * ($this->numeroSeguro($this->porcentajeAnticipo) / 100), 2);

            $clienteCredito = null;
            $credito = null;

            if ($this->tipoOperacion === self::TIPO_CREDITO) {
                $clienteCredito = $this->obtenerClienteCreditoActivo((int) $this->clienteId);
                $credito = $this->calcularCreditoConSaldoFavor($clienteCredito, $totalContrato);

                $cobro = [
                    'pagado_total' => $credito['abono_inicial_total'],
                    'saldo_pendiente' => $credito['saldo_pendiente_credito'],
                    'cambio_entregado' => $credito['cambio_entregado'],
                    'pago_cordobas' => $credito['pago_cordobas_recibido'],
                    'pago_dolares' => $credito['pago_dolares_recibido'],
                    'equivalente_dolares' => $credito['equivalente_dolares_recibido'],
                ];
            } else {
                $cobro = $this->calcularCobroContrato($totalContrato, 0);
            }

            if ((bool) ($this->checklist['anticipo_recibido'] ?? false) && $cobro['pagado_total'] < $anticipoEsperado) {
                throw new \RuntimeException('El pago recibido no cubre el anticipo esperado.');
            }

            $ventaId = $this->crearVentaContratoInstalacion($usuarioId, $totalContrato, $cobro);

            $contrato = ContratoInstalacionCamara::query()->create([
                'Id_Venta' => $ventaId,
                'Numero_Contrato' => $numeroContrato,
                'Fecha_Contrato' => now(),
                'Id_Cliente' => $this->clienteId,
                'Id_Usuario' => $usuarioId,
                'Id_Servicio' => $servicioId,
                'Id_Trabajador' => $this->tecnicoId,
                'Municipio' => $this->municipio ?: null,
                'Direccion_Instalacion' => $this->direccionInstalacion,
                'Cantidad_Camaras' => (int) $this->cantidadCamaras,
                'Metros_Cableado' => (float) $this->metrosCableado,
                'Costo_Mano_Obra' => $this->numeroSeguro($this->costoManoObra),
                'Porcentaje_Anticipo' => $this->numeroSeguro($this->porcentajeAnticipo),
                'Monto_Anticipo' => $cobro['pagado_total'],
                'Tipo_Venta' => $this->tipoOperacion,
                'Tipo_Cambio' => $this->tasaCambio(),
                'Cambio_Entregado_Cordobas' => $cobro['cambio_entregado'],
                'Fecha_Estimada' => $this->fechaEstimada ?: null,
                'Detalle_Contrato' => $this->detalleContrato ?: null,
                'Estado_Contrato' => $this->estadoContrato,
                'Total_Materiales' => $totalMateriales,
                'Total_Contrato' => $totalContrato,
                'Saldo_Pendiente' => $cobro['saldo_pendiente'],
            ]);

            $contratoId = (int) $contrato->Id_Contrato_Instalacion_Camara;

            $this->registrarDetalleVentaContratoInstalacion($ventaId, $servicioId, $numeroContrato);

            if ($this->tipoOperacion === self::TIPO_CREDITO) {
                $this->registrarCreditoContratoInstalacion($ventaId, $clienteCredito, $credito, $numeroContrato);
            } else {
                $this->registrarPagosContrato($contratoId, $ventaId, $cobro);
            }

            ContratoInstalacionCamaraChecklist::query()->create(
                $this->datosChecklist($contratoId)
            );

            foreach ($this->productosUsados as $item) {
                $this->registrarProductoContrato($contratoId, $item);
            }

            return $contratoId;
        }, 3);
    }

    private function actualizarContratoInstalacion(): void
    {
        DB::transaction(function () {
            $contratoId = (int) $this->contratoInstalacionIdSeleccionado;

            $contrato = ContratoInstalacionCamara::query()
                ->where('Id_Contrato_Instalacion_Camara', $contratoId)
                ->lockForUpdate()
                ->first();

            if (!$contrato) {
                throw new \RuntimeException('El contrato seleccionado ya no existe.');
            }

            $totalMateriales = round(collect($this->productosUsados)
                ->sum(fn ($item) => $this->numeroSeguro($item['subtotal'] ?? 0)), 2);
            $totalContrato = round($totalMateriales + $this->numeroSeguro($this->costoManoObra), 2);
            $anticipoEsperado = round($totalContrato * ($this->numeroSeguro($this->porcentajeAnticipo) / 100), 2);
            $servicioId = (int) ($contrato->Id_Servicio ?: $this->servicioPorTipo('INSTALACION'));
            $usuarioId = $this->usuarioActualId();
            $ventaId = $contrato->Id_Venta ? (int) $contrato->Id_Venta : null;

            if (!$ventaId) {
                $cobroInicial = $this->tipoOperacion === self::TIPO_CREDITO
                    ? ['pagado_total' => 0, 'saldo_pendiente' => $totalContrato, 'cambio_entregado' => 0]
                    : $this->calcularCobroContrato($totalContrato, 0);

                $ventaId = $this->crearVentaContratoInstalacion($usuarioId, $totalContrato, $cobroInicial);
            }

            $creditoExistente = DB::table('credito')
                ->where('Id_Venta', $ventaId)
                ->lockForUpdate()
                ->first();

            if ($creditoExistente && $this->tipoOperacion === self::TIPO_CONTADO) {
                throw new \RuntimeException('Este contrato ya está registrado al crédito. Los abonos deben gestionarse desde el módulo de crédito.');
            }

            if ($this->tipoOperacion === self::TIPO_CREDITO) {
                if ($creditoExistente) {
                    $cobro = $this->actualizarCreditoContratoExistente($creditoExistente, $totalContrato);
                } else {
                    $clienteCredito = $this->obtenerClienteCreditoActivo((int) $this->clienteId);
                    $credito = $this->calcularCreditoConSaldoFavor($clienteCredito, $totalContrato);
                    $this->registrarCreditoContratoInstalacion($ventaId, $clienteCredito, $credito, (string) $contrato->Numero_Contrato);

                    $cobro = [
                        'pagado_total' => $credito['abono_inicial_total'],
                        'saldo_pendiente' => $credito['saldo_pendiente_credito'],
                        'cambio_entregado' => $credito['cambio_entregado'],
                    ];
                }
            } else {
                $montoPagadoAnterior = round((float) ($contrato->Monto_Anticipo ?? 0), 2);
                $cobro = $this->calcularCobroContrato($totalContrato, $montoPagadoAnterior);
                $this->registrarPagosContrato($contratoId, $ventaId, $cobro);
            }

            if ((bool) ($this->checklist['anticipo_recibido'] ?? false) && $cobro['pagado_total'] < $anticipoEsperado) {
                throw new \RuntimeException('El pago recibido no cubre el anticipo esperado.');
            }

            $this->actualizarVentaContratoInstalacion($ventaId, $usuarioId, $totalContrato, $cobro);

            $contrato->forceFill([
                'Id_Venta' => $ventaId,
                'Id_Cliente' => $this->clienteId,
                'Id_Trabajador' => $this->tecnicoId,
                'Municipio' => $this->municipio ?: null,
                'Direccion_Instalacion' => $this->direccionInstalacion,
                'Cantidad_Camaras' => (int) $this->cantidadCamaras,
                'Metros_Cableado' => (float) $this->metrosCableado,
                'Costo_Mano_Obra' => $this->numeroSeguro($this->costoManoObra),
                'Porcentaje_Anticipo' => $this->numeroSeguro($this->porcentajeAnticipo),
                'Monto_Anticipo' => $cobro['pagado_total'],
                'Tipo_Venta' => $this->tipoOperacion,
                'Tipo_Cambio' => $this->tasaCambio(),
                'Cambio_Entregado_Cordobas' => round((float) ($contrato->Cambio_Entregado_Cordobas ?? 0) + ($cobro['cambio_entregado'] ?? 0), 2),
                'Fecha_Estimada' => $this->fechaEstimada ?: null,
                'Detalle_Contrato' => $this->detalleContrato ?: null,
                'Estado_Contrato' => $this->estadoContrato,
                'Total_Materiales' => $totalMateriales,
                'Total_Contrato' => $totalContrato,
                'Saldo_Pendiente' => $cobro['saldo_pendiente'],
            ])->save();

            $this->registrarDetalleVentaContratoInstalacion($ventaId, $servicioId, (string) $contrato->Numero_Contrato);

            $checklist = ContratoInstalacionCamaraChecklist::query()
                ->firstOrNew(['Id_Contrato_Instalacion_Camara' => $contratoId]);

            $checklist->forceFill($this->datosChecklist($contratoId))->save();

            foreach ($this->productosUsados as $item) {
                if (!empty($item['ya_guardado'])) {
                    continue;
                }

                $this->registrarProductoContrato($contratoId, $item);
            }
        }, 3);
    }

    private function registrarProductoContrato(int $contratoId, array $item): void
    {
        $this->descontarInventario($item, 'INSTALADO', 'SALIDA_INSTALACION');

        ContratoInstalacionCamaraProducto::query()->create([
            'Id_Contrato_Instalacion_Camara' => $contratoId,
            'Id_Producto' => $item['producto_id'],
            'Id_Producto_Serie' => $item['producto_serie_id'],
            'Cantidad' => $item['cantidad'],
            'Precio_Unitario' => $item['precio'],
            'Subtotal' => $item['subtotal'],
            'Observacion' => null,
        ]);
    }

    private function cargarChecklistContrato(int $contratoId): void
    {
        $check = ContratoInstalacionCamaraChecklist::query()
            ->where('Id_Contrato_Instalacion_Camara', $contratoId)
            ->first();

        $this->checklist = [
            'incluye_instalacion_fisica' => (bool) ($check->Incluye_Instalacion_Fisica ?? true),
            'incluye_configuracion_app' => (bool) ($check->Incluye_Configuracion_App ?? false),
            'incluye_pruebas_sistema' => (bool) ($check->Incluye_Pruebas_Sistema ?? false),
            'incluye_capacitacion_basica' => (bool) ($check->Incluye_Capacitacion_Basica ?? false),
            'incluye_garantia' => (bool) ($check->Incluye_Garantia ?? false),
            'anticipo_recibido' => (bool) ($check->Anticipo_Recibido ?? false),
            'contrato_firmado' => (bool) ($check->Contrato_Firmado ?? false),
            'cliente_aprueba_recorrido' => (bool) ($check->Cliente_Aprueba_Recorrido ?? false),
            'sistema_energizado' => (bool) ($check->Sistema_Energizado ?? false),
            'observacion_checklist' => (string) ($check->Observacion_Checklist ?? ''),
        ];
    }

    private function cargarProductosDelContrato(int $contratoId): void
    {
        $tablaDetalle = (new ContratoInstalacionCamaraProducto())->getTable();

        $this->productosUsados = ContratoInstalacionCamaraProducto::query()
            ->join('producto as p', 'p.Id_Producto', '=', $tablaDetalle . '.Id_Producto')
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'p.Id_Marca')
            ->leftJoin('producto_serie as ps', 'ps.id_producto_serie', '=', $tablaDetalle . '.Id_Producto_Serie')
            ->where($tablaDetalle . '.Id_Contrato_Instalacion_Camara', $contratoId)
            ->select([
                $tablaDetalle . '.Id_Producto',
                $tablaDetalle . '.Id_Producto_Serie',
                $tablaDetalle . '.Cantidad',
                $tablaDetalle . '.Precio_Unitario',
                $tablaDetalle . '.Subtotal',
                'p.Nombre_Producto',
                'p.Modelo',
                'm.Nombre_Marca',
                'ps.Numero_Serie',
            ])
            ->orderBy($tablaDetalle . '.Id_Producto')
            ->get()
            ->values()
            ->map(fn ($item, $index) => [
                'tmp_id' => 'guardado_' . $contratoId . '_' . $index,
                'ya_guardado' => true,
                'producto_id' => (int) $item->Id_Producto,
                'producto_serie_id' => $item->Id_Producto_Serie ? (int) $item->Id_Producto_Serie : null,
                'codigo' => (string) $item->Id_Producto,
                'descripcion' => $this->limpiarTexto(
                    trim(
                        ($item->Nombre_Marca ? $item->Nombre_Marca . ' ' : '') .
                        $item->Nombre_Producto . ' ' .
                        ($item->Modelo ?? '')
                    )
                ),
                'serie' => $item->Numero_Serie ?? 'N/A',
                'cantidad' => (float) $item->Cantidad,
                'precio' => (float) $item->Precio_Unitario,
                'subtotal' => (float) $item->Subtotal,
                'acciones' => '',
            ])
            ->toArray();
    }

    private function datosChecklist(int $contratoId): array
    {
        return [
            'Id_Contrato_Instalacion_Camara' => $contratoId,
            'Incluye_Instalacion_Fisica' => (bool) $this->checklist['incluye_instalacion_fisica'],
            'Incluye_Configuracion_App' => (bool) $this->checklist['incluye_configuracion_app'],
            'Incluye_Pruebas_Sistema' => (bool) $this->checklist['incluye_pruebas_sistema'],
            'Incluye_Capacitacion_Basica' => (bool) $this->checklist['incluye_capacitacion_basica'],
            'Incluye_Garantia' => (bool) $this->checklist['incluye_garantia'],
            'Anticipo_Recibido' => (bool) $this->checklist['anticipo_recibido'] || $this->totalPagadoCordobas() > 0,
            'Contrato_Firmado' => (bool) $this->checklist['contrato_firmado'],
            'Cliente_Aprueba_Recorrido' => (bool) $this->checklist['cliente_aprueba_recorrido'],
            'Sistema_Energizado' => (bool) $this->checklist['sistema_energizado'],
            'Observacion_Checklist' => $this->checklist['observacion_checklist'] ?: null,
        ];
    }

    private function resetProductoForm(): void
    {
        $this->productoId = null;
        $this->productoSerieId = null;
        $this->productoCantidad = 1;
        $this->productoPrecio = 0;
        $this->productoTieneSeries = false;
        $this->seriesDisponibles = [];

        $this->resetErrorBag([
            'productoId',
            'productoSerieId',
            'productoCantidad',
            'productoPrecio',
        ]);
    }

    private function limpiarFormulario(): void
    {
        $this->contratoInstalacionIdSeleccionado = null;
        $this->clienteId = null;
        $this->telefonoCliente = '';
        $this->municipio = '';
        $this->tecnicoId = null;

        $this->cantidadCamaras = 0;
        $this->metrosCableado = 0;
        $this->costoManoObra = 0;
        $this->porcentajeAnticipo = 30;

        $this->fechaEstimada = null;
        $this->direccionInstalacion = '';
        $this->detalleContrato = '';
        $this->estadoContrato = 'PENDIENTE';
        $this->tipoOperacion = self::TIPO_CONTADO;
        $this->filtroCliente = '';
        $this->clienteSeleccionadoNombre = '';
        $this->paginaBusquedaClientes = 1;
        $this->mostrarBusquedaClientes = false;
        $this->filtroProducto = '';
        $this->tipoCambio = $this->tipoCambioActualFormateada();
        $this->limpiarCobroContrato();

        $this->productosUsados = [];

        $this->checklist = [
            'incluye_instalacion_fisica' => true,
            'incluye_configuracion_app' => false,
            'incluye_pruebas_sistema' => false,
            'incluye_capacitacion_basica' => false,
            'incluye_garantia' => false,
            'anticipo_recibido' => false,
            'contrato_firmado' => false,
            'cliente_aprueba_recorrido' => false,
            'sistema_energizado' => false,
            'observacion_checklist' => '',
        ];

        $this->resetProductoForm();
        $this->resetErrorBag();
    }

    public function totalMaterialesContrato(): float
    {
        return round(collect($this->productosUsados)
            ->sum(fn ($item) => $this->numeroSeguro($item['subtotal'] ?? 0)), 2);
    }

    public function totalContratoActual(): float
    {
        return round($this->totalMaterialesContrato() + $this->numeroSeguro($this->costoManoObra), 2);
    }

    public function anticipoEsperadoContrato(): float
    {
        return round($this->totalContratoActual() * ($this->numeroSeguro($this->porcentajeAnticipo) / 100), 2);
    }

    public function totalPagadoCordobas(): float
    {
        return round($this->limpiarMonto($this->pagoCordobas) + ($this->limpiarDecimal($this->pagoDolares) * $this->tasaCambio()), 2);
    }

    public function cambioContrato(): float
    {
        $base = $this->tipoOperacion === self::TIPO_CREDITO
            ? max($this->totalContratoActual() - $this->saldoFavorAplicadoContrato(), 0)
            : $this->totalContratoActual();

        return round(max($this->totalPagadoCordobas() - $base, 0), 2);
    }

    public function saldoContrato(): float
    {
        if ($this->tipoOperacion === self::TIPO_CREDITO) {
            return $this->saldoCreditoContrato();
        }

        return round(max($this->totalContratoActual() - $this->totalPagadoCordobas(), 0), 2);
    }

    private function calcularCobroContrato(float $totalContrato, float $montoPagadoAnterior): array
    {
        $pagoCordobas = round($this->limpiarMonto($this->pagoCordobas), 2);
        $pagoDolares = round($this->limpiarDecimal($this->pagoDolares), 2);
        $equivalenteDolares = round($pagoDolares * $this->tasaCambio(), 2);
        $recibidoEquivalente = round($pagoCordobas + $equivalenteDolares, 2);
        $pendienteAnterior = round(max($totalContrato - $montoPagadoAnterior, 0), 2);
        $aplicado = round(min($recibidoEquivalente, $pendienteAnterior), 2);
        $cambioEntregado = round(max($recibidoEquivalente - $pendienteAnterior, 0), 2);
        $pagadoTotal = round(min($montoPagadoAnterior + $aplicado, $totalContrato), 2);

        return [
            'pago_cordobas' => $pagoCordobas,
            'pago_dolares' => $pagoDolares,
            'equivalente_dolares' => $equivalenteDolares,
            'recibido_equivalente' => $recibidoEquivalente,
            'aplicado' => $aplicado,
            'pagado_total' => $pagadoTotal,
            'saldo_pendiente' => round(max($totalContrato - $pagadoTotal, 0), 2),
            'cambio_entregado' => $cambioEntregado,
        ];
    }

    private function registrarPagosContrato(int $contratoId, int $ventaId, array $cobro): void
    {
        if (($cobro['pago_cordobas'] ?? 0) > 0) {
            DB::table('pago_venta')->insert([
                'Id_Venta' => $ventaId,
                'Fecha_Pago' => now(),
                'Moneda' => 0,
                'Tipo_Pago' => $this->tipoPagoCordobas,
                'Numero_Referencia' => $this->pagoRequiereReferencia($this->tipoPagoCordobas)
                    ? trim($this->referenciaCordobas)
                    : null,
                'Monto' => $cobro['pago_cordobas'],
                'Tipo_Cambio' => 1,
                'Monto_Equivalente_Cordobas' => $cobro['pago_cordobas'],
            ]);
        }

        if (($cobro['pago_dolares'] ?? 0) > 0) {
            DB::table('pago_venta')->insert([
                'Id_Venta' => $ventaId,
                'Fecha_Pago' => now(),
                'Moneda' => 1,
                'Tipo_Pago' => $this->tipoPagoDolares,
                'Numero_Referencia' => $this->pagoRequiereReferencia($this->tipoPagoDolares)
                    ? trim($this->referenciaDolares)
                    : null,
                'Monto' => $cobro['pago_dolares'],
                'Tipo_Cambio' => $this->tasaCambio(),
                'Monto_Equivalente_Cordobas' => $cobro['equivalente_dolares'],
            ]);
        }
    }

    public function saldoFavorDisponible(): float
    {
        if (! $this->clienteId || $this->tipoOperacion !== self::TIPO_CREDITO) {
            return 0;
        }

        return round((float) DB::table('cliente_credito')
            ->where('Id_Cliente', $this->clienteId)
            ->where('Estado', 'ACTIVO')
            ->value('Saldo_Actual'), 2);
    }

    public function saldoFavorAplicadoContrato(): float
    {
        return round(min($this->saldoFavorDisponible(), $this->totalContratoActual()), 2);
    }

    public function abonoInicialContrato(): float
    {
        if ($this->tipoOperacion !== self::TIPO_CREDITO) {
            return $this->totalPagadoCordobas();
        }

        return round(min(
            $this->totalPagadoCordobas(),
            max($this->totalContratoActual() - $this->saldoFavorAplicadoContrato(), 0)
        ), 2);
    }

    public function saldoCreditoContrato(): float
    {
        return round(max(
            $this->totalContratoActual()
            - $this->saldoFavorAplicadoContrato()
            - $this->abonoInicialContrato(),
            0
        ), 2);
    }

    private function clienteEsInstitucion(int $clienteId): bool
    {
        return Cliente::query()
            ->where('Id_Cliente', $clienteId)
            ->where('Estado', 1)
            ->where('Tipo_Cliente', Cliente::TIPO_INSTITUCION)
            ->exists();
    }

    private function obtenerClienteCreditoActivo(int $clienteId): object
    {
        if (! $this->clienteEsInstitucion($clienteId)) {
            throw new \RuntimeException('El crédito solo se puede registrar a clientes institucionales.');
        }

        $clienteCredito = DB::table('cliente_credito')
            ->where('Id_Cliente', $clienteId)
            ->lockForUpdate()
            ->first();

        if (! $clienteCredito) {
            $idClienteCredito = DB::table('cliente_credito')->insertGetId([
                'Id_Cliente' => $clienteId,
                'Saldo_Actual' => 0,
                'Estado' => 'ACTIVO',
                'Fecha_Registro' => now(),
            ]);

            $clienteCredito = DB::table('cliente_credito')
                ->where('Id_Cliente_Credito', $idClienteCredito)
                ->lockForUpdate()
                ->first();
        }

        if (($clienteCredito->Estado ?? '') !== 'ACTIVO') {
            throw new \RuntimeException('La cuenta de crédito de esta institución no está activa.');
        }

        return $clienteCredito;
    }

    private function calcularCreditoConSaldoFavor(object $clienteCredito, float $total): array
    {
        $saldoAnteriorFavor = round((float) ($clienteCredito->Saldo_Actual ?? 0), 2);
        $saldoFavorAplicado = round(min($saldoAnteriorFavor, $total), 2);
        $saldoDespuesFavor = round(max($saldoAnteriorFavor - $saldoFavorAplicado, 0), 2);
        $pendienteDespuesFavor = round(max($total - $saldoFavorAplicado, 0), 2);

        $pagoCordobas = round($this->limpiarMonto($this->pagoCordobas), 2);
        $pagoDolares = round($this->limpiarDecimal($this->pagoDolares), 2);
        $equivalenteDolares = round($pagoDolares * $this->tasaCambio(), 2);
        $recibidoEquivalente = round($pagoCordobas + $equivalenteDolares, 2);
        $abonoPagoAplicado = round(min($recibidoEquivalente, $pendienteDespuesFavor), 2);
        $cambioEntregado = round(max($recibidoEquivalente - $pendienteDespuesFavor, 0), 2);

        $pendienteAplicar = $abonoPagoAplicado;
        $abonoCordobas = round(min($pagoCordobas, $pendienteAplicar), 2);
        $pendienteAplicar = round(max($pendienteAplicar - $abonoCordobas, 0), 2);
        $abonoDolaresEquivalente = round(min($equivalenteDolares, $pendienteAplicar), 2);
        $abonoDolares = $abonoDolaresEquivalente > 0
            ? round($abonoDolaresEquivalente / $this->tasaCambio(), 2)
            : 0.00;

        $abonoInicialTotal = round($saldoFavorAplicado + $abonoPagoAplicado, 2);
        $saldoPendienteCredito = round(max($total - $abonoInicialTotal, 0), 2);

        return [
            'id_cliente_credito' => (int) $clienteCredito->Id_Cliente_Credito,
            'id_cliente' => (int) $clienteCredito->Id_Cliente,
            'saldo_anterior_favor' => $saldoAnteriorFavor,
            'saldo_favor_aplicado' => $saldoFavorAplicado,
            'saldo_despues_favor' => $saldoDespuesFavor,
            'pago_cordobas_recibido' => $pagoCordobas,
            'pago_dolares_recibido' => $pagoDolares,
            'equivalente_dolares_recibido' => $equivalenteDolares,
            'abono_pago_aplicado' => $abonoPagoAplicado,
            'abono_cordobas' => $abonoCordobas,
            'abono_dolares' => $abonoDolares,
            'abono_dolares_equivalente' => $abonoDolaresEquivalente,
            'abono_inicial_total' => $abonoInicialTotal,
            'saldo_pendiente_credito' => $saldoPendienteCredito,
            'cambio_entregado' => $cambioEntregado,
            'estado_credito' => $saldoPendienteCredito <= 0
                ? self::ESTADO_CREDITO_CANCELADO
                : self::ESTADO_CREDITO_PENDIENTE,
        ];
    }

    private function registrarCreditoContratoInstalacion(
        int $ventaId,
        object $clienteCredito,
        array $credito,
        string $numeroContrato
    ): int {
        $creditoId = DB::table('credito')->insertGetId([
            'Id_Cliente_Credito' => $credito['id_cliente_credito'],
            'Id_Venta' => $ventaId,
            'Fecha_Credito' => now()->toDateString(),
            'Abono_Inicial' => $credito['abono_inicial_total'],
            'Saldo_Actual' => $credito['saldo_pendiente_credito'],
            'Firma_Recibido' => null,
            'Estado' => $credito['estado_credito'],
        ]);

        if ($credito['saldo_favor_aplicado'] > 0) {
            DB::table('cliente_credito')
                ->where('Id_Cliente_Credito', $credito['id_cliente_credito'])
                ->update([
                    'Saldo_Actual' => $credito['saldo_despues_favor'],
                ]);

            DB::table('cliente_credito_movimiento')->insert([
                'Id_Cliente_Credito' => $credito['id_cliente_credito'],
                'Id_Cliente' => $credito['id_cliente'],
                'Id_Venta' => $ventaId,
                'Id_Credito' => $creditoId,
                'Tipo_Movimiento' => 'CARGO',
                'Monto' => $credito['saldo_favor_aplicado'],
                'Saldo_Anterior' => $credito['saldo_anterior_favor'],
                'Saldo_Despues' => $credito['saldo_despues_favor'],
                'Fecha_Movimiento' => now(),
                'Observacion' => 'Saldo a favor aplicado al contrato de instalación ' . $numeroContrato,
            ]);
        }

        $this->registrarAbonosCreditoIniciales($creditoId, $credito, 'Anticipo registrado desde instalación de cámaras ' . $numeroContrato);

        return (int) $creditoId;
    }

    private function registrarAbonosCreditoIniciales(int $creditoId, array $credito, string $observacion): void
    {
        if (($credito['abono_cordobas'] ?? 0) > 0) {
            DB::table('abono_credito')->insert([
                'Id_Credito' => $creditoId,
                'Fecha_Abono' => now(),
                'Moneda' => self::MONEDA_CORDOBA,
                'Monto' => $credito['abono_cordobas'],
                'Numero_Transferencia' => $this->pagoRequiereReferencia($this->tipoPagoCordobas)
                    ? trim($this->referenciaCordobas)
                    : null,
                'Observacion' => $observacion . '. Método: ' . $this->tipoPagoCordobas,
                'Tipo_Cambio' => 1,
                'Monto_Equivalente_Cordobas' => $credito['abono_cordobas'],
            ]);
        }

        if (($credito['abono_dolares'] ?? 0) > 0) {
            DB::table('abono_credito')->insert([
                'Id_Credito' => $creditoId,
                'Fecha_Abono' => now(),
                'Moneda' => self::MONEDA_DOLAR,
                'Monto' => $credito['abono_dolares'],
                'Numero_Transferencia' => $this->pagoRequiereReferencia($this->tipoPagoDolares)
                    ? trim($this->referenciaDolares)
                    : null,
                'Observacion' => $observacion . '. Método: ' . $this->tipoPagoDolares,
                'Tipo_Cambio' => $this->tasaCambio(),
                'Monto_Equivalente_Cordobas' => $credito['abono_dolares_equivalente'],
            ]);
        }
    }

    private function actualizarCreditoContratoExistente(object $credito, float $totalContrato): array
    {
        $abonosPosteriores = round((float) DB::table('abono_credito')
            ->where('Id_Credito', $credito->Id_Credito)
            ->sum('Monto_Equivalente_Cordobas'), 2);

        $pagadoTotal = round((float) ($credito->Abono_Inicial ?? 0) + $abonosPosteriores, 2);
        $saldoPendiente = round(max($totalContrato - $pagadoTotal, 0), 2);

        DB::table('credito')
            ->where('Id_Credito', $credito->Id_Credito)
            ->update([
                'Saldo_Actual' => $saldoPendiente,
                'Estado' => $saldoPendiente <= 0
                    ? self::ESTADO_CREDITO_CANCELADO
                    : self::ESTADO_CREDITO_PENDIENTE,
            ]);

        return [
            'pagado_total' => min($pagadoTotal, $totalContrato),
            'saldo_pendiente' => $saldoPendiente,
            'cambio_entregado' => 0,
        ];
    }

    private function crearVentaContratoInstalacion(int $usuarioId, float $totalContrato, array $cobro): int
    {
        $venta = $this->crearModelo(Venta::class, [
            'Numero_Factura' => $this->generarNumeroFactura(),
            'Fecha_venta' => now(),
            'Id_Cliente' => $this->clienteId,
            'Id_Usuario' => $usuarioId,
            'Tipo_Venta' => $this->tipoOperacion,
            'Estado' => Venta::ESTADO_ACTIVA ?? 1,
            'Descuento' => 0,
            'Total' => $totalContrato,
            'Tipo_Cambio' => $this->tasaCambio(),
            'Cambio_Entregado_Cordobas' => $cobro['cambio_entregado'] ?? 0,
        ]);

        return (int) $venta->Id_Venta;
    }

    private function actualizarVentaContratoInstalacion(int $ventaId, int $usuarioId, float $totalContrato, array $cobro): void
    {
        DB::table('venta')
            ->where('Id_Venta', $ventaId)
            ->update([
                'Id_Cliente' => $this->clienteId,
                'Id_Usuario' => $usuarioId,
                'Tipo_Venta' => $this->tipoOperacion,
                'Estado' => Venta::ESTADO_ACTIVA ?? 1,
                'Descuento' => 0,
                'Total' => $totalContrato,
                'Tipo_Cambio' => $this->tasaCambio(),
                'Cambio_Entregado_Cordobas' => DB::raw('COALESCE(Cambio_Entregado_Cordobas, 0) + ' . (float) ($cobro['cambio_entregado'] ?? 0)),
            ]);
    }

    private function registrarDetalleVentaContratoInstalacion(int $ventaId, int $servicioId, string $numeroContrato): void
    {
        DB::table('detalle_venta')->where('Id_Venta', $ventaId)->delete();

        if ($this->numeroSeguro($this->costoManoObra) > 0) {
            DB::table('detalle_venta')->insert([
                'Id_Venta' => $ventaId,
                'Tipo_Detalle' => 'SERVICIO',
                'Id_Producto' => null,
                'Id_Producto_serie' => null,
                'Id_Servicio' => $servicioId,
                'Id_Tarifa_Copia' => null,
                'Nombre_Formato' => null,
                'Formato_Copia' => null,
                'Lados_Copia' => null,
                'Cantidad' => 1,
                'Precio_Unitario' => round($this->numeroSeguro($this->costoManoObra), 2),
                'Subtotal' => round($this->numeroSeguro($this->costoManoObra), 2),
                'Descuento' => 0,
                'Observacion' => 'Mano de obra instalación de cámaras ' . $numeroContrato,
            ]);
        }

        foreach ($this->productosUsados as $item) {
            DB::table('detalle_venta')->insert([
                'Id_Venta' => $ventaId,
                'Tipo_Detalle' => 'PRODUCTO',
                'Id_Producto' => $item['producto_id'],
                'Id_Producto_serie' => $item['producto_serie_id'],
                'Id_Servicio' => null,
                'Id_Tarifa_Copia' => null,
                'Nombre_Formato' => null,
                'Formato_Copia' => null,
                'Lados_Copia' => null,
                'Cantidad' => $item['cantidad'],
                'Precio_Unitario' => $item['precio'],
                'Subtotal' => $item['subtotal'],
                'Descuento' => 0,
                'Observacion' => 'Material usado en instalación de cámaras ' . $numeroContrato,
            ]);
        }
    }

    private function generarNumeroFactura(): string
    {
        do {
            $numero = 'F-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
        } while (Venta::query()->where('Numero_Factura', $numero)->exists());

        return $numero;
    }

    private function limpiarCobroContrato(): void
    {
        $this->tipoPagoCordobas = self::PAGO_EFECTIVO;
        $this->tipoPagoDolares = self::PAGO_EFECTIVO;
        $this->pagoCordobas = '0';
        $this->pagoDolares = '0';
        $this->referenciaCordobas = '';
        $this->referenciaDolares = '';
    }

    private function numeroSeguro(mixed $valor): float
    {
        if (is_null($valor)) {
            return 0.00;
        }

        if (is_int($valor) || is_float($valor)) {
            return round((float) $valor, 2);
        }

        if (is_string($valor)) {
            $valor = trim($valor);

            if ($valor === '') {
                return 0.00;
            }

            $valor = str_replace(',', '', $valor);
            $valor = preg_replace('/[^\d.]/', '', $valor);

            return $valor === '' ? 0.00 : round((float) $valor, 2);
        }

        return 0.00;
    }

    private function limpiarMonto(?string $valor): float
    {
        $valor = str_replace(',', '', $valor ?? '');
        $limpio = preg_replace('/[^\d.]/', '', $valor);

        return $limpio === '' ? 0 : (float) $limpio;
    }

    private function limpiarDecimal(?string $valor): float
    {
        $valor = str_replace(',', '.', $valor ?? '');
        $limpio = preg_replace('/[^\d.]/', '', $valor);

        return $limpio === '' ? 0 : (float) $limpio;
    }

    private function formatearMonto(?string $valor): string
    {
        $limpio = preg_replace('/[^\d]/', '', $valor ?? '');

        if ($limpio === '') {
            return '';
        }

        return number_format((int) $limpio, 0, '.', ',');
    }

    private function formatearDecimal(?string $valor): string
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

        return $tasa > 0 ? $tasa : $this->tipoCambioActual();
    }

    private function tipoCambioActualFormateada(): string
    {
        return number_format($this->tipoCambioActual(), 2, '.', '');
    }

    private function tipoCambioActual(): float
    {
        // MODIFICADO: la apertura actualiza tasa_cambio; usamos la última tasa registrada.
        $tasa = DB::table('tasa_cambio')
            ->orderByDesc('Fecha_Modificacion')
            ->orderByDesc('Id_Tasa_Cambio')
            ->value('Valor_Cambio');

        $tasa = round((float) ($tasa ?? 0), 2);

        return $tasa > 0 ? $tasa : 36.50;
    }

    private function pagoRequiereReferencia(string $tipoPago): bool
    {
        return in_array($tipoPago, [
            self::PAGO_TRANSFERENCIA,
            self::PAGO_TARJETA,
        ], true);
    }

    private function usuarioActualId(): int
    {
        $id = session('Id_Usuario')
            ?? session('id_usuario')
            ?? session('usuario_id')
            ?? auth()->user()?->Id_Usuario
            ?? auth()->id();

        if (!$id) {
            $id = Usuario::query()
                ->where('Estado', 1)
                ->value('Id_Usuario');
        }

        if (!$id) {
            throw new \RuntimeException('No hay usuario activo para registrar el movimiento.');
        }

        return (int) $id;
    }

    private function servicioPorTipo(string $tipo): int
    {
        $id = Servicio::query()
            ->where('Tipo_Servicio', $tipo)
            ->where('Estado', 1)
            ->value('Id_Servicio');

        if ($id) {
            return (int) $id;
        }

        $datos = match ($tipo) {
            'INSTALACION' => [
                'Nombre_Servicio' => 'Instalación de cámaras',
                'Descripcion' => 'Contrato de instalación, configuración y pruebas de sistemas de cámaras.',
                'Tipo_Servicio' => 'INSTALACION',
                'Unidad_Medida' => 'CONTRATO',
            ],
            default => [
                'Nombre_Servicio' => 'Servicio general',
                'Descripcion' => 'Servicio registrado desde el sistema.',
                'Tipo_Servicio' => 'GENERAL',
                'Unidad_Medida' => 'SERVICIO',
            ],
        };

        $servicio = $this->crearModelo(Servicio::class, array_merge($datos, [
            'Precio_Base' => 0,
            'Requiere_Contrato' => 1,
            'Requiere_Anticipo' => 1,
            'Porcentaje_Anticipo' => 30,
            'Garantia' => 1,
            'Estado' => 1,
            'Permite_Credito' => 1,
        ]));

        return (int) $servicio->Id_Servicio;
    }

    private function generarNumeroUnico(string $prefijo, string $modelo, string $columna): string
    {
        do {
            $numero = $prefijo . '-' . now()->format('Ymd') . '-' . str_pad(
                (string) random_int(1, 9999),
                4,
                '0',
                STR_PAD_LEFT
            );
        } while ($modelo::query()->where($columna, $numero)->exists());

        return $numero;
    }

    private function descontarInventario(array $item, string $estadoSerie, string $tipoMovimiento): void
    {
        $producto = Producto::query()
            ->where('Id_Producto', $item['producto_id'])
            ->lockForUpdate()
            ->first();

        if (!$producto || (int) $producto->Estado !== 1) {
            throw new \RuntimeException('Producto no disponible: ' . $item['descripcion']);
        }

        $cantidad = (int) ceil((float) $item['cantidad']);

        if ($cantidad <= 0) {
            throw new \RuntimeException('Cantidad inválida para: ' . $item['descripcion']);
        }

        if ((int) $producto->Stock_Actual < $cantidad) {
            throw new \RuntimeException('Stock insuficiente para: ' . $item['descripcion']);
        }

        if ($item['producto_serie_id']) {
            $serie = ProductoSerie::query()
                ->where('id_producto_serie', $item['producto_serie_id'])
                ->lockForUpdate()
                ->first();

            if (!$serie || $serie->Estado !== 'DISPONIBLE') {
                throw new \RuntimeException('La serie ya no está disponible: ' . $item['serie']);
            }

            $serie->forceFill([
                'Estado' => $estadoSerie,
                'Observacion' => 'Instalado en contrato de cámaras',
            ])->save();
        }

        $producto->decrement('Stock_Actual', $cantidad);

        $this->crearModelo(MovimientoInventario::class, [
            'Id_Producto' => $item['producto_id'],
            'Id_Producto_Serie' => $item['producto_serie_id'],
            'Fecha_Movimiento' => now(),
            'Tipo_Movimiento' => $tipoMovimiento,
            'Cantidad' => $cantidad,
            'Motivo_Movimiento' => 1,
        ]);
    }

    private function crearModelo(string $modelo, array $datos): Model
    {
        /** @var Model $instancia */
        $instancia = new $modelo();

        $instancia->forceFill($datos);
        $instancia->save();

        return $instancia;
    }

    private function normalizarFechaInput(mixed $fecha): ?string
    {
        if (!$fecha) {
            return null;
        }

        if ($fecha instanceof \DateTimeInterface) {
            return $fecha->format('Y-m-d');
        }

        return substr((string) $fecha, 0, 10);
    }

    private function limpiarTexto(?string $texto): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $texto));
    }

    private function mostrarMensaje(string $tipo, string $titulo, string $descripcion): void
    {
        // MODIFICADO: antes se guardaba el mensaje en una propiedad y quedaba fijo en pantalla.
        // Ahora se dispara como toast temporal de MaryUI y desaparece automáticamente.
        match ($tipo) {
            'error' => $this->error(
                $titulo,
                $descripcion,
                position: 'toast-top toast-end',
                timeout: 3500
            ),
            'warning' => $this->warning(
                $titulo,
                $descripcion,
                position: 'toast-top toast-end',
                timeout: 3000
            ),
            'info' => $this->info(
                $titulo,
                $descripcion,
                position: 'toast-top toast-end',
                timeout: 2500
            ),
            default => $this->success(
                $titulo,
                $descripcion,
                position: 'toast-top toast-end',
                timeout: 2500
            ),
        };
    }
};
?>

<div class="h-[calc(100vh-3rem)] min-h-0 overflow-hidden bg-[#F0F3F7]">
    <div class="flex h-full min-h-0 flex-col gap-4 px-4 py-4 md:px-6">

        <div
            class="sticky top-0 z-20 -mx-4 -mt-4 border-b border-[#D7E4F3] bg-[#F0F3F7]/95 px-4 py-4 backdrop-blur md:-mx-6 md:px-6">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-black tracking-tight text-[#1A2B42]">
                            Instalación de cámaras
                        </h1>

                        @if($contratoInstalacionIdSeleccionado)
                        <span class="rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-bold text-[#0B6FE4]">
                            Editando #{{ $contratoInstalacionIdSeleccionado }}
                        </span>
                        @else
                        <span
                            class="rounded-full bg-white px-3 py-1 text-xs font-bold text-[#5F6B7A] ring-1 ring-[#D7E4F3]">
                            Nuevo contrato
                        </span>
                        @endif
                    </div>

                    <p class="mt-1 text-sm text-[#5F6B7A]">
                        Registro del contrato, materiales, condiciones y resumen económico en una sola pantalla.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="cambiarTipoOperacion('CONTADO')"
                        class="{{ $tipoOperacion === 'CONTADO' ? 'bg-[#0B6FE4] text-white shadow-sm' : 'bg-white text-[#1A2B42]' }} inline-flex h-10 items-center justify-center rounded-xl border border-[#D7E4F3] px-4 text-sm font-bold transition hover:bg-[#F7F9FC]">
                        Contado
                    </button>

                    <button type="button" wire:click="cambiarTipoOperacion('CREDITO')"
                        class="{{ $tipoOperacion === 'CREDITO' ? 'bg-[#0B6FE4] text-white shadow-sm' : 'bg-white text-[#1A2B42]' }} inline-flex h-10 items-center justify-center rounded-xl border border-[#D7E4F3] px-4 text-sm font-bold transition hover:bg-[#F7F9FC]">
                        Crédito
                    </button>

                    <x-button icon="o-folder-open" label="Buscar pendientes" wire:click="abrirPendientes"
                        spinner="abrirPendientes"
                        class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-bold text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />

                    <x-button icon="o-document-plus" label="Nuevo" wire:click="nuevoContrato"
                        class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-bold text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />
                </div>
            </div>
        </div>

        {{-- MODIFICADO: se eliminó el bloque manual de mensaje fijo; MaryUI renderiza el toast temporal
        automáticamente. --}}

        @php
        $totalMateriales = $this->totalMaterialesContrato();
        $totalContrato = $this->totalContratoActual();
        $anticipo = $this->anticipoEsperadoContrato();
        $saldo = $this->saldoContrato();

        @endphp

        {{-- MODIFICADO: resumen visual temporal cuando existen errores de validación en campos. --}}
        @if ($errors->any())
        <div wire:key="validation-summary-{{ md5(implode('|', $errors->all())) }}" x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3800)" x-show="show" x-transition.opacity.duration.200ms
            class="shrink-0 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 shadow-sm">
            <div class="flex items-start gap-3">
                <span
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-100 text-sm font-black text-red-700">!</span>
                <div>
                    <p class="font-black">Revisá los campos marcados.</p>
                    <p class="text-xs font-semibold text-red-600">Las alertas se ocultan solas; al intentar guardar se
                        validan nuevamente.</p>
                </div>
            </div>
        </div>
        @endif

        <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 overflow-hidden xl:grid-cols-12">
            <div class="min-h-0 overflow-y-auto pr-0 xl:col-span-8 xl:pr-1">
                <div class="space-y-4">

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="text-lg font-black text-[#1A2B42]">Datos del contrato</h2>
                                <p class="text-sm text-[#5F6B7A]">
                                    Cliente, técnico, ubicación y condiciones principales de instalación.
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#EAF2FB] px-4 py-2 text-right">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#0B6FE4]">Total contrato</p>
                                <p class="text-xl font-black text-[#1A2B42]">
                                    C$ {{ number_format($totalContrato, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">
                                    {{ $tipoOperacion === 'CREDITO' ? 'Institución' : 'Cliente' }}
                                </label>

                                <div class="relative">
                                    <x-input wire:model.live.debounce.300ms="filtroCliente"
                                        wire:focus="abrirBusquedaClientes" wire:keydown.escape="cerrarBusquedaClientes"
                                        icon="o-magnifying-glass"
                                        placeholder="{{ $tipoOperacion === 'CREDITO' ? 'Buscar institución por nombre' : 'Buscar cliente por teléfono o nombre' }}"
                                        class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />

                                    @if($mostrarBusquedaClientes)
                                    <div
                                        class="absolute left-0 right-0 z-50 mt-2 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white shadow-xl">
                                        <div class="max-h-72 overflow-y-auto">
                                            @forelse($clientes as $cliente)
                                            <button type="button" wire:click="seleccionarCliente({{ $cliente['id'] }})"
                                                class="flex w-full items-start gap-3 border-b border-[#EEF3F8] px-3 py-2 text-left transition hover:bg-[#EAF2FB]">
                                                <span
                                                    class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#EAF2FB] text-[#0B6FE4]">
                                                    <x-icon
                                                        :name="$tipoOperacion === 'CREDITO' ? 'o-building-office-2' : 'o-user'"
                                                        class="h-4 w-4" />
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block truncate text-sm font-bold text-[#1A2B42]">{{
                                                        $cliente['name'] }}</span>
                                                    <span
                                                        class="block text-[11px] font-semibold uppercase tracking-wide text-[#5F6B7A]">
                                                        {{ $tipoOperacion === 'CREDITO' ? 'Cliente institucional' :
                                                        'Cliente normal' }}
                                                    </span>
                                                </span>
                                            </button>
                                            @empty
                                            <div class="px-4 py-5 text-center text-sm font-semibold text-[#5F6B7A]">
                                                No encontré coincidencias con esa búsqueda.
                                            </div>
                                            @endforelse
                                        </div>

                                        <div
                                            class="flex flex-col gap-2 bg-[#F7F9FC] px-3 py-2 text-xs font-semibold text-[#5F6B7A] sm:flex-row sm:items-center sm:justify-between">
                                            <span>Mostrando {{ count($clientes) }} de {{ $totalClientesBusqueda }}
                                                registro(s)</span>
                                            <div class="flex justify-end gap-2">
                                                @if($hayMasClientes)
                                                <x-button label="Cargar más" wire:click="cargarMasClientes"
                                                    class="h-8 min-h-8 rounded-xl bg-white px-3 text-xs font-bold text-[#1A2B42] hover:bg-[#EAF2FB]" />
                                                @endif
                                                <x-button icon="o-x-mark" label="Cerrar"
                                                    wire:click="cerrarBusquedaClientes"
                                                    class="h-8 min-h-8 rounded-xl border border-[#D7E4F3] bg-white px-3 text-xs font-bold text-[#1A2B42] hover:bg-[#EAF2FB]" />
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <p class="mt-1 text-[11px] font-semibold text-[#5F6B7A]">
                                    {{ $tipoOperacion === 'CREDITO' ? 'En crédito se listan instituciones y se busca por
                                    nombre institucional.' : 'En contado se listan clientes normales y podés buscar por
                                    teléfono.' }}
                                </p>
                                @error('clienteId')
                                <div wire:key="field-error-clienteId-{{ md5($message) }}" x-data="{ show: true }"
                                    x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('clienteId') }, 4500)"
                                    x-show="show" x-transition.opacity.duration.200ms
                                    class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Teléfono</label>
                                <x-input wire:model="telefonoCliente" readonly
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Municipio</label>
                                <x-input wire:model="municipio"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('municipio')
                                <div wire:key="field-error-municipio-{{ md5($message) }}" x-data="{ show: true }"
                                    x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('municipio') }, 4500)"
                                    x-show="show" x-transition.opacity.duration.200ms
                                    class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Cámaras</label>
                                <x-input wire:model.live="cantidadCamaras" type="number"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('cantidadCamaras')
                                <div wire:key="field-error-cantidadCamaras-{{ md5($message) }}" x-data="{ show: true }"
                                    x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('cantidadCamaras') }, 4500)"
                                    x-show="show" x-transition.opacity.duration.200ms
                                    class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Metros cableado</label>
                                <x-input wire:model.live="metrosCableado" type="number" step="0.01"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('metrosCableado')
                                <div wire:key="field-error-metrosCableado-{{ md5($message) }}" x-data="{ show: true }"
                                    x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('metrosCableado') }, 4500)"
                                    x-show="show" x-transition.opacity.duration.200ms
                                    class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Mano de obra</label>
                                <x-input wire:model.live="costoManoObra" type="number" step="0.01" prefix="C$"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('costoManoObra')
                                <div wire:key="field-error-costoManoObra-{{ md5($message) }}" x-data="{ show: true }"
                                    x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('costoManoObra') }, 4500)"
                                    x-show="show" x-transition.opacity.duration.200ms
                                    class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Anticipo</label>
                                <x-input wire:model.live="porcentajeAnticipo" type="number" step="0.01" suffix="%"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('porcentajeAnticipo')
                                <div wire:key="field-error-porcentajeAnticipo-{{ md5($message) }}"
                                    x-data="{ show: true }"
                                    x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('porcentajeAnticipo') }, 4500)"
                                    x-show="show" x-transition.opacity.duration.200ms
                                    class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div class="xl:col-span-2">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Técnico asignado</label>

                                <x-select wire:model="tecnicoId" :options="$tecnicos" option-value="id"
                                    option-label="name" placeholder="Opcional"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('tecnicoId')
                                <div wire:key="field-error-tecnicoId-{{ md5($message) }}" x-data="{ show: true }"
                                    x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('tecnicoId') }, 4500)"
                                    x-show="show" x-transition.opacity.duration.200ms
                                    class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Fecha estimada</label>
                                <x-input wire:model="fechaEstimada" type="date"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('fechaEstimada')
                                <div wire:key="field-error-fechaEstimada-{{ md5($message) }}" x-data="{ show: true }"
                                    x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('fechaEstimada') }, 4500)"
                                    x-show="show" x-transition.opacity.duration.200ms
                                    class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Estado</label>

                                <x-select wire:model="estadoContrato" :options="$estadosContrato" option-value="id"
                                    option-label="name"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('estadoContrato')
                                <div wire:key="field-error-estadoContrato-{{ md5($message) }}" x-data="{ show: true }"
                                    x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('estadoContrato') }, 4500)"
                                    x-show="show" x-transition.opacity.duration.200ms
                                    class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div class="md:col-span-2 xl:col-span-4">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">
                                    Dirección de instalación
                                </label>

                                <x-input wire:model="direccionInstalacion"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('direccionInstalacion')
                                <div wire:key="field-error-direccionInstalacion-{{ md5($message) }}"
                                    x-data="{ show: true }"
                                    x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('direccionInstalacion') }, 4500)"
                                    x-show="show" x-transition.opacity.duration.200ms
                                    class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>

                            <div class="md:col-span-2 xl:col-span-4">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">
                                    Detalle del contrato
                                </label>

                                <x-textarea wire:model="detalleContrato" rows="2"
                                    class="w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('detalleContrato')
                                <div wire:key="field-error-detalleContrato-{{ md5($message) }}" x-data="{ show: true }"
                                    x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('detalleContrato') }, 4500)"
                                    x-show="show" x-transition.opacity.duration.200ms
                                    class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                    <span>{{ $message }}</span>
                                </div>
                                @enderror
                            </div>
                        </div>
                    </x-card>

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <div class="mb-4">
                            <h2 class="text-lg font-black text-[#1A2B42]">Condiciones del servicio</h2>
                            <p class="text-sm text-[#5F6B7A]">
                                Checklist rápido de instalación y entrega.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-4">
                            @foreach($condicionesChecklist as $key => $label)
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-2xl border border-[#2E8BC0] bg-[#F7F9FC] px-4 py-3 text-sm font-bold text-[#1A2B42] transition hover:bg-[#EAF2FB]">
                                <x-checkbox wire:model.live="checklist.{{ $key }}"
                                    class="checkbox-sm border-2 border-[#2E8BC0] bg-white text-white checked:border-[#0B6FE4] checked:bg-[#0B6FE4] checked:[--chkbg:#0B6FE4] checked:[--chkfg:white]" />

                                <span class="leading-tight">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <label class="mb-1 block text-sm font-bold text-[#1A2B42]">
                                Observación del checklist
                            </label>

                            <x-textarea wire:model="checklist.observacion_checklist" rows="2"
                                class="w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                        </div>
                    </x-card>

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="text-lg font-black text-[#1A2B42]">Materiales / productos usados</h2>
                                <p class="text-sm text-[#5F6B7A]">
                                    Agregá productos directo aquí. Las series instaladas salen del inventario
                                    disponible.
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#EAF2FB] px-4 py-2 text-right">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#0B6FE4]">Materiales</p>
                                <p class="text-xl font-black text-[#1A2B42]">
                                    C$ {{ number_format($totalMateriales, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="mb-4 rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-3"
                            wire:keydown.enter.prevent="agregarProducto">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                <div class="md:col-span-5">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Producto</label>

                                    <x-input wire:model.live.debounce.300ms="filtroProducto" icon="o-magnifying-glass"
                                        placeholder="Buscar producto por nombre, marca, modelo o código"
                                        class="mb-2 h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />

                                    <x-select wire:model.live="productoId" :options="$productosDisponibles"
                                        option-value="id" option-label="name" placeholder="Seleccione producto"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    @error('productoId')
                                    <div wire:key="field-error-productoId-{{ md5($message) }}" x-data="{ show: true }"
                                        x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('productoId') }, 4500)"
                                        x-show="show" x-transition.opacity.duration.200ms
                                        class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                        <span
                                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                        <span>{{ $message }}</span>
                                    </div>
                                    @enderror
                                </div>

                                @if($productoTieneSeries)
                                <div class="md:col-span-3">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Serie</label>

                                    <x-select wire:model="productoSerieId" :options="$seriesDisponibles"
                                        option-value="id" option-label="name" placeholder="Seleccione serie"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    @error('productoSerieId')
                                    <div wire:key="field-error-productoSerieId-{{ md5($message) }}"
                                        x-data="{ show: true }"
                                        x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('productoSerieId') }, 4500)"
                                        x-show="show" x-transition.opacity.duration.200ms
                                        class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                        <span
                                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                        <span>{{ $message }}</span>
                                    </div>
                                    @enderror
                                </div>
                                @else
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Cantidad</label>

                                    <x-input wire:model="productoCantidad" type="number" step="0.01"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    @error('productoCantidad')
                                    <div wire:key="field-error-productoCantidad-{{ md5($message) }}"
                                        x-data="{ show: true }"
                                        x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('productoCantidad') }, 4500)"
                                        x-show="show" x-transition.opacity.duration.200ms
                                        class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                        <span
                                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                        <span>{{ $message }}</span>
                                    </div>
                                    @enderror
                                </div>
                                @endif

                                <div class="{{ $productoTieneSeries ? 'md:col-span-2' : 'md:col-span-2' }}">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Precio</label>

                                    <x-input wire:model="productoPrecio" type="number" step="0.01" prefix="C$"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    @error('productoPrecio')
                                    <div wire:key="field-error-productoPrecio-{{ md5($message) }}"
                                        x-data="{ show: true }"
                                        x-init="setTimeout(() => { show = false; $wire.limpiarErrorCampo('productoPrecio') }, 4500)"
                                        x-show="show" x-transition.opacity.duration.200ms
                                        class="mt-1.5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-snug text-red-700 shadow-sm">
                                        <span
                                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[11px] font-black text-red-700">!</span>
                                        <span>{{ $message }}</span>
                                    </div>
                                    @enderror
                                </div>

                                <div
                                    class="{{ $productoTieneSeries ? 'md:col-span-2' : 'md:col-span-3' }} flex items-end">
                                    <x-button icon="o-plus" label="Agregar" wire:click="agregarProducto"
                                        class="h-10 min-h-10 w-full rounded-xl border-0 bg-[#2E8BC0] text-sm font-bold text-white hover:bg-[#0B6FE4]" />
                                </div>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-[#D7E4F3]">
                            <x-table :headers="$headers" :rows="$productosUsados"
                                class="[&_thead_th]:bg-[#2E8BC0] [&_thead_th]:font-bold [&_thead_th]:text-white [&_tbody_tr:hover]:bg-[#F7F9FC]">

                                @scope('cell_cantidad', $row)
                                {{ number_format((float) $row['cantidad'], 2) }}
                                @endscope

                                @scope('cell_precio', $row)
                                C$ {{ number_format((float) $row['precio'], 2) }}
                                @endscope

                                @scope('cell_subtotal', $row)
                                C$ {{ number_format((float) $row['subtotal'], 2) }}
                                @endscope

                                @scope('cell_acciones', $row)
                                @if(!empty($row['ya_guardado']))
                                <span class="rounded-full bg-[#EAF2FB] px-2 py-1 text-xs font-bold text-[#0B6FE4]">
                                    Guardado
                                </span>
                                @else
                                <x-button icon="o-trash" wire:click="quitarProducto('{{ $row['tmp_id'] }}')"
                                    class="btn-ghost btn-sm text-red-600 hover:bg-red-50" />
                                @endif
                                @endscope
                            </x-table>
                        </div>
                    </x-card>
                </div>
            </div>

            <div class="min-h-0 overflow-y-auto xl:col-span-4">
                <div class="space-y-4">

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <h2 class="mb-3 text-lg font-black text-[#1A2B42]">Resumen</h2>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Estado</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    {{ $this->estadoNombre($estadoContrato) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Cámaras</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    {{ (int) $cantidadCamaras }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Materiales</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    C$ {{ number_format($totalMateriales, 2) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Mano de obra</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    C$ {{ number_format((float) $costoManoObra, 2) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Anticipo</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    C$ {{ number_format($anticipo, 2) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Saldo</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    C$ {{ number_format($saldo, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-3">
                            <div class="mb-3">
                                <h3 class="text-sm font-black text-[#1A2B42]">Pago recibido</h3>
                            </div>

                            <div class="grid grid-cols-1 gap-2">
                                <div>
                                    <label class="mb-1 block text-xs font-bold text-[#1A2B42]">Tipo cambio</label>
                                    <x-input wire:model.live.debounce.250ms="tipoCambio" type="text" inputmode="decimal"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-[#1A2B42]">Tipo C$</label>
                                        <select wire:model.live="tipoPagoCordobas"
                                            class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm text-[#1A2B42]">
                                            <option value="EFECTIVO">Efectivo</option>
                                            <option value="TRANSFERENCIA">Transferencia</option>
                                            <option value="TARJETA">Tarjeta</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-[#1A2B42]">Pago C$</label>
                                        <x-input wire:model.live.debounce.250ms="pagoCordobas" type="text"
                                            inputmode="numeric"
                                            class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    </div>
                                </div>

                                @if(in_array($tipoPagoCordobas, ['TRANSFERENCIA', 'TARJETA'], true))
                                <x-input wire:model.live.debounce.250ms="referenciaCordobas" type="text"
                                    placeholder="Referencia C$"
                                    class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                @endif

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-[#1A2B42]">Tipo US$</label>
                                        <select wire:model.live="tipoPagoDolares"
                                            class="h-10 w-full rounded-xl border-0 bg-white px-3 text-sm text-[#1A2B42]">
                                            <option value="EFECTIVO">Efectivo</option>
                                            <option value="TRANSFERENCIA">Transferencia</option>
                                            <option value="TARJETA">Tarjeta</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-[#1A2B42]">Pago US$</label>
                                        <x-input wire:model.live.debounce.250ms="pagoDolares" type="text"
                                            inputmode="decimal"
                                            class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    </div>
                                </div>

                                @if(in_array($tipoPagoDolares, ['TRANSFERENCIA', 'TARJETA'], true))
                                <x-input wire:model.live.debounce.250ms="referenciaDolares" type="text"
                                    placeholder="Referencia US$"
                                    class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                @endif

                                <div class="grid grid-cols-3 gap-2 text-xs">
                                    <div class="rounded-xl bg-white p-2">
                                        <span class="block text-[#5F6B7A]">Recibido</span>
                                        <strong class="text-[#1A2B42]">C$ {{ number_format($this->totalPagadoCordobas(),
                                            2) }}</strong>
                                    </div>
                                    <div class="rounded-xl bg-white p-2">
                                        <span class="block text-[#5F6B7A]">Saldo</span>
                                        <strong class="text-[#1A2B42]">C$ {{ number_format($this->saldoContrato(), 2)
                                            }}</strong>
                                    </div>
                                    <div class="rounded-xl bg-white p-2">
                                        <span class="block text-[#5F6B7A]">Cambio</span>
                                        <strong class="text-[#1A2B42]">C$ {{ number_format($this->cambioContrato(), 2)
                                            }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($tipoOperacion === 'CREDITO')
                        <div class="mt-3 rounded-2xl border border-[#B7D6F2] bg-[#EAF4FD] p-3">
                            <h3 class="text-sm font-black text-[#1A2B42]">Crédito institucional</h3>

                            <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                                <div class="rounded-xl bg-white p-2">
                                    <span class="block text-[#5F6B7A]">Saldo a favor</span>
                                    <strong class="text-[#1A2B42]">C$ {{ number_format($this->saldoFavorDisponible(), 2)
                                        }}</strong>
                                </div>
                                <div class="rounded-xl bg-white p-2">
                                    <span class="block text-[#5F6B7A]">Aplicado</span>
                                    <strong class="text-[#1A2B42]">C$ {{
                                        number_format($this->saldoFavorAplicadoContrato(), 2) }}</strong>
                                </div>
                                <div class="rounded-xl bg-white p-2">
                                    <span class="block text-[#5F6B7A]">Nuevo crédito</span>
                                    <strong class="text-[#1A2B42]">C$ {{ number_format($this->saldoCreditoContrato(), 2)
                                        }}</strong>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="mt-3 rounded-2xl bg-[#2E8BC0] p-4 text-white">
                            <p class="text-xs font-bold uppercase tracking-wide text-white/80">Total general</p>
                            <p class="text-2xl font-black">
                                C$ {{ number_format($totalContrato, 2) }}
                            </p>
                        </div>

                        <x-button icon="o-check"
                            label="{{ $contratoInstalacionIdSeleccionado ? ($tipoOperacion === 'CREDITO' ? 'Actualizar crédito' : 'Actualizar contrato') : ($tipoOperacion === 'CREDITO' ? 'Guardar crédito' : 'Guardar contrato') }}"
                            wire:click="guardar" spinner="guardar"
                            class="mt-3 h-11 min-h-11 w-full rounded-xl border-0 bg-[#2E8BC0] text-sm font-black text-white hover:bg-[#0B6FE4]" />
                    </x-card>


                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <h2 class="mb-3 text-lg font-black text-[#1A2B42]">Materiales agregados</h2>

                        <div class="space-y-2">
                            @forelse(array_slice($productosUsados, 0, 6) as $item)
                            <div class="rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-[#1A2B42]">
                                            {{ $item['descripcion'] }}
                                        </p>

                                        <p class="truncate text-xs font-semibold text-[#5F6B7A]">
                                            Serie: {{ $item['serie'] }}
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full bg-white px-2 py-1 text-[11px] font-black text-[#0B6FE4] ring-1 ring-[#D7E4F3]">
                                        x{{ number_format((float) $item['cantidad'], 2) }}
                                    </span>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <p class="text-xs text-[#5F6B7A]">
                                        C$ {{ number_format((float) $item['precio'], 2) }} c/u
                                    </p>

                                    <p class="shrink-0 text-xs font-black text-[#1A2B42]">
                                        C$ {{ number_format((float) $item['subtotal'], 2) }}
                                    </p>
                                </div>
                            </div>
                            @empty
                            <div
                                class="rounded-2xl border border-dashed border-[#D7E4F3] bg-[#F7F9FC] px-4 py-8 text-center">
                                <p class="text-sm font-bold text-[#1A2B42]">Sin materiales</p>
                                <p class="text-xs text-[#5F6B7A]">
                                    Agregá productos para calcular el contrato.
                                </p>
                            </div>
                            @endforelse
                        </div>
                    </x-card>

                </div>
            </div>
        </div>
    </div>


    <x-modal wire:model="modalPendientes" title="Contratos de instalación pendientes" separator class="backdrop-blur-sm"
        box-class="w-[96vw] max-w-6xl rounded-3xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">
        <div class="space-y-3">
            <x-input wire:model.live.debounce.350ms="filtroPendientes" icon="o-magnifying-glass"
                placeholder="Buscar por contrato, cliente, municipio o dirección..."
                class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

            <div class="max-h-[60vh] overflow-auto rounded-2xl border border-[#D7E4F3]">
                <table class="w-full min-w-190 text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-[#2E8BC0] text-white">
                        <tr>
                            <th class="px-3 py-2 font-bold">Contrato</th>
                            <th class="px-3 py-2 font-bold">Cliente</th>
                            <th class="px-3 py-2 font-bold">Ubicación</th>
                            <th class="px-3 py-2 font-bold">Estado</th>
                            <th class="px-3 py-2 font-bold">Saldo</th>
                            <th class="px-3 py-2 text-center font-bold">Acción</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[#D7E4F3] bg-white text-[#1A2B42]">
                        @forelse($contratosPendientes as $item)
                        <tr class="hover:bg-[#F7F9FC]">
                            <td class="px-3 py-2 font-black">{{ $item['numero'] }}</td>
                            <td class="px-3 py-2">{{ $item['cliente'] }}</td>
                            <td class="px-3 py-2">{{ $item['ubicacion'] }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full bg-[#EAF2FB] px-2 py-1 text-xs font-black text-[#0B6FE4]">
                                    {{ $this->estadoNombre($item['estado']) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 font-bold">C$ {{ number_format((float) $item['saldo'], 2) }}</td>
                            <td class="px-3 py-2 text-center">
                                <x-button icon="o-arrow-down-tray" label="Cargar"
                                    wire:click="cargarPendiente({{ $item['id'] }})"
                                    class="h-8 min-h-8 rounded-xl border-0 bg-[#2E8BC0] px-3 text-xs font-bold text-white hover:bg-[#0B6FE4]" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-[#5F6B7A]">
                                No hay contratos pendientes con ese filtro.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-2">
                <x-button label="Anterior" wire:click="paginaAnteriorPendientes" :disabled="$paginaPendientes <= 1"
                    class="h-8 min-h-8 rounded-xl border border-[#D7E4F3] bg-white px-3 text-xs font-bold text-[#1A2B42] hover:bg-[#EAF2FB]" />
                <x-button label="Siguiente" wire:click="paginaSiguientePendientes"
                    :disabled="$paginaPendientes >= $totalPaginasPendientes"
                    class="h-8 min-h-8 rounded-xl border border-[#D7E4F3] bg-white px-3 text-xs font-bold text-[#1A2B42] hover:bg-[#EAF2FB]" />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalPendientes', false)"
                class="rounded-xl border border-[#D7E4F3] bg-white text-[#1A2B42]" />
        </x-slot:actions>
    </x-modal>

</div>
