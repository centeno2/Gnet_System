<?php

use App\Models\CategoriaProducto;
use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Proveedor;
use App\Models\Usuario;
use Carbon\Carbon;
use Livewire\Component;

new class extends Component
{
    public string $buscarProveedor = '';
    public string $proveedorNombre = '';
    public $idProveedor = '';

    public string $numeroCompra = '';
    public string $fechaCompra = '';
    public string $tipoCompra = 'CONTADO';
    public string $observacion = '';
    public string $retencion = '0';
    public string $iva = '0';

    public string $modoProducto = 'existente';

    public string $buscarProducto = '';
    public array $proveedoresEncontrados = [];
    public array $productosEncontrados = [];

    public $idProducto = '';
    public string $productoNombre = '';
    public string $productoCategoria = '';
    public string $productoMarca = '';
    public string $productoModelo = '';
    public float $precioVentaActual = 0;
    public bool $precioVentaEditable = false;

    public string $nuevoNombreProducto = '';
    public string $nuevoModelo = '';
    public string $nuevoCategoriaSeleccionada = '';
    public string $nuevoMarcaSeleccionada = '';
    public string $nuevoStockMinimo = '0';
    public string $nuevoGarantiaNuevo = '';
    public string $nuevoGarantiaUsado = '';
    public string $nuevoEstado = '1';

    public bool $mostrarNuevaCategoria = false;
    public bool $mostrarNuevaMarca = false;
    public string $nombreCategoriaNueva = '';
    public string $nombreMarcaNueva = '';

    public string $cantidad = '1';
    public string $precioCompra = '0';
    public string $precioVenta = '0';
    public string $seriesTexto = '';

    public array $categorias = [];
    public array $marcas = [];
    public array $categoriasTemporales = [];
    public array $marcasTemporales = [];
    public array $detalles = [];

    public string $toastMensaje = '';
    public string $toastTipo = 'success';
    public bool $mostrarToast = false;

    public array $tiposCompra = [
        ['id' => 'CONTADO', 'nombre' => 'Contado'],
        ['id' => 'CREDITO', 'nombre' => 'Crédito'],
        ['id' => 'TRANSFERENCIA', 'nombre' => 'Transferencia'],
    ];

    public function mount(): void
    {
        $this->fechaCompra = Carbon::today()->toDateString();
        $this->cargarCatalogos();
    }

    public function updatedBuscarProveedor(): void
    {
        if ($this->idProveedor !== '') {
            return;
        }

        $this->proveedorNombre = '';
        $this->buscarProveedores();
    }

    public function updatedBuscarProducto(): void
    {
        if ($this->idProducto !== '') {
            return;
        }

        $this->limpiarProductoSeleccionado(false);
        $this->buscarProductos();
    }

    public function updatedPrecioCompra(): void
    {
        $precioCompra = (float) $this->precioCompra;

        if ($this->modoProducto === 'nuevo') {
            $this->precioVentaEditable = true;
            return;
        }

        if ($this->modoProducto === 'existente' && $this->idProducto !== '') {
            $this->precioVentaEditable = $precioCompra > $this->precioVentaActual;

            if (! $this->precioVentaEditable) {
                $this->precioVenta = (string) $this->precioVentaActual;
            }
        }
    }

    public function cambiarModoProducto(string $modo): void
    {
        if (! in_array($modo, ['existente', 'nuevo'], true)) {
            return;
        }

        $this->modoProducto = $modo;
        $this->limpiarDetalleProducto();
        $this->precioVentaEditable = $modo === 'nuevo';
    }

    public function desbloquearProveedor(): void
    {
        $this->idProveedor = '';
        $this->proveedorNombre = '';
        $this->buscarProveedor = '';
        $this->proveedoresEncontrados = [];

        $this->resetErrorBag('idProveedor');
    }

    public function desbloquearProducto(): void
    {
        $this->limpiarProductoSeleccionado();
        $this->resetErrorBag('idProducto');
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

    protected function cargarCatalogos(): void
    {
        $categoriasDb = CategoriaProducto::query()
            ->orderBy('Nombre_Categoria')
            ->get(['Id_Categoria', 'Nombre_Categoria'])
            ->map(fn (CategoriaProducto $categoria) => [
                'valor' => 'db:' . $categoria->Id_Categoria,
                'id' => $categoria->Id_Categoria,
                'nombre' => $categoria->Nombre_Categoria,
                'temporal' => false,
            ])
            ->toArray();

        $marcasDb = Marca::query()
            ->orderBy('Nombre_Marca')
            ->get(['Id_Marca', 'Nombre_Marca'])
            ->map(fn (Marca $marca) => [
                'valor' => 'db:' . $marca->Id_Marca,
                'id' => $marca->Id_Marca,
                'nombre' => $marca->Nombre_Marca,
                'temporal' => false,
            ])
            ->toArray();

        $this->categorias = array_merge($categoriasDb, $this->categoriasTemporales);
        $this->marcas = array_merge($marcasDb, $this->marcasTemporales);
    }

    protected function buscarProveedores(): void
    {
        $busqueda = trim($this->buscarProveedor);

        if ($busqueda === '') {
            $this->proveedoresEncontrados = [];
            return;
        }

        $this->proveedoresEncontrados = Proveedor::query()
            ->leftJoin('persona as pe', 'proveedor.Id_Persona', '=', 'pe.Id_Persona')
            ->select(
                'proveedor.Id_Proveedor',
                'proveedor.Codigo_RUC',
                'pe.Primer_Nombre',
                'pe.Segundo_Nombre',
                'pe.Primer_Apellido',
                'pe.Segundo_Apellido'
            )
            ->where(function ($q) use ($busqueda) {
                $q->where('proveedor.Codigo_RUC', 'like', "%{$busqueda}%")
                    ->orWhere('pe.Primer_Nombre', 'like', "%{$busqueda}%")
                    ->orWhere('pe.Segundo_Nombre', 'like', "%{$busqueda}%")
                    ->orWhere('pe.Primer_Apellido', 'like', "%{$busqueda}%")
                    ->orWhere('pe.Segundo_Apellido', 'like', "%{$busqueda}%");
            })
            ->orderBy('pe.Primer_Nombre')
            ->limit(8)
            ->get()
            ->map(function ($proveedor) {
                $nombre = trim(
                    ($proveedor->Primer_Nombre ?? '') . ' ' .
                    ($proveedor->Segundo_Nombre ?? '') . ' ' .
                    ($proveedor->Primer_Apellido ?? '') . ' ' .
                    ($proveedor->Segundo_Apellido ?? '')
                );

                return [
                    'id' => $proveedor->Id_Proveedor,
                    'nombre' => $nombre !== '' ? $nombre : 'Proveedor sin nombre',
                    'ruc' => $proveedor->Codigo_RUC ?: 'Sin RUC',
                ];
            })
            ->toArray();
    }

    public function seleccionarProveedor(int $idProveedor): void
    {
        $proveedor = Proveedor::query()
            ->leftJoin('persona as pe', 'proveedor.Id_Persona', '=', 'pe.Id_Persona')
            ->select(
                'proveedor.Id_Proveedor',
                'proveedor.Codigo_RUC',
                'pe.Primer_Nombre',
                'pe.Segundo_Nombre',
                'pe.Primer_Apellido',
                'pe.Segundo_Apellido'
            )
            ->where('proveedor.Id_Proveedor', $idProveedor)
            ->first();

        if (! $proveedor) {
            $this->mostrarToast('No se encontró el proveedor seleccionado.', 'error');
            return;
        }

        $nombre = trim(
            ($proveedor->Primer_Nombre ?? '') . ' ' .
            ($proveedor->Segundo_Nombre ?? '') . ' ' .
            ($proveedor->Primer_Apellido ?? '') . ' ' .
            ($proveedor->Segundo_Apellido ?? '')
        );

        $this->idProveedor = $proveedor->Id_Proveedor;
        $this->proveedorNombre = $nombre !== '' ? $nombre : 'Proveedor sin nombre';
        $this->buscarProveedor = $this->proveedorNombre . ' - ' . ($proveedor->Codigo_RUC ?: 'Sin RUC');
        $this->proveedoresEncontrados = [];

        $this->resetErrorBag('idProveedor');
    }

    protected function buscarProductos(): void
    {
        $busqueda = trim($this->buscarProducto);

        if ($busqueda === '') {
            $this->productosEncontrados = [];
            return;
        }

        $this->productosEncontrados = Producto::query()
            ->with(['categoria', 'marca'])
            ->where(function ($q) use ($busqueda) {
                $q->where('Nombre_Producto', 'like', "%{$busqueda}%")
                    ->orWhere('Modelo', 'like', "%{$busqueda}%")
                    ->orWhereHas('categoria', function ($categoria) use ($busqueda) {
                        $categoria->where('Nombre_Categoria', 'like', "%{$busqueda}%");
                    })
                    ->orWhereHas('marca', function ($marca) use ($busqueda) {
                        $marca->where('Nombre_Marca', 'like', "%{$busqueda}%");
                    });
            })
            ->orderBy('Nombre_Producto')
            ->limit(8)
            ->get()
            ->map(fn (Producto $producto) => [
                'id' => $producto->Id_Producto,
                'nombre' => $producto->Nombre_Producto,
                'modelo' => $producto->Modelo ?: 'Sin modelo',
                'categoria' => $producto->categoria?->Nombre_Categoria ?: 'Sin categoría',
                'marca' => $producto->marca?->Nombre_Marca ?: 'Sin marca',
                'precio_venta' => (float) $producto->Precio_Venta,
                'stock' => (int) $producto->Stock_Actual,
            ])
            ->toArray();
    }

    public function seleccionarProducto(int $idProducto): void
    {
        $producto = Producto::query()
            ->with(['categoria', 'marca'])
            ->find($idProducto);

        if (! $producto) {
            $this->mostrarToast('No se encontró el producto seleccionado.', 'error');
            return;
        }

        $this->idProducto = $producto->Id_Producto;
        $this->productoNombre = $producto->Nombre_Producto;
        $this->productoModelo = $producto->Modelo ?: 'Sin modelo';
        $this->productoCategoria = $producto->categoria?->Nombre_Categoria ?: 'Sin categoría';
        $this->productoMarca = $producto->marca?->Nombre_Marca ?: 'Sin marca';

        $this->precioVentaActual = (float) $producto->Precio_Venta;
        $this->precioVenta = (string) $this->precioVentaActual;
        $this->precioVentaEditable = ((float) $this->precioCompra) > $this->precioVentaActual;

        $this->buscarProducto = trim(
            $this->productoMarca . ' ' .
            $this->productoNombre . ' ' .
            $this->productoModelo
        );

        $this->productosEncontrados = [];

        $this->resetErrorBag('idProducto');
    }

    public function agregarCategoriaTemporal(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'nombreCategoriaNueva' => 'required|string|max:100',
        ]);

        $nombre = trim($this->nombreCategoriaNueva);

        $existeDb = CategoriaProducto::query()
            ->whereRaw('LOWER(Nombre_Categoria) = ?', [mb_strtolower($nombre)])
            ->exists();

        $existeTemporal = collect($this->categoriasTemporales)
            ->contains(fn ($categoria) => mb_strtolower($categoria['nombre']) === mb_strtolower($nombre));

        if ($existeDb || $existeTemporal) {
            $this->addError('nombreCategoriaNueva', 'Esta categoría ya existe.');
            return;
        }

        $uid = uniqid('cat_', true);

        $this->categoriasTemporales[] = [
            'valor' => 'tmp:' . $uid,
            'id' => null,
            'nombre' => $nombre,
            'temporal' => true,
        ];

        $this->nuevoCategoriaSeleccionada = 'tmp:' . $uid;
        $this->nombreCategoriaNueva = '';
        $this->mostrarNuevaCategoria = false;

        $this->cargarCatalogos();
        $this->mostrarToast('Categoría agregada temporalmente. Se guardará al guardar la compra.');
    }

    public function agregarMarcaTemporal(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'nombreMarcaNueva' => 'required|string|max:100',
        ]);

        $nombre = trim($this->nombreMarcaNueva);

        $existeDb = Marca::query()
            ->whereRaw('LOWER(Nombre_Marca) = ?', [mb_strtolower($nombre)])
            ->exists();

        $existeTemporal = collect($this->marcasTemporales)
            ->contains(fn ($marca) => mb_strtolower($marca['nombre']) === mb_strtolower($nombre));

        if ($existeDb || $existeTemporal) {
            $this->addError('nombreMarcaNueva', 'Esta marca ya existe.');
            return;
        }

        $uid = uniqid('mar_', true);

        $this->marcasTemporales[] = [
            'valor' => 'tmp:' . $uid,
            'id' => null,
            'nombre' => $nombre,
            'temporal' => true,
        ];

        $this->nuevoMarcaSeleccionada = 'tmp:' . $uid;
        $this->nombreMarcaNueva = '';
        $this->mostrarNuevaMarca = false;

        $this->cargarCatalogos();
        $this->mostrarToast('Marca agregada temporalmente. Se guardará al guardar la compra.');
    }

    protected function existeEnCatalogo(string $valor, array $catalogo): bool
    {
        return collect($catalogo)->contains(fn ($item) => $item['valor'] === $valor);
    }

    protected function nombreCatalogo(string $valor, array $catalogo, string $default): string
    {
        $item = collect($catalogo)->firstWhere('valor', $valor);

        return $item['nombre'] ?? $default;
    }

    protected function obtenerSeries(): array
    {
        $texto = trim($this->seriesTexto);

        if ($texto === '') {
            return [];
        }

        return collect(preg_split('/[\r\n,;]+/', $texto))
            ->map(fn ($serie) => trim($serie))
            ->filter()
            ->values()
            ->toArray();
    }

    protected function validarSeries(array $series, int $cantidad): bool
    {
        if (count($series) === 0) {
            return true;
        }

        if (count($series) !== $cantidad) {
            $this->addError(
                'seriesTexto',
                'Si ingresa números de serie, debe ingresar exactamente ' . $cantidad . ' número(s) de serie, uno por cada producto comprado.'
            );

            return false;
        }

        if (count($series) !== count(array_unique($series))) {
            $this->addError('seriesTexto', 'Hay números de serie repetidos en este detalle.');
            return false;
        }

        foreach ($series as $serie) {
            if (strlen($serie) > 100) {
                $this->addError('seriesTexto', 'Cada número de serie debe tener máximo 100 caracteres.');
                return false;
            }
        }

        $seriesTemporales = collect($this->detalles)
            ->flatMap(fn ($detalle) => $detalle['series'])
            ->toArray();

        foreach ($series as $serie) {
            if (in_array($serie, $seriesTemporales, true)) {
                $this->addError('seriesTexto', "El número de serie {$serie} ya está agregado temporalmente.");
                return false;
            }
        }

        $seriesExistentes = ProductoSerie::query()
            ->whereIn('Numero_Serie', $series)
            ->pluck('Numero_Serie')
            ->toArray();

        if (count($seriesExistentes) > 0) {
            $this->addError('seriesTexto', 'Ya existe este número de serie: ' . implode(', ', $seriesExistentes));
            return false;
        }

        return true;
    }

    public function agregarDetalle(): void
    {
        $this->resetErrorBag();

        $reglasBase = [
            'cantidad' => 'required|integer|min:1',
            'precioCompra' => 'required|numeric|min:0.01',
            'precioVenta' => 'required|numeric|min:0.01',
            'seriesTexto' => 'nullable|string|max:1000',
        ];

        if ($this->modoProducto === 'existente') {
            if ($this->idProducto === '') {
                $this->addError('idProducto', 'Seleccione un producto existente de la lista. Si no aparece, use “Producto nuevo”.');
                return;
            }

            $this->validate(array_merge($reglasBase, [
                'idProducto' => 'required|exists:producto,Id_Producto',
            ]));

            if ((float) $this->precioCompra > $this->precioVentaActual && (float) $this->precioVenta < (float) $this->precioCompra) {
                $this->addError('precioVenta', 'El precio de venta debe ser mayor o igual al precio de compra.');
                return;
            }

            $productoId = (int) $this->idProducto;
            $nombreProducto = $this->productoNombre;
            $modelo = $this->productoModelo;
            $categoriaValor = null;
            $categoriaNombre = $this->productoCategoria;
            $marcaValor = null;
            $marcaNombre = $this->productoMarca;
            $stockMinimo = null;
            $garantiaNuevo = null;
            $garantiaUsado = null;
            $estado = 1;

            $series = $this->obtenerSeries();

            if (! $this->validarSeries($series, (int) $this->cantidad)) {
                return;
            }
        } else {
            $this->validate(array_merge($reglasBase, [
                'nuevoNombreProducto' => 'required|string|max:150',
                'nuevoModelo' => 'required|string|max:100',
                'nuevoCategoriaSeleccionada' => 'required|string',
                'nuevoMarcaSeleccionada' => 'required|string',
                'nuevoStockMinimo' => 'required|integer|min:0',
                'nuevoGarantiaNuevo' => 'nullable|integer|min:0',
                'nuevoGarantiaUsado' => 'nullable|integer|min:0',
                'nuevoEstado' => 'required|in:0,1',
            ]));

            if (! $this->existeEnCatalogo($this->nuevoCategoriaSeleccionada, $this->categorias)) {
                $this->addError('nuevoCategoriaSeleccionada', 'Seleccione una categoría válida.');
                return;
            }

            if (! $this->existeEnCatalogo($this->nuevoMarcaSeleccionada, $this->marcas)) {
                $this->addError('nuevoMarcaSeleccionada', 'Seleccione una marca válida.');
                return;
            }

            if ((float) $this->precioVenta < (float) $this->precioCompra) {
                $this->addError('precioVenta', 'El precio de venta debe ser mayor o igual al precio de compra.');
                return;
            }

            $productoId = null;
            $nombreProducto = trim($this->nuevoNombreProducto);
            $modelo = trim($this->nuevoModelo);
            $categoriaValor = $this->nuevoCategoriaSeleccionada;
            $categoriaNombre = $this->nombreCatalogo($this->nuevoCategoriaSeleccionada, $this->categorias, 'Sin categoría');

            $marcaValor = $this->nuevoMarcaSeleccionada;
            $marcaNombre = $this->nombreCatalogo($this->nuevoMarcaSeleccionada, $this->marcas, 'Sin marca');

            $stockMinimo = (int) $this->nuevoStockMinimo;
            $garantiaNuevo = $this->nuevoGarantiaNuevo !== '' ? (int) $this->nuevoGarantiaNuevo : null;
            $garantiaUsado = $this->nuevoGarantiaUsado !== '' ? (int) $this->nuevoGarantiaUsado : null;
            $estado = (int) $this->nuevoEstado;

            $series = $this->obtenerSeries();

            if (! $this->validarSeries($series, (int) $this->cantidad)) {
                return;
            }
        }

        $cantidad = (int) $this->cantidad;
        $precioCompra = (float) $this->precioCompra;
        $precioVenta = (float) $this->precioVenta;
        $subtotal = $cantidad * $precioCompra;

        $this->detalles[] = [
            'uid' => uniqid('detalle_', true),
            'modo' => $this->modoProducto,
            'producto_id' => $productoId,
            'nombre_producto' => $nombreProducto,
            'modelo' => $modelo,
            'categoria_valor' => $categoriaValor,
            'categoria' => $categoriaNombre,
            'marca_valor' => $marcaValor,
            'marca' => $marcaNombre,
            'stock_minimo' => $stockMinimo,
            'garantia_nuevo' => $garantiaNuevo,
            'garantia_usado' => $garantiaUsado,
            'estado' => $estado,
            'cantidad' => $cantidad,
            'precio_compra' => $precioCompra,
            'precio_venta' => $precioVenta,
            'subtotal' => $subtotal,
            'series' => $series,
            'actualizar_precio_venta' => $this->modoProducto === 'nuevo' || $precioVenta > $this->precioVentaActual,
        ];

        $this->limpiarDetalleProducto();
        $this->mostrarToast('Producto agregado temporalmente a la compra.');
    }

    public function quitarDetalle(int $indice): void
    {
        if (! isset($this->detalles[$indice])) {
            return;
        }

        unset($this->detalles[$indice]);
        $this->detalles = array_values($this->detalles);

        $this->mostrarToast('Producto quitado de la compra.');
    }

    protected function resolverCategoriaId(string $valor, array &$categoriasCreadas): int
    {
        if (str_starts_with($valor, 'db:')) {
            return (int) str_replace('db:', '', $valor);
        }

        if (! isset($categoriasCreadas[$valor])) {
            $categoriaTemporal = collect($this->categoriasTemporales)->firstWhere('valor', $valor);

            if (! $categoriaTemporal) {
                throw new \RuntimeException('No se encontró la categoría temporal.');
            }

            $categoria = CategoriaProducto::query()->create([
                'Nombre_Categoria' => $categoriaTemporal['nombre'],
            ]);

            $categoriasCreadas[$valor] = $categoria->Id_Categoria;
        }

        return (int) $categoriasCreadas[$valor];
    }

    protected function resolverMarcaId(?string $valor, array &$marcasCreadas): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (str_starts_with($valor, 'db:')) {
            return (int) str_replace('db:', '', $valor);
        }

        if (! isset($marcasCreadas[$valor])) {
            $marcaTemporal = collect($this->marcasTemporales)->firstWhere('valor', $valor);

            if (! $marcaTemporal) {
                throw new \RuntimeException('No se encontró la marca temporal.');
            }

            $marca = Marca::query()->create([
                'Nombre_Marca' => $marcaTemporal['nombre'],
                'Estado' => 1,
            ]);

            $marcasCreadas[$valor] = $marca->Id_Marca;
        }

        return (int) $marcasCreadas[$valor];
    }

    public function guardarCompra(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'idProveedor' => 'required|exists:proveedor,Id_Proveedor',
            'numeroCompra' => 'required|string|max:50',
            'fechaCompra' => 'required|date|before_or_equal:today',
            'tipoCompra' => 'required|string|max:50',
            'retencion' => 'required|numeric|min:0',
            'iva' => 'required|numeric|min:0',
            'observacion' => 'nullable|string|max:255',
        ]);

        if (count($this->detalles) === 0) {
            $this->mostrarToast('Debe agregar al menos un producto a la compra.', 'error');
            return;
        }

        foreach ($this->detalles as $detalle) {
            $series = $detalle['series'] ?? [];

            if (count($series) > 0 && count($series) !== (int) $detalle['cantidad']) {
                $this->mostrarToast(
                    'Si un producto tiene números de serie, la cantidad de series debe coincidir exactamente con la cantidad comprada.',
                    'error'
                );
                return;
            }
        }

        $idUsuario = $this->obtenerUsuarioActual();

        if (! $idUsuario) {
            $this->mostrarToast('No existe un usuario registrado para guardar la compra. Cree al menos un usuario o active el login.', 'error');
            return;
        }

        $todasLasSeries = collect($this->detalles)
            ->flatMap(fn ($detalle) => $detalle['series'])
            ->values()
            ->toArray();

        if (count($todasLasSeries) !== count(array_unique($todasLasSeries))) {
            $this->mostrarToast('Hay números de serie repetidos en la compra.', 'error');
            return;
        }

        if (count($todasLasSeries) > 0) {
            $seriesExistentes = ProductoSerie::query()
                ->whereIn('Numero_Serie', $todasLasSeries)
                ->pluck('Numero_Serie')
                ->toArray();

            if (count($seriesExistentes) > 0) {
                $this->mostrarToast('Ya existen números de serie registrados: ' . implode(', ', $seriesExistentes), 'error');
                return;
            }
        }

        try {
            Compra::query()->getConnection()->transaction(function () use ($idUsuario) {
                $categoriasCreadas = [];
                $marcasCreadas = [];
                $primerProductoId = null;

                $compra = Compra::query()->create([
                    'Numero_Compra' => trim($this->numeroCompra),
                    'Fecha_Compra' => Carbon::parse($this->fechaCompra)->startOfDay(),
                    'Id_Proveedor' => (int) $this->idProveedor,
                    'Id_Usuario' => (int) $idUsuario,
                    'Tipo_Compra' => $this->tipoCompra,
                    'Total' => $this->totalGeneral(),
                    'Observacion' => trim($this->observacion) !== '' ? trim($this->observacion) : null,
                    'Retencion' => (float) $this->retencion,
                    'Iva' => (float) $this->iva,
                    'Id_producto' => null,
                ]);

                foreach ($this->detalles as $detalle) {
                    if ($detalle['modo'] === 'nuevo') {
                        $categoriaId = $this->resolverCategoriaId($detalle['categoria_valor'], $categoriasCreadas);
                        $marcaId = $this->resolverMarcaId($detalle['marca_valor'], $marcasCreadas);

                        $producto = Producto::query()->create([
                            'Id_Categoria' => $categoriaId,
                            'Id_Marca' => $marcaId,
                            'Nombre_Producto' => $detalle['nombre_producto'],
                            'Modelo' => $detalle['modelo'],
                            'Stock_Actual' => 0,
                            'Stock_Minimo' => $detalle['stock_minimo'],
                            'Precio_Venta' => $detalle['precio_venta'],
                            'Fecha_Vencimiento' => now(),
                            'Meses_Garantia_Nuevo' => $detalle['garantia_nuevo'],
                            'Meses_Garantia_Usado' => $detalle['garantia_usado'],
                            'Estado' => $detalle['estado'],
                        ]);
                    } else {
                        $producto = Producto::query()->findOrFail((int) $detalle['producto_id']);

                        if ($detalle['actualizar_precio_venta']) {
                            $producto->Precio_Venta = $detalle['precio_venta'];
                            $producto->save();
                        }
                    }

                    if ($primerProductoId === null) {
                        $primerProductoId = $producto->Id_Producto;
                    }

                    $producto->increment('Stock_Actual', (int) $detalle['cantidad']);

                    foreach ($detalle['series'] as $serie) {
                        ProductoSerie::query()->create([
                            'Id_Producto' => $producto->Id_Producto,
                            'Numero_Serie' => $serie,
                            'Fecha_Ingreso' => now(),
                            'Estado' => ProductoSerie::ESTADO_DISPONIBLE,
                            'Observacion' => null,
                        ]);
                    }

                    DetalleCompra::query()->create([
                        'Id_Compra' => $compra->Id_Compra,
                        'Id_Producto' => $producto->Id_Producto,
                        'Cantidad' => $detalle['cantidad'],
                        'Precio_Compra' => $detalle['precio_compra'],
                        'Subtotal' => $detalle['subtotal'],
                    ]);
                }

                $compra->Id_producto = $primerProductoId;
                $compra->save();
            });

            $this->cancelarCompra(false);
            $this->mostrarToast('Compra guardada correctamente.');
        } catch (\Throwable $e) {
            $this->mostrarToast('Error al guardar la compra: ' . $e->getMessage(), 'error');
        }
    }

    protected function obtenerUsuarioActual(): ?int
    {
        $idAuth = auth()->id();

        if ($idAuth && Usuario::query()->where('Id_Usuario', $idAuth)->exists()) {
            return (int) $idAuth;
        }

        $primerUsuario = Usuario::query()
            ->orderBy('Id_Usuario')
            ->value('Id_Usuario');

        return $primerUsuario ? (int) $primerUsuario : null;
    }

    public function cancelarCompra(bool $mostrarMensaje = true): void
    {
        $this->buscarProveedor = '';
        $this->proveedorNombre = '';
        $this->idProveedor = '';

        $this->numeroCompra = '';
        $this->fechaCompra = Carbon::today()->toDateString();
        $this->tipoCompra = 'CONTADO';
        $this->observacion = '';
        $this->retencion = '0';
        $this->iva = '0';

        $this->detalles = [];
        $this->categoriasTemporales = [];
        $this->marcasTemporales = [];
        $this->mostrarNuevaCategoria = false;
        $this->mostrarNuevaMarca = false;
        $this->nombreCategoriaNueva = '';
        $this->nombreMarcaNueva = '';

        $this->limpiarDetalleProducto();
        $this->cargarCatalogos();

        if ($mostrarMensaje) {
            $this->mostrarToast('Compra cancelada. No se guardó nada en la base de datos.');
        }
    }

    public function limpiarDetalleProducto(): void
    {
        $this->limpiarProductoSeleccionado();
        $this->cantidad = '1';
        $this->precioCompra = '0';
        $this->precioVenta = '0';
        $this->seriesTexto = '';

        $this->resetErrorBag();
        $this->resetValidation();
    }

    protected function limpiarProductoSeleccionado(bool $limpiarBusqueda = true): void
    {
        if ($limpiarBusqueda) {
            $this->buscarProducto = '';
        }

        $this->productosEncontrados = [];
        $this->idProducto = '';
        $this->productoNombre = '';
        $this->productoCategoria = '';
        $this->productoMarca = '';
        $this->productoModelo = '';
        $this->precioVentaActual = 0;

        $this->nuevoNombreProducto = '';
        $this->nuevoModelo = '';
        $this->nuevoCategoriaSeleccionada = '';
        $this->nuevoMarcaSeleccionada = '';
        $this->nuevoStockMinimo = '0';
        $this->nuevoGarantiaNuevo = '';
        $this->nuevoGarantiaUsado = '';
        $this->nuevoEstado = '1';

        $this->precioVentaEditable = $this->modoProducto === 'nuevo';
    }

    public function subtotalGeneral(): float
    {
        return (float) collect($this->detalles)->sum('subtotal');
    }

    public function ivaGeneral(): float
    {
        return (float) $this->iva;
    }

    public function retencionGeneral(): float
    {
        return (float) $this->retencion;
    }

    public function totalGeneral(): float
    {
        return $this->subtotalGeneral() + $this->ivaGeneral() + $this->retencionGeneral();
    }
};
?>

<div class="min-h-screen bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="mx-auto flex w-full max-w-[1450px] flex-col gap-4">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#1A2B42]">Compras</h1>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    Registre compras, agregue productos existentes o ingrese productos nuevos.
                </p>
            </div>

            <div class="rounded-2xl border border-[#D7E4F3] bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Total de compra</p>
                <p class="text-2xl font-bold text-[#1A2B42]">
                    C$ {{ number_format($this->totalGeneral(), 2) }}
                </p>
            </div>
        </div>

        @if ($mostrarToast)
            <div class="fixed right-5 top-5 z-999 w-full max-w-sm">
                <div class="{{ $toastTipo === 'success' ? 'border-[#B7D6F2] bg-[#EAF4FD] text-[#1A2B42]' : 'border-red-200 bg-red-50 text-red-700' }} rounded-2xl border px-4 py-4 shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-medium">{{ $toastMensaje }}</p>
                        <button type="button" wire:click="cerrarToast" class="text-lg leading-none text-[#5F6B7A] hover:text-[#1A2B42]">
                            ×
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Datos generales de la compra</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Seleccione el proveedor, registre la factura y los montos generales.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <div class="relative lg:col-span-4">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Proveedor <span class="text-red-600">*</span>
                    </label>

                    <x-input
                        wire:model.live.debounce.250ms="buscarProveedor"
                        wire:dblclick="desbloquearProveedor"
                        type="text"
                        :readonly="$idProveedor !== ''"
                        placeholder="Buscar proveedor por nombre o RUC"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    />

                    @if ($idProveedor !== '')
                        <p class="mt-1 text-xs text-[#5F6B7A]">
                            Doble click para cambiar el proveedor.
                        </p>
                    @endif

                    @error('idProveedor')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror

                    @if (count($proveedoresEncontrados) > 0 && $buscarProveedor !== '' && $idProveedor === '')
                        <div class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                            @foreach ($proveedoresEncontrados as $proveedor)
                                <button
                                    type="button"
                                    wire:click="seleccionarProveedor({{ $proveedor['id'] }})"
                                    class="block w-full px-4 py-3 text-left text-sm text-[#1A2B42] hover:bg-[#EAF2FB]"
                                >
                                    <span class="block font-semibold">{{ $proveedor['nombre'] }}</span>
                                    <span class="text-xs text-[#5F6B7A]">RUC: {{ $proveedor['ruc'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @elseif ($buscarProveedor !== '' && $idProveedor === '' && count($proveedoresEncontrados) === 0)
                        <div class="mt-1 rounded-xl border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs text-yellow-700">
                            No se encontraron proveedores con esa búsqueda.
                        </div>
                    @endif
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Número de factura <span class="text-red-600">*</span>
                    </label>
                    <x-input
                        wire:model.defer="numeroCompra"
                        type="text"
                        maxlength="50"
                        placeholder="No. factura"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('numeroCompra')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Fecha <span class="text-red-600">*</span>
                    </label>
                    <x-input
                        wire:model.defer="fechaCompra"
                        type="date"
                        max="{{ now()->toDateString() }}"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('fechaCompra')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Tipo de pago <span class="text-red-600">*</span>
                    </label>
                    <select
                        wire:model.defer="tipoCompra"
                        class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                    >
                        @foreach ($tiposCompra as $tipo)
                            <option value="{{ $tipo['id'] }}">{{ $tipo['nombre'] }}</option>
                        @endforeach
                    </select>
                    @error('tipoCompra')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="lg:col-span-1">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">IVA</label>
                    <x-input
                        wire:model.live.debounce.250ms="iva"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('iva')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="lg:col-span-1">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Retención</label>
                    <x-input
                        wire:model.live.debounce.250ms="retencion"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('retencion')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="lg:col-span-12">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Observación</label>
                    <textarea
                        wire:model.defer="observacion"
                        rows="2"
                        placeholder="Opcional"
                        class="w-full rounded-lg border-0 bg-[#F0F3F7] px-3 py-2 text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    ></textarea>
                    @error('observacion')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </x-card>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-[#1A2B42]">Agregar producto</h2>
                    <p class="text-sm text-[#5F6B7A]">
                        Busque un producto existente o registre uno nuevo.
                    </p>
                </div>

                <div class="inline-flex rounded-xl bg-[#F0F3F7] p-1">
                    <button
                        type="button"
                        wire:click="cambiarModoProducto('existente')"
                        class="{{ $modoProducto === 'existente' ? 'bg-[#2E8BC0] text-white' : 'text-[#1A2B42]' }} rounded-lg px-4 py-2 text-sm font-semibold transition"
                    >
                        Producto existente
                    </button>

                    <button
                        type="button"
                        wire:click="cambiarModoProducto('nuevo')"
                        class="{{ $modoProducto === 'nuevo' ? 'bg-[#2E8BC0] text-white' : 'text-[#1A2B42]' }} rounded-lg px-4 py-2 text-sm font-semibold transition"
                    >
                        Producto nuevo
                    </button>
                </div>
            </div>

            @if ($modoProducto === 'existente')
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="relative xl:col-span-4">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Buscar producto <span class="text-red-600">*</span>
                        </label>

                        <x-input
                            wire:model.live.debounce.250ms="buscarProducto"
                            wire:dblclick="desbloquearProducto"
                            type="text"
                            :readonly="$idProducto !== ''"
                            placeholder="Escriba para buscar coincidencias"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                        />

                        @if ($idProducto !== '')
                            <p class="mt-1 text-xs text-[#5F6B7A]">
                                Doble click para cambiar el producto.
                            </p>
                        @endif

                        @error('idProducto')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror

                        @if (count($productosEncontrados) > 0 && $idProducto === '')
                            <div class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                                @foreach ($productosEncontrados as $producto)
                                    <button
                                        type="button"
                                        wire:click="seleccionarProducto({{ $producto['id'] }})"
                                        class="block w-full px-4 py-3 text-left text-sm text-[#1A2B42] hover:bg-[#EAF2FB]"
                                    >
                                        <span class="block font-semibold">
                                            {{ $producto['marca'] }} {{ $producto['nombre'] }}
                                        </span>
                                        <span class="text-xs text-[#5F6B7A]">
                                            {{ $producto['modelo'] }} · {{ $producto['categoria'] }} · Stock: {{ $producto['stock'] }} · C$ {{ number_format($producto['precio_venta'], 2) }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        @elseif ($buscarProducto !== '' && $idProducto === '' && count($productosEncontrados) === 0)
                            <div class="mt-1 rounded-xl border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs text-yellow-700">
                                Este producto no existe. Puede registrarlo usando la opción “Producto nuevo”.
                            </div>
                        @endif
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Categoría</label>
                        <x-input
                            value="{{ $productoCategoria }}"
                            readonly
                            placeholder="Se carga al seleccionar"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Marca</label>
                        <x-input
                            value="{{ $productoMarca }}"
                            readonly
                            placeholder="Se carga al seleccionar"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Modelo</label>
                        <x-input
                            value="{{ $productoModelo }}"
                            readonly
                            placeholder="Se carga al seleccionar"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-3">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Nombre del producto <span class="text-red-600">*</span>
                        </label>
                        <x-input
                            wire:model.defer="nuevoNombreProducto"
                            type="text"
                            maxlength="150"
                            placeholder="Ej: Laptop HP 15"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                        />
                        @error('nuevoNombreProducto')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Modelo <span class="text-red-600">*</span>
                        </label>
                        <x-input
                            wire:model.defer="nuevoModelo"
                            type="text"
                            maxlength="100"
                            placeholder="Modelo obligatorio"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                        />
                        @error('nuevoModelo')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="xl:col-span-3">
                        <div class="flex items-center justify-between gap-2">
                            <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                                Categoría <span class="text-red-600">*</span>
                            </label>
                            <button type="button" wire:click="$toggle('mostrarNuevaCategoria')" class="text-xs font-semibold text-[#0B6FE4]">
                                + Nueva
                            </button>
                        </div>

                        <select
                            wire:model.defer="nuevoCategoriaSeleccionada"
                            class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                        >
                            <option value="">Seleccione categoría</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria['valor'] }}">
                                    {{ $categoria['nombre'] }} {{ $categoria['temporal'] ? '(nueva)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('nuevoCategoriaSeleccionada')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror

                        @if ($mostrarNuevaCategoria)
                            <div class="mt-2 flex gap-2">
                                <x-input
                                    wire:model.defer="nombreCategoriaNueva"
                                    type="text"
                                    placeholder="Nueva categoría"
                                    class="h-9 min-h-9 w-full rounded-lg bg-[#F0F3F7] text-xs text-[#1A2B42]"
                                />
                                <x-button
                                    label="Agregar"
                                    wire:click="agregarCategoriaTemporal"
                                    class="h-9 min-h-9 border-0 bg-[#2E8BC0] px-3 text-xs text-white hover:bg-[#0B6FE4]"
                                />
                            </div>
                            @error('nombreCategoriaNueva')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>

                    <div class="xl:col-span-3">
                        <div class="flex items-center justify-between gap-2">
                            <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                                Marca <span class="text-red-600">*</span>
                            </label>
                            <button type="button" wire:click="$toggle('mostrarNuevaMarca')" class="text-xs font-semibold text-[#0B6FE4]">
                                + Nueva
                            </button>
                        </div>

                        <select
                            wire:model.defer="nuevoMarcaSeleccionada"
                            class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                        >
                            <option value="">Seleccione marca</option>
                            @foreach ($marcas as $marca)
                                <option value="{{ $marca['valor'] }}">
                                    {{ $marca['nombre'] }} {{ $marca['temporal'] ? '(nueva)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('nuevoMarcaSeleccionada')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror

                        @if ($mostrarNuevaMarca)
                            <div class="mt-2 flex gap-2">
                                <x-input
                                    wire:model.defer="nombreMarcaNueva"
                                    type="text"
                                    placeholder="Nueva marca"
                                    class="h-9 min-h-9 w-full rounded-lg bg-[#F0F3F7] text-xs text-[#1A2B42]"
                                />
                                <x-button
                                    label="Agregar"
                                    wire:click="agregarMarcaTemporal"
                                    class="h-9 min-h-9 border-0 bg-[#2E8BC0] px-3 text-xs text-white hover:bg-[#0B6FE4]"
                                />
                            </div>
                            @error('nombreMarcaNueva')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>

                    <div class="xl:col-span-1">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Stock mín. <span class="text-red-600">*</span>
                        </label>
                        <x-input
                            wire:model.defer="nuevoStockMinimo"
                            type="number"
                            min="0"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                        @error('nuevoStockMinimo')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Garantía nuevo</label>
                        <x-input
                            wire:model.defer="nuevoGarantiaNuevo"
                            type="number"
                            min="0"
                            placeholder="Meses"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                        @error('nuevoGarantiaNuevo')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Garantía usado</label>
                        <x-input
                            wire:model.defer="nuevoGarantiaUsado"
                            type="number"
                            min="0"
                            placeholder="Meses"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                        @error('nuevoGarantiaUsado')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Estado <span class="text-red-600">*</span>
                        </label>
                        <select
                            wire:model.defer="nuevoEstado"
                            class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]"
                        >
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                        @error('nuevoEstado')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            @endif

            <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-12">
                <div class="xl:col-span-1">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Cantidad <span class="text-red-600">*</span>
                    </label>
                    <x-input
                        wire:model.defer="cantidad"
                        type="number"
                        min="1"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('cantidad')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Precio compra <span class="text-red-600">*</span>
                    </label>
                    <x-input
                        wire:model.live.debounce.250ms="precioCompra"
                        type="number"
                        step="0.01"
                        min="0"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('precioCompra')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Precio venta <span class="text-red-600">*</span>
                        @if ($modoProducto === 'existente' && ! $precioVentaEditable)
                            <span class="text-xs font-normal text-[#5F6B7A]">👁️</span>
                        @endif
                    </label>
                    <x-input
                        wire:model.defer="precioVenta"
                        type="number"
                        step="0.01"
                        min="0"
                        :readonly="! $precioVentaEditable"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                    @error('precioVenta')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="xl:col-span-7">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                        Número de serie
                        <span class="text-xs font-normal text-[#5F6B7A]">
                            Opcional
                        </span>
                    </label>
                    <textarea
                        wire:model.defer="seriesTexto"
                        rows="2"
                        placeholder="Opcional. Ej: SN001, SN002"
                        class="w-full rounded-lg border-0 bg-[#F0F3F7] px-3 py-2 text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    ></textarea>
                    @error('seriesTexto')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="mt-5 flex flex-wrap justify-end gap-3">
                <x-button
                    label="Limpiar producto"
                    type="button"
                    wire:click="limpiarDetalleProducto"
                    class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]"
                />

                <x-button
                    label="Agregar a la compra"
                    icon="o-plus"
                    type="button"
                    wire:click="agregarDetalle"
                    class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]"
                />
            </div>
        </x-card>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-[#1A2B42]">Detalle de compra</h2>
                </div>

                <div class="grid grid-cols-2 gap-2 text-right md:grid-cols-4">
                    <div class="rounded-xl bg-[#F0F3F7] px-3 py-2">
                        <p class="text-xs text-[#5F6B7A]">Subtotal</p>
                        <p class="text-sm font-bold text-[#1A2B42]">C$ {{ number_format($this->subtotalGeneral(), 2) }}</p>
                    </div>

                    <div class="rounded-xl bg-[#F0F3F7] px-3 py-2">
                        <p class="text-xs text-[#5F6B7A]">IVA</p>
                        <p class="text-sm font-bold text-[#1A2B42]">C$ {{ number_format($this->ivaGeneral(), 2) }}</p>
                    </div>

                    <div class="rounded-xl bg-[#F0F3F7] px-3 py-2">
                        <p class="text-xs text-[#5F6B7A]">Retención</p>
                        <p class="text-sm font-bold text-[#1A2B42]">C$ {{ number_format($this->retencionGeneral(), 2) }}</p>
                    </div>

                    <div class="rounded-xl bg-[#EAF4FD] px-3 py-2">
                        <p class="text-xs text-[#5F6B7A]">Total</p>
                        <p class="text-sm font-bold text-[#1A2B42]">C$ {{ number_format($this->totalGeneral(), 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-[#D7E4F3] bg-white">
                <div class="max-h-[430px] overflow-x-auto overflow-y-auto">
                    <table class="min-w-[1200px] w-full border-separate border-spacing-0 text-[13px] text-[#1A2B42]">
                        <thead class="sticky top-0 z-10">
                            <tr>
                                <th class="rounded-tl-xl bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">Tipo</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">Producto</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">Categoría</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">Marca</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">Cantidad</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white">P. compra</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white">P. venta</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">Series</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white">Subtotal</th>
                                <th class="rounded-tr-xl bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">Acción</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($detalles as $index => $detalle)
                                <tr class="odd:bg-white even:bg-[#F8FBFF]">
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <span class="{{ $detalle['modo'] === 'nuevo' ? 'bg-[#EAF4FD] text-[#0E48A1]' : 'bg-green-100 text-green-700' }} rounded-full px-2.5 py-1 text-xs font-semibold">
                                            {{ $detalle['modo'] === 'nuevo' ? 'Nuevo' : 'Existente' }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-3 whitespace-nowrap font-semibold">
                                        {{ $detalle['nombre_producto'] }}
                                        <span class="block text-xs font-normal text-[#5F6B7A]">{{ $detalle['modelo'] }}</span>
                                    </td>

                                    <td class="px-3 py-3 whitespace-nowrap">{{ $detalle['categoria'] }}</td>
                                    <td class="px-3 py-3 whitespace-nowrap">{{ $detalle['marca'] }}</td>
                                    <td class="px-3 py-3 text-center whitespace-nowrap">{{ $detalle['cantidad'] }}</td>
                                    <td class="px-3 py-3 text-right whitespace-nowrap">C$ {{ number_format($detalle['precio_compra'], 2) }}</td>
                                    <td class="px-3 py-3 text-right whitespace-nowrap">C$ {{ number_format($detalle['precio_venta'], 2) }}</td>
                                    <td class="px-3 py-3 text-center whitespace-nowrap">{{ count($detalle['series']) }}</td>
                                    <td class="px-3 py-3 text-right font-semibold whitespace-nowrap">C$ {{ number_format($detalle['subtotal'], 2) }}</td>

                                    <td class="px-3 py-3 text-center whitespace-nowrap">
                                        <x-button
                                            icon="o-trash"
                                            wire:click="quitarDetalle({{ $index }})"
                                            class="h-8 min-h-8 border-0 bg-red-50 px-3 text-xs text-red-700 hover:bg-red-100"
                                        />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-8 text-center text-sm text-[#7B8794]">
                                        Todavía no hay productos agregados a la compra.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap justify-end gap-3">
                <x-button
                    label="Cancelar compra"
                    icon="o-x-mark"
                    type="button"
                    wire:click="cancelarCompra"
                    class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]"
                />

                <x-button
                    label="Guardar compra"
                    icon="o-check"
                    type="button"
                    wire:click="guardarCompra"
                    class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]"
                />
            </div>
        </x-card>
    </div>
</div>