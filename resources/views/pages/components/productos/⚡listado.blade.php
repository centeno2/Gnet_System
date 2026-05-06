<?php

use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoSerie;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $vista = 'productos';

    public string $buscar = '';
    public string $filtroCategoria = '';
    public string $filtroMarca = '';
    public string $filtroEstado = '';

    public array $categorias = [];
    public array $marcas = [];
    public array $filasProductos = [];
    public array $filasSeries = [];

    public bool $modalDetalle = false;
    public bool $modalSeries = false;
    public bool $modalAgregarSerie = false;

    public array $detalleProducto = [];
    public array $seriesProducto = [];
    public string $productoNombreSeries = '';

    public int $productoIdAgregarSerie = 0;
    public string $productoNombreAgregarSerie = '';
    public string $numeroSerieExtra = '';
    public string $estadoSerieExtra = 'DISPONIBLE';
    public string $observacionSerieExtra = '';

    public bool $mostrarToast = false;
    public string $toastMensaje = '';
    public string $toastTipo = 'success';


    public function mount(): void
    {
        $this->cargarCatalogos();
        $this->cargarVistaActual();
    }


    public function updatedBuscar(): void
    {
        $this->cargarVistaActual();
    }


    public function updatedFiltroCategoria(): void
    {
        $this->cargarVistaActual();
    }

    public function updatedFiltroMarca(): void
    {
        $this->cargarVistaActual();
    }

    public function updatedFiltroEstado(): void
    {
        $this->cargarVistaActual();
    }

    public function cambiarVista(string $vista): void
    {
        $this->vista = $vista;
        $this->filtroEstado = '';
        $this->cargarVistaActual();
    }

    public function limpiarFiltros(): void
    {
        $this->buscar = '';
        $this->filtroCategoria = '';
        $this->filtroMarca = '';
        $this->filtroEstado = '';
        $this->cargarVistaActual();
    }


    protected function cargarCatalogos(): void
    {
        $this->categorias = DB::table('categoria_producto')
            ->select('Id_Categoria', 'Nombre_Categoria')
            ->orderBy('Nombre_Categoria')
            ->get()
            ->map(fn ($categoria) => [
                'id' => (string) $categoria->Id_Categoria,
                'nombre' => $categoria->Nombre_Categoria,
            ])
            ->toArray();

        $this->marcas = DB::table('marca')
            ->select('Id_Marca', 'Nombre_Marca')
            ->orderBy('Nombre_Marca')
            ->get()
            ->map(fn ($marca) => [
                'id' => (string) $marca->Id_Marca,
                'nombre' => $marca->Nombre_Marca,
            ])
            ->toArray();
    }


    protected function cargarVistaActual(): void
    {
        if ($this->vista === 'productos') {
            $this->cargarProductosAgrupados();
            return;
        }

        $this->cargarSeriesInventario();
    }


    protected function cargarProductosAgrupados(): void
    {
        $busqueda = trim($this->buscar);

        $query = DB::table('producto as p')
            ->leftJoin('categoria_producto as c', 'p.Id_Categoria', '=', 'c.Id_Categoria')
            ->leftJoin('marca as m', 'p.Id_Marca', '=', 'm.Id_Marca')
            ->leftJoin('producto_serie as ps', 'p.Id_Producto', '=', 'ps.Id_Producto')
            ->selectRaw('
                p.Id_Producto,
                p.Nombre_Producto,
                p.Modelo,
                p.Stock_Actual,
                p.Stock_Minimo,
                p.Precio_Venta,
                p.Fecha_Vencimiento,
                p.Meses_Garantia_Nuevo,
                p.Meses_Garantia_Usado,
                p.Estado,
                c.Nombre_Categoria as categoria,
                m.Nombre_Marca as marca,
                COUNT(ps.id_producto_serie) as total_series
            ')
            ->groupBy(
                'p.Id_Producto',
                'p.Nombre_Producto',
                'p.Modelo',
                'p.Stock_Actual',
                'p.Stock_Minimo',
                'p.Precio_Venta',
                'p.Fecha_Vencimiento',
                'p.Meses_Garantia_Nuevo',
                'p.Meses_Garantia_Usado',
                'p.Estado',
                'c.Nombre_Categoria',
                'm.Nombre_Marca'
            )
            ->orderByDesc('p.Id_Producto');

        if ($this->filtroCategoria !== '') {
            $query->where('p.Id_Categoria', (int) $this->filtroCategoria);
        }

        if ($this->filtroMarca !== '') {
            $query->where('p.Id_Marca', (int) $this->filtroMarca);
        }

        if ($this->filtroEstado === '1' || $this->filtroEstado === '0') {
            $query->where('p.Estado', (int) $this->filtroEstado);
        }

        if ($this->filtroEstado === 'stock_bajo') {
            $query->whereColumn('p.Stock_Actual', '<=', 'p.Stock_Minimo');
        }

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('p.Nombre_Producto', 'like', "%{$busqueda}%")
                    ->orWhere('p.Modelo', 'like', "%{$busqueda}%")
                    ->orWhere('c.Nombre_Categoria', 'like', "%{$busqueda}%")
                    ->orWhere('m.Nombre_Marca', 'like', "%{$busqueda}%")
                    ->orWhereExists(function ($sub) use ($busqueda) {
                        $sub->select(DB::raw(1))
                            ->from('producto_serie as ps2')
                            ->whereColumn('ps2.Id_Producto', 'p.Id_Producto')
                            ->where('ps2.Numero_Serie', 'like', "%{$busqueda}%");
                    });
            });
        }

        $this->filasProductos = $query->get()
            ->map(function ($producto) {
                $stock = (int) $producto->Stock_Actual;
                $stockMinimo = (int) $producto->Stock_Minimo;

                return [
                    'id_producto' => (int) $producto->Id_Producto,
                    'codigo' => '#' . $producto->Id_Producto,
                    'producto' => $producto->Nombre_Producto,
                    'marca' => $producto->marca ?: '—',
                    'modelo' => $producto->Modelo ?: '—',
                    'categoria' => $producto->categoria ?: '—',
                    'series' => (int) $producto->total_series,
                    'stock' => $stock,
                    'stock_minimo' => $stockMinimo,
                    'stock_bajo' => $stock <= $stockMinimo && $stockMinimo > 0,
                    'precio_venta' => 'C$ ' . number_format((float) $producto->Precio_Venta, 2),
                    'estado' => (int) $producto->Estado === 1 ? 'Activo' : 'Inactivo',
                ];
            })
            ->toArray();
    }


    protected function cargarSeriesInventario(): void
    {
        $busqueda = trim($this->buscar);

        $query = DB::table('producto_serie as ps')
            ->join('producto as p', 'ps.Id_Producto', '=', 'p.Id_Producto')
            ->leftJoin('categoria_producto as c', 'p.Id_Categoria', '=', 'c.Id_Categoria')
            ->leftJoin('marca as m', 'p.Id_Marca', '=', 'm.Id_Marca')
            ->select(
                'ps.id_producto_serie',
                'ps.Numero_Serie',
                'ps.Fecha_Ingreso',
                'ps.Estado as estado_serie',
                'ps.Observacion',
                'p.Id_Producto',
                'p.Nombre_Producto',
                'p.Modelo',
                'c.Nombre_Categoria as categoria',
                'm.Nombre_Marca as marca'
            )
            ->orderByDesc('ps.id_producto_serie');

        if ($this->filtroCategoria !== '') {
            $query->where('p.Id_Categoria', (int) $this->filtroCategoria);
        }

        if ($this->filtroMarca !== '') {
            $query->where('p.Id_Marca', (int) $this->filtroMarca);
        }

        if ($this->filtroEstado !== '') {
            $query->where('ps.Estado', $this->filtroEstado);
        }

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('p.Nombre_Producto', 'like', "%{$busqueda}%")
                    ->orWhere('p.Modelo', 'like', "%{$busqueda}%")
                    ->orWhere('c.Nombre_Categoria', 'like', "%{$busqueda}%")
                    ->orWhere('m.Nombre_Marca', 'like', "%{$busqueda}%")
                    ->orWhere('ps.Numero_Serie', 'like', "%{$busqueda}%")
                    ->orWhere('ps.Observacion', 'like', "%{$busqueda}%");
            });
        }

        $this->filasSeries = $query->get()
            ->map(function ($serie) {
                return [
                    'id_serie' => (int) $serie->id_producto_serie,
                    'id_producto' => (int) $serie->Id_Producto,
                    'producto' => $serie->Nombre_Producto,
                    'marca' => $serie->marca ?: '—',
                    'modelo' => $serie->Modelo ?: '—',
                    'numero_serie' => $serie->Numero_Serie ?: '—',
                    'fecha_ingreso' => $serie->Fecha_Ingreso
                        ? \Carbon\Carbon::parse($serie->Fecha_Ingreso)->format('d/m/Y H:i')
                        : '—',
                    'estado_serie' => $serie->estado_serie ?: '—',
                    'observacion' => $serie->Observacion ?: '—',
                ];
            })
            ->toArray();
    }


    public function abrirDetalleProducto(int $idProducto): void
    {
        $producto = DB::table('producto as p')
            ->leftJoin('categoria_producto as c', 'p.Id_Categoria', '=', 'c.Id_Categoria')
            ->leftJoin('marca as m', 'p.Id_Marca', '=', 'm.Id_Marca')
            ->leftJoin('producto_serie as ps', 'p.Id_Producto', '=', 'ps.Id_Producto')
            ->selectRaw('
                p.Id_Producto,
                p.Nombre_Producto,
                p.Modelo,
                p.Stock_Actual,
                p.Stock_Minimo,
                p.Precio_Venta,
                p.Fecha_Vencimiento,
                p.Meses_Garantia_Nuevo,
                p.Meses_Garantia_Usado,
                p.Estado,
                c.Nombre_Categoria as categoria,
                m.Nombre_Marca as marca,
                COUNT(ps.id_producto_serie) as total_series
            ')
            ->where('p.Id_Producto', $idProducto)
            ->groupBy(
                'p.Id_Producto',
                'p.Nombre_Producto',
                'p.Modelo',
                'p.Stock_Actual',
                'p.Stock_Minimo',
                'p.Precio_Venta',
                'p.Fecha_Vencimiento',
                'p.Meses_Garantia_Nuevo',
                'p.Meses_Garantia_Usado',
                'p.Estado',
                'c.Nombre_Categoria',
                'm.Nombre_Marca'
            )
            ->first();

        if (!$producto) {
            return;
        }

        $this->detalleProducto = [
            'id' => (int) $producto->Id_Producto,
            'codigo' => '#' . $producto->Id_Producto,
            'producto' => $producto->Nombre_Producto,
            'categoria' => $producto->categoria ?: '—',
            'marca' => $producto->marca ?: '—',
            'modelo' => $producto->Modelo ?: '—',
            'stock_actual' => (int) $producto->Stock_Actual,
            'stock_minimo' => (int) $producto->Stock_Minimo,
            'precio_venta' => 'C$ ' . number_format((float) $producto->Precio_Venta, 2),
            'fecha_vencimiento' => $producto->Fecha_Vencimiento
                ? \Carbon\Carbon::parse($producto->Fecha_Vencimiento)->format('d/m/Y')
                : 'No vence',
            'garantia_nuevo' => $producto->Meses_Garantia_Nuevo !== null ? $producto->Meses_Garantia_Nuevo . ' meses' : '—',
            'garantia_usado' => $producto->Meses_Garantia_Usado !== null ? $producto->Meses_Garantia_Usado . ' meses' : '—',
            'estado' => (int) $producto->Estado === 1 ? 'Activo' : 'Inactivo',
            'series' => (int) $producto->total_series,
        ];

        $this->modalDetalle = true;
    }


    public function cerrarModalDetalle(): void
    {
        $this->modalDetalle = false;
        $this->detalleProducto = [];
    }

    public function verSeries(int $idProducto): void
    {
        $producto = DB::table('producto')
            ->select('Id_Producto', 'Nombre_Producto', 'Modelo')
            ->where('Id_Producto', $idProducto)
            ->first();

        $this->productoNombreSeries = $producto
            ? trim($producto->Nombre_Producto . ($producto->Modelo ? ' - ' . $producto->Modelo : ''))
            : 'Producto';

        $this->seriesProducto = DB::table('producto_serie')
            ->where('Id_Producto', $idProducto)
            ->orderByDesc('id_producto_serie')
            ->get([
                'Numero_Serie',
                'Fecha_Ingreso',
                'Estado',
                'Observacion',
            ])
            ->map(function ($serie) {
                return [
                    'numero_serie' => $serie->Numero_Serie ?: '—',
                    'fecha_ingreso' => $serie->Fecha_Ingreso
                        ? \Carbon\Carbon::parse($serie->Fecha_Ingreso)->format('d/m/Y H:i')
                        : '—',
                    'estado' => $serie->Estado ?: '—',
                    'observacion' => $serie->Observacion ?: '—',
                ];
            })
            ->toArray();

        $this->modalSeries = true;
    }


    public function cerrarModalSeries(): void
    {
        $this->modalSeries = false;
        $this->productoNombreSeries = '';
        $this->seriesProducto = [];
    }

    public function abrirModalAgregarSerie(int $idProducto): void
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $producto = DB::table('producto')
            ->leftJoin('marca as m', 'producto.Id_Marca', '=', 'm.Id_Marca')
            ->select(
                'producto.Id_Producto',
                'producto.Nombre_Producto',
                'producto.Modelo',
                'm.Nombre_Marca'
            )
            ->where('producto.Id_Producto', $idProducto)
            ->first();

        $this->productoIdAgregarSerie = $idProducto;
        $this->productoNombreAgregarSerie = $producto
            ? trim(($producto->Nombre_Marca ? $producto->Nombre_Marca . ' ' : '') . $producto->Nombre_Producto . ($producto->Modelo ? ' - ' . $producto->Modelo : ''))
            : 'Producto';

        $this->numeroSerieExtra = '';
        $this->observacionSerieExtra = '';
        $this->estadoSerieExtra = 'DISPONIBLE';
        $this->modalAgregarSerie = true;
    }

    public function cerrarModalAgregarSerie(): void
    {
        $this->modalAgregarSerie = false;
        $this->productoIdAgregarSerie = 0;
        $this->productoNombreAgregarSerie = '';
        $this->numeroSerieExtra = '';
        $this->observacionSerieExtra = '';
        $this->estadoSerieExtra = 'DISPONIBLE';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function guardarSerieProducto(): void
    {
        $this->resetErrorBag();

        $datos = $this->validate([
            'productoIdAgregarSerie' => 'required|integer|exists:producto,Id_Producto',
            'numeroSerieExtra' => 'required|string|max:100|unique:producto_serie,Numero_Serie',
            'estadoSerieExtra' => 'required|string|max:50',
            'observacionSerieExtra' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($datos) {
            $serie = new ProductoSerie();
            $serie->Id_Producto = (int) $datos['productoIdAgregarSerie'];
            $serie->Numero_Serie = trim($datos['numeroSerieExtra']);
            $serie->Fecha_Ingreso = now();
            $serie->Estado = trim($datos['estadoSerieExtra']);
            $serie->Observacion = $datos['observacionSerieExtra'] !== '' ? trim($datos['observacionSerieExtra']) : null;
            $serie->save();

            Producto::where('Id_Producto', (int) $datos['productoIdAgregarSerie'])
                ->increment('Stock_Actual');
        });

        $this->cerrarModalAgregarSerie();
        $this->cargarVistaActual();
        $this->mostrarToast('Serie agregada correctamente.');
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

<div
    class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-hidden bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="flex shrink-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1A2B42]">Listado completo de productos</h1>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Centro de control del inventario.
            </p>
        </div>

        <a href="{{ route('productos.index') }}"
            class="inline-flex h-10 min-h-10 items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] transition hover:bg-[#F0F3F7]">
            Volver a productos
        </a>
    </div>

    @if ($mostrarToast)
    <div class="fixed right-5 top-5 z-[9999] w-full max-w-sm">
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

    <x-card class="flex min-h-0 flex-1 flex-col rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4 flex shrink-0 flex-col gap-3">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <h2 class="text-xl font-bold text-[#1A2B42]">Inventario completo</h2>

                <div class="inline-flex rounded-xl bg-[#EAF4FD] p-1">
                    <button type="button" wire:click="cambiarVista('productos')"
                        class="{{ $vista === 'productos' ? 'bg-[#2E8BC0] text-white shadow-sm' : 'text-[#1A2B42]' }} rounded-lg px-4 py-2 text-sm font-semibold transition">
                        Productos
                    </button>

                    <button type="button" wire:click="cambiarVista('series')"
                        class="{{ $vista === 'series' ? 'bg-[#2E8BC0] text-white shadow-sm' : 'text-[#1A2B42]' }} rounded-lg px-4 py-2 text-sm font-semibold transition">
                        Series
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 xl:grid-cols-5">
                <div class="xl:col-span-2">
                    <x-input wire:model.live.debounce.250ms="buscar" type="text"
                        placeholder="{{ $vista === 'productos' ? 'Buscar por producto, modelo, marca, categoría o serie' : 'Buscar por serie, producto, modelo o marca' }}"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>

                <div>
                    <select wire:model.live="filtroCategoria"
                        class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $categoria)
                        <option value="{{ $categoria['id'] }}">{{ $categoria['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select wire:model.live="filtroMarca"
                        class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                        <option value="">Todas las marcas</option>
                        @foreach ($marcas as $marca)
                        <option value="{{ $marca['id'] }}">{{ $marca['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <select wire:model.live="filtroEstado"
                        class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                        @if ($vista === 'productos')
                        <option value="">Todos los estados</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                        <option value="stock_bajo">Stock bajo</option>
                        @else
                        <option value="">Todos los estados de serie</option>
                        <option value="DISPONIBLE">Disponible</option>
                        <option value="VENDIDO">Vendido</option>
                        <option value="RESERVADO">Reservado</option>
                        <option value="DAÑADO">Dañado</option>
                        @endif
                    </select>

                    <button type="button" wire:click="limpiarFiltros"
                        class="inline-flex h-10 min-h-10 shrink-0 items-center justify-center rounded-lg border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] transition hover:bg-[#F0F3F7]">
                        Limpiar
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-sm text-[#5F6B7A]">
                <span class="inline-flex rounded-full bg-[#EAF4FD] px-3 py-1 font-medium text-[#0E48A1]">
                    {{ $vista === 'productos' ? count($filasProductos) : count($filasSeries) }} resultados
                </span>

                @if ($vista === 'productos')
                <span class="inline-flex rounded-full bg-[#F7F9FC] px-3 py-1">
                    Vista agrupada por producto
                </span>
                @else
                <span class="inline-flex rounded-full bg-[#F7F9FC] px-3 py-1">
                    Vista por unidad serializada
                </span>
                @endif
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-hidden rounded-xl border border-[#D7E4F3] bg-white">
            @if ($vista === 'productos')
            <div class="h-full overflow-x-auto overflow-y-auto">
                <table class="min-w-[1150px] w-full border-separate border-spacing-0 text-sm text-[#1A2B42]">
                    <thead class="sticky top-0 z-10">
                        <tr>
                            <th
                                class="rounded-tl-xl bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Código</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Producto</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Marca</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Modelo</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Categoría</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-center font-semibold text-white whitespace-nowrap">
                                Series</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-center font-semibold text-white whitespace-nowrap">
                                Stock</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-right font-semibold text-white whitespace-nowrap">
                                Precio venta</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-center font-semibold text-white whitespace-nowrap">
                                Estado</th>
                            <th
                                class="rounded-tr-xl bg-[#2E8BC0] px-4 py-3 text-center font-semibold text-white whitespace-nowrap">
                                Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($filasProductos as $fila)
                        <tr class="odd:bg-white even:bg-[#F8FBFF]">
                            <td class="px-4 py-3 align-middle whitespace-nowrap font-semibold">{{ $fila['codigo'] }}
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap font-medium">{{ $fila['producto'] }}
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap">{{ $fila['marca'] }}</td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap">{{ $fila['modelo'] }}</td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap">{{ $fila['categoria'] }}</td>
                            <td class="px-4 py-3 text-center align-middle whitespace-nowrap">
                                <span
                                    class="inline-flex min-w-[34px] justify-center rounded-full bg-[#EAF4FD] px-2.5 py-1 text-xs font-semibold text-[#0E48A1]">
                                    {{ $fila['series'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center align-middle whitespace-nowrap">
                                <span
                                    class="{{ $fila['stock_bajo'] ? 'bg-red-100 text-red-700' : 'bg-[#F0F3F7] text-[#1A2B42]' }} inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                                    {{ $fila['stock'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right align-middle whitespace-nowrap">{{ $fila['precio_venta'] }}
                            </td>
                            <td class="px-4 py-3 text-center align-middle whitespace-nowrap">
                                <span
                                    class="{{ $fila['estado'] === 'Activo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                                    {{ $fila['estado'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center align-middle whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <x-button label="Detalle"
                                        wire:click="abrirDetalleProducto({{ $fila['id_producto'] }})"
                                        class="h-8 min-h-8 border-0 bg-white px-3 text-xs text-[#1A2B42] ring-1 ring-[#D7E4F3] hover:bg-[#F0F3F7]" />

                                    <x-button label="Series" wire:click="verSeries({{ $fila['id_producto'] }})"
                                        class="h-8 min-h-8 border-0 bg-[#2E8BC0] px-3 text-xs text-white hover:bg-[#0B6FE4]" />

                                    <x-button label="+ Serie"
                                        wire:click="abrirModalAgregarSerie({{ $fila['id_producto'] }})"
                                        class="h-8 min-h-8 border-0 bg-[#0E48A1] px-3 text-xs text-white hover:bg-[#0B6FE4]" />
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-sm text-[#7B8794]">
                                No hay productos que coincidan con los filtros.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @else
            <div class="h-full overflow-x-auto overflow-y-auto">
                <table class="min-w-[1200px] w-full border-separate border-spacing-0 text-sm text-[#1A2B42]">
                    <thead class="sticky top-0 z-10">
                        <tr>
                            <th
                                class="rounded-tl-xl bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Producto</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Marca</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Modelo</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Número de serie</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Fecha ingreso</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-center font-semibold text-white whitespace-nowrap">
                                Estado serie</th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Observación</th>
                            <th
                                class="rounded-tr-xl bg-[#2E8BC0] px-4 py-3 text-center font-semibold text-white whitespace-nowrap">
                                Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($filasSeries as $fila)
                        <tr class="odd:bg-white even:bg-[#F8FBFF]">
                            <td class="px-4 py-3 align-middle whitespace-nowrap font-medium">{{ $fila['producto'] }}
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap">{{ $fila['marca'] }}</td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap">{{ $fila['modelo'] }}</td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap font-semibold">{{ $fila['numero_serie']
                                }}</td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap">{{ $fila['fecha_ingreso'] }}</td>
                            <td class="px-4 py-3 text-center align-middle whitespace-nowrap">
                                <span
                                    class="inline-flex rounded-full bg-[#EAF4FD] px-2.5 py-1 text-xs font-semibold text-[#0E48A1]">
                                    {{ $fila['estado_serie'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle">{{ $fila['observacion'] }}</td>
                            <td class="px-4 py-3 text-center align-middle whitespace-nowrap">
                                <x-button label="Ver producto"
                                    wire:click="abrirDetalleProducto({{ $fila['id_producto'] }})"
                                    class="h-8 min-h-8 border-0 bg-[#2E8BC0] px-3 text-xs text-white hover:bg-[#0B6FE4]" />
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-[#7B8794]">
                                No hay series que coincidan con los filtros.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </x-card>

    <x-modal wire:model="modalDetalle" class="backdrop-blur-sm"
        box-class="w-full max-w-4xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">
        <div class="mb-5">
            <h3 class="text-2xl font-bold text-[#1A2B42]">Detalle del producto</h3>
            <p class="mt-1 text-sm text-[#5F6B7A]">{{ $detalleProducto['producto'] ?? '' }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Código</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['codigo'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Categoría</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['categoria'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Marca</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['marca'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Modelo</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['modelo'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Stock actual</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['stock_actual'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Stock mínimo</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['stock_minimo'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Precio venta</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['precio_venta'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Garantía nuevo</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['garantia_nuevo'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Garantía usado</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['garantia_usado'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Fecha de vencimiento</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['fecha_vencimiento'] ?? '—' }}
                </p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Series registradas</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['series'] ?? '—' }}</p>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" type="button" wire:click="cerrarModalDetalle"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalSeries" class="backdrop-blur-sm"
        box-class="w-full max-w-5xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">
        <div class="mb-5">
            <h3 class="text-2xl font-bold text-[#1A2B42]">Series del producto</h3>
            <p class="mt-1 text-sm text-[#5F6B7A]">{{ $productoNombreSeries }}</p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-[#D7E4F3] bg-white">
            <table class="w-full border-separate border-spacing-0 text-sm text-[#1A2B42]">
                <thead>
                    <tr>
                        <th
                            class="rounded-tl-xl bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                            Número de serie</th>
                        <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">Fecha
                            ingreso</th>
                        <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">Estado
                        </th>
                        <th
                            class="rounded-tr-xl bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                            Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($seriesProducto as $serie)
                    <tr class="odd:bg-white even:bg-[#F8FBFF]">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $serie['numero_serie'] }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $serie['fecha_ingreso'] }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $serie['estado'] }}</td>
                        <td class="px-4 py-3">{{ $serie['observacion'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-[#7B8794]">
                            Este producto no tiene series registradas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" type="button" wire:click="cerrarModalSeries"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalAgregarSerie" class="backdrop-blur-sm"
        box-class="w-full max-w-2xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">
        <div class="mb-5">
            <h3 class="text-2xl font-bold text-[#1A2B42]">Agregar serie</h3>
            <p class="mt-1 text-sm text-[#5F6B7A]">{{ $productoNombreAgregarSerie }}</p>
        </div>

        <div class="space-y-4">
            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Número de serie</label>
                <x-input type="text" wire:model.defer="numeroSerieExtra" placeholder="Ingrese el número de serie"
                    class="w-full rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]" />
                @error('numeroSerieExtra')
                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Estado</label>
                <select wire:model.defer="estadoSerieExtra"
                    class="w-full rounded-xl border-0 bg-[#F0F3F7] px-3 py-2 text-[#1A2B42]">
                    <option value="DISPONIBLE">Disponible</option>
                    <option value="VENDIDO">Vendido</option>
                    <option value="RESERVADO">Reservado</option>
                    <option value="DAÑADO">Dañado</option>
                </select>
                @error('estadoSerieExtra')
                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Observación</label>
                <textarea wire:model.defer="observacionSerieExtra" rows="3"
                    class="w-full rounded-xl border-0 bg-[#F0F3F7] px-3 py-2 text-[#1A2B42] placeholder:text-[#7B8794]"
                    placeholder="Opcional"></textarea>
                @error('observacionSerieExtra')
                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <x-slot:actions>
                <x-button label="Cancelar" type="button" wire:click="cerrarModalAgregarSerie"
                    class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />

                <x-button label="Guardar serie" type="button" wire:click="guardarSerieProducto"
                    class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]" />
            </x-slot:actions>
        </div>
    </x-modal>
</div>
