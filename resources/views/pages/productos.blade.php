<?php

use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoSerie;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public bool $modalCategoria = false;
    public bool $modalMarca = false;
    public bool $modalSeries = false;
    public bool $modalAgregarSerie = false;

    public string $nombreProducto = '';
    public string $modelo = '';
    public string $numeroSerie = '';
    public $idCategoria = '';
    public $idMarca = '';

    public string $stockActual = '0';
    public string $stockMinimo = '0';
    public string $precioCompra = '0';
    public string $precioVenta = '0';
    public string $garantiaNuevo = '';
    public string $garantiaUsado = '';
    public string $estado = '1';
    public string $fechaVencimiento = '';

    public string $nombreCategoria = '';
    public string $nombreMarca = '';
    public string $estadoMarca = '1';

    public string $buscar = '';

    public ?int $productoBaseSeleccionado = null;
    public array $coincidenciasProducto = [];
    public bool $mostrarCoincidenciasProducto = false;

    public string $toastMensaje = '';
    public string $toastTipo = 'success';
    public bool $mostrarToast = false;

    public string $productoNombreSeries = '';
    public string $productoNombreAgregarSerie = '';
    public int $productoIdAgregarSerie = 0;
    public string $numeroSerieExtra = '';
    public string $observacionSerieExtra = '';
    public string $estadoSerieExtra = 'DISPONIBLE';

    public array $categorias = [];
    public array $marcas = [];
    public array $productos = [];
    public array $seriesProducto = [];

    public function mount(): void
    {
        $this->cargarCatalogos();
        $this->cargarProductos();
    }

    public function updatedBuscar(): void
    {
        $this->cargarProductos();
    }

    public function updatedNombreProducto(): void
    {
        $this->productoBaseSeleccionado = null;
        $this->buscarCoincidenciasProducto();
    }

    public function updatedPrecioCompra($value): void
    {
        $this->precioCompra = $this->formatearMonto((string) $value);
    }

    public function updatedPrecioVenta($value): void
    {
        $this->precioVenta = $this->formatearMonto((string) $value);
    }

    protected function formatearMonto(?string $valor): string
    {
        $limpio = preg_replace('/[^\d]/', '', $valor ?? '');

        if ($limpio === '') {
            return '';
        }

        return number_format((int) $limpio, 0, '.', ',');
    }

    protected function desformatearMonto(?string $valor): int
    {
        $limpio = preg_replace('/[^\d]/', '', $valor ?? '');

        return $limpio === '' ? 0 : (int) $limpio;
    }

    protected function buscarCoincidenciasProducto(): void
    {
        $busqueda = trim($this->nombreProducto);

        if (strlen($busqueda) < 2) {
            $this->coincidenciasProducto = [];
            $this->mostrarCoincidenciasProducto = false;
            return;
        }

        $this->coincidenciasProducto = DB::table('producto as p')
            ->leftJoin('categoria_producto as c', 'p.Id_Categoria', '=', 'c.Id_Categoria')
            ->leftJoin('marca as m', 'p.Id_Marca', '=', 'm.Id_Marca')
            ->select(
                'p.Id_Producto',
                'p.Nombre_Producto',
                'p.Modelo',
                'p.Precio_Venta',
                'c.Nombre_Categoria as categoria',
                'm.Nombre_Marca as marca'
            )
            ->where(function ($q) use ($busqueda) {
                $q->where('p.Nombre_Producto', 'like', "%{$busqueda}%")
                    ->orWhere('p.Modelo', 'like', "%{$busqueda}%")
                    ->orWhere('m.Nombre_Marca', 'like', "%{$busqueda}%")
                    ->orWhere('c.Nombre_Categoria', 'like', "%{$busqueda}%");
            })
            ->orderByRaw('CASE WHEN p.Nombre_Producto LIKE ? THEN 0 ELSE 1 END', [$busqueda . '%'])
            ->orderBy('p.Nombre_Producto')
            ->limit(8)
            ->get()
            ->map(function ($producto) {
                return [
                    'id_producto' => (int) $producto->Id_Producto,
                    'nombre' => $producto->Nombre_Producto,
                    'modelo' => $producto->Modelo ?: 'Sin modelo',
                    'categoria' => $producto->categoria ?: 'Sin categoría',
                    'marca' => $producto->marca ?: 'Sin marca',
                    'precio_venta' => 'C$ ' . number_format((float) $producto->Precio_Venta, 0, '.', ','),
                ];
            })
            ->toArray();

        $this->mostrarCoincidenciasProducto = count($this->coincidenciasProducto) > 0;
    }

    public function seleccionarProducto(int $idProducto): void
    {
        $producto = DB::table('producto')
            ->select(
                'Id_Producto',
                'Id_Categoria',
                'Id_Marca',
                'Nombre_Producto',
                'Modelo',
                'Stock_Actual',
                'Stock_Minimo',
                'Precio_Compra',
                'Precio_Venta',
                'Fecha_Vencimiento',
                'Meses_Garantia_Nuevo',
                'Meses_Garantia_Usado',
                'Estado'
            )
            ->where('Id_Producto', $idProducto)
            ->first();

        if (!$producto) {
            $this->coincidenciasProducto = [];
            $this->mostrarCoincidenciasProducto = false;
            $this->mostrarToast('No se encontró el producto seleccionado.', 'error');
            return;
        }

        $this->productoBaseSeleccionado = (int) $producto->Id_Producto;

        $this->nombreProducto = $producto->Nombre_Producto ?? '';
        $this->modelo = $producto->Modelo ?? '';
        $this->numeroSerie = '';

        $this->idCategoria = $producto->Id_Categoria ? (string) $producto->Id_Categoria : '';
        $this->idMarca = $producto->Id_Marca ? (string) $producto->Id_Marca : '';

        $this->stockActual = (string) ($producto->Stock_Actual ?? 0);
        $this->stockMinimo = (string) ($producto->Stock_Minimo ?? 0);
        $this->precioCompra = number_format((float) ($producto->Precio_Compra ?? 0), 0, '.', ',');
        $this->precioVenta = number_format((float) ($producto->Precio_Venta ?? 0), 0, '.', ',');

        $this->garantiaNuevo = $producto->Meses_Garantia_Nuevo !== null
            ? (string) $producto->Meses_Garantia_Nuevo
            : '';

        $this->garantiaUsado = $producto->Meses_Garantia_Usado !== null
            ? (string) $producto->Meses_Garantia_Usado
            : '';

        $this->estado = (string) ($producto->Estado ?? 1);

        $this->fechaVencimiento = $producto->Fecha_Vencimiento
            ? \Carbon\Carbon::parse($producto->Fecha_Vencimiento)->format('Y-m-d')
            : '';

        $this->coincidenciasProducto = [];
        $this->mostrarCoincidenciasProducto = false;

        $this->resetErrorBag();
        $this->resetValidation();

        $this->mostrarToast('Datos del producto cargados correctamente.');
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

    public function abrirModalCategoria(): void
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->nombreCategoria = '';
        $this->modalCategoria = true;
    }

    public function cerrarModalCategoria(): void
    {
        $this->modalCategoria = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function guardarCategoria(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'nombreCategoria' => 'required|string|max:100|unique:categoria_producto,Nombre_Categoria',
        ]);

        $categoria = new CategoriaProducto();
        $categoria->Nombre_Categoria = trim($this->nombreCategoria);
        $categoria->save();

        $this->idCategoria = (string) $categoria->Id_Categoria;
        $this->nombreCategoria = '';
        $this->modalCategoria = false;

        $this->cargarCatalogos();
        $this->mostrarToast('Categoría guardada correctamente.');
    }

    public function abrirModalMarca(): void
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->nombreMarca = '';
        $this->estadoMarca = '1';
        $this->modalMarca = true;
    }

    public function cerrarModalMarca(): void
    {
        $this->modalMarca = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function guardarMarca(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'nombreMarca' => 'required|string|max:100|unique:marca,Nombre_Marca',
            'estadoMarca' => 'required|in:0,1',
        ]);

        $marca = new Marca();
        $marca->Nombre_Marca = trim($this->nombreMarca);
        $marca->Estado = (int) $this->estadoMarca;
        $marca->save();

        $this->idMarca = (string) $marca->Id_Marca;
        $this->nombreMarca = '';
        $this->estadoMarca = '1';
        $this->modalMarca = false;

        $this->cargarCatalogos();
        $this->mostrarToast('Marca guardada correctamente.');
    }

    public function guardarProducto(): void
    {
        $this->resetErrorBag();

        $datos = $this->validate([
            'nombreProducto' => 'required|string|max:150',
            'modelo' => 'nullable|string|max:100',
            'numeroSerie' => 'nullable|string|max:100|unique:producto_serie,Numero_Serie',
            'idCategoria' => 'required|exists:categoria_producto,Id_Categoria',
            'idMarca' => 'nullable|exists:marca,Id_Marca',
            'stockActual' => 'required|integer|min:0',
            'stockMinimo' => 'required|integer|min:0',
            'precioCompra' => ['required', 'regex:/^\d+(,\d{3})*$/'],
            'precioVenta' => ['required', 'regex:/^\d+(,\d{3})*$/'],
            'garantiaNuevo' => 'nullable|integer|min:0',
            'garantiaUsado' => 'nullable|integer|min:0',
            'estado' => 'required|in:0,1',
            'fechaVencimiento' => 'nullable|date',
        ], [
            'precioCompra.regex' => 'Ingrese el precio sin decimales. Ejemplo: 40,000',
            'precioVenta.regex' => 'Ingrese el precio sin decimales. Ejemplo: 40,000',
        ]);

        $precioCompraLimpio = $this->desformatearMonto($datos['precioCompra']);
        $precioVentaLimpio = $this->desformatearMonto($datos['precioVenta']);
        $productoBaseId = $this->productoBaseSeleccionado;
        $esProductoExistente = $productoBaseId !== null;

        DB::transaction(function () use ($datos, $precioCompraLimpio, $precioVentaLimpio, $productoBaseId) {
            if ($productoBaseId !== null) {
                Producto::where('Id_Producto', $productoBaseId)->update([
                    'Id_Categoria' => (int) $datos['idCategoria'],
                    'Id_Marca' => $datos['idMarca'] !== '' ? (int) $datos['idMarca'] : null,
                    'Nombre_Producto' => trim($datos['nombreProducto']),
                    'Modelo' => $datos['modelo'] !== '' ? trim($datos['modelo']) : null,
                    'Stock_Actual' => (int) $datos['stockActual'],
                    'Stock_Minimo' => (int) $datos['stockMinimo'],
                    'Precio_Compra' => $precioCompraLimpio,
                    'Precio_Venta' => $precioVentaLimpio,
                    'Fecha_Vencimiento' => $datos['fechaVencimiento'] !== '' ? $datos['fechaVencimiento'] : null,
                    'Meses_Garantia_Nuevo' => $datos['garantiaNuevo'] !== '' ? (int) $datos['garantiaNuevo'] : null,
                    'Meses_Garantia_Usado' => $datos['garantiaUsado'] !== '' ? (int) $datos['garantiaUsado'] : null,
                    'Estado' => (int) $datos['estado'],
                ]);

                if (!empty($datos['numeroSerie'])) {
                    $serie = new ProductoSerie();
                    $serie->Id_Producto = $productoBaseId;
                    $serie->Numero_Serie = trim($datos['numeroSerie']);
                    $serie->Fecha_Ingreso = now();
                    $serie->Estado = 'DISPONIBLE';
                    $serie->Observacion = null;
                    $serie->save();

                    Producto::where('Id_Producto', $productoBaseId)->increment('Stock_Actual');
                }

                return;
            }

            $producto = new Producto();
            $producto->Id_Categoria = (int) $datos['idCategoria'];
            $producto->Id_Marca = $datos['idMarca'] !== '' ? (int) $datos['idMarca'] : null;
            $producto->Nombre_Producto = trim($datos['nombreProducto']);
            $producto->Modelo = $datos['modelo'] !== '' ? trim($datos['modelo']) : null;
            $producto->Stock_Actual = (int) $datos['stockActual'];
            $producto->Stock_Minimo = (int) $datos['stockMinimo'];
            $producto->Precio_Compra = $precioCompraLimpio;
            $producto->Precio_Venta = $precioVentaLimpio;
            $producto->Fecha_Vencimiento = $datos['fechaVencimiento'] !== '' ? $datos['fechaVencimiento'] : null;
            $producto->Meses_Garantia_Nuevo = $datos['garantiaNuevo'] !== '' ? (int) $datos['garantiaNuevo'] : null;
            $producto->Meses_Garantia_Usado = $datos['garantiaUsado'] !== '' ? (int) $datos['garantiaUsado'] : null;
            $producto->Estado = (int) $datos['estado'];
            $producto->save();

            if (!empty($datos['numeroSerie'])) {
                $serie = new ProductoSerie();
                $serie->Id_Producto = $producto->Id_Producto;
                $serie->Numero_Serie = trim($datos['numeroSerie']);
                $serie->Fecha_Ingreso = now();
                $serie->Estado = 'DISPONIBLE';
                $serie->Observacion = null;
                $serie->save();
            }
        });

        $this->resetFormularioProducto();
        $this->cargarProductos();

        $this->mostrarToast(
            $esProductoExistente
                ? 'Producto actualizado correctamente.'
                : 'Producto guardado correctamente.'
        );
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
        $this->cargarProductos();
        $this->mostrarToast('Serie agregada correctamente.');
    }

    protected function resetFormularioProducto(): void
    {
        $this->nombreProducto = '';
        $this->modelo = '';
        $this->numeroSerie = '';
        $this->idCategoria = '';
        $this->idMarca = '';
        $this->stockActual = '0';
        $this->stockMinimo = '0';
        $this->precioCompra = '0';
        $this->precioVenta = '0';
        $this->garantiaNuevo = '';
        $this->garantiaUsado = '';
        $this->estado = '1';
        $this->fechaVencimiento = '';

        $this->productoBaseSeleccionado = null;
        $this->coincidenciasProducto = [];
        $this->mostrarCoincidenciasProducto = false;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    protected function cargarCatalogos(): void
    {
        $this->categorias = DB::table('categoria_producto')
            ->select('Id_Categoria', 'Nombre_Categoria')
            ->orderBy('Nombre_Categoria')
            ->get()
            ->map(fn ($categoria) => [
                'id' => $categoria->Id_Categoria,
                'nombre' => $categoria->Nombre_Categoria,
            ])
            ->toArray();

        $this->marcas = DB::table('marca')
            ->select('Id_Marca', 'Nombre_Marca')
            ->orderBy('Nombre_Marca')
            ->get()
            ->map(fn ($marca) => [
                'id' => $marca->Id_Marca,
                'nombre' => $marca->Nombre_Marca,
            ])
            ->toArray();
    }

    protected function cargarProductos(): void
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
                p.Precio_Venta,
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
                'p.Precio_Venta',
                'p.Estado',
                'c.Nombre_Categoria',
                'm.Nombre_Marca'
            )
            ->orderByDesc('p.Id_Producto');

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

        $this->productos = $query->limit(25)->get()
            ->map(function ($producto) {
                return [
                    'id_producto' => $producto->Id_Producto,
                    'codigo' => $producto->Id_Producto,
                    'producto' => $producto->Nombre_Producto,
                    'categoria' => $producto->categoria ?: '—',
                    'marca' => $producto->marca ?: '—',
                    'modelo' => $producto->Modelo ?: '—',
                    'series_registradas' => (int) $producto->total_series,
                    'stock' => $producto->Stock_Actual,
                    'precio_venta' => 'C$ ' . number_format((float) $producto->Precio_Venta, 0, '.', ','),
                    'estado' => (int) $producto->Estado === 1 ? 'Activo' : 'Inactivo',
                ];
            })
            ->toArray();
    }
};
?>

<div class="w-full min-h-[calc(100vh-3rem)] bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="mx-auto flex w-full max-w-350 flex-col gap-4">
        <div class="flex shrink-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#1A2B42]">Productos</h1>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    Registro y gestión de productos del sistema.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button label="Nueva categoría" wire:click="abrirModalCategoria"
                    class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]" />

                <x-button label="Nueva marca" wire:click="abrirModalMarca"
                    class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]" />

                <a href="{{ route('productos.listado') }}"
                    class="inline-flex h-10 min-h-10 items-center justify-center rounded-xl bg-[#2E8BC0] px-4 text-sm font-semibold text-white transition hover:bg-[#0B6FE4]">
                    Ver tabla completa
                </a>
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

        <form wire:submit.prevent="guardarProducto">
            <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-[#1A2B42]">Registrar producto</h2>
                    <p class="text-sm text-[#5F6B7A]">
                        Cree la ficha base del producto. Luego las demás unidades iguales se agregan desde “Agregar
                        serie”.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-4">
                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Nombre del producto</label>

                        <div class="relative">
                            <x-input wire:model.live.debounce.300ms="nombreProducto" type="text" autocomplete="off"
                                placeholder="Escriba para buscar coincidencias"
                                class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />

                            @if ($mostrarCoincidenciasProducto)
                            <div
                                class="absolute left-0 right-0 top-full z-50 mt-1 max-h-64 overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                                @forelse ($coincidenciasProducto as $coincidencia)
                                <button type="button"
                                    wire:key="coincidencia-producto-{{ $coincidencia['id_producto'] }}"
                                    wire:click="seleccionarProducto({{ $coincidencia['id_producto'] }})"
                                    class="flex w-full flex-col gap-1 border-b border-[#EAF2FB] px-4 py-3 text-left transition hover:bg-[#EAF4FD] last:border-b-0">

                                    <span class="text-sm font-semibold text-[#1A2B42]">
                                        {{ $coincidencia['nombre'] }}
                                    </span>

                                    <span class="text-xs text-[#5F6B7A]">
                                        {{ $coincidencia['marca'] }}
                                        · {{ $coincidencia['modelo'] }}
                                        · {{ $coincidencia['categoria'] }}
                                        · {{ $coincidencia['precio_venta'] }}
                                    </span>
                                </button>
                                @empty
                                <div class="px-4 py-3 text-sm text-[#7B8794]">
                                    No se encontraron coincidencias.
                                </div>
                                @endforelse
                            </div>
                            @endif
                        </div>

                        @if ($productoBaseSeleccionado)
                        <p class="mt-1 text-xs font-medium text-[#0E48A1]">
                            Producto base seleccionado. Si ingresa un nuevo número de serie, se agregará a este
                            producto.
                        </p>
                        @endif

                        @error('nombreProducto')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Modelo</label>
                        <x-input wire:model.defer="modelo" type="text" placeholder="Ingrese el modelo"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                        @error('modelo')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Primer número de serie</label>
                        <x-input wire:model.defer="numeroSerie" type="text" placeholder="Opcional"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                        @error('numeroSerie')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Categoría</label>
                        <select wire:model.defer="idCategoria"
                            class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                            <option value="">Seleccione una categoría</option>
                            @foreach ($categorias as $categoria)
                            <option value="{{ $categoria['id'] }}">{{ $categoria['nombre'] }}</option>
                            @endforeach
                        </select>
                        @error('idCategoria')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Marca</label>
                        <select wire:model.defer="idMarca"
                            class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                            <option value="">Seleccione una marca</option>
                            @foreach ($marcas as $marca)
                            <option value="{{ $marca['id'] }}">{{ $marca['nombre'] }}</option>
                            @endforeach
                        </select>
                        @error('idMarca')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Stock inicial</label>
                        <x-input wire:model.defer="stockActual" type="number" min="0" placeholder="0"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        @error('stockActual')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Stock mínimo</label>
                        <x-input wire:model.defer="stockMinimo" type="number" min="0" placeholder="0"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        @error('stockMinimo')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Precio de compra</label>
                        <x-input wire:model.live.debounce.250ms="precioCompra" type="text" inputmode="numeric"
                            placeholder="0"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        @error('precioCompra')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Precio de venta</label>
                        <x-input wire:model.live.debounce.250ms="precioVenta" type="text" inputmode="numeric"
                            placeholder="0"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        @error('precioVenta')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Garantía nuevo</label>
                        <x-input wire:model.defer="garantiaNuevo" type="number" min="0" placeholder="Meses"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        @error('garantiaNuevo')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Garantía usado</label>
                        <x-input wire:model.defer="garantiaUsado" type="number" min="0" placeholder="Meses"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        @error('garantiaUsado')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Estado</label>
                        <select wire:model.defer="estado"
                            class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42]">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                        @error('estado')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Fecha de vencimiento</label>
                        <x-input wire:model.defer="fechaVencimiento" type="date"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                        @error('fechaVencimiento')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <x-slot:actions>
                    <x-button label="Guardar producto" type="submit"
                        class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]" />
                </x-slot:actions>
            </x-card>
        </form>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-bold text-[#1A2B42]">Resumen de productos</h2>

                <div class="w-full md:w-80">
                    <x-input wire:model.live.debounce.250ms="buscar" type="text" placeholder="Buscar productos"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-[#D7E4F3] bg-white">
                <div class="max-h-105 overflow-x-auto overflow-y-auto">
                    <table class="min-w-275 w-full border-separate border-spacing-0 text-[13px] text-[#1A2B42]">
                        <thead class="sticky top-0 z-10">
                            <tr>
                                <th
                                    class="rounded-tl-xl bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">
                                    Código</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">
                                    Producto</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">
                                    Marca</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">
                                    Modelo</th>
                                <th
                                    class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white whitespace-nowrap">
                                    Series</th>
                                <th
                                    class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white whitespace-nowrap">
                                    Stock</th>
                                <th
                                    class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white whitespace-nowrap">
                                    Precio venta</th>
                                <th
                                    class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white whitespace-nowrap">
                                    Estado</th>
                                <th
                                    class="rounded-tr-xl bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white whitespace-nowrap">
                                    Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($productos as $producto)
                            <tr class="odd:bg-white even:bg-[#F8FBFF]">
                                <td class="px-3 py-3 align-middle whitespace-nowrap font-semibold">
                                    #{{ $producto['codigo'] }}
                                </td>
                                <td class="px-3 py-3 align-middle whitespace-nowrap font-medium">
                                    {{ $producto['producto'] }}
                                </td>
                                <td class="px-3 py-3 align-middle whitespace-nowrap">
                                    {{ $producto['marca'] }}
                                </td>
                                <td class="px-3 py-3 align-middle whitespace-nowrap">
                                    {{ $producto['modelo'] }}
                                </td>
                                <td class="px-3 py-3 text-center align-middle whitespace-nowrap">
                                    <span
                                        class="inline-flex min-w-8.5 justify-center rounded-full bg-[#EAF4FD] px-2.5 py-1 text-xs font-semibold text-[#0E48A1]">
                                        {{ $producto['series_registradas'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center align-middle whitespace-nowrap">
                                    {{ $producto['stock'] }}
                                </td>
                                <td class="px-3 py-3 text-right align-middle whitespace-nowrap">
                                    {{ $producto['precio_venta'] }}
                                </td>
                                <td class="px-3 py-3 text-center align-middle whitespace-nowrap">
                                    <span
                                        class="{{ $producto['estado'] === 'Activo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                                        {{ $producto['estado'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center align-middle whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-button label="Series" wire:click="verSeries({{ $producto['id_producto'] }})"
                                            class="h-8 min-h-8 border-0 bg-[#2E8BC0] px-3 text-xs text-white hover:bg-[#0B6FE4]" />

                                        <x-button label="+ Serie"
                                            wire:click="abrirModalAgregarSerie({{ $producto['id_producto'] }})"
                                            class="h-8 min-h-8 border-0 bg-[#0E48A1] px-3 text-xs text-white hover:bg-[#0B6FE4]" />
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-[#7B8794]">
                                    No hay productos registrados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-card>

        <x-modal wire:model="modalCategoria" class="backdrop-blur-sm"
            box-class="w-full max-w-2xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">
            <div class="mb-5">
                <h3 class="text-2xl font-bold text-[#1A2B42]">Registrar categoría</h3>
                <p class="mt-1 text-sm text-[#5F6B7A]">Agregue una nueva categoría</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Nombre de la categoría</label>
                    <x-input type="text" wire:model.defer="nombreCategoria" placeholder="Ingrese el nombre"
                        class="w-full rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]" />
                    @error('nombreCategoria')
                    <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <x-slot:actions>
                    <x-button label="Cancelar" type="button" wire:click="cerrarModalCategoria"
                        class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />

                    <x-button label="Guardar categoría" type="button" wire:click="guardarCategoria"
                        class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]" />
                </x-slot:actions>
            </div>
        </x-modal>

        <x-modal wire:model="modalMarca" class="backdrop-blur-sm"
            box-class="w-full max-w-2xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl">
            <div class="mb-5">
                <h3 class="text-2xl font-bold text-[#1A2B42]">Registrar marca</h3>
                <p class="mt-1 text-sm text-[#5F6B7A]">Agregue una nueva marca</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Nombre de la marca</label>
                    <x-input type="text" wire:model.defer="nombreMarca" placeholder="Ingrese el nombre"
                        class="w-full rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]" />
                    @error('nombreMarca')
                    <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Estado</label>
                    <select wire:model.defer="estadoMarca"
                        class="w-full rounded-xl border-0 bg-[#F0F3F7] px-3 py-2 text-[#1A2B42]">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                    @error('estadoMarca')
                    <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <x-slot:actions>
                    <x-button label="Cancelar" type="button" wire:click="cerrarModalMarca"
                        class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />

                    <x-button label="Guardar marca" type="button" wire:click="guardarMarca"
                        class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]" />
                </x-slot:actions>
            </div>
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
                                Número de serie
                            </th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Fecha ingreso
                            </th>
                            <th class="bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Estado
                            </th>
                            <th
                                class="rounded-tr-xl bg-[#2E8BC0] px-4 py-3 text-left font-semibold text-white whitespace-nowrap">
                                Observación
                            </th>
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
</div>
