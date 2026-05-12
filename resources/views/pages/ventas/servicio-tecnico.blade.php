<?php

use Livewire\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

new class extends Component
{
    public array $clientes = [];
    public array $tecnicos = [];
    public array $productosDisponibles = [];
    public array $seriesDisponibles = [];
    public array $productos = [];
    public array $serviciosPendientes = [];

    public ?int $servicioTecnicoIdSeleccionado = null;
    public ?array $mensaje = null;

    public bool $modalPendientes = false;
    public string $filtroPendientes = '';

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
    public float|string $costoEstimado = 0;
    public ?string $fechaEstimadaEntrega = null;
    public string $observacionTecnica = '';

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
    public float|string $productoCantidad = 1;
    public float|string $productoPrecio = 0;
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

    public function mount(): void
    {
        $this->cargarCombos();
        $this->cargarPendientes();
    }

    public function cargarCombos(): void
    {
        $this->clientes = Cliente::query()
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'cliente.Id_Persona')
            ->where('cliente.Estado', 1)
            ->select([
                'cliente.Id_Cliente as id',
                'cliente.Institucion',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido',
                'p.Telefono',
            ])
            ->orderBy('cliente.Institucion')
            ->orderBy('p.Primer_Nombre')
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $this->limpiarTexto(
                    trim(
                        ($item->Institucion ? $item->Institucion . ' - ' : '') .
                        trim(
                            ($item->Primer_Nombre ?? '') . ' ' .
                            ($item->Segundo_Nombre ?? '') . ' ' .
                            ($item->Primer_Apellido ?? '') . ' ' .
                            ($item->Segundo_Apellido ?? '')
                        ) .
                        ' | Tel: ' . ($item->Telefono ?? '')
                    )
                ),
            ])
            ->toArray();

        $this->tecnicos = Trabajador::query()
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
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $this->limpiarTexto(
                    trim(
                        ($item->Primer_Nombre ?? '') . ' ' .
                        ($item->Segundo_Nombre ?? '') . ' ' .
                        ($item->Primer_Apellido ?? '') . ' ' .
                        ($item->Segundo_Apellido ?? '')
                    ) . ' - ' . ($item->Cargo_Asignado ?: 'Trabajador')
                ),
            ])
            ->toArray();

        $seriesDisponiblesPorProducto = ProductoSerie::query()
            ->where('Estado', 'DISPONIBLE')
            ->get(['Id_Producto'])
            ->groupBy('Id_Producto')
            ->map(fn ($items) => $items->count());

        $this->productosDisponibles = Producto::query()
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'producto.Id_Marca')
            ->where('producto.Estado', 1)
            ->where('producto.Stock_Actual', '>', 0)
            ->select([
                'producto.Id_Producto as id',
                'producto.Nombre_Producto',
                'producto.Modelo',
                'producto.Precio_Venta as precio',
                'producto.Stock_Actual',
                'm.Nombre_Marca',
            ])
            ->orderBy('producto.Nombre_Producto')
            ->get()
            ->map(function ($item) use ($seriesDisponiblesPorProducto) {
                $seriesDisponibles = (int) ($seriesDisponiblesPorProducto[$item->id] ?? 0);

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
            })
            ->toArray();
    }

    public function cargarPendientes(): void
    {
        $query = ServicioTecnico::query()
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', 'servicio_tecnico.Id_Cliente')
            ->leftJoin('persona as pc', 'pc.Id_Persona', '=', 'c.Id_Persona')
            ->leftJoin('trabajador as t', 't.Id_Trabajador', '=', 'servicio_tecnico.Id_Trabajador')
            ->leftJoin('persona as pt', 'pt.Id_Persona', '=', 't.Id_Persona')
            ->whereNotIn('servicio_tecnico.Estado_Servicio', ['ENTREGADO', 'CANCELADO'])
            ->select([
                'servicio_tecnico.Id_Servicio_Tecnico as id',
                'servicio_tecnico.Numero_Orden as numero',
                'servicio_tecnico.Fecha_Ingreso as fecha',
                'servicio_tecnico.Tipo_Equipo as equipo',
                'servicio_tecnico.Marca as marca',
                'servicio_tecnico.Modelo as modelo',
                'servicio_tecnico.Estado_Servicio as estado',
                'servicio_tecnico.Total_Servicio as total',
                'c.Institucion as cliente_institucion',
                'pc.Primer_Nombre as cliente_primer_nombre',
                'pc.Segundo_Nombre as cliente_segundo_nombre',
                'pc.Primer_Apellido as cliente_primer_apellido',
                'pc.Segundo_Apellido as cliente_segundo_apellido',
                'pt.Primer_Nombre as tecnico_primer_nombre',
                'pt.Primer_Apellido as tecnico_primer_apellido',
            ])
            ->orderByDesc('servicio_tecnico.Fecha_Ingreso')
            ->limit(25);

        $filtro = trim($this->filtroPendientes);

        if ($filtro !== '') {
            $query->where(function ($q) use ($filtro) {
                $q->where('servicio_tecnico.Numero_Orden', 'like', '%' . $filtro . '%')
                    ->orWhere('servicio_tecnico.Tipo_Equipo', 'like', '%' . $filtro . '%')
                    ->orWhere('servicio_tecnico.Marca', 'like', '%' . $filtro . '%')
                    ->orWhere('servicio_tecnico.Modelo', 'like', '%' . $filtro . '%')
                    ->orWhere('pc.Primer_Nombre', 'like', '%' . $filtro . '%')
                    ->orWhere('pc.Primer_Apellido', 'like', '%' . $filtro . '%')
                    ->orWhere('c.Institucion', 'like', '%' . $filtro . '%');
            });
        }

        $this->serviciosPendientes = $query
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
                    'equipo' => $this->limpiarTexto(
                        trim(($item->equipo ?? '') . ' ' . ($item->marca ?? '') . ' ' . ($item->modelo ?? ''))
                    ),
                    'tecnico' => $tecnico ?: 'Sin técnico',
                    'estado' => $item->estado,
                    'total' => (float) $item->total,
                ];
            })
            ->toArray();
    }

    public function abrirPendientes(): void
    {
        $this->cargarPendientes();
        $this->modalPendientes = true;
    }

    public function updatedFiltroPendientes(): void
    {
        $this->cargarPendientes();
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
        $this->fechaEstimadaEntrega = $servicio->Fecha_Estimada_Entrega;
        $this->observacionTecnica = (string) ($servicio->Observacion_Tecnica ?? '');

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

        if (!$value) {
            return;
        }

        $telefono = Cliente::query()
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'cliente.Id_Persona')
            ->where('cliente.Id_Cliente', $value)
            ->value('p.Telefono');

        $this->telefonoCliente = (string) ($telefono ?? '');
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
        $this->validate([
            'productoId' => ['required', 'integer'],
            'productoCantidad' => ['required', 'numeric', 'min:0.01'],
            'productoPrecio' => ['required', 'numeric', 'min:0'],
        ], [
            'productoId.required' => 'Seleccione un producto.',
            'productoCantidad.min' => 'La cantidad debe ser mayor a cero.',
        ]);

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

        $cantidadYaAgregada = collect($this->productos)
            ->where('producto_id', $this->productoId)
            ->where('ya_guardado', false)
            ->sum('cantidad');

        if (!$this->productoTieneSeries && ($cantidadYaAgregada + $cantidad) > (float) $producto->Stock_Actual) {
            $this->addError('productoCantidad', 'La cantidad supera el stock disponible.');
            return;
        }

        if ($serie && collect($this->productos)->contains('producto_serie_id', (int) $serie->id_producto_serie)) {
            $this->addError('productoSerieId', 'Esta serie ya fue agregada.');
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

    public function guardar(): void
    {
        $this->validate([
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
        ], [
            'clienteId.required' => 'Seleccione el cliente.',
            'tecnicoId.required' => 'Seleccione el técnico receptor.',
            'tipoEquipo.required' => 'Seleccione el tipo de equipo.',
            'problemaReportado.required' => 'Ingrese el problema reportado.',
        ]);

        try {
            if ($this->servicioTecnicoIdSeleccionado) {
                $this->actualizarServicioTecnico();

                $id = $this->servicioTecnicoIdSeleccionado;

                $this->cargarCombos();
                $this->cargarPendientes();
                $this->cargarPendiente($id, false);

                $this->mostrarMensaje('success', 'Servicio actualizado', 'El servicio técnico se actualizó correctamente.');
                return;
            }

            $id = $this->crearServicioTecnico();

            $this->limpiarFormulario();
            $this->cargarCombos();
            $this->cargarPendientes();

            $this->mostrarMensaje('success', 'Ingreso guardado', 'El servicio técnico se registró correctamente. Orden #' . $id . '.');
        } catch (\Throwable $e) {
            report($e);
            $this->mostrarMensaje('error', 'No se pudo guardar', $e->getMessage());
        }
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

            $totalRepuestos = round((float) collect($this->productos)->sum('subtotal'), 2);
            $totalServicio = round($totalRepuestos + (float) $this->costoEstimado, 2);
            $servicioId = $this->servicioPorTipo('TECNICO');

            $servicioTecnico = $this->crearModelo(ServicioTecnico::class, [
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
                'Estado_Servicio' => $this->estadoServicio,
                'Costo_Estimado' => (float) $this->costoEstimado,
                'Fecha_Estimada_Entrega' => $this->fechaEstimadaEntrega ?: null,
                'Observacion_Tecnica' => $this->observacionTecnica ?: null,
                'Total_Repuestos' => $totalRepuestos,
                'Total_Servicio' => $totalServicio,
            ]);

            $servicioTecnicoId = (int) $servicioTecnico->Id_Servicio_Tecnico;

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
                throw new RuntimeException('El servicio técnico seleccionado ya no existe.');
            }

            $totalRepuestos = round((float) collect($this->productos)->sum('subtotal'), 2);
            $totalServicio = round($totalRepuestos + (float) $this->costoEstimado, 2);

            $servicio->forceFill([
                'Id_Cliente' => $this->clienteId,
                'Id_Trabajador' => $this->tecnicoId,
                'Tipo_Equipo' => $this->tipoEquipo,
                'Marca' => $this->marca ?: null,
                'Modelo' => $this->modelo ?: null,
                'Numero_Serie' => $this->numeroSerie ?: null,
                'Problema_Reportado' => $this->problemaReportado,
                'Detalle_Descriptivo' => $this->detalleDescriptivo ?: null,
                'Estado_Servicio' => $this->estadoServicio,
                'Costo_Estimado' => (float) $this->costoEstimado,
                'Fecha_Estimada_Entrega' => $this->fechaEstimadaEntrega ?: null,
                'Observacion_Tecnica' => $this->observacionTecnica ?: null,
                'Total_Repuestos' => $totalRepuestos,
                'Total_Servicio' => $totalServicio,
            ])->save();

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

        $this->resetProductoForm();

        foreach (array_keys($this->checklistItems) as $key) {
            $this->checklist[$key] = false;
        }

        $this->checklist['observacion_checklist'] = '';
        $this->resetErrorBag();
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
            throw new RuntimeException('No hay usuario activo para registrar el movimiento.');
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
            throw new RuntimeException('Producto no disponible: ' . $item['descripcion']);
        }

        $cantidad = (int) ceil((float) $item['cantidad']);

        if ($cantidad <= 0) {
            throw new RuntimeException('Cantidad inválida para: ' . $item['descripcion']);
        }

        if ((int) $producto->Stock_Actual < $cantidad) {
            throw new RuntimeException('Stock insuficiente para: ' . $item['descripcion']);
        }

        if ($item['producto_serie_id']) {
            $serie = ProductoSerie::query()
                ->where('id_producto_serie', $item['producto_serie_id'])
                ->lockForUpdate()
                ->first();

            if (!$serie || $serie->Estado !== 'DISPONIBLE') {
                throw new RuntimeException('La serie ya no está disponible: ' . $item['serie']);
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

    private function limpiarTexto(?string $texto): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $texto));
    }

    private function mostrarMensaje(string $tipo, string $titulo, string $descripcion): void
    {
        $this->mensaje = [
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
        ];
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
                    <x-button icon="o-document-plus" label="Nuevo" wire:click="nuevoIngreso"
                        class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-bold text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />
                </div>
            </div>
        </div>

        @if($mensaje)
        <div
            class="fixed right-5 top-5 z-50 w-[min(420px,calc(100vw-2rem))] rounded-2xl border px-4 py-3 shadow-xl {{ $mensaje['tipo'] === 'success' ? 'border-[#B7E4C7] bg-[#ECFDF3] text-[#166534]' : 'border-[#F5C2C7] bg-[#FEF2F2] text-[#991B1B]' }}">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-black">{{ $mensaje['titulo'] }}</p>
                    <p class="text-sm">{{ $mensaje['descripcion'] }}</p>
                </div>

                <button type="button" wire:click="$set('mensaje', null)"
                    class="rounded-lg px-2 text-sm font-black opacity-70 hover:bg-white/60 hover:opacity-100">
                    X
                </button>
            </div>
        </div>
        @endif

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
                                    C$ {{ number_format((float) $costoEstimado + collect($productos)->sum('subtotal'),
                                    2) }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Cliente</label>
                                <x-select wire:model.live="clienteId" :options="$clientes" option-value="id"
                                    option-label="name" placeholder="Seleccione cliente"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('clienteId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Teléfono</label>
                                <x-input wire:model="telefonoCliente" readonly
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Técnico</label>
                                <x-select wire:model="tecnicoId" :options="$tecnicos" option-value="id"
                                    option-label="name" placeholder="Seleccione técnico"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('tecnicoId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Tipo</label>
                                <x-select wire:model="tipoEquipo" :options="$tiposEquipo" option-value="id"
                                    option-label="name" placeholder="Seleccione"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('tipoEquipo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Marca</label>
                                <x-input wire:model="marca"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Modelo</label>
                                <x-input wire:model="modelo"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div class="xl:col-span-2">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Serie del equipo
                                    recibido</label>
                                <x-input wire:model="numeroSerie"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Estado</label>
                                <x-select wire:model="estadoServicio" :options="$estadosServicio" option-value="id"
                                    option-label="name"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Costo estimado</label>
                                <x-input wire:model.live="costoEstimado" type="number" step="0.01" prefix="C$"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Fecha estimada</label>
                                <x-input wire:model="fechaEstimadaEntrega" type="date"
                                    class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                            </div>

                            <div class="md:col-span-2 xl:col-span-4">
                                <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Problema reportado</label>
                                <x-textarea wire:model="problemaReportado" rows="2"
                                    class="w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42]" />
                                @error('problemaReportado') <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
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

                                <span class="leading-tight">
                                    {{ $label }}
                                </span>
                            </label>
                            @endforeach
                        </div>


                    </x-card>

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="text-lg font-black text-[#1A2B42]">Repuestos / insumos</h2>
                                <p class="text-sm text-[#5F6B7A]">
                                    Agregá repuestos directo aquí. Sin modal. Menos clics, menos sufrimiento
                                    administrativo.
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#EAF2FB] px-4 py-2 text-right">
                                <p class="text-xs font-bold uppercase tracking-wide text-[#0B6FE4]">Repuestos</p>
                                <p class="text-xl font-black text-[#1A2B42]">
                                    C$ {{ number_format(collect($productos)->sum('subtotal'), 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="mb-4 rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-3"
                            wire:keydown.enter.prevent="agregarProducto">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                <div class="md:col-span-5">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Producto</label>
                                    <x-select wire:model.live="productoId" :options="$productosDisponibles"
                                        option-value="id" option-label="name" placeholder="Seleccione producto"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    @error('productoId') <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>

                                @if($productoTieneSeries)
                                <div class="md:col-span-3">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Serie</label>
                                    <x-select wire:model="productoSerieId" :options="$seriesDisponibles"
                                        option-value="id" option-label="name" placeholder="Seleccione serie"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    @error('productoSerieId') <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                                @else
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Cantidad</label>
                                    <x-input wire:model="productoCantidad" type="number" step="0.01"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    @error('productoCantidad') <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                                @endif

                                <div class="{{ $productoTieneSeries ? 'md:col-span-2' : 'md:col-span-2' }}">
                                    <label class="mb-1 block text-sm font-bold text-[#1A2B42]">Precio</label>
                                    <x-input wire:model="productoPrecio" type="number" step="0.01" prefix="C$"
                                        class="h-10 min-h-10 w-full rounded-xl bg-white text-sm text-[#1A2B42]" />
                                    @error('productoPrecio') <span class="text-xs text-red-600">{{ $message }}</span>
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
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-black text-[#1A2B42]">Pendientes rápidos</h2>
                                <p class="text-sm text-[#5F6B7A]">Buscá y cargá sin abrir otra ventana.</p>
                            </div>

                            <span class="rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-black text-[#0B6FE4]">
                                {{ count($serviciosPendientes) }}
                            </span>
                        </div>

                        <x-input wire:model.live.debounce.350ms="filtroPendientes" icon="o-magnifying-glass"
                            placeholder="Orden, cliente, equipo..."
                            class="mb-3 h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

                        <div class="space-y-2">
                            @forelse(array_slice($serviciosPendientes, 0, 7) as $item)
                            <button type="button" wire:click="cargarPendiente({{ $item['id'] }}, false)"
                                class="w-full rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-3 text-left transition hover:border-[#2E8BC0] hover:bg-[#EAF2FB]">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-[#1A2B42]">
                                            {{ $item['numero'] }}
                                        </p>

                                        <p class="truncate text-xs font-semibold text-[#5F6B7A]">
                                            {{ $item['cliente'] }}
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full bg-white px-2 py-1 text-[11px] font-black text-[#0B6FE4] ring-1 ring-[#D7E4F3]">
                                        {{ $this->estadoNombre($item['estado']) }}
                                    </span>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <p class="truncate text-xs text-[#5F6B7A]">{{ $item['equipo'] }}</p>
                                    <p class="shrink-0 text-xs font-black text-[#1A2B42]">
                                        C$ {{ number_format((float) $item['total'], 2) }}
                                    </p>
                                </div>
                            </button>
                            @empty
                            <div
                                class="rounded-2xl border border-dashed border-[#D7E4F3] bg-[#F7F9FC] px-4 py-8 text-center">
                                <p class="text-sm font-bold text-[#1A2B42]">Sin pendientes</p>
                                <p class="text-xs text-[#5F6B7A]">No hay resultados con ese filtro.</p>
                            </div>
                            @endforelse
                        </div>

                        <x-button icon="o-folder-open" label="Abrir listado completo" wire:click="abrirPendientes"
                            class="mt-3 h-10 min-h-10 w-full rounded-xl border border-[#D7E4F3] bg-white text-sm font-bold text-[#1A2B42] hover:bg-[#F7F9FC]" />
                    </x-card>

                    <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                        <h2 class="text-lg font-black text-[#1A2B42]">Resumen</h2>
                        <p class="mb-3 text-sm text-[#5F6B7A]">Vista rápida antes de guardar.</p>

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
                                    C$ {{ number_format(collect($productos)->sum('subtotal'), 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 rounded-2xl bg-[#2E8BC0] p-4 text-white">
                            <p class="text-xs font-bold uppercase tracking-wide text-white/80">Total general</p>
                            <p class="text-2xl font-black">
                                C$ {{ number_format((float) $costoEstimado + collect($productos)->sum('subtotal'), 2) }}
                            </p>
                        </div>

                        <x-button icon="o-check"
                            label="{{ $servicioTecnicoIdSeleccionado ? 'Actualizar servicio' : 'Guardar ingreso' }}"
                            wire:click="guardar" spinner="guardar"
                            class="mt-3 h-11 min-h-11 w-full rounded-xl border-0 bg-[#2E8BC0] text-sm font-black text-white hover:bg-[#0B6FE4]" />
                    </x-card>
                </div>
            </div>
        </div>
    </div>

    <x-modal wire:model="modalPendientes" title="Servicios técnicos pendientes" separator class="backdrop-blur">
        <div class="space-y-3">
            <x-input wire:model.live.debounce.350ms="filtroPendientes" icon="o-magnifying-glass"
                placeholder="Buscar por orden, cliente, equipo, marca o modelo..."
                class="h-10 min-h-10 w-full rounded-xl bg-[#F7F9FC] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

            <div class="max-h-[60vh] overflow-auto rounded-2xl border border-[#D7E4F3]">
                <table class="w-full min-w-190 text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-[#2E8BC0] text-white">
                        <tr>
                            <th class="px-3 py-2 font-bold">Orden</th>
                            <th class="px-3 py-2 font-bold">Cliente</th>
                            <th class="px-3 py-2 font-bold">Equipo</th>
                            <th class="px-3 py-2 font-bold">Estado</th>
                            <th class="px-3 py-2 font-bold">Total</th>
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
                            <td class="px-3 py-2 font-bold">C$ {{ number_format((float) $item['total'], 2) }}</td>
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
</div>
