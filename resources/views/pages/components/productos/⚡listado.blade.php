<?php

use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $vista = 'productos';

    public string $buscar = '';
    public string $filtroCategoria = '';
    public string $filtroMarca = '';
    public string $filtroEstado = '';

    public int $pagina = 1;
    public int $porPagina = 6;
    public int $totalRegistros = 0;
    public int $totalPaginas = 1;

    public array $categorias = [];
    public array $marcas = [];
    public array $filasProductos = [];
    public array $filasSeries = [];

    public bool $modalDetalle = false;
    public bool $modalSeries = false;
    public bool $modalDetalleSerie = false;

    public array $detalleProducto = [];
    public array $seriesProducto = [];
    public array $detalleSerie = [];

    public string $productoNombreSeries = '';

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
        $this->reiniciarPagina();
        $this->cargarVistaActual();
    }

    public function updatedFiltroCategoria(): void
    {
        $this->reiniciarPagina();
        $this->cargarVistaActual();
    }

    public function updatedFiltroMarca(): void
    {
        $this->reiniciarPagina();
        $this->cargarVistaActual();
    }

    public function updatedFiltroEstado(): void
    {
        $this->reiniciarPagina();
        $this->cargarVistaActual();
    }

    public function cambiarVista(string $vista): void
    {
        $this->vista = $vista;
        $this->filtroEstado = '';
        $this->reiniciarPagina();
        $this->cargarVistaActual();
    }

    public function limpiarFiltros(): void
    {
        $this->buscar = '';
        $this->filtroCategoria = '';
        $this->filtroMarca = '';
        $this->filtroEstado = '';
        $this->reiniciarPagina();
        $this->cargarVistaActual();
    }

    protected function reiniciarPagina(): void
    {
        $this->pagina = 1;
    }

    protected function normalizarPaginacion(int $total): void
    {
        $this->totalRegistros = $total;
        $this->totalPaginas = max(1, (int) ceil($total / max(1, $this->porPagina)));

        if ($this->pagina > $this->totalPaginas) {
            $this->pagina = $this->totalPaginas;
        }

        if ($this->pagina < 1) {
            $this->pagina = 1;
        }
    }

    public function paginaAnterior(): void
    {
        if ($this->pagina <= 1) {
            return;
        }

        $this->pagina--;
        $this->cargarVistaActual();
    }

    public function paginaSiguiente(): void
    {
        if ($this->pagina >= $this->totalPaginas) {
            return;
        }

        $this->pagina++;
        $this->cargarVistaActual();
    }

    public function irPagina(int $pagina): void
    {
        $this->pagina = max(1, min($pagina, $this->totalPaginas));
        $this->cargarVistaActual();
    }

    public function paginasVisibles(): array
    {
        $inicio = max(1, $this->pagina - 2);
        $fin = min($this->totalPaginas, $this->pagina + 2);

        return range($inicio, $fin);
    }

    public function rangoDesde(): int
    {
        if ($this->totalRegistros === 0) {
            return 0;
        }

        return (($this->pagina - 1) * $this->porPagina) + 1;
    }

    public function rangoHasta(): int
    {
        return min($this->totalRegistros, $this->pagina * $this->porPagina);
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
            ->where('p.Estado', 1)
            ->where('p.Stock_Actual', '>', 0)
            ->select(
                'p.Id_Producto',
                'p.Nombre_Producto',
                'p.Modelo',
                'p.Stock_Actual',
                'p.Stock_Minimo',
                'p.Precio_Venta',
                'p.Fecha_Vencimiento',
                'p.Meses_Garantia_Nuevo',
                'p.Meses_Garantia_Usado',
                'c.Nombre_Categoria as categoria',
                'm.Nombre_Marca as marca'
            )
            ->selectSub(function ($sub) {
                $sub->from('producto_serie as ps')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('ps.Id_Producto', 'p.Id_Producto')
                    ->where('ps.Estado', '<>', 'VENDIDO');
            }, 'total_series');

        if ($this->filtroCategoria !== '') {
            $query->where('p.Id_Categoria', (int) $this->filtroCategoria);
        }

        if ($this->filtroMarca !== '') {
            $query->where('p.Id_Marca', (int) $this->filtroMarca);
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
                            ->where('ps2.Estado', '<>', 'VENDIDO')
                            ->where('ps2.Numero_Serie', 'like', "%{$busqueda}%");
                    });
            });
        }

        $total = (clone $query)->count('p.Id_Producto');
        $this->normalizarPaginacion($total);

        $this->filasProductos = $query
            ->orderByDesc('p.Id_Producto')
            ->forPage($this->pagina, $this->porPagina)
            ->get()
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
                    'precio_venta' => 'C$ ' . number_format((float) $producto->Precio_Venta, 0, '.', ','),
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
            ->where('ps.Estado', '<>', 'VENDIDO')
            ->where('p.Estado', 1)
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
            );

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

        $total = (clone $query)->count('ps.id_producto_serie');
        $this->normalizarPaginacion($total);

        $this->filasSeries = $query
            ->orderByDesc('ps.id_producto_serie')
            ->forPage($this->pagina, $this->porPagina)
            ->get()
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
            ->where('p.Estado', 1)
            ->where('p.Stock_Actual', '>', 0)
            ->select(
                'p.Id_Producto',
                'p.Nombre_Producto',
                'p.Modelo',
                'p.Stock_Actual',
                'p.Stock_Minimo',
                'p.Precio_Venta',
                'p.Fecha_Vencimiento',
                'p.Meses_Garantia_Nuevo',
                'p.Meses_Garantia_Usado',
                'c.Nombre_Categoria as categoria',
                'm.Nombre_Marca as marca'
            )
            ->where('p.Id_Producto', $idProducto)
            ->first();

        if (! $producto) {
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
            'precio_venta' => 'C$ ' . number_format((float) $producto->Precio_Venta, 0, '.', ','),
            'fecha_vencimiento' => $producto->Fecha_Vencimiento
                ? \Carbon\Carbon::parse($producto->Fecha_Vencimiento)->format('d/m/Y')
                : 'No vence',
            'garantia_nuevo' => $producto->Meses_Garantia_Nuevo !== null ? $producto->Meses_Garantia_Nuevo . ' meses' : '—',
            'garantia_usado' => $producto->Meses_Garantia_Usado !== null ? $producto->Meses_Garantia_Usado . ' meses' : '—',
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
            ->where('Estado', 1)
            ->where('Stock_Actual', '>', 0)
            ->first();

        if (! $producto) {
            return;
        }

        $this->productoNombreSeries = trim(
            $producto->Nombre_Producto . ($producto->Modelo ? ' - ' . $producto->Modelo : '')
        );

        $this->seriesProducto = DB::table('producto_serie')
            ->where('Id_Producto', $idProducto)
            ->where('Estado', '<>', 'VENDIDO')
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

    public function abrirDetalleSerie(int $idSerie): void
    {
        $serie = DB::table('producto_serie as ps')
            ->join('producto as p', 'ps.Id_Producto', '=', 'p.Id_Producto')
            ->leftJoin('categoria_producto as c', 'p.Id_Categoria', '=', 'c.Id_Categoria')
            ->leftJoin('marca as m', 'p.Id_Marca', '=', 'm.Id_Marca')
            ->where('ps.id_producto_serie', $idSerie)
            ->where('ps.Estado', '<>', 'VENDIDO')
            ->where('p.Estado', 1)
            ->select(
                'ps.id_producto_serie',
                'ps.Numero_Serie',
                'ps.Fecha_Ingreso',
                'ps.Estado as estado_serie',
                'ps.Observacion',
                'p.Nombre_Producto',
                'p.Modelo',
                'p.Precio_Venta',
                'p.Meses_Garantia_Nuevo',
                'p.Meses_Garantia_Usado',
                'c.Nombre_Categoria as categoria',
                'm.Nombre_Marca as marca'
            )
            ->first();

        if (! $serie) {
            return;
        }

        $this->detalleSerie = [
            'id_serie' => (int) $serie->id_producto_serie,
            'producto' => $serie->Nombre_Producto,
            'categoria' => $serie->categoria ?: '—',
            'marca' => $serie->marca ?: '—',
            'modelo' => $serie->Modelo ?: '—',
            'numero_serie' => $serie->Numero_Serie ?: '—',
            'estado_serie' => $serie->estado_serie ?: '—',
            'fecha_ingreso' => $serie->Fecha_Ingreso
                ? \Carbon\Carbon::parse($serie->Fecha_Ingreso)->format('d/m/Y H:i')
                : '—',
            'precio_venta' => 'C$ ' . number_format((float) $serie->Precio_Venta, 0, '.', ','),
            'garantia_nuevo' => $serie->Meses_Garantia_Nuevo !== null ? $serie->Meses_Garantia_Nuevo . ' meses' : '—',
            'garantia_usado' => $serie->Meses_Garantia_Usado !== null ? $serie->Meses_Garantia_Usado . ' meses' : '—',
            'observacion' => $serie->Observacion ?: '—',
        ];

        $this->modalDetalleSerie = true;
    }

    public function cerrarModalDetalleSerie(): void
    {
        $this->modalDetalleSerie = false;
        $this->detalleSerie = [];
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

<div class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col overflow-hidden bg-[#F0F3F7] px-3 py-3 md:px-5">
    <div class="mx-auto flex h-full min-h-0 w-full max-w-[1320px] flex-col gap-3">

        <div class="flex shrink-0 flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold leading-tight text-[#1A2B42] md:text-[28px]">
                    Listado completo de productos
                </h1>
                <p class="mt-0.5 text-sm text-[#5F6B7A]">
                    Centro de control del inventario.
                </p>
            </div>

            <a href="{{ route('productos.index') }}"
                class="inline-flex h-10 min-h-10 items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] shadow-sm transition hover:bg-[#F0F3F7]">
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

        <x-card class="flex min-h-0 flex-1 flex-col rounded-2xl border border-[#D7E4F3] bg-white p-3 shadow-sm md:p-4">

            <div class="flex shrink-0 flex-col gap-3">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-bold leading-tight text-[#1A2B42]">Inventario completo</h2>
                        <p class="text-xs text-[#5F6B7A]">
                            Filtre y revise solamente productos disponibles en inventario.
                        </p>
                    </div>

                    <div class="inline-flex w-fit rounded-xl bg-[#EAF4FD] p-1">
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

                <div class="grid shrink-0 grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-4">
                        <x-input wire:model.live.debounce.350ms="buscar" type="text"
                            placeholder="{{ $vista === 'productos' ? 'Buscar producto, modelo, marca, categoría o serie' : 'Buscar serie, producto, modelo o marca' }}"
                            class="h-10 min-h-10 w-full rounded-xl border-0 bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                    </div>

                    <div class="xl:col-span-2">
                        <select wire:model.live="filtroCategoria"
                            class="h-10 min-h-10 w-full rounded-xl border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                            <option value="">Categorías</option>
                            @foreach ($categorias as $categoria)
                            <option value="{{ $categoria['id'] }}">{{ $categoria['nombre'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <select wire:model.live="filtroMarca"
                            class="h-10 min-h-10 w-full rounded-xl border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                            <option value="">Marcas</option>
                            @foreach ($marcas as $marca)
                            <option value="{{ $marca['id'] }}">{{ $marca['nombre'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <select wire:model.live="filtroEstado"
                            class="h-10 min-h-10 w-full rounded-xl border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                            @if ($vista === 'productos')
                            <option value="">Estados</option>
                            <option value="stock_bajo">Stock bajo</option>
                            @else
                            <option value="">Estados de serie</option>
                            <option value="DISPONIBLE">Disponible</option>
                            <option value="RESERVADO">Reservado</option>
                            <option value="DAÑADO">Dañado</option>
                            @endif
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <button type="button" wire:click="limpiarFiltros"
                            class="inline-flex h-10 min-h-10 w-full items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] transition hover:bg-[#F0F3F7]">
                            Limpiar
                        </button>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2 text-xs text-[#5F6B7A]">
                    <span class="inline-flex rounded-full bg-[#EAF4FD] px-3 py-1 font-semibold text-[#0E48A1]">
                        {{ $totalRegistros }} resultados
                    </span>

                    <span class="inline-flex rounded-full bg-[#F7F9FC] px-3 py-1">
                        {{ $vista === 'productos' ? 'Productos disponibles' : 'Series disponibles / no vendidas' }}
                    </span>
                </div>
            </div>

            <div class="mt-3 min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white">
                @if ($vista === 'productos')
                <div class="h-full w-full overflow-auto">
                    <table
                        class="min-w-[900px] w-full table-fixed border-separate border-spacing-0 text-[13px] text-[#1A2B42]">
                        <colgroup>
                            <col class="w-[78px]">
                            <col class="w-[235px]">
                            <col class="w-[110px]">
                            <col class="w-[135px]">
                            <col class="w-[135px]">
                            <col class="w-[65px]">
                            <col class="w-[70px]">
                            <col class="w-[110px]">
                            <col class="w-[74px]">
                        </colgroup>

                        <thead class="sticky top-0 z-10">
                            <tr>
                                <th class="rounded-tl-xl bg-[#2E8BC0] px-3 py-2.5 text-left font-semibold text-white">
                                    Código</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-left font-semibold text-white">Producto</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-left font-semibold text-white">Marca</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-left font-semibold text-white">Modelo</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-left font-semibold text-white">Categoría</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-center font-semibold text-white">Series</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-center font-semibold text-white">Stock</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-right font-semibold text-white">Precio</th>
                                <th class="rounded-tr-xl bg-[#2E8BC0] px-3 py-2.5 text-center font-semibold text-white">
                                    Ver</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($filasProductos as $fila)
                            <tr class="odd:bg-white even:bg-[#F8FBFF]">
                                <td class="px-3 py-2.5 align-middle font-semibold whitespace-nowrap">
                                    {{ $fila['codigo'] }}
                                </td>

                                <td class="px-3 py-2.5 align-middle">
                                    <div class="truncate font-medium" title="{{ $fila['producto'] }}">
                                        {{ $fila['producto'] }}
                                    </div>
                                </td>

                                <td class="px-3 py-2.5 align-middle">
                                    <div class="truncate" title="{{ $fila['marca'] }}">
                                        {{ $fila['marca'] }}
                                    </div>
                                </td>

                                <td class="px-3 py-2.5 align-middle">
                                    <div class="truncate" title="{{ $fila['modelo'] }}">
                                        {{ $fila['modelo'] }}
                                    </div>
                                </td>

                                <td class="px-3 py-2.5 align-middle">
                                    <div class="truncate" title="{{ $fila['categoria'] }}">
                                        {{ $fila['categoria'] }}
                                    </div>
                                </td>

                                <td class="px-3 py-2.5 text-center align-middle">
                                    <button type="button" wire:click="verSeries({{ $fila['id_producto'] }})"
                                        class="inline-flex min-w-[2rem] justify-center rounded-full bg-[#EAF4FD] px-2 py-0.5 text-xs font-semibold text-[#0E48A1] hover:bg-[#DDEFFD]"
                                        title="Ver series no vendidas">
                                        {{ $fila['series'] }}
                                    </button>
                                </td>

                                <td class="px-3 py-2.5 text-center align-middle">
                                    <span
                                        class="{{ $fila['stock_bajo'] ? 'bg-red-100 text-red-700' : 'bg-[#F0F3F7] text-[#1A2B42]' }} inline-flex min-w-[2.25rem] justify-center rounded-full px-2 py-0.5 text-xs font-semibold">
                                        {{ $fila['stock'] }}
                                    </span>
                                </td>

                                <td class="px-3 py-2.5 text-right align-middle whitespace-nowrap">
                                    {{ $fila['precio_venta'] }}
                                </td>

                                <td class="px-3 py-2.5 text-center align-middle">
                                    <button type="button" title="Ver detalle"
                                        wire:click="abrirDetalleProducto({{ $fila['id_producto'] }})"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border-0 bg-[#2E8BC0] text-xs font-bold text-white transition hover:bg-[#0B6FE4]">
                                        i
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-sm text-[#7B8794]">
                                    No hay productos disponibles que coincidan con los filtros.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @else
                <div class="h-full w-full overflow-auto">
                    <table
                        class="min-w-220 w-full table-fixed border-separate border-spacing-0 text-[13px] text-[#1A2B42]">
                        <colgroup>
                            <col class="w-55">
                            <col class="w-25">
                            <col class="w-31.25">
                            <col class="w-41.25">
                            <col class="w-35">
                            <col class="w-26.25">
                            <col class="w-42.5">
                            <col class="w-16.25">
                        </colgroup>

                        <thead class="sticky top-0 z-10">
                            <tr>
                                <th class="rounded-tl-xl bg-[#2E8BC0] px-3 py-2.5 text-left font-semibold text-white">
                                    Producto</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-left font-semibold text-white">Marca</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-left font-semibold text-white">Modelo</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-left font-semibold text-white">Serie</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-left font-semibold text-white">Ingreso</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-center font-semibold text-white">Estado</th>
                                <th class="bg-[#2E8BC0] px-3 py-2.5 text-left font-semibold text-white">Observación</th>
                                <th class="rounded-tr-xl bg-[#2E8BC0] px-3 py-2.5 text-center font-semibold text-white">
                                    Ver</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($filasSeries as $fila)
                            <tr class="odd:bg-white even:bg-[#F8FBFF]">
                                <td class="px-3 py-2.5 align-middle">
                                    <div class="truncate font-medium" title="{{ $fila['producto'] }}">
                                        {{ $fila['producto'] }}
                                    </div>
                                </td>

                                <td class="px-3 py-2.5 align-middle">
                                    <div class="truncate" title="{{ $fila['marca'] }}">
                                        {{ $fila['marca'] }}
                                    </div>
                                </td>

                                <td class="px-3 py-2.5 align-middle">
                                    <div class="truncate" title="{{ $fila['modelo'] }}">
                                        {{ $fila['modelo'] }}
                                    </div>
                                </td>

                                <td class="px-3 py-2.5 align-middle">
                                    <div class="truncate font-semibold" title="{{ $fila['numero_serie'] }}">
                                        {{ $fila['numero_serie'] }}
                                    </div>
                                </td>

                                <td class="px-3 py-2.5 align-middle whitespace-nowrap">
                                    {{ $fila['fecha_ingreso'] }}
                                </td>

                                <td class="px-3 py-2.5 text-center align-middle">
                                    <span
                                        class="{{ $fila['estado_serie'] === 'DISPONIBLE' ? 'bg-green-100 text-green-700' : ($fila['estado_serie'] === 'DAÑADO' ? 'bg-red-100 text-red-700' : 'bg-[#EAF4FD] text-[#0E48A1]') }} inline-flex rounded-full px-2 py-0.5 text-xs font-semibold">
                                        {{ $fila['estado_serie'] }}
                                    </span>
                                </td>

                                <td class="px-3 py-2.5 align-middle">
                                    <div class="truncate" title="{{ $fila['observacion'] }}">
                                        {{ $fila['observacion'] }}
                                    </div>
                                </td>

                                <td class="px-3 py-2.5 text-center align-middle">
                                    <button type="button" title="Ver detalle de serie"
                                        wire:click="abrirDetalleSerie({{ $fila['id_serie'] }})"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border-0 bg-[#2E8BC0] text-xs font-bold text-white transition hover:bg-[#0B6FE4]">
                                        i
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-[#7B8794]">
                                    No hay series disponibles que coincidan con los filtros.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            <div
                class="mt-3 flex shrink-0 flex-col gap-2 border-t border-[#EAF2FB] pt-3 text-sm text-[#5F6B7A] lg:flex-row lg:items-center lg:justify-between">
                <div>
                    Mostrando
                    <span class="font-semibold text-[#1A2B42]">{{ $this->rangoDesde() }}</span>
                    a
                    <span class="font-semibold text-[#1A2B42]">{{ $this->rangoHasta() }}</span>
                    de
                    <span class="font-semibold text-[#1A2B42]">{{ $totalRegistros }}</span>
                    registros
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="paginaAnterior" @disabled($pagina <=1)
                        class="inline-flex h-8 items-center justify-center rounded-lg border border-[#D7E4F3] bg-white px-3 text-xs font-semibold text-[#1A2B42] transition hover:bg-[#F0F3F7] disabled:cursor-not-allowed disabled:opacity-50">
                        Anterior
                    </button>

                    @foreach ($this->paginasVisibles() as $numeroPagina)
                    <button type="button" wire:key="pagina-{{ $vista }}-{{ $numeroPagina }}"
                        wire:click="irPagina({{ $numeroPagina }})"
                        class="{{ $pagina === $numeroPagina ? 'bg-[#2E8BC0] text-white' : 'bg-white text-[#1A2B42]' }} inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-[#D7E4F3] px-2.5 text-xs font-semibold transition hover:bg-[#EAF4FD]">
                        {{ $numeroPagina }}
                    </button>
                    @endforeach

                    <button type="button" wire:click="paginaSiguiente" @disabled($pagina>= $totalPaginas)
                        class="inline-flex h-8 items-center justify-center rounded-lg border border-[#D7E4F3] bg-white
                        px-3 text-xs font-semibold text-[#1A2B42] transition hover:bg-[#F0F3F7]
                        disabled:cursor-not-allowed disabled:opacity-50">
                        Siguiente
                    </button>
                </div>
            </div>
        </x-card>
    </div>

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
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Fecha de vencimiento</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['fecha_vencimiento'] ?? '—' }}
                </p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Garantía nuevo</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['garantia_nuevo'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Garantía usado</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleProducto['garantia_usado'] ?? '—' }}</p>
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
            <table class="min-w-190 w-full border-separate border-spacing-0 text-sm text-[#1A2B42]">
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
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span
                                class="{{ $serie['estado'] === 'DISPONIBLE' ? 'bg-green-100 text-green-700' : ($serie['estado'] === 'DAÑADO' ? 'bg-red-100 text-red-700' : 'bg-[#EAF4FD] text-[#0E48A1]') }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                                {{ $serie['estado'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $serie['observacion'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-[#7B8794]">
                            Este producto no tiene series disponibles.
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

    <x-modal wire:model="modalDetalleSerie" class="backdrop-blur-sm"
        box-class="w-full max-w-4xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">

        <div class="mb-5">
            <h3 class="text-2xl font-bold text-[#1A2B42]">Detalle de la serie</h3>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                {{ $detalleSerie['producto'] ?? '' }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Producto</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleSerie['producto'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Número de serie</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleSerie['numero_serie'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Marca</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleSerie['marca'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Modelo</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleSerie['modelo'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Categoría</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleSerie['categoria'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Estado de la serie</p>

                @php
                $estadoSerie = $detalleSerie['estado_serie'] ?? '—';
                @endphp

                <span
                    class="{{ $estadoSerie === 'DISPONIBLE' ? 'bg-green-100 text-green-700' : ($estadoSerie === 'DAÑADO' ? 'bg-red-100 text-red-700' : 'bg-[#EAF4FD] text-[#0E48A1]') }} mt-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                    {{ $estadoSerie }}
                </span>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Fecha de ingreso</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleSerie['fecha_ingreso'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Precio venta</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleSerie['precio_venta'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Garantía nuevo</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleSerie['garantia_nuevo'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Garantía usado</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleSerie['garantia_usado'] ?? '—' }}</p>
            </div>

            <div class="rounded-xl bg-[#F7F9FC] p-4 md:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">Observación</p>
                <p class="mt-1 text-sm font-medium text-[#1A2B42]">{{ $detalleSerie['observacion'] ?? '—' }}</p>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" type="button" wire:click="cerrarModalDetalleSerie"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />
        </x-slot:actions>
    </x-modal>
</div>
