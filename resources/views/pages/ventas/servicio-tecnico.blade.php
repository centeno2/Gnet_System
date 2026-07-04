<?php

use Livewire\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mary\Traits\Toast;

use App\Models\Cliente;
use App\Models\Trabajador;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Servicio;
use App\Models\ServicioTecnico;
use App\Models\ServicioTecnicoChecklist;
use App\Models\ServicioTecnicoProducto;
use App\Models\MovimientoInventario;
use App\Models\Usuario;
use App\Models\Venta;

new class extends Component
{

    use Toast;

    private const MONEDA_CORDOBA = 'NIO';
    private const MONEDA_DOLAR = 'USD';

    private const PAGO_EFECTIVO = 'EFECTIVO';
    private const PAGO_TRANSFERENCIA = 'TRANSFERENCIA';
    private const PAGO_TARJETA = 'TARJETA';

    private const TIPO_CONTADO = 'CONTADO';
    private const TIPO_CREDITO = 'CREDITO';

    private const TIPO_CLIENTE_NATURAL = 1;
    private const TIPO_CLIENTE_INSTITUCION = 2;

    private const ESTADO_CREDITO_PENDIENTE = 'PENDIENTE';
    private const ESTADO_CREDITO_CANCELADO = 'CANCELADO';

    // MODIFICADO: el sistema permite consultar todos los registros,
    // pero solo renderiza una página o un bloque pequeño por petición para no romper Livewire.
    private const RESULTADOS_BUSQUEDA_SELECT = 75;
    private const CLIENTES_POR_PAGINA = 15;
    private const PRODUCTOS_POR_PAGINA = 15;
    private const PENDIENTES_POR_PAGINA = 12;

    public array $clientes = [];
    public array $tecnicos = [];
    public array $productosDisponibles = [];
    public array $seriesDisponibles = [];
    public array $productos = [];
    public array $serviciosPendientes = [];

    // MODIFICADO: búsqueda dinámica de clientes sin cargar miles de registros en el snapshot.
    public string $filtroCliente = '';
    public bool $mostrarBusquedaClientes = false;
    public bool $hayMasClientes = false;
    public int $paginaBusquedaClientes = 1;
    public int $totalClientesBusqueda = 0;
    public string $clienteSeleccionadoNombre = '';

    public string $filtroProducto = '';
    public bool $mostrarBusquedaProductos = false;
    public bool $hayMasProductos = false;
    public int $paginaBusquedaProductos = 1;
    public int $totalProductosBusqueda = 0;
    public string $productoSeleccionadoNombre = '';

    // MODIFICADO: listados completos paginados. No hay límite total de registros,
    // solo se pagina para que el snapshot de Livewire no se vuelva gigante.
    public bool $modalClientes = false;
    public bool $modalProductos = false;
    public array $clientesListado = [];
    public array $productosListado = [];
    public string $filtroListadoClientes = '';
    public string $filtroListadoProductos = '';
    public int $paginaClientes = 1;
    public int $paginaProductos = 1;
    public int $totalClientesListado = 0;
    public int $totalProductosListado = 0;
    public int $totalPaginasClientes = 1;
    public int $totalPaginasProductos = 1;

    public ?int $servicioTecnicoIdSeleccionado = null;
    public bool $servicioPagado = false;

    public bool $modalPendientes = false;
    public string $filtroPendientes = '';

    public bool $modalVoucherPdf = false;
    public string $voucherPdfUrl = '';
    public int $paginaPendientes = 1;
    public int $totalPendientes = 0;
    public int $totalPaginasPendientes = 1;

    public string $tipoOperacion = self::TIPO_CONTADO;

    public ?int $clienteId = null;
    public string $telefonoCliente = '';
    public ?int $tecnicoId = null;
    public string $tipoEquipo = '';
    public string $marca = '';
    public string $modelo = '';
    public string $numeroSerie = '';
    public string $problemaReportado = '';
    public string $detalleDescriptivo = '';
    public string $estadoServicio = 'RECIBIDO';
    public $costoEstimado = 0;
    public ?string $fechaEstimadaEntrega = null;
    public string $observacionTecnica = '';

    public string $tipoCambio = '36.50';
    public string $tipoPagoCordobas = self::PAGO_EFECTIVO;
    public string $tipoPagoDolares = self::PAGO_EFECTIVO;
    public string $pagoCordobas = '0';
    public string $pagoDolares = '0';
    public string $referenciaCordobas = '';
    public string $referenciaDolares = '';

    public array $checklist = [
        'enciende' => false,
        'lleva_cargador' => false,
        'lleva_bateria' => false,
        'pantalla_sana' => false,
        'teclado_completo' => false,
        'touchpad_funcional' => false,
        'tiene_golpes_visibles' => false,
        'tiene_humedad' => false,
        'tiene_sello_roto' => false,
        'lleva_cable_poder' => false,
        'lleva_cartucho_toner' => false,
        'lleva_mouse_accesorios' => false,
        'observacion_checklist' => '',
    ];

    public array $checklistItems = [
        'enciende' => 'Enciende',
        'lleva_cargador' => 'Lleva cargador',
        'lleva_bateria' => 'Lleva batería',
        'pantalla_sana' => 'Pantalla sana',
        'teclado_completo' => 'Teclado completo',
        'touchpad_funcional' => 'Touchpad funcional',
        'tiene_golpes_visibles' => 'Golpes visibles',
        'tiene_humedad' => 'Humedad',
        'tiene_sello_roto' => 'Sello roto',
        'lleva_cable_poder' => 'Cable de poder',
        'lleva_cartucho_toner' => 'Cartucho / tóner',
        'lleva_mouse_accesorios' => 'Mouse / accesorios',
    ];

    public array $tiposEquipo = [
        ['id' => 'Computadora', 'name' => 'Computadora'],
        ['id' => 'Laptop', 'name' => 'Laptop'],
        ['id' => 'Impresora', 'name' => 'Impresora'],
        ['id' => 'Otro', 'name' => 'Otro'],
    ];

    public array $estadosServicio = [
        ['id' => 'RECIBIDO', 'name' => 'Recibido'],
        ['id' => 'EN_REVISION', 'name' => 'En revisión'],
        ['id' => 'PENDIENTE_REPUESTO', 'name' => 'Pendiente repuesto'],
        ['id' => 'REPARADO', 'name' => 'Reparado'],
        ['id' => 'ENTREGADO', 'name' => 'Entregado'],
        ['id' => 'CANCELADO', 'name' => 'Cancelado'],
    ];

    public ?int $productoId = null;
    public ?int $productoSerieId = null;
    public $productoCantidad = 1;
    public $productoPrecio = 0;
    public bool $productoTieneSeries = false;

    public array $headers = [
        ['key' => 'codigo', 'label' => 'Código'],
        ['key' => 'descripcion', 'label' => 'Descripción'],
        ['key' => 'serie', 'label' => 'Serie'],
        ['key' => 'cantidad', 'label' => 'Cantidad'],
        ['key' => 'precio', 'label' => 'Precio'],
        ['key' => 'subtotal', 'label' => 'Subtotal'],
        ['key' => 'acciones', 'label' => ''],
    ];


    public function estadosServicioDisponibles(): array
    {
        return collect($this->estadosServicio)
            ->filter(function (array $estado) {
                if (($estado['id'] ?? '') !== 'ENTREGADO') {
                    return true;
                }

                return strtoupper((string) $this->estadoServicio) === 'ENTREGADO';
            })
            ->values()
            ->toArray();
    }

    public function mount(): void
    {
        // MODIFICADO: toma la tasa vigente registrada al abrir/actualizar caja, no un valor fijo.
        $this->tipoCambio = $this->tipoCambioActualFormateada();

        $this->cargarCombos();
        $this->cargarPendientes();
    }

    // MODIFICADO: solo limpia el campo al editar; la validación completa ocurre al guardar/agregar.
    public function updated(string $campo): void
    {
        if (in_array($campo, $this->camposConValidacion(), true)) {
            $this->resetErrorBag($campo);
        }
    }

    // MODIFICADO: permite que las alertas visuales desaparezcan automáticamente luego de unos segundos.
    public function limpiarErrorCampo(string $campo): void
    {
        if (in_array($campo, $this->camposConValidacion(), true)) {
            $this->resetErrorBag($campo);
        }
    }

    private function camposConValidacion(): array
    {
        return [
            'clienteId',
            'tecnicoId',
            'tipoEquipo',
            'marca',
            'modelo',
            'numeroSerie',
            'problemaReportado',
            'detalleDescriptivo',
            'estadoServicio',
            'costoEstimado',
            'fechaEstimadaEntrega',
            'observacionTecnica',
            'productoId',
            'productoSerieId',
            'productoCantidad',
            'productoPrecio',
        ];
    }

    private function validarConToast(array $rules, array $messages = []): ?array
    {
        try {
            return $this->validate($rules, $messages);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
            $this->mostrarMensaje(
                'error',
                'Complete todos los campos obligatorios.',
                'Revise los campos marcados en rojo.'
            );

            return null;
        }
    }

    private function reglasServicio(): array
    {
        return [
            'clienteId' => ['required', 'integer'],
            'tecnicoId' => ['required', 'integer'],
            'tipoEquipo' => ['required', 'string', 'max:100'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'numeroSerie' => ['nullable', 'string', 'max:100'],
            'problemaReportado' => ['required', 'string', 'max:1000'],
            'detalleDescriptivo' => ['nullable', 'string', 'max:1000'],
            'estadoServicio' => ['required', 'in:RECIBIDO,EN_REVISION,PENDIENTE_REPUESTO,REPARADO,ENTREGADO,CANCELADO'],
            'costoEstimado' => ['required', 'numeric', 'min:0'],
            'fechaEstimadaEntrega' => ['nullable', 'date'],
            'observacionTecnica' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function mensajesValidacionServicio(): array
    {
        return [
            'clienteId.required' => 'Seleccione el cliente.',
            'clienteId.integer' => 'Seleccione un cliente válido.',
            'tecnicoId.required' => 'Seleccione el técnico receptor.',
            'tecnicoId.integer' => 'Seleccione un técnico válido.',
            'tipoEquipo.required' => 'Seleccione el tipo de equipo.',
            'tipoEquipo.max' => 'El tipo de equipo no debe superar los 100 caracteres.',
            'marca.max' => 'La marca no debe superar los 100 caracteres.',
            'modelo.max' => 'El modelo no debe superar los 100 caracteres.',
            'numeroSerie.max' => 'La serie no debe superar los 100 caracteres.',
            'problemaReportado.required' => 'Ingrese el problema reportado.',
            'problemaReportado.max' => 'El problema reportado no debe superar los 1000 caracteres.',
            'detalleDescriptivo.max' => 'El detalle descriptivo no debe superar los 1000 caracteres.',
            'estadoServicio.required' => 'Seleccione el estado del servicio.',
            'estadoServicio.in' => 'Seleccione un estado válido.',
            'costoEstimado.required' => 'Ingrese el costo estimado.',
            'costoEstimado.numeric' => 'El costo estimado debe ser numérico.',
            'costoEstimado.min' => 'El costo estimado no puede ser negativo.',
            'fechaEstimadaEntrega.date' => 'Ingrese una fecha estimada válida.',
            'observacionTecnica.max' => 'La observación técnica no debe superar los 1000 caracteres.',
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

    public function updatedProductoPrecio($value): void
    {
        $this->productoPrecio = $this->formatearDecimal((string) $value);
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

        $tipoCambio = $this->tipoOperacion !== $tipo;
        $this->tipoOperacion = $tipo;
        $this->limpiarCobroServicio();

        if ($tipoCambio) {
            $this->clienteId = null;
            $this->telefonoCliente = '';
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
        $filtro = trim($this->filtroProducto);

        if ($this->productoId && $filtro === trim($this->productoSeleccionadoNombre)) {
            $filtro = '';
        }

        $limite = max(1, $this->paginaBusquedaProductos) * self::PRODUCTOS_POR_PAGINA;

        $query = $this->consultaProductosBase($filtro);

        $this->totalProductosBusqueda = (clone $query)->count();

        $productos = $query
            ->select([
                'producto.Id_Producto as id',
                'producto.Nombre_Producto',
                'producto.Modelo',
                'producto.Precio_Venta as precio',
                'producto.Stock_Actual',
                'm.Nombre_Marca',
            ])
            ->orderBy('producto.Nombre_Producto')
            ->orderBy('producto.Modelo')
            ->limit($limite)
            ->get();

        if ($this->productoId && ! $productos->contains(fn ($item) => (int) $item->id === (int) $this->productoId)) {
            $seleccionado = $this->buscarProductoPorId((int) $this->productoId);

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

        $this->hayMasProductos = $this->totalProductosBusqueda > count($this->productosDisponibles);
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

        $stock = (int) $item->Stock_Actual;
        $precio = (float) $item->precio;

        return [
            'id' => (int) $item->id,
            'name' => $nombre .
                ' - Stock: ' . $stock .
                ($seriesDisponibles > 0 ? ' | Series: ' . $seriesDisponibles : ''),
            'titulo' => $nombre,
            'stock' => $stock,
            'precio' => $precio,
            'precio_texto' => 'C$ ' . number_format($precio, 2),
            'series_disponibles' => $seriesDisponibles,
        ];
    }

    private function buscarProductoPorId(int $id): ?object
    {
        return Producto::query()
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'producto.Id_Marca')
            ->where('producto.Id_Producto', $id)
            ->select([
                'producto.Id_Producto as id',
                'producto.Nombre_Producto',
                'producto.Modelo',
                'producto.Precio_Venta as precio',
                'producto.Stock_Actual',
                'm.Nombre_Marca',
            ])
            ->first();
    }

    public function cargarPendientes(): void
    {
        $query = $this->consultaPendientesBase();

        $this->totalPendientes = (clone $query)->count();
        $this->totalPaginasPendientes = max(1, (int) ceil($this->totalPendientes / self::PENDIENTES_POR_PAGINA));

        if ($this->paginaPendientes > $this->totalPaginasPendientes) {
            $this->paginaPendientes = $this->totalPaginasPendientes;
        }

        if ($this->paginaPendientes < 1) {
            $this->paginaPendientes = 1;
        }

        $this->serviciosPendientes = $query
            ->select([
                'servicio_tecnico.Id_Servicio_Tecnico as id',
                'servicio_tecnico.Numero_Orden as numero',
                'servicio_tecnico.Fecha_Ingreso as fecha',
                'servicio_tecnico.Tipo_Equipo as equipo',
                'servicio_tecnico.Marca as marca',
                'servicio_tecnico.Modelo as modelo',
                'servicio_tecnico.Estado_Servicio as estado',
                'servicio_tecnico.Total_Servicio as total',
                'servicio_tecnico.Monto_Pagado as pagado',
                'servicio_tecnico.Saldo_Pendiente as saldo',
                'c.Institucion as cliente_institucion',
                'pc.Primer_Nombre as cliente_primer_nombre',
                'pc.Segundo_Nombre as cliente_segundo_nombre',
                'pc.Primer_Apellido as cliente_primer_apellido',
                'pc.Segundo_Apellido as cliente_segundo_apellido',
                'pt.Primer_Nombre as tecnico_primer_nombre',
                'pt.Primer_Apellido as tecnico_primer_apellido',
            ])
            ->orderByDesc('servicio_tecnico.Fecha_Ingreso')
            ->orderByDesc('servicio_tecnico.Id_Servicio_Tecnico')
            ->forPage($this->paginaPendientes, self::PENDIENTES_POR_PAGINA)
            ->get()
            ->map(fn ($item) => $this->pendienteOpcion($item))
            ->values()
            ->toArray();
    }

    private function consultaPendientesBase()
    {
        $filtro = trim($this->filtroPendientes);

        return ServicioTecnico::query()
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', 'servicio_tecnico.Id_Cliente')
            ->leftJoin('persona as pc', 'pc.Id_Persona', '=', 'c.Id_Persona')
            ->leftJoin('trabajador as t', 't.Id_Trabajador', '=', 'servicio_tecnico.Id_Trabajador')
            ->leftJoin('persona as pt', 'pt.Id_Persona', '=', 't.Id_Persona')
            ->whereNotIn('servicio_tecnico.Estado_Servicio', ['ENTREGADO', 'CANCELADO'])
            ->when($filtro !== '', function ($query) use ($filtro) {
                $query->where(function ($q) use ($filtro) {
                    $q->where('servicio_tecnico.Numero_Orden', 'like', '%' . $filtro . '%')
                        ->orWhere('servicio_tecnico.Tipo_Equipo', 'like', '%' . $filtro . '%')
                        ->orWhere('servicio_tecnico.Marca', 'like', '%' . $filtro . '%')
                        ->orWhere('servicio_tecnico.Modelo', 'like', '%' . $filtro . '%')
                        ->orWhere('pc.Primer_Nombre', 'like', '%' . $filtro . '%')
                        ->orWhere('pc.Segundo_Nombre', 'like', '%' . $filtro . '%')
                        ->orWhere('pc.Primer_Apellido', 'like', '%' . $filtro . '%')
                        ->orWhere('pc.Segundo_Apellido', 'like', '%' . $filtro . '%')
                        ->orWhere('c.Institucion', 'like', '%' . $filtro . '%');
                });
            });
    }

    private function pendienteOpcion(object $item): array
    {
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
            'equipo' => $this->limpiarTexto(
                trim(($item->equipo ?? '') . ' ' . ($item->marca ?? '') . ' ' . ($item->modelo ?? ''))
            ),
            'tecnico' => $tecnico ?: 'Sin técnico',
            'estado' => $item->estado,
            'total' => (float) $item->total,
            'pagado' => (float) ($item->pagado ?? 0),
            'saldo' => (float) ($item->saldo ?? $item->total),
        ];
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
        $this->clienteSeleccionadoNombre = (string) $cliente['name'];
        $this->filtroCliente = (string) $cliente['name'];
        $this->mostrarBusquedaClientes = false;
        $this->resetErrorBag('clienteId');
        $this->cargarClientes();
    }

    public function updatedFiltroProducto(): void
    {
        if ($this->productoId && trim($this->filtroProducto) !== trim($this->productoSeleccionadoNombre)) {
            $this->productoId = null;
            $this->productoSerieId = null;
            $this->seriesDisponibles = [];
            $this->productoTieneSeries = false;
            $this->productoCantidad = 1;
            $this->productoPrecio = 0;
            $this->productoSeleccionadoNombre = '';
        }

        $this->paginaBusquedaProductos = 1;
        $this->mostrarBusquedaProductos = true;
        $this->cargarProductosDisponibles();
    }

    public function abrirBusquedaProductos(): void
    {
        $this->paginaBusquedaProductos = 1;
        $this->mostrarBusquedaProductos = true;
        $this->cargarProductosDisponibles();
    }

    public function cerrarBusquedaProductos(): void
    {
        $this->mostrarBusquedaProductos = false;
    }

    public function cargarMasProductos(): void
    {
        if (! $this->hayMasProductos) {
            return;
        }

        $this->paginaBusquedaProductos++;
        $this->mostrarBusquedaProductos = true;
        $this->cargarProductosDisponibles();
    }

    public function seleccionarProducto(int $id): void
    {
        $producto = $this->buscarProductoPorId($id);

        if (! $producto) {
            $this->mostrarMensaje('error', 'Producto no encontrado', 'El producto seleccionado ya no está disponible.');
            $this->cargarProductosDisponibles();
            return;
        }

        $seriesDisponibles = ProductoSerie::query()
            ->where('Id_Producto', $id)
            ->where('Estado', 'DISPONIBLE')
            ->count();

        $opcion = $this->productoOpcion($producto, (int) $seriesDisponibles);

        $this->productoId = (int) $opcion['id'];
        $this->productoSeleccionadoNombre = (string) $opcion['name'];
        $this->filtroProducto = (string) $opcion['name'];
        $this->mostrarBusquedaProductos = false;
        $this->resetErrorBag('productoId');
        $this->updatedProductoId($this->productoId);
        $this->cargarProductosDisponibles();
    }

    public function updatedFiltroPendientes(): void
    {
        $this->paginaPendientes = 1;
        $this->cargarPendientes();
    }

    public function abrirListadoClientes(): void
    {
        $this->filtroListadoClientes = $this->filtroCliente;
        $this->paginaClientes = 1;
        $this->cargarListadoClientes();
        $this->modalClientes = true;
    }

    public function cargarListadoClientes(): void
    {
        $query = $this->consultaClientesBase(trim($this->filtroListadoClientes));

        $this->totalClientesListado = (clone $query)->count();
        $this->totalPaginasClientes = max(1, (int) ceil($this->totalClientesListado / self::CLIENTES_POR_PAGINA));

        if ($this->paginaClientes > $this->totalPaginasClientes) {
            $this->paginaClientes = $this->totalPaginasClientes;
        }

        if ($this->paginaClientes < 1) {
            $this->paginaClientes = 1;
        }

        $this->clientesListado = $query
            ->select($this->columnasClienteSelect())
            ->orderByRaw('CASE WHEN cliente.Tipo_Cliente = ? THEN cliente.Institucion ELSE p.Primer_Nombre END ASC', [self::TIPO_CLIENTE_INSTITUCION])
            ->orderBy('p.Primer_Apellido')
            ->forPage($this->paginaClientes, self::CLIENTES_POR_PAGINA)
            ->get()
            ->map(fn ($item) => $this->clienteOpcion($item))
            ->values()
            ->toArray();
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

    public function updatedFiltroListadoClientes(): void
    {
        $this->paginaClientes = 1;
        $this->cargarListadoClientes();
    }

    public function paginaAnteriorClientes(): void
    {
        if ($this->paginaClientes > 1) {
            $this->paginaClientes--;
            $this->cargarListadoClientes();
        }
    }

    public function paginaSiguienteClientes(): void
    {
        if ($this->paginaClientes < $this->totalPaginasClientes) {
            $this->paginaClientes++;
            $this->cargarListadoClientes();
        }
    }

    public function seleccionarClienteListado(int $id): void
    {
        $this->seleccionarCliente($id);
        $this->modalClientes = false;
    }

    public function abrirListadoProductos(): void
    {
        $this->filtroListadoProductos = $this->filtroProducto;
        $this->paginaProductos = 1;
        $this->cargarListadoProductos();
        $this->modalProductos = true;
    }

    public function cargarListadoProductos(): void
    {
        $query = $this->consultaProductosBase(trim($this->filtroListadoProductos));

        $this->totalProductosListado = (clone $query)->count();
        $this->totalPaginasProductos = max(1, (int) ceil($this->totalProductosListado / self::PRODUCTOS_POR_PAGINA));

        if ($this->paginaProductos > $this->totalPaginasProductos) {
            $this->paginaProductos = $this->totalPaginasProductos;
        }

        if ($this->paginaProductos < 1) {
            $this->paginaProductos = 1;
        }

        $productos = $query
            ->select([
                'producto.Id_Producto as id',
                'producto.Nombre_Producto',
                'producto.Modelo',
                'producto.Precio_Venta as precio',
                'producto.Stock_Actual',
                'm.Nombre_Marca',
            ])
            ->orderBy('producto.Nombre_Producto')
            ->forPage($this->paginaProductos, self::PRODUCTOS_POR_PAGINA)
            ->get();

        $ids = $productos->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        $seriesDisponiblesPorProducto = empty($ids)
            ? collect()
            : ProductoSerie::query()
                ->whereIn('Id_Producto', $ids)
                ->where('Estado', 'DISPONIBLE')
                ->select('Id_Producto', DB::raw('COUNT(*) as total'))
                ->groupBy('Id_Producto')
                ->pluck('total', 'Id_Producto');

        $this->productosListado = $productos
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
                        ->orWhere('producto.Id_Producto', 'like', '%' . $filtro . '%')
                        ->orWhereExists(function ($subquery) use ($filtro) {
                            $subquery->select(DB::raw(1))
                                ->from('producto_serie as ps_busqueda')
                                ->whereColumn('ps_busqueda.Id_Producto', 'producto.Id_Producto')
                                ->where('ps_busqueda.Estado', 'DISPONIBLE')
                                ->where('ps_busqueda.Numero_Serie', 'like', '%' . $filtro . '%');
                        });
                });
            });
    }

    public function updatedFiltroListadoProductos(): void
    {
        $this->paginaProductos = 1;
        $this->cargarListadoProductos();
    }

    public function paginaAnteriorProductos(): void
    {
        if ($this->paginaProductos > 1) {
            $this->paginaProductos--;
            $this->cargarListadoProductos();
        }
    }

    public function paginaSiguienteProductos(): void
    {
        if ($this->paginaProductos < $this->totalPaginasProductos) {
            $this->paginaProductos++;
            $this->cargarListadoProductos();
        }
    }

    public function seleccionarProductoListado(int $id): void
    {
        $this->seleccionarProducto($id);
        $this->modalProductos = false;
    }

    public function cargarPendiente(int $id, bool $cerrarModal = true): void
    {
        $servicio = ServicioTecnico::query()
            ->where('Id_Servicio_Tecnico', $id)
            ->first();

        if (!$servicio) {
            $this->mostrarMensaje('error', 'No encontrado', 'El servicio técnico seleccionado ya no existe.');
            return;
        }

        $this->servicioTecnicoIdSeleccionado = (int) $servicio->Id_Servicio_Tecnico;
        $this->servicioPagado = $this->servicioEstaPagado((int) $servicio->Id_Servicio_Tecnico);
        $this->clienteId = $servicio->Id_Cliente ? (int) $servicio->Id_Cliente : null;
        $this->tecnicoId = $servicio->Id_Trabajador ? (int) $servicio->Id_Trabajador : null;
        $this->tipoEquipo = (string) $servicio->Tipo_Equipo;
        $this->marca = (string) ($servicio->Marca ?? '');
        $this->modelo = (string) ($servicio->Modelo ?? '');
        $this->numeroSerie = (string) ($servicio->Numero_Serie ?? '');
        $this->problemaReportado = (string) $servicio->Problema_Reportado;
        $this->detalleDescriptivo = (string) ($servicio->Detalle_Descriptivo ?? '');
        $this->estadoServicio = (string) $servicio->Estado_Servicio;
        $this->costoEstimado = (float) $servicio->Costo_Estimado;
        $this->fechaEstimadaEntrega = $this->normalizarFechaInput($servicio->Fecha_Estimada_Entrega);
        $this->observacionTecnica = (string) ($servicio->Observacion_Tecnica ?? '');
        // MODIFICADO: si el servicio no tiene venta aún, tomamos Tipo_Venta directo de servicio_tecnico.
        $tipoVentaGuardada = strtoupper((string) ($servicio->Tipo_Venta ?? ''));

        if ($tipoVentaGuardada === '' && $servicio->Id_Venta) {
            $tipoVentaGuardada = strtoupper((string) DB::table('venta')
                ->where('Id_Venta', $servicio->Id_Venta)
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
        // MODIFICADO: para pagos/actualizaciones se muestra la tasa actual vigente, no el hardcode 36.50.
        $this->tipoCambio = $this->tipoCambioActualFormateada();
        $this->limpiarCobroServicio();

        $this->updatedClienteId($this->clienteId);
        $this->cargarChecklist((int) $servicio->Id_Servicio_Tecnico);
        $this->cargarProductosDelServicio((int) $servicio->Id_Servicio_Tecnico);
        $this->resetProductoForm();
        $this->resetErrorBag();

        if ($cerrarModal) {
            $this->modalPendientes = false;
        }

        $this->mostrarMensaje('success', 'Pendiente cargado', 'Ya podés revisar, actualizar estado o agregar repuestos.');
    }

    public function nuevoIngreso(): void
    {
        $this->limpiarFormulario();
        $this->mostrarMensaje('success', 'Formulario limpio', 'Listo para registrar un nuevo ingreso.');
    }

    public function updatedClienteId($value): void
    {
        $this->telefonoCliente = '';
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

        $seriesUsadasEnPantalla = collect($this->productos)
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
        if ($this->validarConToast($this->reglasProducto(), $this->mensajesValidacionProducto()) === null) {
            return;
        }

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
            $this->mostrarMensaje('error', 'Producto no disponible', 'Seleccione otro producto con stock.');
            return;
        }

        $serie = null;
        $cantidad = (float) $this->productoCantidad;

        if ($this->productoTieneSeries) {
            if (!$this->productoSerieId) {
                $this->addError('productoSerieId', 'Seleccione la serie del producto.');
                $this->mostrarMensaje('error', 'Complete todos los campos obligatorios.', 'Seleccione una serie disponible.');
                return;
            }

            $serie = ProductoSerie::query()
                ->where('id_producto_serie', $this->productoSerieId)
                ->where('Id_Producto', $this->productoId)
                ->where('Estado', 'DISPONIBLE')
                ->first();

            if (!$serie) {
                $this->addError('productoSerieId', 'La serie seleccionada ya no está disponible.');
                $this->mostrarMensaje('error', 'Serie no disponible', 'Seleccione otra serie disponible.');
                return;
            }

            $cantidad = 1;
        }

        $cantidadYaAgregada = collect($this->productos)
            ->where('producto_id', $this->productoId)
            ->where('ya_guardado', false)
            ->sum('cantidad');

        if (!$this->productoTieneSeries && ($cantidadYaAgregada + $cantidad) > (float) $producto->Stock_Actual) {
            $this->addError('productoCantidad', 'La cantidad supera el stock disponible.');
            $this->mostrarMensaje('error', 'Stock insuficiente', 'La cantidad supera el stock disponible.');
            return;
        }

        if ($serie && collect($this->productos)->contains('producto_serie_id', (int) $serie->id_producto_serie)) {
            $this->addError('productoSerieId', 'Esta serie ya fue agregada.');
            $this->mostrarMensaje('error', 'Serie duplicada', 'Esta serie ya fue agregada al servicio.');
            return;
        }

        $precio = round((float) $this->productoPrecio, 2);
        $subtotal = round($cantidad * $precio, 2);

        $this->productos[] = [
            'tmp_id' => uniqid('prod_', true),
            'servicio_producto_id' => null,
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
        $this->mostrarMensaje('success', 'Producto agregado', 'El repuesto quedó listo para guardarse con el servicio.');
    }

    public function quitarProducto(string $tmpId): void
    {
        $producto = collect($this->productos)->firstWhere('tmp_id', $tmpId);

        if ($producto && !empty($producto['ya_guardado'])) {
            $this->mostrarMensaje('error', 'No permitido', 'Este producto ya fue descontado del inventario. Para revertirlo hay que hacer una devolución o ajuste de inventario.');
            return;
        }

        $this->productos = array_values(array_filter(
            $this->productos,
            fn ($item) => $item['tmp_id'] !== $tmpId
        ));

        $this->cargarCombos();
    }


    public function servicioPermiteCobro(): bool
    {
        return strtoupper((string) $this->estadoServicio) === 'REPARADO'
            && ! $this->servicioPagado;
    }

    private function servicioTienePagoIngresado(): bool
    {
        return $this->limpiarMonto($this->pagoCordobas) > 0
            || $this->limpiarDecimal($this->pagoDolares) > 0;
    }

    private function servicioPagadoEnBase(): bool
    {
        if (! $this->servicioTecnicoIdSeleccionado) {
            return false;
        }

        $servicio = DB::table('servicio_tecnico')
            ->where('Id_Servicio_Tecnico', (int) $this->servicioTecnicoIdSeleccionado)
            ->select(['Total_Servicio', 'Monto_Pagado', 'Saldo_Pendiente'])
            ->first();

        if (! $servicio) {
            return false;
        }

        $total = round((float) ($servicio->Total_Servicio ?? 0), 2);
        $saldo = round((float) ($servicio->Saldo_Pendiente ?? $total), 2);

        return $total > 0 && $saldo <= 0;
    }

    public function updatedEstadoServicio($value): void
    {
        $this->estadoServicio = (string) $value;

        if (strtoupper((string) $this->estadoServicio) !== 'REPARADO') {
            $this->limpiarCobroServicio();
        }

        $this->resetErrorBag('estadoServicio');
    }

    public function guardar(): void
    {
        // MODIFICADO: reglas centralizadas para permitir alertas dinámicas y limpieza automática.
        if ($this->validarConToast($this->reglasServicio(), $this->mensajesValidacionServicio()) === null) {
            return;
        }

        if (! $this->servicioTecnicoIdSeleccionado && strtoupper((string) $this->estadoServicio) === 'ENTREGADO') {
            $this->estadoServicio = 'RECIBIDO';

            $this->mostrarMensaje(
                'error',
                'Estado no permitido',
                'Un servicio nuevo no puede registrarse directamente como ENTREGADO.'
            );

            return;
        }

        if (($this->servicioPagado || $this->servicioPagadoEnBase()) && $this->servicioTienePagoIngresado()) {
            $this->limpiarCobroServicio();
            $this->servicioPagado = true;

            $this->mostrarMensaje(
                'error',
                'Servicio ya pagado',
                'Este servicio ya está pagado completo. No se puede registrar otro pago.'
            );

            return;
        }

        if (! $this->servicioPermiteCobro() && $this->servicioTienePagoIngresado()) {
            $this->limpiarCobroServicio();

            $this->mostrarMensaje(
                'error',
                'Cobro no permitido',
                'Solo puede registrar pagos cuando el servicio esté en estado REPARADO.'
            );

            return;
        }

        if (
            strtoupper((string) $this->estadoServicio) === 'ENTREGADO'
            && ! $this->servicioListoParaVoucher((int) ($this->servicioTecnicoIdSeleccionado ?? 0))
        ) {
            $this->mostrarMensaje(
                'error',
                'Entrega no permitida',
                'El estado ENTREGADO se asigna automáticamente cuando el servicio está REPARADO y queda pagado completo.'
            );

            return;
        }

        if ($this->limpiarDecimal($this->tipoCambio) <= 0) {
            $this->mostrarMensaje('error', 'Tasa inválida', 'La tasa de cambio debe ser mayor que cero.');
            return;
        }

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
            if ($this->servicioTecnicoIdSeleccionado) {
                $id = (int) $this->servicioTecnicoIdSeleccionado;
                $yaEstabaListoParaVoucher = $this->servicioListoParaVoucher($id);

                $this->actualizarServicioTecnico();

                $abrirVoucher = ! $yaEstabaListoParaVoucher && $this->servicioListoParaVoucher($id);

                $this->cargarCombos();
                $this->cargarPendientes();

                if ($abrirVoucher) {
                    $this->prepararVoucherServicioTecnicoYLimpia($id);
                    $this->mostrarMensaje('success', 'Servicio entregado automáticamente', 'El servicio estaba REPARADO y quedó pagado completo. Se limpiaron los campos y se abrió el voucher.');
                    return;
                }

                $this->cargarPendiente($id, false);
                $this->limpiarCobroServicio();

                if ($this->estadoServicio === 'ENTREGADO' && ! $this->servicioEstaPagado($id)) {
                    $this->mostrarMensaje('warning', 'Saldo pendiente', 'El servicio quedó entregado, pero aún tiene saldo pendiente. No se generó voucher.');
                    return;
                }

                $this->mostrarMensaje('success', 'Servicio actualizado', 'El servicio técnico se actualizó correctamente.');
                return;
            }

            $id = $this->crearServicioTecnico();
            $abrirVoucher = $this->servicioListoParaVoucher($id);

            $this->cargarCombos();
            $this->cargarPendientes();

            if ($abrirVoucher) {
                $this->prepararVoucherServicioTecnicoYLimpia($id);
                $this->mostrarMensaje('success', 'Servicio entregado automáticamente', 'El servicio estaba REPARADO y quedó pagado completo. Se limpiaron los campos y se abrió el voucher.');
                return;
            }

            $this->limpiarFormulario();

            $this->mostrarMensaje('success', 'Ingreso guardado', 'El servicio técnico se registró correctamente. Orden #' . $id . '.');
        } catch (\Throwable $e) {
            report($e);
            $this->mostrarMensaje('error', 'No se pudo guardar', $e->getMessage());
        }
    }


    private function estadoFinalDespuesDeCobro(string $estadoActual, array $cobro): string
    {
        $estado = strtoupper(trim($estadoActual));
        $saldoPendiente = round((float) ($cobro['saldo_pendiente'] ?? 0), 2);

        if ($estado === 'REPARADO' && $saldoPendiente <= 0) {
            return 'ENTREGADO';
        }

        return $estado !== '' ? $estado : 'RECIBIDO';
    }

    private function prepararVoucherServicioTecnico(int $servicioTecnicoId): void
    {
        $this->servicioPagado = true;
        $this->voucherPdfUrl = $this->voucherServicioTecnicoUrl($servicioTecnicoId);
        $this->modalVoucherPdf = true;
    }

    private function prepararVoucherServicioTecnicoYLimpia(int $servicioTecnicoId): void
    {
        $url = $this->voucherServicioTecnicoUrl($servicioTecnicoId);

        $this->limpiarFormulario();
        $this->voucherPdfUrl = $url;
        $this->modalVoucherPdf = true;
    }

    private function voucherServicioTecnicoUrl(int $servicioTecnicoId): string
    {
        return route('ventas.servicio-tecnico.voucher', [
            'servicio' => $servicioTecnicoId,
            'ancho' => 80,
        ]);
    }

    public function cerrarVoucherPdf(): void
    {
        $this->modalVoucherPdf = false;
        $this->voucherPdfUrl = '';
    }

    private function servicioListoParaVoucher(int $servicioTecnicoId): bool
    {
        $servicio = DB::table('servicio_tecnico')
            ->where('Id_Servicio_Tecnico', $servicioTecnicoId)
            ->select(['Estado_Servicio', 'Total_Servicio', 'Monto_Pagado', 'Saldo_Pendiente'])
            ->first();

        if (! $servicio) {
            return false;
        }

        return strtoupper((string) ($servicio->Estado_Servicio ?? '')) === 'ENTREGADO'
            && $this->servicioEstaPagado($servicioTecnicoId);
    }

    private function servicioEstaPagado(int $servicioTecnicoId): bool
    {
        $servicio = DB::table('servicio_tecnico')
            ->where('Id_Servicio_Tecnico', $servicioTecnicoId)
            ->select(['Total_Servicio', 'Monto_Pagado', 'Saldo_Pendiente'])
            ->first();

        if (! $servicio) {
            return false;
        }

        $total = round((float) ($servicio->Total_Servicio ?? 0), 2);
        $pagado = round((float) ($servicio->Monto_Pagado ?? 0), 2);
        $saldo = round((float) ($servicio->Saldo_Pendiente ?? max($total - $pagado, 0)), 2);

        return $total > 0 && $saldo <= 0;
    }

    public function estadoNombre(?string $estado): string
    {
        return match ($estado) {
            'RECIBIDO' => 'Recibido',
            'EN_REVISION' => 'En revisión',
            'PENDIENTE_REPUESTO' => 'Pendiente repuesto',
            'REPARADO' => 'Reparado',
            'ENTREGADO' => 'Entregado',
            'CANCELADO' => 'Cancelado',
            default => str_replace('_', ' ', (string) $estado),
        };
    }

    private function crearServicioTecnico(): int
    {
        return DB::transaction(function () {
            $usuarioId = $this->usuarioActualId();
            $numeroOrden = $this->generarNumeroUnico('ST', ServicioTecnico::class, 'Numero_Orden');

            $totalRepuestos = round(collect($this->productos)
                ->sum(fn ($item) => $this->numeroSeguro($item['subtotal'] ?? 0)), 2);
            $totalServicio = round($totalRepuestos + $this->numeroSeguro($this->costoEstimado), 2);
            $servicioId = $this->servicioPorTipo('TECNICO');

            $clienteCredito = null;
            $credito = null;

            if ($this->tipoOperacion === self::TIPO_CREDITO) {
                $clienteCredito = $this->obtenerClienteCreditoActivo((int) $this->clienteId);
                $credito = $this->calcularCreditoConSaldoFavor($clienteCredito, $totalServicio);

                $cobro = [
                    'pagado_total' => $credito['abono_inicial_total'],
                    'saldo_pendiente' => $credito['saldo_pendiente_credito'],
                    'cambio_entregado' => $credito['cambio_entregado'],
                    'pago_cordobas' => $credito['pago_cordobas_recibido'],
                    'pago_dolares' => $credito['pago_dolares_recibido'],
                    'equivalente_dolares' => $credito['equivalente_dolares_recibido'],
                ];
            } else {
                $cobro = $this->calcularCobroServicio($totalServicio, 0);
            }

            $ventaId = $this->crearVentaServicioTecnico($usuarioId, $totalServicio, $cobro);
            $estadoFinal = $this->estadoFinalDespuesDeCobro($this->estadoServicio, $cobro);
            $this->estadoServicio = $estadoFinal;

            $servicioTecnico = $this->crearModelo(ServicioTecnico::class, [
                'Id_Venta' => $ventaId,
                'Numero_Orden' => $numeroOrden,
                'Fecha_Ingreso' => now(),
                'Id_Cliente' => $this->clienteId,
                'Id_Usuario' => $usuarioId,
                'Id_Servicio' => $servicioId,
                'Id_Trabajador' => $this->tecnicoId,
                'Tipo_Equipo' => $this->tipoEquipo,
                'Marca' => $this->marca ?: null,
                'Modelo' => $this->modelo ?: null,
                'Numero_Serie' => $this->numeroSerie ?: null,
                'Problema_Reportado' => $this->problemaReportado,
                'Detalle_Descriptivo' => $this->detalleDescriptivo ?: null,
                'Estado_Servicio' => $estadoFinal,
                'Costo_Estimado' => $this->numeroSeguro($this->costoEstimado),
                'Fecha_Estimada_Entrega' => $this->fechaEstimadaEntrega ?: null,
                'Observacion_Tecnica' => $this->observacionTecnica ?: null,
                'Total_Repuestos' => $totalRepuestos,
                'Total_Servicio' => $totalServicio,
                'Tipo_Venta' => $this->tipoOperacion,
                'Tipo_Cambio' => $this->tasaCambio(),
                'Monto_Pagado' => $cobro['pagado_total'],
                'Saldo_Pendiente' => $cobro['saldo_pendiente'],
                'Cambio_Entregado_Cordobas' => $cobro['cambio_entregado'],
            ]);

            $servicioTecnicoId = (int) $servicioTecnico->Id_Servicio_Tecnico;

            $this->registrarDetalleVentaServicioTecnico($ventaId, $servicioId, $numeroOrden);

            if ($this->tipoOperacion === self::TIPO_CREDITO) {
                $this->registrarCreditoServicioTecnico($ventaId, $clienteCredito, $credito, $numeroOrden);
            } else {
                $this->registrarPagosServicioTecnico($servicioTecnicoId, $ventaId, $cobro);
            }

            $this->crearModelo(
                ServicioTecnicoChecklist::class,
                $this->datosChecklist($servicioTecnicoId)
            );

            foreach ($this->productos as $item) {
                $this->registrarProductoServicio($servicioTecnicoId, $item);
            }

            return $servicioTecnicoId;
        }, 3);
    }

    private function actualizarServicioTecnico(): void
    {
        DB::transaction(function () {
            $servicioTecnicoId = (int) $this->servicioTecnicoIdSeleccionado;

            $servicio = ServicioTecnico::query()
                ->where('Id_Servicio_Tecnico', $servicioTecnicoId)
                ->lockForUpdate()
                ->first();

            if (!$servicio) {
                throw new \RuntimeException('El servicio técnico seleccionado ya no existe.');
            }

            $totalRepuestos = round(collect($this->productos)
                ->sum(fn ($item) => $this->numeroSeguro($item['subtotal'] ?? 0)), 2);
            $totalServicio = round($totalRepuestos + $this->numeroSeguro($this->costoEstimado), 2);
            $servicioId = (int) ($servicio->Id_Servicio ?: $this->servicioPorTipo('TECNICO'));
            $usuarioId = $this->usuarioActualId();
            $ventaId = $servicio->Id_Venta ? (int) $servicio->Id_Venta : null;

            if (!$ventaId) {
                $cobroInicial = $this->tipoOperacion === self::TIPO_CREDITO
                    ? ['pagado_total' => 0, 'saldo_pendiente' => $totalServicio, 'cambio_entregado' => 0]
                    : $this->calcularCobroServicio($totalServicio, 0);

                $ventaId = $this->crearVentaServicioTecnico($usuarioId, $totalServicio, $cobroInicial);
            }

            $creditoExistente = DB::table('credito')
                ->where('Id_Venta', $ventaId)
                ->lockForUpdate()
                ->first();

            if ($creditoExistente && $this->tipoOperacion === self::TIPO_CONTADO) {
                throw new \RuntimeException('Este servicio ya está registrado al crédito. Los abonos deben gestionarse desde el módulo de crédito.');
            }

            if ($this->tipoOperacion === self::TIPO_CREDITO) {
                if ($creditoExistente) {
                    $cobro = $this->actualizarCreditoServicioTecnicoExistente($creditoExistente, $totalServicio);
                } else {
                    $clienteCredito = $this->obtenerClienteCreditoActivo((int) $this->clienteId);
                    $credito = $this->calcularCreditoConSaldoFavor($clienteCredito, $totalServicio);
                    $this->registrarCreditoServicioTecnico($ventaId, $clienteCredito, $credito, (string) $servicio->Numero_Orden);

                    $cobro = [
                        'pagado_total' => $credito['abono_inicial_total'],
                        'saldo_pendiente' => $credito['saldo_pendiente_credito'],
                        'cambio_entregado' => $credito['cambio_entregado'],
                    ];
                }
            } else {
                $montoPagadoAnterior = round((float) ($servicio->Monto_Pagado ?? 0), 2);
                $cobro = $this->calcularCobroServicio($totalServicio, $montoPagadoAnterior);
                $this->registrarPagosServicioTecnico($servicioTecnicoId, $ventaId, $cobro);
            }

            $this->actualizarVentaServicioTecnico($ventaId, $usuarioId, $totalServicio, $cobro);

            $estadoFinal = $this->estadoFinalDespuesDeCobro($this->estadoServicio, $cobro);
            $this->estadoServicio = $estadoFinal;

            $servicio->forceFill([
                'Id_Venta' => $ventaId,
                'Id_Cliente' => $this->clienteId,
                'Id_Trabajador' => $this->tecnicoId,
                'Tipo_Equipo' => $this->tipoEquipo,
                'Marca' => $this->marca ?: null,
                'Modelo' => $this->modelo ?: null,
                'Numero_Serie' => $this->numeroSerie ?: null,
                'Problema_Reportado' => $this->problemaReportado,
                'Detalle_Descriptivo' => $this->detalleDescriptivo ?: null,
                'Estado_Servicio' => $estadoFinal,
                'Costo_Estimado' => $this->numeroSeguro($this->costoEstimado),
                'Fecha_Estimada_Entrega' => $this->fechaEstimadaEntrega ?: null,
                'Observacion_Tecnica' => $this->observacionTecnica ?: null,
                'Total_Repuestos' => $totalRepuestos,
                'Total_Servicio' => $totalServicio,
                'Tipo_Venta' => $this->tipoOperacion,
                'Tipo_Cambio' => $this->tasaCambio(),
                'Monto_Pagado' => $cobro['pagado_total'],
                'Saldo_Pendiente' => $cobro['saldo_pendiente'],
                'Cambio_Entregado_Cordobas' => $this->tipoOperacion === self::TIPO_CONTADO
                    ? round((float) ($servicio->Cambio_Entregado_Cordobas ?? 0) + $cobro['cambio_entregado'], 2)
                    : round((float) ($servicio->Cambio_Entregado_Cordobas ?? 0) + ($cobro['cambio_entregado'] ?? 0), 2),
            ])->save();

            $this->registrarDetalleVentaServicioTecnico($ventaId, $servicioId, (string) $servicio->Numero_Orden);

            $checklist = ServicioTecnicoChecklist::query()
                ->firstOrNew(['Id_Servicio_Tecnico' => $servicioTecnicoId]);

            $checklist->forceFill($this->datosChecklist($servicioTecnicoId))->save();

            foreach ($this->productos as $item) {
                if (!empty($item['ya_guardado'])) {
                    continue;
                }

                $this->registrarProductoServicio($servicioTecnicoId, $item);
            }
        }, 3);
    }

    private function registrarProductoServicio(int $servicioTecnicoId, array $item): void
    {
        $this->descontarInventario($item, 'USADO_SERVICIO', 'SALIDA_SERVICIO_TECNICO');

        $this->crearModelo(ServicioTecnicoProducto::class, [
            'Id_Servicio_Tecnico' => $servicioTecnicoId,
            'Id_Producto' => $item['producto_id'],
            'Id_Producto_Serie' => $item['producto_serie_id'],
            'Cantidad' => $item['cantidad'],
            'Precio_Unitario' => $item['precio'],
            'Subtotal' => $item['subtotal'],
            'Observacion' => null,
        ]);
    }

    private function cargarChecklist(int $servicioTecnicoId): void
    {
        $check = ServicioTecnicoChecklist::query()
            ->where('Id_Servicio_Tecnico', $servicioTecnicoId)
            ->first();

        $this->checklist = [
            'enciende' => (bool) ($check->Enciende ?? false),
            'lleva_cargador' => (bool) ($check->Lleva_Cargador ?? false),
            'lleva_bateria' => (bool) ($check->Lleva_Bateria ?? false),
            'pantalla_sana' => (bool) ($check->Pantalla_Sana ?? false),
            'teclado_completo' => (bool) ($check->Teclado_Completo ?? false),
            'touchpad_funcional' => (bool) ($check->Touchpad_Funcional ?? false),
            'tiene_golpes_visibles' => (bool) ($check->Tiene_Golpes_Visibles ?? false),
            'tiene_humedad' => (bool) ($check->Tiene_Humedad ?? false),
            'tiene_sello_roto' => (bool) ($check->Tiene_Sello_Roto ?? false),
            'lleva_cable_poder' => (bool) ($check->Lleva_Cable_Poder ?? false),
            'lleva_cartucho_toner' => (bool) ($check->Lleva_Cartucho_Toner ?? false),
            'lleva_mouse_accesorios' => (bool) ($check->Lleva_Mouse_Accesorios ?? false),
            'observacion_checklist' => (string) ($check->Observacion_Checklist ?? ''),
        ];
    }

    private function cargarProductosDelServicio(int $servicioTecnicoId): void
    {
        $this->productos = ServicioTecnicoProducto::query()
            ->join('producto as p', 'p.Id_Producto', '=', 'servicio_tecnico_producto.Id_Producto')
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'p.Id_Marca')
            ->leftJoin('producto_serie as ps', 'ps.id_producto_serie', '=', 'servicio_tecnico_producto.Id_Producto_Serie')
            ->where('servicio_tecnico_producto.Id_Servicio_Tecnico', $servicioTecnicoId)
            ->select([
                'servicio_tecnico_producto.Id_Servicio_Tecnico_Producto',
                'servicio_tecnico_producto.Id_Producto',
                'servicio_tecnico_producto.Id_Producto_Serie',
                'servicio_tecnico_producto.Cantidad',
                'servicio_tecnico_producto.Precio_Unitario',
                'servicio_tecnico_producto.Subtotal',
                'p.Nombre_Producto',
                'p.Modelo',
                'm.Nombre_Marca',
                'ps.Numero_Serie',
            ])
            ->orderBy('servicio_tecnico_producto.Id_Servicio_Tecnico_Producto')
            ->get()
            ->map(fn ($item) => [
                'tmp_id' => 'guardado_' . $item->Id_Servicio_Tecnico_Producto,
                'servicio_producto_id' => (int) $item->Id_Servicio_Tecnico_Producto,
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

    private function datosChecklist(int $servicioTecnicoId): array
    {
        return [
            'Id_Servicio_Tecnico' => $servicioTecnicoId,
            'Enciende' => (bool) $this->checklist['enciende'],
            'Lleva_Cargador' => (bool) $this->checklist['lleva_cargador'],
            'Lleva_Bateria' => (bool) $this->checklist['lleva_bateria'],
            'Pantalla_Sana' => (bool) $this->checklist['pantalla_sana'],
            'Teclado_Completo' => (bool) $this->checklist['teclado_completo'],
            'Touchpad_Funcional' => (bool) $this->checklist['touchpad_funcional'],
            'Tiene_Golpes_Visibles' => (bool) $this->checklist['tiene_golpes_visibles'],
            'Tiene_Humedad' => (bool) $this->checklist['tiene_humedad'],
            'Tiene_Sello_Roto' => (bool) $this->checklist['tiene_sello_roto'],
            'Lleva_Cable_Poder' => (bool) $this->checklist['lleva_cable_poder'],
            'Lleva_Cartucho_Toner' => (bool) $this->checklist['lleva_cartucho_toner'],
            'Lleva_Mouse_Accesorios' => (bool) $this->checklist['lleva_mouse_accesorios'],
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
        $this->filtroProducto = '';
        $this->productoSeleccionadoNombre = '';
        $this->mostrarBusquedaProductos = false;
        $this->paginaBusquedaProductos = 1;

        $this->resetErrorBag([
            'productoId',
            'productoSerieId',
            'productoCantidad',
            'productoPrecio',
        ]);
    }

    private function limpiarFormulario(): void
    {
        $this->servicioTecnicoIdSeleccionado = null;
        $this->servicioPagado = false;
        $this->modalVoucherPdf = false;
        $this->voucherPdfUrl = '';
        $this->clienteId = null;
        $this->telefonoCliente = '';
        $this->tecnicoId = null;
        $this->tipoEquipo = '';
        $this->marca = '';
        $this->modelo = '';
        $this->numeroSerie = '';
        $this->problemaReportado = '';
        $this->detalleDescriptivo = '';
        $this->estadoServicio = 'RECIBIDO';
        $this->costoEstimado = 0;
        $this->fechaEstimadaEntrega = null;
        $this->observacionTecnica = '';
        $this->productos = [];
        $this->tipoOperacion = self::TIPO_CONTADO;
        $this->filtroCliente = '';
        $this->clienteSeleccionadoNombre = '';
        $this->paginaBusquedaClientes = 1;
        $this->mostrarBusquedaClientes = false;
        $this->filtroProducto = '';
        $this->productoSeleccionadoNombre = '';
        $this->mostrarBusquedaProductos = false;
        $this->paginaBusquedaProductos = 1;
        $this->tipoCambio = $this->tipoCambioActualFormateada();
        $this->limpiarCobroServicio();
        $this->cargarCombos();

        $this->resetProductoForm();

        foreach (array_keys($this->checklistItems) as $key) {
            $this->checklist[$key] = false;
        }

        $this->checklist['observacion_checklist'] = '';
        $this->resetErrorBag();
    }

    public function totalServicioActual(): float
    {
        $costoEstimado = $this->numeroSeguro($this->costoEstimado);

        $totalRepuestos = collect($this->productos)
            ->sum(fn ($item) => $this->numeroSeguro($item['subtotal'] ?? 0));

        return round($costoEstimado + $totalRepuestos, 2);
    }

    public function totalPagadoCordobas(): float
    {
        return round($this->limpiarMonto($this->pagoCordobas) + ($this->limpiarDecimal($this->pagoDolares) * $this->tasaCambio()), 2);
    }

    public function montoPagadoAnteriorServicio(): float
    {
        if (! $this->servicioTecnicoIdSeleccionado) {
            return 0.00;
        }

        return round((float) DB::table('servicio_tecnico')
            ->where('Id_Servicio_Tecnico', (int) $this->servicioTecnicoIdSeleccionado)
            ->value('Monto_Pagado'), 2);
    }

    public function saldoPendienteAntesDePago(): float
    {
        return round(max($this->totalServicioActual() - $this->montoPagadoAnteriorServicio(), 0), 2);
    }

    public function cambioServicio(): float
    {
        $base = $this->tipoOperacion === self::TIPO_CREDITO
            ? max($this->totalServicioActual() - $this->saldoFavorAplicadoServicio(), 0)
            : $this->saldoPendienteAntesDePago();

        return round(max($this->totalPagadoCordobas() - $base, 0), 2);
    }

    public function saldoServicio(): float
    {
        if ($this->tipoOperacion === self::TIPO_CREDITO) {
            return $this->saldoCreditoServicio();
        }

        return round(max($this->saldoPendienteAntesDePago() - $this->totalPagadoCordobas(), 0), 2);
    }

    private function calcularCobroServicio(float $totalServicio, float $montoPagadoAnterior): array
    {
        $pagoCordobas = round($this->limpiarMonto($this->pagoCordobas), 2);
        $pagoDolares = round($this->limpiarDecimal($this->pagoDolares), 2);
        $equivalenteDolares = round($pagoDolares * $this->tasaCambio(), 2);
        $recibidoEquivalente = round($pagoCordobas + $equivalenteDolares, 2);
        $pendienteAnterior = round(max($totalServicio - $montoPagadoAnterior, 0), 2);
        $aplicado = round(min($recibidoEquivalente, $pendienteAnterior), 2);
        $cambioEntregado = round(max($recibidoEquivalente - $pendienteAnterior, 0), 2);
        $pagadoTotal = round(min($montoPagadoAnterior + $aplicado, $totalServicio), 2);

        return [
            'pago_cordobas' => $pagoCordobas,
            'pago_dolares' => $pagoDolares,
            'equivalente_dolares' => $equivalenteDolares,
            'recibido_equivalente' => $recibidoEquivalente,
            'aplicado' => $aplicado,
            'pagado_total' => $pagadoTotal,
            'saldo_pendiente' => round(max($totalServicio - $pagadoTotal, 0), 2),
            'cambio_entregado' => $cambioEntregado,
        ];
    }

    private function registrarPagosServicioTecnico(int $servicioTecnicoId, int $ventaId, array $cobro): void
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

    public function saldoFavorAplicadoServicio(): float
    {
        return round(min($this->saldoFavorDisponible(), $this->totalServicioActual()), 2);
    }

    public function abonoInicialServicio(): float
    {
        if ($this->tipoOperacion !== self::TIPO_CREDITO) {
            return $this->totalPagadoCordobas();
        }

        return round(min(
            $this->totalPagadoCordobas(),
            max($this->totalServicioActual() - $this->saldoFavorAplicadoServicio(), 0)
        ), 2);
    }

    public function saldoCreditoServicio(): float
    {
        return round(max(
            $this->totalServicioActual()
            - $this->saldoFavorAplicadoServicio()
            - $this->abonoInicialServicio(),
            0
        ), 2);
    }

    private function clienteEsInstitucion(int $clienteId): bool
    {
        return Cliente::query()
            ->where('Id_Cliente', $clienteId)
            ->where('Estado', 1)
            ->where('Tipo_Cliente', self::TIPO_CLIENTE_INSTITUCION)
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

    private function registrarCreditoServicioTecnico(
        int $ventaId,
        object $clienteCredito,
        array $credito,
        string $numeroOrden
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
                'Observacion' => 'Saldo a favor aplicado al servicio técnico ' . $numeroOrden,
            ]);
        }

        $this->registrarAbonosCreditoIniciales($creditoId, $credito, 'Anticipo registrado desde servicio técnico ' . $numeroOrden);

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

    private function actualizarCreditoServicioTecnicoExistente(object $credito, float $totalServicio): array
    {
        $abonosPosteriores = round((float) DB::table('abono_credito')
            ->where('Id_Credito', $credito->Id_Credito)
            ->sum('Monto_Equivalente_Cordobas'), 2);

        $pagadoTotal = round((float) ($credito->Abono_Inicial ?? 0) + $abonosPosteriores, 2);
        $saldoPendiente = round(max($totalServicio - $pagadoTotal, 0), 2);

        DB::table('credito')
            ->where('Id_Credito', $credito->Id_Credito)
            ->update([
                'Saldo_Actual' => $saldoPendiente,
                'Estado' => $saldoPendiente <= 0
                    ? self::ESTADO_CREDITO_CANCELADO
                    : self::ESTADO_CREDITO_PENDIENTE,
            ]);

        return [
            'pagado_total' => min($pagadoTotal, $totalServicio),
            'saldo_pendiente' => $saldoPendiente,
            'cambio_entregado' => 0,
        ];
    }

    private function crearVentaServicioTecnico(int $usuarioId, float $totalServicio, array $cobro): int
    {
        $venta = $this->crearModelo(Venta::class, [
            'Numero_Factura' => $this->generarNumeroFactura(),
            'Fecha_venta' => now(),
            'Id_Cliente' => $this->clienteId,
            'Id_Usuario' => $usuarioId,
            'Tipo_Venta' => $this->tipoOperacion,
            'Estado' => Venta::ESTADO_ACTIVA ?? 1,
            'Descuento' => 0,
            'Total' => $totalServicio,
            'Tipo_Cambio' => $this->tasaCambio(),
            'Cambio_Entregado_Cordobas' => $cobro['cambio_entregado'] ?? 0,
        ]);

        return (int) $venta->Id_Venta;
    }

    private function actualizarVentaServicioTecnico(int $ventaId, int $usuarioId, float $totalServicio, array $cobro): void
    {
        DB::table('venta')
            ->where('Id_Venta', $ventaId)
            ->update([
                'Id_Cliente' => $this->clienteId,
                'Id_Usuario' => $usuarioId,
                'Tipo_Venta' => $this->tipoOperacion,
                'Estado' => Venta::ESTADO_ACTIVA ?? 1,
                'Descuento' => 0,
                'Total' => $totalServicio,
                'Tipo_Cambio' => $this->tasaCambio(),
                'Cambio_Entregado_Cordobas' => DB::raw('COALESCE(Cambio_Entregado_Cordobas, 0) + ' . (float) ($cobro['cambio_entregado'] ?? 0)),
            ]);
    }

    private function registrarDetalleVentaServicioTecnico(int $ventaId, int $servicioId, string $numeroOrden): void
    {
        DB::table('detalle_venta')->where('Id_Venta', $ventaId)->delete();

        if ($this->numeroSeguro($this->costoEstimado) > 0) {
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
                'Precio_Unitario' => round($this->numeroSeguro($this->costoEstimado), 2),
                'Subtotal' => round($this->numeroSeguro($this->costoEstimado), 2),
                'Descuento' => 0,
                'Observacion' => 'Mano de obra servicio técnico ' . $numeroOrden,
            ]);
        }

        foreach ($this->productos as $item) {
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
                'Observacion' => 'Repuesto usado en servicio técnico ' . $numeroOrden,
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

    private function limpiarCobroServicio(): void
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
        // MODIFICADO: la apertura actualiza la tasa_cambio; usamos la última tasa registrada.
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
            'TECNICO' => [
                'Nombre_Servicio' => 'Servicio técnico',
                'Descripcion' => 'Recepción, diagnóstico y reparación de equipos.',
                'Tipo_Servicio' => 'TECNICO',
                'Unidad_Medida' => 'SERVICIO',
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
            'Requiere_Contrato' => 0,
            'Requiere_Anticipo' => 0,
            'Porcentaje_Anticipo' => 0,
            'Garantia' => 0,
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
                'Observacion' => 'Usado en servicio técnico',
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
                        <h1 class="text-2xl font-black tracking-tight text-[#1A2B42]">Servicio técnico</h1>

                        @if($servicioTecnicoIdSeleccionado)
                        <span class="rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-bold text-[#0B6FE4]">
                            Editando #{{ $servicioTecnicoIdSeleccionado }}
                        </span>
                        @else
                        <span
                            class="rounded-full bg-white px-3 py-1 text-xs font-bold text-[#5F6B7A] ring-1 ring-[#D7E4F3]">
                            Nuevo ingreso
                        </span>
                        @endif
                    </div>

                    <p class="mt-1 text-sm text-[#5F6B7A]">
                        Registro, diagnóstico, checklist y repuestos en una sola pantalla.
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
                        class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-bold text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />

                    <x-button icon="o-document-plus" label="Nuevo" wire:click="nuevoIngreso"
                        class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-bold text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />
                </div>
            </div>
        </div>

        {{-- MODIFICADO: se eliminó el bloque manual de mensaje fijo; MaryUI renderiza el toast temporal
        automáticamente. --}}

        @php
        $totalRepuestos = round((float) collect($productos)->sum('subtotal'), 2);
        $totalServicio = $this->totalServicioActual();
        $saldo = $this->saldoServicio();
        $montoPagadoAnterior = $this->montoPagadoAnteriorServicio();
        $saldoAntesPago = $this->saldoPendienteAntesDePago();
        @endphp

        <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 overflow-hidden xl:grid-cols-12">
            <div class="min-h-0 overflow-y-auto pr-0 xl:col-span-8 xl:pr-1">
                <div class="space-y-4">

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="text-lg font-black text-[#1A2B42]">Datos del equipo</h2>
                                <p class="text-sm text-[#5F6B7A]">Información principal para recibir el equipo.</p>
                            </div>

                            <div class="rounded-2xl bg-[#EAF2FB] px-4 py-2 text-right">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#0B6FE4]">Total estimado</p>
                                <p class="text-xl font-black text-[#1A2B42]">
                                    C$ {{ number_format($totalServicio, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="md:col-span-2 xl:col-span-3">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">
                                    {{ $tipoOperacion === 'CREDITO' ? 'Institución' : 'Cliente' }}
                                </label>

                                <div class="relative">
                                    <x-input error-field="clienteId" error-class="hidden" wire:model.live.debounce.300ms="filtroCliente"
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
                            </div>


                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Técnico</label>
                                <x-select error-class="hidden" wire:model.live="tecnicoId" :options="$tecnicos" option-value="id"
                                    option-label="name" placeholder="Seleccione técnico"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Tipo</label>
                                <x-select error-class="hidden" wire:model.live="tipoEquipo" :options="$tiposEquipo" option-value="id"
                                    option-label="name" placeholder="Seleccione"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Marca</label>
                                <x-input error-class="hidden" wire:model.live="marca"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Modelo</label>
                                <x-input error-class="hidden" wire:model.live="modelo"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div class="xl:col-span-2">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Serie del equipo
                                    recibido</label>
                                <x-input error-class="hidden" wire:model.live="numeroSerie"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Estado</label>
                                <x-select error-class="hidden" wire:model.live="estadoServicio"
                                    :options="$this->estadosServicioDisponibles()" option-value="id" option-label="name"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Costo estimado</label>
                                <x-input error-class="hidden" wire:model.live="costoEstimado" type="number" step="0.01" prefix="C$"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Fecha estimada</label>
                                <x-input error-class="hidden" wire:model.live="fechaEstimadaEntrega" type="date"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div class="md:col-span-2 xl:col-span-4">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Problema reportado</label>
                                <x-textarea error-class="hidden" wire:model.live="problemaReportado" rows="2"
                                    class="w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div class="md:col-span-2 xl:col-span-4">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Detalle descriptivo</label>
                                <x-textarea error-class="hidden" wire:model.live="detalleDescriptivo" rows="2"
                                    class="w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div class="md:col-span-2 xl:col-span-4">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Observación técnica</label>
                                <x-textarea error-class="hidden" wire:model.live="observacionTecnica" rows="2"
                                    class="w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>
                        </div>
                    </x-card>

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <div class="mb-4">
                            <h2 class="text-lg font-black text-[#1A2B42]">Estado físico del equipo</h2>
                            <p class="text-sm text-[#5F6B7A]">Checklist rápido de recepción.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 md:grid-cols-3 xl:grid-cols-4">
                            @foreach($checklistItems as $key => $label)
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-2xl border border-[#2E8BC0] bg-[#F7F9FC] px-4 py-3 text-sm font-bold text-[#1A2B42] transition hover:bg-[#EAF2FB]">
                                <x-checkbox wire:model.live="checklist.{{ $key }}"
                                    class="checkbox-sm border-2 border-[#2E8BC0] bg-white text-white checked:border-[#0B6FE4] checked:bg-[#0B6FE4] checked:[--chkbg:#0B6FE4] checked:[--chkfg:white]" />

                                <span class="leading-tight">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Observación del checklist</label>
                            <x-textarea error-class="hidden" wire:model="checklist.observacion_checklist" rows="2"
                                class="w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                        </div>
                    </x-card>

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="text-lg font-black text-[#1A2B42]">Repuestos / insumos</h2>
                                <p class="text-sm text-[#5F6B7A]">
                                    Agregá repuestos directo aquí. Las series usadas salen del inventario disponible.
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#EAF2FB] px-4 py-2 text-right">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#0B6FE4]">Repuestos</p>
                                <p class="text-xl font-black text-[#1A2B42]">
                                    C$ {{ number_format($totalRepuestos, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="mb-4 rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-3"
                            wire:keydown.enter.prevent="agregarProducto">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                <div class="relative md:col-span-5">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Producto</label>
                                    <x-input error-field="productoId" error-class="hidden" wire:model.live.debounce.350ms="filtroProducto"
                                        wire:focus="abrirBusquedaProductos"
                                        wire:keydown.escape="cerrarBusquedaProductos" icon="o-magnifying-glass"
                                        placeholder="Buscar producto, marca, modelo, código o serie"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

                                    @if($mostrarBusquedaProductos)
                                    <div
                                        class="absolute left-0 right-0 z-50 mt-2 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white shadow-xl">
                                        <div class="max-h-72 overflow-y-auto">
                                            @forelse($productosDisponibles as $producto)
                                            <button type="button"
                                                wire:click="seleccionarProducto({{ $producto['id'] }})"
                                                class="flex w-full items-start gap-3 border-b border-[#EEF3F8] px-3 py-2 text-left transition hover:bg-[#EAF2FB]">
                                                <span
                                                    class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#EAF2FB] text-[#0B6FE4]">
                                                    <x-icon name="o-cube" class="h-4 w-4" />
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm font-bold text-[#1A2B42]">{{
                                                        $producto['titulo'] }}</span>
                                                    <span class="block text-[11px] font-semibold text-[#5F6B7A]">
                                                        Stock: {{ $producto['stock'] }}
                                                        @if(($producto['series_disponibles'] ?? 0) > 0)
                                                        · Series: {{ $producto['series_disponibles'] }}
                                                        @endif
                                                        · {{ $producto['precio_texto'] }}
                                                    </span>
                                                </span>
                                            </button>
                                            @empty
                                            <div class="px-4 py-5 text-center text-sm font-semibold text-[#5F6B7A]">
                                                No encontré productos disponibles con esa búsqueda.
                                            </div>
                                            @endforelse
                                        </div>

                                        <div
                                            class="flex flex-col gap-2 bg-[#F7F9FC] px-3 py-2 text-xs font-semibold text-[#5F6B7A] sm:flex-row sm:items-center sm:justify-between">
                                            <span>Mostrando {{ count($productosDisponibles) }} de {{
                                                $totalProductosBusqueda }} producto(s)</span>
                                            <div class="flex justify-end gap-2">
                                                @if($hayMasProductos)
                                                <x-button label="Cargar más" wire:click="cargarMasProductos"
                                                    class="h-8 min-h-8 rounded-xl bg-white px-3 text-xs font-bold text-[#1A2B42] hover:bg-[#EAF2FB]" />
                                                @endif
                                                <x-button icon="o-x-mark" label="Cerrar"
                                                    wire:click="cerrarBusquedaProductos"
                                                    class="h-8 min-h-8 rounded-xl border border-[#D7E4F3] bg-white px-3 text-xs font-bold text-[#1A2B42] hover:bg-[#EAF2FB]" />
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                </div>

                                @if($productoTieneSeries)
                                <div class="md:col-span-3">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Serie</label>
                                    <x-select error-class="hidden" wire:model.live="productoSerieId" :options="$seriesDisponibles"
                                        option-value="id" option-label="name" placeholder="Seleccione serie"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                </div>
                                @else
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Cantidad</label>
                                    <x-input error-class="hidden" wire:model.live="productoCantidad" type="number" step="0.01"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                </div>
                                @endif

                                <div class="{{ $productoTieneSeries ? 'md:col-span-2' : 'md:col-span-2' }} min-w-0">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Precio</label>
                                    <x-input error-class="hidden" wire:model.live.debounce.250ms="productoPrecio" type="text"
                                        inputmode="decimal" placeholder="C$ 0.00"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white px-3 text-right text-sm font-black tabular-nums text-[#1A2B42] placeholder:text-left placeholder:font-semibold placeholder:text-[#7B8794]" />
                                </div>

                                <div
                                    class="{{ $productoTieneSeries ? 'md:col-span-2' : 'md:col-span-3' }} flex items-end">
                                    <x-button icon="o-plus" label="Agregar" wire:click="agregarProducto"
                                        class="h-10 min-h-10 w-full rounded-xl border-0 bg-[#2E8BC0] text-sm font-bold text-white hover:bg-[#0B6FE4]" />
                                </div>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-[#D7E4F3]">
                            <x-table :headers="$headers" :rows="$productos"
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
                                    {{ $this->estadoNombre($estadoServicio) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Repuestos</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    {{ count($productos) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Mano de obra</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    C$ {{ number_format((float) $costoEstimado, 2) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#F7F9FC] p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">Insumos</p>
                                <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                    C$ {{ number_format($totalRepuestos, 2) }}
                                </p>
                            </div>
                        </div>

                        @if($servicioTecnicoIdSeleccionado && $montoPagadoAnterior > 0 && ! $servicioPagado)
                        <div class="mt-3 rounded-2xl border border-[#B7D6F2] bg-[#EAF4FD] p-3">
                            <div class="flex items-start gap-3">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-[#0B6FE4]">
                                    <x-icon name="o-banknotes" class="h-5 w-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-black text-[#1A2B42]">Este servicio ya tiene dinero recibido
                                    </h3>
                                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                                        <div class="rounded-xl bg-white p-2">
                                            <span class="block font-semibold text-[#5F6B7A]">Pagado acumulado</span>
                                            <strong class="text-[#1A2B42]">C$ {{ number_format($montoPagadoAnterior, 2)
                                                }}</strong>
                                        </div>
                                        <div class="rounded-xl bg-white p-2">
                                            <span class="block font-semibold text-[#5F6B7A]">Saldo pendiente</span>
                                            <strong class="text-[#1A2B42]">C$ {{ number_format($saldoAntesPago, 2)
                                                }}</strong>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs font-semibold leading-5 text-[#5F6B7A]">
                                        Los campos de pago se limpian automáticamente. Ingresá solo el nuevo abono o el
                                        saldo restante.
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($servicioPagado)
                        <div class="mt-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-3">
                            <div class="flex items-start gap-3">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-700">
                                    <x-icon name="o-check-circle" class="h-5 w-5" />
                                </span>
                                <div>
                                    <h3 class="text-sm font-black text-[#1A2B42]">Servicio pagado completo</h3>
                                    <p class="mt-1 text-xs font-semibold leading-5 text-emerald-700">
                                        Este servicio ya quedó pagado completo. Los campos de cobro se limpian y no se
                                        permite registrar otro pago.
                                    </p>
                                </div>
                            </div>
                        </div>
                        @elseif($this->servicioPermiteCobro())
                        <div class="mt-3 rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-3">
                            <h3 class="mb-3 text-sm font-black text-[#1A2B42]">Pago recibido</h3>

                            <div class="grid grid-cols-1 gap-2">
                                <div>
                                    <label class="mb-1 block text-xs font-bold text-[#1A2B42]">Tipo cambio
                                        actual</label>
                                    <x-input error-class="hidden" wire:model.live.debounce.250ms="tipoCambio" type="text" inputmode="decimal"
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
                                        <x-input error-class="hidden" wire:model.live.debounce.250ms="pagoCordobas" type="text"
                                            inputmode="numeric"
                                            class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    </div>
                                </div>

                                @if(in_array($tipoPagoCordobas, ['TRANSFERENCIA', 'TARJETA'], true))
                                <x-input error-class="hidden" wire:model.live.debounce.250ms="referenciaCordobas" type="text"
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
                                        <x-input error-class="hidden" wire:model.live.debounce.250ms="pagoDolares" type="text"
                                            inputmode="decimal"
                                            class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    </div>
                                </div>

                                @if(in_array($tipoPagoDolares, ['TRANSFERENCIA', 'TARJETA'], true))
                                <x-input error-class="hidden" wire:model.live.debounce.250ms="referenciaDolares" type="text"
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
                                        <strong class="text-[#1A2B42]">C$ {{ number_format($saldo, 2) }}</strong>
                                    </div>
                                    <div class="rounded-xl bg-white p-2">
                                        <span class="block text-[#5F6B7A]">Cambio</span>
                                        <strong class="text-[#1A2B42]">C$ {{ number_format($this->cambioServicio(), 2)
                                            }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @else
                        <div class="mt-3 rounded-2xl border border-[#F6D28B] bg-[#FFF8E6] p-3">
                            <div class="flex items-start gap-3">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-[#B7791F]">
                                    <x-icon name="o-lock-closed" class="h-5 w-5" />
                                </span>

                                <div>
                                    <h3 class="text-sm font-black text-[#1A2B42]">Pago bloqueado</h3>
                                    <p class="mt-1 text-xs font-semibold leading-5 text-[#7A5A16]">
                                        Para registrar el pago, primero cambie el estado del servicio a
                                        <strong>Reparado</strong>. Cuando el pago complete el saldo, el sistema lo
                                        pasará automáticamente a <strong>Entregado</strong> y abrirá el voucher.
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($tipoOperacion === 'CREDITO')
                        <div class="mt-3 rounded-2xl border border-[#B7D6F2] bg-[#EAF4FD] p-3">
                            <h3 class="text-sm font-black text-[#1A2B42]">Crédito institucional</h3>
                            <p class="text-xs text-[#5F6B7A]">Solo instituciones. Se aplica el saldo a favor disponible
                                y el restante queda en crédito.</p>

                            <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                                <div class="rounded-xl bg-white p-2">
                                    <span class="block text-[#5F6B7A]">Saldo a favor</span>
                                    <strong class="text-[#1A2B42]">C$ {{ number_format($this->saldoFavorDisponible(), 2)
                                        }}</strong>
                                </div>
                                <div class="rounded-xl bg-white p-2">
                                    <span class="block text-[#5F6B7A]">Aplicado</span>
                                    <strong class="text-[#1A2B42]">C$ {{
                                        number_format($this->saldoFavorAplicadoServicio(), 2) }}</strong>
                                </div>
                                <div class="rounded-xl bg-white p-2">
                                    <span class="block text-[#5F6B7A]">Nuevo crédito</span>
                                    <strong class="text-[#1A2B42]">C$ {{ number_format($this->saldoCreditoServicio(), 2)
                                        }}</strong>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="mt-3 rounded-2xl bg-[#2E8BC0] p-4 text-white">
                            <p class="text-xs font-bold uppercase tracking-wide text-white/80">Total general</p>
                            <p class="text-2xl font-black">
                                C$ {{ number_format($totalServicio, 2) }}
                            </p>
                        </div>

                        <x-button icon="o-check"
                            label="{{ $servicioTecnicoIdSeleccionado ? ($tipoOperacion === 'CREDITO' ? 'Actualizar crédito' : 'Actualizar servicio') : ($tipoOperacion === 'CREDITO' ? 'Guardar crédito' : 'Guardar ingreso') }}"
                            wire:click="guardar" spinner="guardar"
                            class="mt-3 h-11 min-h-11 w-full rounded-xl border-0 bg-[#2E8BC0] text-sm font-black text-white hover:bg-[#0B6FE4]" />

                    </x-card>


                </div>
            </div>
        </div>
    </div>

    <x-modal wire:model="modalPendientes" class="backdrop-blur-sm"
        box-class="w-[96vw] max-w-7xl max-h-[90vh] overflow-hidden rounded-3xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">
        <div class="flex max-h-[86vh] flex-col gap-3">
            <div
                class="flex flex-col gap-3 border-b border-[#D7E4F3] pb-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-2xl font-black text-[#1A2B42]">Servicios técnicos pendientes</h3>
                </div>

                <span class="w-fit rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-black text-[#0B6FE4]">
                    Página {{ $paginaPendientes }} / {{ $totalPaginasPendientes }}
                </span>
            </div>
            <x-input error-class="hidden" wire:model.live.debounce.350ms="filtroPendientes" icon="o-magnifying-glass"
                placeholder="Buscar por orden, cliente, equipo, marca o modelo..."
                class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

            <div class="flex justify-end rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] px-3 py-2">
                <div class="flex gap-2">
                    <x-button icon="o-chevron-left" label="Anterior" wire:click="paginaAnteriorPendientes"
                        :disabled="$paginaPendientes <= 1"
                        class="h-8 min-h-8 rounded-xl border border-[#D7E4F3] bg-white px-3 text-xs font-bold text-[#1A2B42]" />
                    <x-button icon="o-chevron-right" label="Siguiente" wire:click="paginaSiguientePendientes"
                        :disabled="$paginaPendientes >= $totalPaginasPendientes"
                        class="h-8 min-h-8 rounded-xl border border-[#D7E4F3] bg-white px-3 text-xs font-bold text-[#1A2B42]" />
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-auto rounded-2xl border border-[#D7E4F3]">
                <table class="w-full min-w-190 text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-[#2E8BC0] text-white">
                        <tr>
                            <th class="px-3 py-2 font-bold">Orden</th>
                            <th class="px-3 py-2 font-bold">Cliente</th>
                            <th class="px-3 py-2 font-bold">Equipo</th>
                            <th class="px-3 py-2 font-bold">Estado</th>
                            <th class="px-3 py-2 font-bold">Saldo</th>
                            <th class="px-3 py-2 text-center font-bold">Acción</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[#D7E4F3] bg-white text-[#1A2B42]">
                        @forelse($serviciosPendientes as $item)
                        <tr class="hover:bg-[#F7F9FC]">
                            <td class="px-3 py-2 font-black">{{ $item['numero'] }}</td>
                            <td class="px-3 py-2">{{ $item['cliente'] }}</td>
                            <td class="px-3 py-2">{{ $item['equipo'] }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full bg-[#EAF2FB] px-2 py-1 text-xs font-black text-[#0B6FE4]">
                                    {{ $this->estadoNombre($item['estado']) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 font-bold">C$ {{ number_format((float) ($item['saldo'] ??
                                $item['total']), 2) }}</td>
                            <td class="px-3 py-2 text-center">
                                <x-button icon="o-arrow-down-tray" label="Cargar"
                                    wire:click="cargarPendiente({{ $item['id'] }})"
                                    class="h-8 min-h-8 rounded-xl border-0 bg-[#2E8BC0] px-3 text-xs font-bold text-white hover:bg-[#0B6FE4]" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-[#5F6B7A]">
                                No hay servicios pendientes con ese filtro.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalPendientes', false)"
                class="rounded-xl border border-[#D7E4F3] bg-white text-[#1A2B42]" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalVoucherPdf" class="backdrop-blur-sm"
        box-class="w-full max-w-md rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">
        <div class="mb-4">
            <h3 class="text-2xl font-bold text-[#1A2B42]">Voucher de servicio técnico</h3>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Revise el voucher. Se genera automáticamente al entregar un servicio pagado completo.
            </p>
        </div>

        <div class="overflow-hidden rounded-xl border border-[#D7E4F3] bg-[#F8FBFF]">
            @if($voucherPdfUrl !== '')
            <iframe src="{{ $voucherPdfUrl }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" loading="eager"
                class="h-[68vh] w-full bg-white">
            </iframe>
            @else
            <div class="px-4 py-12 text-center text-sm text-[#7B8794]">
                No hay voucher para mostrar.
            </div>
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" type="button" wire:click="cerrarVoucherPdf"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalClientes" title="Listado completo de clientes" separator class="backdrop-blur-sm"
        box-class="w-[95vw] max-w-5xl rounded-3xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">
        <div class="space-y-3">
            <x-input error-class="hidden" wire:model.live.debounce.350ms="filtroListadoClientes" icon="o-magnifying-glass"
                placeholder="Buscar por nombre, institución, teléfono o código..."
                class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

            <div
                class="flex flex-col gap-2 rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] px-3 py-2 text-xs text-[#5F6B7A] md:flex-row md:items-center md:justify-between">
                <span>
                    Página {{ $paginaClientes }} de {{ $totalPaginasClientes }} · {{ $totalClientesListado }} cliente(s)
                    encontrado(s).
                </span>
                <div class="flex gap-2">
                    <x-button icon="o-chevron-left" label="Anterior" wire:click="paginaAnteriorClientes"
                        :disabled="$paginaClientes <= 1"
                        class="h-8 min-h-8 rounded-xl border border-[#D7E4F3] bg-white px-3 text-xs font-bold text-[#1A2B42]" />
                    <x-button icon="o-chevron-right" label="Siguiente" wire:click="paginaSiguienteClientes"
                        :disabled="$paginaClientes >= $totalPaginasClientes"
                        class="h-8 min-h-8 rounded-xl border border-[#D7E4F3] bg-white px-3 text-xs font-bold text-[#1A2B42]" />
                </div>
            </div>

            <div class="max-h-[60vh] overflow-auto rounded-2xl border border-[#D7E4F3]">
                <table class="w-full min-w-180 text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-[#2E8BC0] text-white">
                        <tr>
                            <th class="px-3 py-2 font-bold">Cliente</th>
                            <th class="px-3 py-2 text-center font-bold">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D7E4F3] bg-white text-[#1A2B42]">
                        @forelse($clientesListado as $item)
                        <tr class="hover:bg-[#F7F9FC]">
                            <td class="px-3 py-2 font-semibold">{{ $item['name'] }}</td>
                            <td class="px-3 py-2 text-center">
                                <x-button icon="o-check" label="Seleccionar"
                                    wire:click="seleccionarClienteListado({{ $item['id'] }})"
                                    class="h-8 min-h-8 rounded-xl border-0 bg-[#2E8BC0] px-3 text-xs font-bold text-white hover:bg-[#0B6FE4]" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-3 py-8 text-center text-[#5F6B7A]">
                                No hay clientes con ese filtro.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalClientes', false)"
                class="rounded-xl border border-[#D7E4F3] bg-white text-[#1A2B42]" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalProductos" title="Listado completo de productos disponibles" separator
        class="backdrop-blur-sm"
        box-class="w-[95vw] max-w-5xl rounded-3xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">
        <div class="space-y-3">
            <x-input error-class="hidden" wire:model.live.debounce.350ms="filtroListadoProductos" icon="o-magnifying-glass"
                placeholder="Buscar por producto, marca, modelo o código..."
                class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

            <div
                class="flex flex-col gap-2 rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] px-3 py-2 text-xs text-[#5F6B7A] md:flex-row md:items-center md:justify-between">
                <span>
                    Página {{ $paginaProductos }} de {{ $totalPaginasProductos }} · {{ $totalProductosListado }}
                    producto(s) encontrado(s).
                </span>
                <div class="flex gap-2">
                    <x-button icon="o-chevron-left" label="Anterior" wire:click="paginaAnteriorProductos"
                        :disabled="$paginaProductos <= 1"
                        class="h-8 min-h-8 rounded-xl border border-[#D7E4F3] bg-white px-3 text-xs font-bold text-[#1A2B42]" />
                    <x-button icon="o-chevron-right" label="Siguiente" wire:click="paginaSiguienteProductos"
                        :disabled="$paginaProductos >= $totalPaginasProductos"
                        class="h-8 min-h-8 rounded-xl border border-[#D7E4F3] bg-white px-3 text-xs font-bold text-[#1A2B42]" />
                </div>
            </div>

            <div class="max-h-[60vh] overflow-auto rounded-2xl border border-[#D7E4F3]">
                <table class="w-full min-w-190 text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-[#2E8BC0] text-white">
                        <tr>
                            <th class="px-3 py-2 font-bold">Producto</th>
                            <th class="px-3 py-2 text-center font-bold">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D7E4F3] bg-white text-[#1A2B42]">
                        @forelse($productosListado as $item)
                        <tr class="hover:bg-[#F7F9FC]">
                            <td class="px-3 py-2 font-semibold">{{ $item['name'] }}</td>
                            <td class="px-3 py-2 text-center">
                                <x-button icon="o-check" label="Seleccionar"
                                    wire:click="seleccionarProductoListado({{ $item['id'] }})"
                                    class="h-8 min-h-8 rounded-xl border-0 bg-[#2E8BC0] px-3 text-xs font-bold text-white hover:bg-[#0B6FE4]" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-3 py-8 text-center text-[#5F6B7A]">
                                No hay productos disponibles con ese filtro.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalProductos', false)"
                class="rounded-xl border border-[#D7E4F3] bg-white text-[#1A2B42]" />
        </x-slot:actions>
    </x-modal>
</div>
