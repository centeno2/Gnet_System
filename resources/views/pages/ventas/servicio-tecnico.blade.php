<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;
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

    public bool $modalProducto = false;
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
        $this->clientes = DB::table('cliente as c')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->where('c.Estado', 1)
            ->selectRaw("c.Id_Cliente as id, TRIM(CONCAT(COALESCE(c.Institucion, ''), CASE WHEN c.Institucion IS NOT NULL AND c.Institucion <> '' THEN ' - ' ELSE '' END, COALESCE(p.Primer_Nombre, ''), ' ', COALESCE(p.Segundo_Nombre, ''), ' ', COALESCE(p.Primer_Apellido, ''), ' ', COALESCE(p.Segundo_Apellido, ''), ' | Tel: ', COALESCE(p.Telefono, ''))) as name")
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => trim(preg_replace('/\s+/', ' ', $item->name)),
            ])
            ->toArray();

        $this->tecnicos = DB::table('trabajador as t')
            ->join('persona as p', 'p.Id_Persona', '=', 't.Id_Persona')
            ->leftJoin('cargo as cg', 'cg.Id_Cargo', '=', 't.Id_Cargo')
            ->where('t.Estado', 1)
            ->selectRaw("t.Id_Trabajador as id, TRIM(CONCAT(p.Primer_Nombre, ' ', COALESCE(p.Segundo_Nombre, ''), ' ', p.Primer_Apellido, ' ', COALESCE(p.Segundo_Apellido, ''), ' - ', COALESCE(cg.Cargo_Asignado, 'Trabajador'))) as name")
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => trim(preg_replace('/\s+/', ' ', $item->name)),
            ])
            ->toArray();

        $this->productosDisponibles = DB::table('producto as p')
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'p.Id_Marca')
            ->leftJoin('producto_serie as ps', function ($join) {
                $join->on('ps.Id_Producto', '=', 'p.Id_Producto')
                    ->where('ps.Estado', '=', 'DISPONIBLE');
            })
            ->where('p.Estado', 1)
            ->where('p.Stock_Actual', '>', 0)
            ->groupBy('p.Id_Producto', 'p.Nombre_Producto', 'p.Modelo', 'p.Precio_Venta', 'p.Stock_Actual', 'm.Nombre_Marca')
            ->orderBy('p.Nombre_Producto')
            ->selectRaw("p.Id_Producto as id, CONCAT(COALESCE(m.Nombre_Marca, ''), CASE WHEN m.Nombre_Marca IS NULL OR m.Nombre_Marca = '' THEN '' ELSE ' ' END, p.Nombre_Producto, ' ', COALESCE(p.Modelo, ''), ' - Stock: ', p.Stock_Actual, CASE WHEN COUNT(ps.id_producto_serie) > 0 THEN CONCAT(' | Series: ', COUNT(ps.id_producto_serie)) ELSE '' END) as name, p.Precio_Venta as precio, COUNT(ps.id_producto_serie) as series_disponibles")
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => trim(preg_replace('/\s+/', ' ', $item->name)),
                'precio' => (float) $item->precio,
                'series_disponibles' => (int) $item->series_disponibles,
            ])
            ->toArray();
    }

    public function cargarPendientes(): void
    {
        $query = DB::table('servicio_tecnico as st')
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', 'st.Id_Cliente')
            ->leftJoin('persona as pc', 'pc.Id_Persona', '=', 'c.Id_Persona')
            ->leftJoin('trabajador as t', 't.Id_Trabajador', '=', 'st.Id_Trabajador')
            ->leftJoin('persona as pt', 'pt.Id_Persona', '=', 't.Id_Persona')
            ->whereNotIn('st.Estado_Servicio', ['ENTREGADO', 'CANCELADO'])
            ->selectRaw("st.Id_Servicio_Tecnico as id,
                st.Numero_Orden as numero,
                st.Fecha_Ingreso as fecha,
                st.Tipo_Equipo as equipo,
                st.Marca as marca,
                st.Modelo as modelo,
                st.Estado_Servicio as estado,
                st.Total_Servicio as total,
                TRIM(CONCAT(COALESCE(c.Institucion, ''), CASE WHEN c.Institucion IS NOT NULL AND c.Institucion <> '' THEN ' - ' ELSE '' END, COALESCE(pc.Primer_Nombre, ''), ' ', COALESCE(pc.Segundo_Nombre, ''), ' ', COALESCE(pc.Primer_Apellido, ''), ' ', COALESCE(pc.Segundo_Apellido, ''))) as cliente,
                TRIM(CONCAT(COALESCE(pt.Primer_Nombre, ''), ' ', COALESCE(pt.Primer_Apellido, ''))) as tecnico")
            ->orderByDesc('st.Fecha_Ingreso')
            ->limit(25);

        $filtro = trim($this->filtroPendientes);

        if ($filtro !== '') {
            $query->where(function ($q) use ($filtro) {
                $q->where('st.Numero_Orden', 'like', '%' . $filtro . '%')
                    ->orWhere('st.Tipo_Equipo', 'like', '%' . $filtro . '%')
                    ->orWhere('st.Marca', 'like', '%' . $filtro . '%')
                    ->orWhere('st.Modelo', 'like', '%' . $filtro . '%')
                    ->orWhere('pc.Primer_Nombre', 'like', '%' . $filtro . '%')
                    ->orWhere('pc.Primer_Apellido', 'like', '%' . $filtro . '%')
                    ->orWhere('c.Institucion', 'like', '%' . $filtro . '%');
            });
        }

        $this->serviciosPendientes = $query
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'numero' => $item->numero,
                'fecha' => $item->fecha,
                'cliente' => trim(preg_replace('/\s+/', ' ', $item->cliente ?: 'Cliente no especificado')),
                'equipo' => trim(($item->equipo ?? '') . ' ' . ($item->marca ?? '') . ' ' . ($item->modelo ?? '')),
                'tecnico' => trim($item->tecnico ?: 'Sin técnico'),
                'estado' => $item->estado,
                'total' => (float) $item->total,
            ])
            ->toArray();
    }

    public function abrirPendientes(): void
    {
        $this->filtroPendientes = '';
        $this->cargarPendientes();
        $this->modalPendientes = true;
    }

    public function updatedFiltroPendientes(): void
    {
        $this->cargarPendientes();
    }

    public function cargarPendiente(int $id, bool $cerrarModal = true): void
    {
        $servicio = DB::table('servicio_tecnico')
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

        $this->mostrarMensaje('success', 'Pendiente cargado', 'Ya podés revisar, actualizar estado o agregar más repuestos.');
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

        $telefono = DB::table('cliente as c')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->where('c.Id_Cliente', $value)
            ->value('p.Telefono');

        $this->telefonoCliente = (string) ($telefono ?? '');
    }

    public function abrirProducto(): void
    {
        $this->resetProductoForm();
        $this->modalProducto = true;
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

        $producto = DB::table('producto')
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

        $query = DB::table('producto_serie')
            ->where('Id_Producto', $value)
            ->where('Estado', 'DISPONIBLE');

        if (!empty($seriesUsadasEnPantalla)) {
            $query->whereNotIn('id_producto_serie', $seriesUsadasEnPantalla);
        }

        $this->seriesDisponibles = $query
            ->orderBy('Numero_Serie')
            ->get(['id_producto_serie as id', 'Numero_Serie as name'])
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $item->name,
            ])
            ->toArray();

        $totalSeriesDelProducto = DB::table('producto_serie')
            ->where('Id_Producto', $value)
            ->count();

        $this->productoTieneSeries = $totalSeriesDelProducto > 0;

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

        $producto = DB::table('producto as p')
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'p.Id_Marca')
            ->where('p.Id_Producto', $this->productoId)
            ->select('p.*', 'm.Nombre_Marca')
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

            $serie = DB::table('producto_serie')
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
            'descripcion' => trim(preg_replace('/\s+/', ' ', ($producto->Nombre_Marca ? $producto->Nombre_Marca . ' ' : '') . $producto->Nombre_Producto . ' ' . ($producto->Modelo ?? ''))),
            'serie' => $serie->Numero_Serie ?? 'N/A',
            'cantidad' => $cantidad,
            'precio' => $precio,
            'subtotal' => $subtotal,
            'acciones' => '',
        ];

        $this->modalProducto = false;
        $this->resetProductoForm();
        $this->cargarCombos();
    }

    public function quitarProducto(string $tmpId): void
    {
        $producto = collect($this->productos)->firstWhere('tmp_id', $tmpId);

        if ($producto && !empty($producto['ya_guardado'])) {
            $this->mostrarMensaje('error', 'No permitido', 'Este producto ya fue descontado del inventario. Para revertirlo hay que hacer una devolución/ajuste de inventario.');
            return;
        }

        $this->productos = array_values(array_filter($this->productos, fn ($item) => $item['tmp_id'] !== $tmpId));
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
        } catch (Throwable $e) {
            report($e);
            $this->mostrarMensaje('error', 'No se pudo guardar', $e->getMessage());
        }
    }

    private function crearServicioTecnico(): int
    {
        return DB::transaction(function () {
            $usuarioId = $this->usuarioActualId();
            $numeroOrden = $this->generarNumeroUnico('ST', 'servicio_tecnico', 'Numero_Orden');
            $totalRepuestos = round((float) collect($this->productos)->sum('subtotal'), 2);
            $totalServicio = round($totalRepuestos + (float) $this->costoEstimado, 2);
            $servicioId = $this->servicioPorTipo('TECNICO');

            $servicioTecnicoId = DB::table('servicio_tecnico')->insertGetId([
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

            DB::table('servicio_tecnico_checklist')->insert($this->datosChecklist($servicioTecnicoId));

            foreach ($this->productos as $item) {
                $this->registrarProductoServicio($servicioTecnicoId, $item);
            }

            return (int) $servicioTecnicoId;
        }, 3);
    }

    private function actualizarServicioTecnico(): void
    {
        DB::transaction(function () {
            $servicioTecnicoId = (int) $this->servicioTecnicoIdSeleccionado;

            $servicio = DB::table('servicio_tecnico')
                ->where('Id_Servicio_Tecnico', $servicioTecnicoId)
                ->lockForUpdate()
                ->first();

            if (!$servicio) {
                throw new RuntimeException('El servicio técnico seleccionado ya no existe.');
            }

            $totalRepuestos = round((float) collect($this->productos)->sum('subtotal'), 2);
            $totalServicio = round($totalRepuestos + (float) $this->costoEstimado, 2);

            DB::table('servicio_tecnico')
                ->where('Id_Servicio_Tecnico', $servicioTecnicoId)
                ->update([
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
                ]);

            DB::table('servicio_tecnico_checklist')->updateOrInsert(
                ['Id_Servicio_Tecnico' => $servicioTecnicoId],
                $this->datosChecklist($servicioTecnicoId)
            );

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

        DB::table('servicio_tecnico_producto')->insert([
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
        $check = DB::table('servicio_tecnico_checklist')
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
        $this->productos = DB::table('servicio_tecnico_producto as stp')
            ->join('producto as p', 'p.Id_Producto', '=', 'stp.Id_Producto')
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'p.Id_Marca')
            ->leftJoin('producto_serie as ps', 'ps.id_producto_serie', '=', 'stp.Id_Producto_Serie')
            ->where('stp.Id_Servicio_Tecnico', $servicioTecnicoId)
            ->select('stp.*', 'p.Nombre_Producto', 'p.Modelo', 'm.Nombre_Marca', 'ps.Numero_Serie')
            ->orderBy('stp.Id_Servicio_Tecnico_Producto')
            ->get()
            ->map(fn ($item) => [
                'tmp_id' => 'guardado_' . $item->Id_Servicio_Tecnico_Producto,
                'servicio_producto_id' => (int) $item->Id_Servicio_Tecnico_Producto,
                'ya_guardado' => true,
                'producto_id' => (int) $item->Id_Producto,
                'producto_serie_id' => $item->Id_Producto_Serie ? (int) $item->Id_Producto_Serie : null,
                'codigo' => (string) $item->Id_Producto,
                'descripcion' => trim(preg_replace('/\s+/', ' ', ($item->Nombre_Marca ? $item->Nombre_Marca . ' ' : '') . $item->Nombre_Producto . ' ' . ($item->Modelo ?? ''))),
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
        $this->resetErrorBag(['productoId', 'productoSerieId', 'productoCantidad', 'productoPrecio']);
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
        $this->checklist = [
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
            $id = DB::table('usuario')->where('Estado', 1)->value('Id_Usuario');
        }

        if (!$id) {
            throw new RuntimeException('No hay usuario activo para registrar el movimiento.');
        }

        return (int) $id;
    }

    private function servicioPorTipo(string $tipo): int
    {
        $id = DB::table('servicio')
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

        return (int) DB::table('servicio')->insertGetId(array_merge($datos, [
            'Precio_Base' => 0,
            'Requiere_Contrato' => 0,
            'Requiere_Anticipo' => 0,
            'Porcentaje_Anticipo' => 0,
            'Garantia' => 0,
            'Estado' => 1,
            'Permite_Credito' => 1,
        ]));
    }

    private function generarNumeroUnico(string $prefijo, string $tabla, string $columna): string
    {
        do {
            $numero = $prefijo . '-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (DB::table($tabla)->where($columna, $numero)->exists());

        return $numero;
    }

    private function descontarInventario(array $item, string $estadoSerie, string $tipoMovimiento): void
    {
        $producto = DB::table('producto')
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
            $serie = DB::table('producto_serie')
                ->where('id_producto_serie', $item['producto_serie_id'])
                ->lockForUpdate()
                ->first();

            if (!$serie || $serie->Estado !== 'DISPONIBLE') {
                throw new RuntimeException('La serie ya no está disponible: ' . $item['serie']);
            }

            DB::table('producto_serie')
                ->where('id_producto_serie', $item['producto_serie_id'])
                ->update([
                    'Estado' => $estadoSerie,
                    'Observacion' => 'Usado en servicio técnico',
                ]);
        }

        DB::table('producto')
            ->where('Id_Producto', $item['producto_id'])
            ->update(['Stock_Actual' => DB::raw('Stock_Actual - ' . $cantidad)]);

        DB::table('movimiento_inventario')->insert([
            'Id_Producto' => $item['producto_id'],
            'Id_Producto_Serie' => $item['producto_serie_id'],
            'Fecha_Movimiento' => now(),
            'Tipo_Movimiento' => $tipoMovimiento,
            'Cantidad' => $cantidad,
            'Motivo_Movimiento' => 1,
        ]);
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

<div
    class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-y-auto bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="flex shrink-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1A2B42]">Servicio técnico</h1>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Registro de ingreso, revisión y control del equipo.
            </p>
            @if($servicioTecnicoIdSeleccionado)
            <p class="mt-1 inline-flex rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-semibold text-[#0B6FE4]">
                Editando servicio técnico #{{ $servicioTecnicoIdSeleccionado }}
            </p>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            <x-button label="Nuevo ingreso" wire:click="nuevoIngreso"
                class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]" />

            <x-button label="Buscar pendientes" wire:click="abrirPendientes"
                class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]" />

            <x-button label="{{ $servicioTecnicoIdSeleccionado ? 'Actualizar ingreso' : 'Guardar ingreso' }}"
                wire:click="guardar" spinner="guardar"
                class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]" />
        </div>
    </div>

    @if($mensaje)
    <div
        class="rounded-2xl border px-4 py-3 shadow-sm {{ $mensaje['tipo'] === 'success' ? 'border-[#B7E4C7] bg-[#ECFDF3] text-[#166534]' : 'border-[#F5C2C7] bg-[#FEF2F2] text-[#991B1B]' }}">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="font-bold">{{ $mensaje['titulo'] }}</p>
                <p class="text-sm">{{ $mensaje['descripcion'] }}</p>
            </div>
            <button type="button" wire:click="$set('mensaje', null)"
                class="text-sm font-bold opacity-70 hover:opacity-100">X</button>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
        <x-card class="xl:col-span-3 rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Ingreso del equipo</h2>
                <p class="text-sm text-[#5F6B7A]">Seleccione datos reales del cliente y del técnico receptor.</p>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cliente</label>
                    <x-select wire:model.live="clienteId" :options="$clientes" option-value="id" option-label="name"
                        placeholder="Seleccione cliente"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                    @error('clienteId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Teléfono</label>
                    <x-input wire:model="telefonoCliente" readonly
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo de equipo</label>
                    <x-select wire:model="tipoEquipo" :options="[
                            ['id' => 'Computadora', 'name' => 'Computadora'],
                            ['id' => 'Laptop', 'name' => 'Laptop'],
                            ['id' => 'Impresora', 'name' => 'Impresora'],
                            ['id' => 'Otro', 'name' => 'Otro']
                        ]" option-value="id" option-label="name" placeholder="Seleccione"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                    @error('tipoEquipo') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Técnico receptor</label>
                    <x-select wire:model="tecnicoId" :options="$tecnicos" option-value="id" option-label="name"
                        placeholder="Seleccione técnico"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                    @error('tecnicoId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Marca</label>
                    <x-input wire:model="marca"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Modelo</label>
                    <x-input wire:model="modelo"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Número de serie del equipo
                        recibido</label>
                    <x-input wire:model="numeroSerie"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div class="xl:col-span-4">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Problema reportado</label>
                    <x-textarea wire:model="problemaReportado" rows="3"
                        class="w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                    @error('problemaReportado') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="xl:col-span-4">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Detalle descriptivo / diagnóstico
                        inicial</label>
                    <x-textarea wire:model="detalleDescriptivo" rows="2"
                        class="w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>
            </div>
        </x-card>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Seguimiento</h2>
                <p class="text-sm text-[#5F6B7A]">Control del servicio.</p>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Estado</label>
                    <x-select wire:model="estadoServicio" :options="[
                            ['id' => 'RECIBIDO', 'name' => 'Recibido'],
                            ['id' => 'EN_REVISION', 'name' => 'En revisión'],
                            ['id' => 'PENDIENTE_REPUESTO', 'name' => 'Pendiente repuesto'],
                            ['id' => 'REPARADO', 'name' => 'Reparado'],
                            ['id' => 'ENTREGADO', 'name' => 'Entregado'],
                            ['id' => 'CANCELADO', 'name' => 'Cancelado']
                        ]" option-value="id" option-label="name"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Costo estimado</label>
                    <x-input wire:model.live="costoEstimado" type="number" step="0.01" prefix="C$"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Fecha estimada</label>
                    <x-input wire:model="fechaEstimadaEntrega" type="date"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Observación técnica</label>
                    <x-textarea wire:model="observacionTecnica" rows="2"
                        class="w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div class="rounded-xl bg-[#EAF2FB] px-4 py-3 text-[#0B6FE4]">
                    <span class="block text-xs font-semibold">Total estimado</span>
                    <span class="text-lg font-bold">C$ {{ number_format((float) $costoEstimado +
                        collect($productos)->sum('subtotal'), 2) }}</span>
                </div>
            </div>
        </x-card>
    </div>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-[#1A2B42]">Estado del equipo al ingresar</h2>
            <p class="text-sm text-[#5F6B7A]">Marque las condiciones visibles del equipo.</p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.enciende" /> Enciende
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.lleva_cargador" /> Lleva cargador
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.lleva_bateria" /> Lleva batería
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.pantalla_sana" /> Pantalla en buen estado
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.teclado_completo" /> Teclado completo
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.touchpad_funcional" /> Touchpad funcional
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.tiene_golpes_visibles" /> Tiene golpes visibles
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.tiene_humedad" /> Tiene humedad
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.tiene_sello_roto" /> Tiene sello roto
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.lleva_cable_poder" /> Lleva cable de poder
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.lleva_cartucho_toner" /> Lleva cartucho / tóner
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.lleva_mouse_accesorios" /> Lleva mouse / accesorios
            </label>
        </div>

        <div class="mt-3">
            <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Observación del checklist</label>
            <x-textarea wire:model="checklist.observacion_checklist" rows="2"
                class="w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
        </div>
    </x-card>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-3 flex shrink-0 flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-bold text-[#1A2B42]">Repuestos / insumos</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Solo se permiten productos con stock disponible. Las series vendidas o ya usadas no aparecen.
                </p>
            </div>
            <x-button label="Agregar producto" wire:click="abrirProducto"
                class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]" />
        </div>

        <div class="overflow-hidden rounded-xl border border-[#D7E4F3]">
            <x-table :headers="$headers" :rows="$productos"
                class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0]">
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
                <span class="rounded-full bg-[#EAF2FB] px-2 py-1 text-xs font-semibold text-[#0B6FE4]">Guardado</span>
                @else
                <x-button icon="o-trash" wire:click="quitarProducto('{{ $row['tmp_id'] }}')"
                    class="btn-ghost btn-sm text-red-600" />
                @endif
                @endscope
            </x-table>
        </div>
    </x-card>

    <x-modal wire:model="modalProducto" title="Agregar repuesto / insumo" separator>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Producto</label>
                <x-select wire:model.live="productoId" :options="$productosDisponibles" option-value="id"
                    option-label="name" placeholder="Seleccione producto"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                @error('productoId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            @if($productoTieneSeries)
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Serie disponible</label>
                <x-select wire:model="productoSerieId" :options="$seriesDisponibles" option-value="id"
                    option-label="name" placeholder="Seleccione serie"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                @error('productoSerieId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            @else
            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cantidad</label>
                <x-input wire:model="productoCantidad" type="number" step="0.01"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                @error('productoCantidad') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            @endif

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Precio</label>
                <x-input wire:model="productoPrecio" type="number" step="0.01" prefix="C$"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                @error('productoPrecio') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalProducto', false)"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42]" />
            <x-button label="Agregar" wire:click="agregarProducto"
                class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalPendientes" title="Buscar servicios pendientes" separator class="backdrop-blur">
        <div class="space-y-3">
            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Buscar por orden, cliente, equipo, marca
                    o modelo</label>
                <x-input wire:model.live.debounce.400ms="filtroPendientes"
                    placeholder="Ej: ST-20260506, Heyner, Laptop, Asus..."
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
            </div>

            <div class="max-h-[60vh] overflow-auto rounded-xl border border-[#D7E4F3]">
                <table class="w-full min-w-195 text-left text-sm">
                    <thead class="bg-[#2E8BC0] text-white">
                        <tr>
                            <th class="px-3 py-2 font-semibold">Orden</th>
                            <th class="px-3 py-2 font-semibold">Cliente</th>
                            <th class="px-3 py-2 font-semibold">Equipo</th>
                            <th class="px-3 py-2 font-semibold">Estado</th>
                            <th class="px-3 py-2 font-semibold">Total</th>
                            <th class="px-3 py-2 font-semibold text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#D7E4F3] bg-white text-[#1A2B42]">
                        @forelse($serviciosPendientes as $item)
                        <tr class="hover:bg-[#F0F3F7]">
                            <td class="px-3 py-2 font-semibold">{{ $item['numero'] }}</td>
                            <td class="px-3 py-2">{{ $item['cliente'] }}</td>
                            <td class="px-3 py-2">{{ $item['equipo'] }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full bg-[#EAF2FB] px-2 py-1 text-xs font-semibold text-[#0B6FE4]">
                                    {{ str_replace('_', ' ', $item['estado']) }}
                                </span>
                            </td>
                            <td class="px-3 py-2">C$ {{ number_format((float) $item['total'], 2) }}</td>
                            <td class="px-3 py-2 text-center">
                                <x-button label="Cargar" wire:click="cargarPendiente({{ $item['id'] }})"
                                    class="h-8 min-h-8 border-0 bg-[#2E8BC0] px-3 text-xs text-white hover:bg-[#0B6FE4]" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-[#5F6B7A]">
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
                class="border border-[#D7E4F3] bg-white text-[#1A2B42]" />
        </x-slot:actions>
    </x-modal>
</div>
