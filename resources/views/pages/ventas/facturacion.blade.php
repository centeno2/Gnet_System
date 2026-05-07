<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public bool $modalCobro = false;

    public string $tipoVenta = 'CONTADO';

    public string $tipoPagoCordobas = 'EFECTIVO';
    public string $tipoPagoDolares = 'EFECTIVO';
    public string $tipoCambio = '36.50';

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
    public array $copiasRapidas = [];

    public array $detalleVenta = [];

    public string $pagoCordobas = '0';
    public string $pagoDolares = '0';

    public string $abonoCordobas = '0';
    public string $abonoDolares = '0';
    public string $abonoInicial = '0';
    public string $firmaRecibido = '';

    public ?int $ultimaVentaId = null;
    public string $ultimaFacturaNumero = '';
    public string $ultimoTipoVenta = '';

    public string $toastMensaje = '';
    public string $toastTipo = 'success';
    public bool $mostrarToast = false;

    public function mount(): void
    {
        $this->cargarCopiasRapidas();
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

    public function updatedPagoCordobas($value): void
    {
        $this->pagoCordobas = $this->formatearMonto((string) $value);
    }

    public function updatedPagoDolares($value): void
    {
        $this->pagoDolares = $this->formatearDecimal((string) $value);
    }

    public function updatedAbonoCordobas($value): void
    {
        $this->abonoCordobas = $this->formatearMonto((string) $value);
    }

    public function updatedAbonoDolares($value): void
    {
        $this->abonoDolares = $this->formatearDecimal((string) $value);
    }

    public function updatedTipoCambio($value): void
    {
        $this->tipoCambio = $this->formatearDecimal((string) $value);
    }

    public function updatedTipoVenta(): void
    {
        if ($this->tipoVenta === 'CONTADO') {
            $this->abonoCordobas = '0';
            $this->abonoDolares = '0';
            $this->abonoInicial = '0';
            $this->firmaRecibido = '';
            $this->departamentoMunicipio = '';
        }

        if ($this->tipoVenta === 'CREDITO') {
            $this->pagoCordobas = '0';
            $this->pagoDolares = '0';
        }
    }

    protected function buscarClientes(): void
    {
        $busqueda = trim($this->buscarCliente);

        if (strlen($busqueda) < 2) {
            $this->clientesEncontrados = [];
            $this->mostrarClientes = false;
            return;
        }

        $this->clientesEncontrados = DB::table('cliente as c')
            ->leftJoin('persona as p', 'c.Id_Persona', '=', 'p.Id_Persona')
            ->select(
                'c.Id_Cliente',
                'c.Institucion',
                'c.Municipio',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido',
                'p.Telefono'
            )
            ->where('c.Estado', 1)
            ->where(function ($q) use ($busqueda) {
                $q->where('c.Institucion', 'like', "%{$busqueda}%")
                    ->orWhere('p.Primer_Nombre', 'like', "%{$busqueda}%")
                    ->orWhere('p.Segundo_Nombre', 'like', "%{$busqueda}%")
                    ->orWhere('p.Primer_Apellido', 'like', "%{$busqueda}%")
                    ->orWhere('p.Segundo_Apellido', 'like', "%{$busqueda}%")
                    ->orWhere('p.Telefono', 'like', "%{$busqueda}%");
            })
            ->orderBy('p.Primer_Nombre')
            ->limit(8)
            ->get()
            ->map(function ($cliente) {
                $nombrePersona = trim(implode(' ', array_filter([
                    $cliente->Primer_Nombre,
                    $cliente->Segundo_Nombre,
                    $cliente->Primer_Apellido,
                    $cliente->Segundo_Apellido,
                ])));

                return [
                    'id' => (int) $cliente->Id_Cliente,
                    'nombre' => $cliente->Institucion ?: $nombrePersona ?: 'Cliente',
                    'telefono' => $cliente->Telefono ?: 'Sin teléfono',
                    'municipio' => $cliente->Municipio ?: '',
                ];
            })
            ->toArray();

        $this->mostrarClientes = count($this->clientesEncontrados) > 0;
    }

    public function seleccionarCliente(int $idCliente): void
    {
        $cliente = DB::table('cliente as c')
            ->leftJoin('persona as p', 'c.Id_Persona', '=', 'p.Id_Persona')
            ->select(
                'c.Id_Cliente',
                'c.Institucion',
                'c.Municipio',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido'
            )
            ->where('c.Id_Cliente', $idCliente)
            ->first();

        if (! $cliente) {
            $this->mostrarToast('No se encontró el cliente seleccionado.', 'error');
            return;
        }

        $nombrePersona = trim(implode(' ', array_filter([
            $cliente->Primer_Nombre,
            $cliente->Segundo_Nombre,
            $cliente->Primer_Apellido,
            $cliente->Segundo_Apellido,
        ])));

        $this->clienteId = (int) $cliente->Id_Cliente;
        $this->clienteNombre = $cliente->Institucion ?: $nombrePersona ?: 'Cliente';
        $this->buscarCliente = $this->clienteNombre;

        if ($this->tipoVenta === 'CREDITO') {
            $this->departamentoMunicipio = $cliente->Municipio ?? '';
        }

        $this->clientesEncontrados = [];
        $this->mostrarClientes = false;
    }

    public function usarConsumidorFinal(): void
    {
        $this->clienteId = null;
        $this->buscarCliente = '';
        $this->clienteNombre = 'Consumidor final';
        $this->departamentoMunicipio = '';
        $this->clientesEncontrados = [];
        $this->mostrarClientes = false;
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

        $productos = DB::table('producto as p')
            ->leftJoin('marca as m', 'p.Id_Marca', '=', 'm.Id_Marca')
            ->select(
                'p.Id_Producto',
                'p.Nombre_Producto',
                'p.Modelo',
                'p.Stock_Actual',
                'p.Precio_Venta',
                'm.Nombre_Marca'
            )
            ->where('p.Estado', 1)
            ->where('p.Stock_Actual', '>', 0)
            ->where(function ($q) use ($busqueda, $seriesUsadas) {
                $q->where('p.Nombre_Producto', 'like', "%{$busqueda}%")
                    ->orWhere('p.Modelo', 'like', "%{$busqueda}%")
                    ->orWhere('m.Nombre_Marca', 'like', "%{$busqueda}%")
                    ->orWhereExists(function ($sub) use ($busqueda, $seriesUsadas) {
                        $sub->select(DB::raw(1))
                            ->from('producto_serie as ps')
                            ->whereColumn('ps.Id_Producto', 'p.Id_Producto')
                            ->where('ps.Estado', 'DISPONIBLE')
                            ->where('ps.Numero_Serie', 'like', "%{$busqueda}%")
                            ->when(count($seriesUsadas) > 0, function ($query) use ($seriesUsadas) {
                                $query->whereNotIn('ps.id_producto_serie', $seriesUsadas);
                            });
                    });
            })
            ->orderBy('p.Nombre_Producto')
            ->limit(8)
            ->get()
            ->map(function ($producto) use ($busqueda, $seriesUsadas) {
                $serie = DB::table('producto_serie')
                    ->where('Id_Producto', $producto->Id_Producto)
                    ->where('Estado', 'DISPONIBLE')
                    ->where('Numero_Serie', 'like', "%{$busqueda}%")
                    ->when(count($seriesUsadas) > 0, function ($query) use ($seriesUsadas) {
                        $query->whereNotIn('id_producto_serie', $seriesUsadas);
                    })
                    ->orderBy('Numero_Serie')
                    ->first();

                $titulo = $this->nombreProductoLimpio(
                    $producto->Nombre_Marca,
                    $producto->Nombre_Producto
                );

                return [
                    'tipo' => 'PRODUCTO',
                    'id' => (int) $producto->Id_Producto,
                    'serie_id' => $serie ? (int) $serie->id_producto_serie : null,
                    'titulo' => $titulo,
                    'subtitulo' => trim(($producto->Modelo ?: 'Sin modelo') . ' · Stock: ' . $producto->Stock_Actual . ($serie ? ' · Serie: ' . $serie->Numero_Serie : '')),
                    'precio' => (float) $producto->Precio_Venta,
                    'precio_texto' => 'C$ ' . number_format((float) $producto->Precio_Venta, 0, '.', ','),
                ];
            })
            ->toArray();

        $copias = DB::table('tarifa_copia as tc')
            ->join('servicio as s', 'tc.Id_Servicio', '=', 's.Id_Servicio')
            ->select(
                'tc.Id_Tarifa_Copia',
                'tc.Nombre_Tarifa',
                'tc.Tipo_Color',
                'tc.Formato',
                'tc.Lados',
                'tc.Precio_Unitario'
            )
            ->where('tc.Estado', 1)
            ->where('s.Estado', 1)
            ->where('s.Tipo_Servicio', 'COPIA')
            ->where(function ($q) use ($busqueda) {
                $q->where('tc.Nombre_Tarifa', 'like', "%{$busqueda}%")
                    ->orWhere('tc.Tipo_Color', 'like', "%{$busqueda}%")
                    ->orWhere('tc.Formato', 'like', "%{$busqueda}%")
                    ->orWhere('tc.Lados', 'like', "%{$busqueda}%");
            })
            ->orderBy('tc.Nombre_Tarifa')
            ->limit(8)
            ->get()
            ->map(function ($tarifa) {
                return [
                    'tipo' => 'COPIA',
                    'id' => (int) $tarifa->Id_Tarifa_Copia,
                    'serie_id' => null,
                    'titulo' => $tarifa->Nombre_Tarifa,
                    'subtitulo' => $tarifa->Tipo_Color . ' · ' . $tarifa->Formato . ' · ' . $tarifa->Lados,
                    'precio' => (float) $tarifa->Precio_Unitario,
                    'precio_texto' => 'C$ ' . number_format((float) $tarifa->Precio_Unitario, 0, '.', ','),
                ];
            })
            ->toArray();

        $this->resultadosItems = array_values(array_merge($productos, $copias));
        $this->mostrarItems = count($this->resultadosItems) > 0;
    }

    protected function cargarCopiasRapidas(): void
    {
        $this->copiasRapidas = DB::table('tarifa_copia as tc')
            ->join('servicio as s', 'tc.Id_Servicio', '=', 's.Id_Servicio')
            ->select(
                'tc.Id_Tarifa_Copia',
                'tc.Nombre_Tarifa',
                'tc.Precio_Unitario'
            )
            ->where('tc.Estado', 1)
            ->where('s.Estado', 1)
            ->where('s.Tipo_Servicio', 'COPIA')
            ->orderBy('tc.Nombre_Tarifa')
            ->get()
            ->map(fn ($tarifa) => [
                'id' => (int) $tarifa->Id_Tarifa_Copia,
                'name' => $tarifa->Nombre_Tarifa . ' · C$ ' . number_format((float) $tarifa->Precio_Unitario, 0, '.', ','),
            ])
            ->toArray();
    }

    public function seleccionarItem(string $tipo, int $id, ?int $serieId = null): void
    {
        $this->descuentoItem = '0';

        if ($tipo === 'PRODUCTO') {
            $producto = DB::table('producto as p')
                ->leftJoin('marca as m', 'p.Id_Marca', '=', 'm.Id_Marca')
                ->select(
                    'p.Id_Producto',
                    'p.Nombre_Producto',
                    'p.Modelo',
                    'p.Stock_Actual',
                    'p.Precio_Venta',
                    'm.Nombre_Marca'
                )
                ->where('p.Id_Producto', $id)
                ->where('p.Estado', 1)
                ->first();

            if (! $producto) {
                $this->mostrarToast('No se encontró el producto seleccionado.', 'error');
                return;
            }

            $seriesUsadas = $this->seriesUsadasEnDetalle((int) $producto->Id_Producto);

            $seriesTotalesDisponibles = DB::table('producto_serie')
                ->where('Id_Producto', $producto->Id_Producto)
                ->where('Estado', 'DISPONIBLE')
                ->count();

            $this->productoUsaSerie = $seriesTotalesDisponibles > 0;

            $this->seriesDisponibles = DB::table('producto_serie')
                ->where('Id_Producto', $producto->Id_Producto)
                ->where('Estado', 'DISPONIBLE')
                ->when(count($seriesUsadas) > 0, function ($query) use ($seriesUsadas) {
                    $query->whereNotIn('id_producto_serie', $seriesUsadas);
                })
                ->orderBy('Numero_Serie')
                ->limit(50)
                ->get()
                ->map(fn ($serie) => [
                    'id' => (int) $serie->id_producto_serie,
                    'name' => $serie->Numero_Serie,
                ])
                ->toArray();

            $descripcion = $this->nombreProductoLimpio(
                $producto->Nombre_Marca,
                $producto->Nombre_Producto,
                $producto->Modelo
            );

            $serieIdValida = null;

            if ($serieId) {
                $serieIdValida = collect($this->seriesDisponibles)
                    ->firstWhere('id', (int) $serieId)['id'] ?? null;
            }

            $this->itemSeleccionado = [
                'tipo' => 'PRODUCTO',
                'id_producto' => (int) $producto->Id_Producto,
                'id_tarifa_copia' => null,
                'id_servicio' => null,
                'descripcion' => $descripcion,
                'formato' => null,
                'lados' => null,
            ];

            $this->descripcionSeleccionada = $descripcion;
            $this->tipoItemSeleccionado = 'PRODUCTO';
            $this->stockDisponible = (int) $producto->Stock_Actual;
            $this->precioItem = number_format((float) $producto->Precio_Venta, 0, '.', ',');
            $this->cantidadItem = '1';
            $this->serieProductoId = $serieIdValida;
        }

        if ($tipo === 'COPIA') {
            $tarifa = DB::table('tarifa_copia')
                ->where('Id_Tarifa_Copia', $id)
                ->where('Estado', 1)
                ->first();

            if (! $tarifa) {
                $this->mostrarToast('No se encontró la copia seleccionada.', 'error');
                return;
            }

            $this->itemSeleccionado = [
                'tipo' => 'COPIA',
                'id_producto' => null,
                'id_tarifa_copia' => (int) $tarifa->Id_Tarifa_Copia,
                'id_servicio' => (int) $tarifa->Id_Servicio,
                'descripcion' => $tarifa->Nombre_Tarifa,
                'formato' => $tarifa->Formato,
                'lados' => $tarifa->Lados,
            ];

            $this->descripcionSeleccionada = $tarifa->Nombre_Tarifa;
            $this->tipoItemSeleccionado = 'COPIA';
            $this->stockDisponible = 0;
            $this->seriesDisponibles = [];
            $this->serieProductoId = null;
            $this->productoUsaSerie = false;
            $this->precioItem = number_format((float) $tarifa->Precio_Unitario, 0, '.', ',');
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

        $this->descuentoItem = '0';
        $this->seleccionarItem('COPIA', (int) $this->copiaRapidaId);
        $this->cantidadItem = (string) max(1, (int) $this->cantidadCopiaRapida);
        $this->agregarItem();

        $this->copiaRapidaId = null;
        $this->cantidadCopiaRapida = '1';
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
            $this->mostrarToast('El precio debe ser mayor a cero.', 'error');
            return;
        }

        if ($this->itemSeleccionado['tipo'] === 'PRODUCTO') {
            if ($this->productoUsaSerie && ! $this->serieProductoId) {
                $this->mostrarToast('Seleccione una serie disponible para este producto.', 'error');
                return;
            }

            if ($this->serieProductoId) {
                $cantidad = 1;
            }

            $stockUsadoEnDetalle = collect($this->detalleVenta)
                ->where('tipo', 'PRODUCTO')
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

        $serieTexto = null;

        if ($this->serieProductoId) {
            $serieTexto = DB::table('producto_serie')
                ->where('id_producto_serie', $this->serieProductoId)
                ->value('Numero_Serie');
        }

        $subtotalBruto = $cantidad * $precio;
        $descuentoItem = min($this->limpiarMonto($this->descuentoItem), $subtotalBruto);
        $subtotal = $subtotalBruto - $descuentoItem;

        $this->detalleVenta[] = [
            'uid' => uniqid('det_', true),
            'tipo' => $this->itemSeleccionado['tipo'],
            'codigo' => $this->itemSeleccionado['tipo'] === 'PRODUCTO'
                ? 'P-' . $this->itemSeleccionado['id_producto']
                : 'C-' . $this->itemSeleccionado['id_tarifa_copia'],
            'descripcion' => $this->itemSeleccionado['descripcion'] . ($serieTexto ? ' · Serie: ' . $serieTexto : ''),
            'id_producto' => $this->itemSeleccionado['id_producto'],
            'id_producto_serie' => $this->serieProductoId ? (int) $this->serieProductoId : null,
            'id_servicio' => $this->itemSeleccionado['id_servicio'],
            'id_tarifa_copia' => $this->itemSeleccionado['id_tarifa_copia'],
            'formato' => $this->itemSeleccionado['formato'],
            'lados' => $this->itemSeleccionado['lados'],
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal_bruto_valor' => $subtotalBruto,
            'descuento_valor' => $descuentoItem,
            'subtotal_valor' => $subtotal,
        ];

        $this->limpiarItemSeleccionado();
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
        if (count($this->detalleVenta) === 0) {
            $this->mostrarToast('Agregue al menos un item a la venta.', 'error');
            return;
        }

        if ($this->tipoVenta === 'CREDITO' && ! $this->clienteId) {
            $this->mostrarToast('Para crédito debe seleccionar un cliente registrado.', 'error');
            return;
        }

        if ($this->tipoVenta === 'CONTADO') {
            $this->pagoCordobas = '0';
            $this->pagoDolares = '0';
        }

        if ($this->tipoVenta === 'CREDITO') {
            $this->abonoCordobas = '0';
            $this->abonoDolares = '0';
            $this->abonoInicial = '0';
        }

        $this->modalCobro = true;
    }

    public function cerrarModalCobro(): void
    {
        $this->modalCobro = false;
    }

    public function guardarVenta(): void
    {
        $this->resetErrorBag();

        $total = $this->totalVenta();
        $descuento = $this->descuentoVenta();

        $pagoCordobas = $this->limpiarMonto($this->pagoCordobas);
        $pagoDolares = $this->limpiarDecimal($this->pagoDolares);
        $equivalenteDolares = $pagoDolares * $this->tasaCambio();
        $totalPagado = $pagoCordobas + $equivalenteDolares;

        $abonoCordobas = $this->limpiarMonto($this->abonoCordobas);
        $abonoDolares = $this->limpiarDecimal($this->abonoDolares);
        $equivalenteAbonoDolares = $abonoDolares * $this->tasaCambio();
        $abonoInicial = $abonoCordobas + $equivalenteAbonoDolares;

        if ($this->tipoVenta === 'CONTADO' && $totalPagado < $total) {
            $this->mostrarToast('El monto recibido no puede ser menor que el total.', 'error');
            return;
        }

        if ($this->tipoVenta === 'CREDITO' && $abonoInicial > $total) {
            $this->mostrarToast('El abono inicial no puede ser mayor al total.', 'error');
            return;
        }

        try {
            $resultado = DB::transaction(function () use (
                $total,
                $descuento,
                $pagoCordobas,
                $pagoDolares,
                $equivalenteDolares,
                $abonoCordobas,
                $abonoDolares,
                $equivalenteAbonoDolares,
                $abonoInicial
            ) {
                $idUsuario = $this->obtenerUsuarioId();
                $numeroFactura = $this->generarNumeroFactura();

                $idVenta = DB::table('venta')->insertGetId([
                    'Numero_Factura' => $numeroFactura,
                    'Fecha_venta' => now(),
                    'Id_Cliente' => $this->clienteId,
                    'Id_Usuario' => $idUsuario,
                    'Tipo_Venta' => $this->tipoVenta,
                    'Estado' => 1,
                    'Descuento' => $descuento,
                    'Total' => $total,
                ]);

                foreach ($this->detalleVenta as $item) {
                    if ($item['tipo'] === 'PRODUCTO') {
                        $producto = DB::table('producto')
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
                            $serie = DB::table('producto_serie')
                                ->where('id_producto_serie', $item['id_producto_serie'])
                                ->where('Estado', 'DISPONIBLE')
                                ->lockForUpdate()
                                ->first();

                            if (! $serie) {
                                throw ValidationException::withMessages([
                                    'serie' => 'La serie ya no está disponible: ' . $item['descripcion'],
                                ]);
                            }

                            DB::table('producto_serie')
                                ->where('id_producto_serie', $item['id_producto_serie'])
                                ->update(['Estado' => 'VENDIDO']);
                        }

                        DB::table('producto')
                            ->where('Id_Producto', $item['id_producto'])
                            ->update([
                                'Stock_Actual' => ((int) $producto->Stock_Actual) - $cantidad,
                            ]);
                    }

                    DB::table('detalle_venta')->insert([
                        'Id_Venta' => $idVenta,
                        'Tipo_Detalle' => $item['tipo'],
                        'Id_Producto' => $item['id_producto'],
                        'Id_Producto_serie' => $item['id_producto_serie'],
                        'Id_Servicio' => $item['id_servicio'],
                        'Id_Tarifa_Copia' => $item['id_tarifa_copia'],
                        'Nombre_Formato' => $item['tipo'] === 'COPIA' ? $item['descripcion'] : null,
                        'Formato_Copia' => $item['tipo'] === 'COPIA' ? $this->formatoCopiaValor($item['formato']) : null,
                        'Lados_Copia' => $item['tipo'] === 'COPIA' ? $this->ladosCopiaValor($item['lados']) : null,
                        'Cantidad' => $item['cantidad'],
                        'Precio_Unitario' => $item['precio_unitario'],
                        'Subtotal' => $item['subtotal_valor'],
                        'Descuento' => $item['descuento_valor'],
                        'Observacion' => null,
                    ]);
                }

                if ($this->tipoVenta === 'CONTADO') {
                    if ($pagoCordobas > 0) {
                        DB::table('pago_venta')->insert([
                            'Id_Venta' => $idVenta,
                            'Fecha_Pago' => now(),
                            'Moneda' => 0,
                            'Tipo_Pago' => $this->tipoPagoCordobas,
                            'Monto' => $pagoCordobas,
                            'Tipo_Cambio' => 1,
                            'Monto_Equivalente_Cordobas' => $pagoCordobas,
                        ]);
                    }

                    if ($pagoDolares > 0) {
                        DB::table('pago_venta')->insert([
                            'Id_Venta' => $idVenta,
                            'Fecha_Pago' => now(),
                            'Moneda' => 1,
                            'Tipo_Pago' => $this->tipoPagoDolares,
                            'Monto' => $pagoDolares,
                            'Tipo_Cambio' => $this->tasaCambio(),
                            'Monto_Equivalente_Cordobas' => $equivalenteDolares,
                        ]);
                    }
                }

                if ($this->tipoVenta === 'CREDITO') {
                    $saldo = max($total - $abonoInicial, 0);

                    $estadoCredito = $saldo <= 0
                        ? 'CANCELADO'
                        : ($abonoInicial > 0 ? 'PARCIAL' : 'PENDIENTE');

                    $idCredito = DB::table('credito')->insertGetId([
                        'Id_Venta' => $idVenta,
                        'Fecha_Credito' => now()->toDateString(),
                        'Abono_Inicial' => $abonoInicial,
                        'Saldo_Actual' => $saldo,
                        'Firma_Recibido' => $this->firmaRecibido !== '' ? trim($this->firmaRecibido) : null,
                        'Estado' => $estadoCredito,
                    ]);

                    if ($abonoInicial > 0) {
                        if ($abonoCordobas > 0) {
                            DB::table('abono_credito')->insert([
                                'Id_Credito' => $idCredito,
                                'Fecha_Abono' => now(),
                                'Moneda' => 'NIO',
                                'Monto' => $abonoCordobas,
                                'Tipo_Cambio' => 1,
                                'Monto_Equivalente_Cordobas' => $abonoCordobas,
                                'Numero_Transferencia' => null,
                                'Observacion' => 'Abono inicial en córdobas desde facturación.',
                            ]);

                            DB::table('pago_venta')->insert([
                                'Id_Venta' => $idVenta,
                                'Fecha_Pago' => now(),
                                'Moneda' => 0,
                                'Tipo_Pago' => $this->tipoPagoCordobas,
                                'Monto' => $abonoCordobas,
                                'Tipo_Cambio' => 1,
                                'Monto_Equivalente_Cordobas' => $abonoCordobas,
                            ]);
                        }

                        if ($abonoDolares > 0) {
                            DB::table('abono_credito')->insert([
                                'Id_Credito' => $idCredito,
                                'Fecha_Abono' => now(),
                                'Moneda' => 'USD',
                                'Monto' => $abonoDolares,
                                'Tipo_Cambio' => $this->tasaCambio(),
                                'Monto_Equivalente_Cordobas' => $equivalenteAbonoDolares,
                                'Numero_Transferencia' => null,
                                'Observacion' => 'Abono inicial en dólares desde facturación.',
                            ]);

                            DB::table('pago_venta')->insert([
                                'Id_Venta' => $idVenta,
                                'Fecha_Pago' => now(),
                                'Moneda' => 1,
                                'Tipo_Pago' => $this->tipoPagoDolares,
                                'Monto' => $abonoDolares,
                                'Tipo_Cambio' => $this->tasaCambio(),
                                'Monto_Equivalente_Cordobas' => $equivalenteAbonoDolares,
                            ]);
                        }
                    }
                }

                return [
                    'id_venta' => $idVenta,
                    'numero_factura' => $numeroFactura,
                ];
            });

            if ($this->tipoVenta === 'CREDITO') {
                session(['venta_municipio_' . $resultado['id_venta'] => $this->departamentoMunicipio]);
            }

            $this->ultimaVentaId = $resultado['id_venta'];
            $this->ultimaFacturaNumero = $resultado['numero_factura'];
            $this->ultimoTipoVenta = $this->tipoVenta;

            $this->limpiarVentaActual();
            $this->cerrarModalCobro();

            $this->mostrarToast('Venta guardada correctamente. Factura: ' . $resultado['numero_factura']);
        } catch (ValidationException $e) {
            $mensaje = collect($e->errors())->flatten()->first() ?: 'No se pudo guardar la venta.';
            $this->mostrarToast($mensaje, 'error');
        } catch (\Throwable $e) {
            $this->mostrarToast('Error al guardar la venta: ' . $e->getMessage(), 'error');
        }
    }

    protected function limpiarVentaActual(): void
    {
        $this->tipoVenta = 'CONTADO';
        $this->tipoPagoCordobas = 'EFECTIVO';
        $this->tipoPagoDolares = 'EFECTIVO';

        $this->usarConsumidorFinal();

        $this->detalleVenta = [];
        $this->pagoCordobas = '0';
        $this->pagoDolares = '0';
        $this->abonoCordobas = '0';
        $this->abonoDolares = '0';
        $this->abonoInicial = '0';
        $this->firmaRecibido = '';

        $this->limpiarItemSeleccionado();

        $this->copiaRapidaId = null;
        $this->cantidadCopiaRapida = '1';
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

    public function abonoInicialEquivalente(): float
    {
        return $this->limpiarMonto($this->abonoCordobas)
            + ($this->limpiarDecimal($this->abonoDolares) * $this->tasaCambio());
    }

    public function cambioVenta(): float
    {
        if ($this->tipoVenta !== 'CONTADO') {
            return 0;
        }

        return $this->totalPagadoCordobas() - $this->totalVenta();
    }

    public function saldoCredito(): float
    {
        return max($this->totalVenta() - $this->abonoInicialEquivalente(), 0);
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

    protected function tasaCambio(): float
    {
        $tasa = $this->limpiarDecimal($this->tipoCambio);

        return $tasa > 0 ? $tasa : 1;
    }

    protected function seriesUsadasEnDetalle(?int $idProducto = null): array
    {
        return collect($this->detalleVenta)
            ->filter(fn ($item) => $item['tipo'] === 'PRODUCTO')
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

        $idUsuario = DB::table('usuario')->value('Id_Usuario');

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
        } while (DB::table('venta')->where('Numero_Factura', $numero)->exists());

        return $numero;
    }

    protected function mostrarToast(string $mensaje, string $tipo = 'success'): void
    {
        $this->toastMensaje = $mensaje;
        $this->toastTipo = $tipo;
        $this->mostrarToast = true;
    }

    public function cerrarToast(): void
    {
        $this->mostrarToast = false;
        $this->toastMensaje = '';
        $this->toastTipo = 'success';
    }
};
?>

<div class="min-h-[calc(100vh-3rem)] w-full overflow-x-hidden bg-[#F0F3F7] px-3 py-3 md:px-5 md:py-4">
    <div class="mx-auto flex w-full max-w-330 flex-col gap-4">

        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold leading-tight text-[#1A2B42] md:text-3xl">Facturación</h1>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($ultimaVentaId && $ultimoTipoVenta === 'CONTADO' &&
                \Illuminate\Support\Facades\Route::has('ventas.factura'))
                <a href="{{ route('ventas.factura', $ultimaVentaId) }}" target="_blank"
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-[#0E48A1] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0B6FE4]">
                    Imprimir última factura
                </a>
                @endif

                <button type="button" wire:click="$set('tipoVenta', 'CONTADO')"
                    class="{{ $tipoVenta === 'CONTADO' ? 'bg-[#0B6FE4] text-white shadow-sm' : 'bg-white text-[#1A2B42]' }} inline-flex h-10 items-center justify-center rounded-xl border border-[#D7E4F3] px-5 text-sm font-semibold transition">
                    Contado
                </button>

                <button type="button" wire:click="$set('tipoVenta', 'CREDITO')"
                    class="{{ $tipoVenta === 'CREDITO' ? 'bg-[#0B6FE4] text-white shadow-sm' : 'bg-white text-[#1A2B42]' }} inline-flex h-10 items-center justify-center rounded-xl border border-[#D7E4F3] px-5 text-sm font-semibold transition">
                    Crédito
                </button>
            </div>
        </div>

        @if ($mostrarToast)
        <div class="fixed right-5 top-5 z-999 w-full max-w-sm">
            <div
                class="{{ $toastTipo === 'success' ? 'border-[#B7D6F2] bg-[#EAF4FD] text-[#1A2B42]' : 'border-red-200 bg-red-50 text-red-700' }} rounded-2xl border px-4 py-4 shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium">{{ $toastMensaje }}</p>
                    <button type="button" wire:click="cerrarToast"
                        class="text-lg leading-none text-[#5F6B7A] hover:text-[#1A2B42]">
                        ×
                    </button>
                </div>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_270px]">

            <div class="min-w-0 space-y-4">

                <x-card class="rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                        <div
                            class="relative min-w-0 {{ $tipoVenta === 'CREDITO' ? 'lg:col-span-5' : 'lg:col-span-7' }}">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Cliente</label>

                            <div class="flex gap-2">
                                <x-input wire:model.live.debounce.250ms="buscarCliente" type="text" autocomplete="off"
                                    placeholder="Buscar cliente o institución"
                                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

                                <button type="button" wire:click="usarConsumidorFinal"
                                    class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] hover:bg-[#F8FAFC]">
                                    Final
                                </button>
                            </div>

                            @if ($mostrarClientes)
                            <div
                                class="absolute left-0 right-0 top-full z-50 mt-1 max-h-64 overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                                @foreach ($clientesEncontrados as $cliente)
                                <button type="button" wire:key="cliente-{{ $cliente['id'] }}"
                                    wire:click="seleccionarCliente({{ $cliente['id'] }})"
                                    class="flex w-full flex-col border-b border-[#EAF2FB] px-4 py-3 text-left hover:bg-[#EAF4FD] last:border-b-0">
                                    <span class="truncate text-sm font-semibold text-[#1A2B42]">{{ $cliente['nombre']
                                        }}</span>
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
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Cliente
                                seleccionado</label>
                            <div
                                class="flex h-11 items-center rounded-xl bg-[#EAF2FB] px-3 text-sm font-semibold text-[#1A2B42]">
                                <span class="truncate">{{ $clienteNombre }}</span>
                            </div>
                        </div>

                        @if ($tipoVenta === 'CREDITO')
                        <div class="min-w-0 lg:col-span-4">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Departamento /
                                municipio</label>
                            <x-input wire:model="departamentoMunicipio" type="text" placeholder="Ingrese municipio"
                                class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        </div>
                        @endif
                    </div>
                </x-card>

                <x-card class="rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
                    <div class="mb-4">
                        <h2 class="text-lg font-bold text-[#1A2B42]">Agregar a la venta</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
                        <div class="relative min-w-0 xl:col-span-4">
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

                        <div class="min-w-0 xl:col-span-1">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Precio</label>
                            <x-input wire:model.live.debounce.250ms="precioItem" type="text" inputmode="numeric"
                                class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        </div>

                        <div class="min-w-0 xl:col-span-1">
                            <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Desc.</label>
                            <x-input wire:model.live.debounce.250ms="descuentoItem" type="text" inputmode="numeric"
                                placeholder="0"
                                class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        </div>

                        <div class="xl:col-span-1">
                            <label class="mb-1.5 block text-sm font-semibold text-transparent">Acción</label>
                            <x-button label="+" wire:click="agregarItem"
                                class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#2E8BC0] text-lg font-bold text-white shadow-sm hover:bg-[#0B6FE4]" />
                        </div>
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

                    <div class="mt-4 rounded-2xl border border-[#E3EDF8] bg-[#F8FBFF] p-3">
                        <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
                            <div class="min-w-0 xl:col-span-8">
                                <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Copia rápida</label>
                                <x-select wire:model.live="copiaRapidaId" :options="$copiasRapidas" option-value="id"
                                    option-label="name" placeholder="Seleccione una tarifa de copia"
                                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-white text-sm text-[#1A2B42]" />
                            </div>

                            <div class="min-w-0 xl:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold text-[#1A2B42]">Cantidad</label>
                                <x-input wire:model="cantidadCopiaRapida" type="number" min="1"
                                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-white text-center text-sm text-[#1A2B42]" />
                            </div>

                            <div class="xl:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold text-transparent">Acción</label>
                                <x-button label="Agregar copia" wire:click="agregarCopiaRapida"
                                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#0E48A1] text-sm font-semibold text-white shadow-sm hover:bg-[#0B6FE4]" />
                            </div>
                        </div>
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
                            <table class="min-w-205 w-full border-separate border-spacing-0 text-[13px] text-[#1A2B42]">
                                <thead>
                                    <tr>
                                        <th
                                            class="rounded-tl-xl bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">
                                            Código</th>
                                        <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">
                                            Descripción</th>
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
                                        <td colspan="8" class="px-4 py-10 text-center text-sm text-[#7B8794]">
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

                        <div class="grid grid-cols-1 gap-2 pt-1">
                            <x-button label="Cobrar" wire:click="abrirModalCobro"
                                class="h-10 min-h-10 rounded-xl border-0 bg-[#2E8BC0] px-3 text-sm font-semibold text-white shadow-sm hover:bg-[#0B6FE4]" />

                            <x-button label="Cancelar" wire:click="cancelarVenta"
                                class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white px-3 text-sm font-semibold text-[#1A2B42] hover:bg-[#F5F9FC]" />
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    <x-modal wire:model="modalCobro" class="backdrop-blur-sm"
        box-class="w-full max-w-2xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">

        <div class="mb-5">
            <h3 class="text-2xl font-bold text-[#1A2B42]">Finalizar venta</h3>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Confirme el cobro antes de guardar.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo cambio</label>
                <x-input wire:model.live.debounce.250ms="tipoCambio" type="text" inputmode="decimal"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
            </div>

            @if ($tipoVenta === 'CONTADO')
            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo pago C$</label>
                <select wire:model="tipoPagoCordobas"
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

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo pago US$</label>
                <select wire:model="tipoPagoDolares"
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

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cambio</label>
                <div
                    class="{{ $this->cambioVenta() < 0 ? 'bg-red-50 text-red-700' : 'bg-[#EAF2FB] text-[#1A2B42]' }} flex h-11 items-center rounded-xl px-3 text-sm font-bold">
                    C$ {{ number_format($this->cambioVenta(), 0, '.', ',') }}
                </div>
            </div>

            <div class="md:col-span-2 rounded-xl bg-[#F8FBFF] px-4 py-3 text-sm text-[#1A2B42]">
                Recibido equivalente:
                <strong>C$ {{ number_format($this->totalPagadoCordobas(), 0, '.', ',') }}</strong>
            </div>
            @endif

            @if ($tipoVenta === 'CREDITO')
            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo pago C$</label>
                <select wire:model="tipoPagoCordobas"
                    class="h-11 w-full rounded-xl border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                    <option value="EFECTIVO">Efectivo</option>
                    <option value="TRANSFERENCIA">Transferencia</option>
                    <option value="TARJETA">Tarjeta</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Abono C$</label>
                <x-input wire:model.live.debounce.250ms="abonoCordobas" type="text" inputmode="numeric"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo pago US$</label>
                <select wire:model="tipoPagoDolares"
                    class="h-11 w-full rounded-xl border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                    <option value="EFECTIVO">Efectivo</option>
                    <option value="TRANSFERENCIA">Transferencia</option>
                    <option value="TARJETA">Tarjeta</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Abono US$</label>
                <x-input wire:model.live.debounce.250ms="abonoDolares" type="text" inputmode="decimal"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Saldo crédito</label>
                <div class="flex h-11 items-center rounded-xl bg-[#EAF2FB] px-3 text-sm font-semibold text-[#1A2B42]">
                    C$ {{ number_format($this->saldoCredito(), 0, '.', ',') }}
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Firma / recibido por</label>
                <x-input wire:model="firmaRecibido" type="text" placeholder="Opcional"
                    class="h-11 min-h-11 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42]" />
            </div>
            @endif

            <div class="md:col-span-2">
                <div class="rounded-xl bg-[#F8FBFF] px-4 py-3 text-sm text-[#1A2B42]">
                    Cliente: <strong>{{ $clienteNombre }}</strong><br>

                    @if ($tipoVenta === 'CREDITO')
                    Municipio: <strong>{{ $departamentoMunicipio ?: 'No especificado' }}</strong><br>
                    @endif

                    Subtotal: <strong>C$ {{ number_format($this->subtotalVenta(), 0, '.', ',') }}</strong><br>
                    Descuentos: <strong>C$ {{ number_format($this->descuentoVenta(), 0, '.', ',') }}</strong><br>
                    Total a guardar: <strong>C$ {{ number_format($this->totalVenta(), 0, '.', ',') }}</strong>
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Volver" type="button" wire:click="cerrarModalCobro"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />

            <x-button label="Guardar venta" type="button" wire:click="guardarVenta"
                class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]" />
        </x-slot:actions>
    </x-modal>
</div>
