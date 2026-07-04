<?php

use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    private const ESTADO_SERIE_DISPONIBLE = 'DISPONIBLE';

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

    public string $precioVenta = '0';
    public string $garantiaNuevo = '';
    public string $garantiaUsado = '';

    public bool $bloquearGarantiaNuevo = false;
    public bool $bloquearGarantiaUsado = false;

    public string $estado = '1';
    public string $fechaVencimiento = '';

    public string $nombreCategoria = '';
    public string $nombreMarca = '';
    public string $estadoMarca = '1';

    public string $buscar = '';

    public ?int $productoBaseSeleccionado = null;
    public array $coincidenciasProducto = [];
    public bool $mostrarCoincidenciasProducto = false;

    public string $productoNombreSeries = '';
    public string $productoNombreAgregarSerie = '';
    public int $productoIdAgregarSerie = 0;
    public string $numeroSerieExtra = '';
    public string $observacionSerieExtra = '';
    public string $estadoSerieExtra = self::ESTADO_SERIE_DISPONIBLE;

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

    public function updatedPrecioVenta($value): void
    {
        $this->precioVenta = $this->formatearMonto((string) $value);
    }

    public function updatedGarantiaNuevo($value): void
    {
        $this->garantiaNuevo = preg_replace('/[^\d]/', '', (string) $value) ?? '';

        if (trim($this->garantiaNuevo) !== '') {
            $this->garantiaUsado = '';
            $this->bloquearGarantiaUsado = true;
            $this->bloquearGarantiaNuevo = false;
        } else {
            $this->bloquearGarantiaNuevo = false;
            $this->bloquearGarantiaUsado = false;
        }

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatedGarantiaUsado($value): void
    {
        $this->garantiaUsado = preg_replace('/[^\d]/', '', (string) $value) ?? '';

        if (trim($this->garantiaUsado) !== '') {
            $this->garantiaNuevo = '';
            $this->bloquearGarantiaNuevo = true;
            $this->bloquearGarantiaUsado = false;
        } else {
            $this->bloquearGarantiaNuevo = false;
            $this->bloquearGarantiaUsado = false;
        }

        $this->resetErrorBag();
        $this->resetValidation();
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

        $this->coincidenciasProducto = Producto::query()
            ->with([
                'categoria:Id_Categoria,Nombre_Categoria',
                'marca:Id_Marca,Nombre_Marca',
            ])
            ->select([
                'Id_Producto',
                'Id_Categoria',
                'Id_Marca',
                'Nombre_Producto',
                'Modelo',
                'Precio_Venta',
            ])
            ->where(function ($query) use ($busqueda) {
                $query->where('Nombre_Producto', 'like', "%{$busqueda}%")
                    ->orWhere('Modelo', 'like', "%{$busqueda}%")
                    ->orWhereHas('marca', function ($marca) use ($busqueda) {
                        $marca->where('Nombre_Marca', 'like', "%{$busqueda}%");
                    })
                    ->orWhereHas('categoria', function ($categoria) use ($busqueda) {
                        $categoria->where('Nombre_Categoria', 'like', "%{$busqueda}%");
                    });
            })
            ->orderBy('Nombre_Producto')
            ->limit(8)
            ->get()
            ->map(function ($producto) {
                return [
                    'id_producto' => (int) $producto->Id_Producto,
                    'nombre' => $producto->Nombre_Producto,
                    'modelo' => $producto->Modelo ?: 'Sin modelo',
                    'categoria' => $producto->categoria?->Nombre_Categoria ?: 'Sin categoría',
                    'marca' => $producto->marca?->Nombre_Marca ?: 'Sin marca',
                    'precio_venta' => 'C$ ' . number_format((float) $producto->Precio_Venta, 0, '.', ','),
                ];
            })
            ->toArray();

        $this->mostrarCoincidenciasProducto = count($this->coincidenciasProducto) > 0;
    }

    public function seleccionarProducto(int $idProducto): void
    {
        $producto = Producto::query()
            ->select([
                'Id_Producto',
                'Id_Categoria',
                'Id_Marca',
                'Nombre_Producto',
                'Modelo',
                'Stock_Actual',
                'Stock_Minimo',
                'Precio_Venta',
                'Fecha_Vencimiento',
                'Meses_Garantia_Nuevo',
                'Meses_Garantia_Usado',
                'Estado',
            ])
            ->find($idProducto);

        if (! $producto) {
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
        $this->precioVenta = number_format((float) ($producto->Precio_Venta ?? 0), 0, '.', ',');

        $this->garantiaNuevo = $producto->Meses_Garantia_Nuevo !== null
            ? (string) $producto->Meses_Garantia_Nuevo
            : '';

        $this->garantiaUsado = $producto->Meses_Garantia_Usado !== null
            ? (string) $producto->Meses_Garantia_Usado
            : '';

        $this->bloquearGarantiaNuevo = false;
        $this->bloquearGarantiaUsado = false;

        $this->estado = (string) ((int) $producto->Estado);

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
        match ($tipo) {
            'error' => $this->error($mensaje, position: 'toast-top toast-end', timeout: 3500),
            'warning' => $this->warning($mensaje, position: 'toast-top toast-end', timeout: 3000),
            'info' => $this->info($mensaje, position: 'toast-top toast-end', timeout: 2500),
            default => $this->success($mensaje, position: 'toast-top toast-end', timeout: 2500),
        };
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

        $categoria = CategoriaProducto::create([
            'Nombre_Categoria' => trim($this->nombreCategoria),
        ]);

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

        $marca = Marca::create([
            'Nombre_Marca' => trim($this->nombreMarca),
            'Estado' => (int) $this->estadoMarca,
        ]);

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
            'precioVenta' => ['required', 'regex:/^\d+(,\d{3})*$/'],
            'garantiaNuevo' => 'nullable|integer|min:0',
            'garantiaUsado' => 'nullable|integer|min:0',
            'estado' => 'required|in:0,1',
            'fechaVencimiento' => 'nullable|date',
        ], [
            'precioVenta.regex' => 'Ingrese el precio sin decimales. Ejemplo: 40,000',
        ]);

        $usuarioEscribioGarantia = $this->bloquearGarantiaNuevo || $this->bloquearGarantiaUsado;

        if (
            $usuarioEscribioGarantia
            && trim((string) $datos['garantiaNuevo']) !== ''
            && trim((string) $datos['garantiaUsado']) !== ''
        ) {
            $this->addError('garantiaNuevo', 'Solo puede editar una garantía a la vez.');
            $this->addError('garantiaUsado', 'Limpie uno de los dos campos.');
            $this->mostrarToast('Solo puede escribir en una garantía a la vez.', 'error');
            return;
        }

        $precioVentaLimpio = $this->desformatearMonto($datos['precioVenta']);
        $productoBaseId = $this->productoBaseSeleccionado;
        $esProductoExistente = $productoBaseId !== null;

        DB::transaction(function () use ($datos, $precioVentaLimpio, $productoBaseId) {
            if ($productoBaseId !== null) {
                $producto = Producto::query()->findOrFail($productoBaseId);

                $producto->update([
                    'Id_Categoria' => (int) $datos['idCategoria'],
                    'Id_Marca' => $datos['idMarca'] !== '' ? (int) $datos['idMarca'] : null,
                    'Nombre_Producto' => trim($datos['nombreProducto']),
                    'Modelo' => $datos['modelo'] !== '' ? trim($datos['modelo']) : null,
                    'Stock_Actual' => (int) $datos['stockActual'],
                    'Stock_Minimo' => (int) $datos['stockMinimo'],
                    'Precio_Venta' => $precioVentaLimpio,
                    'Fecha_Vencimiento' => $datos['fechaVencimiento'] !== '' ? $datos['fechaVencimiento'] : null,
                    'Meses_Garantia_Nuevo' => $datos['garantiaNuevo'] !== '' ? (int) $datos['garantiaNuevo'] : null,
                    'Meses_Garantia_Usado' => $datos['garantiaUsado'] !== '' ? (int) $datos['garantiaUsado'] : null,
                    'Estado' => (int) $datos['estado'],
                ]);

                if (! empty($datos['numeroSerie'])) {
                    $producto->series()->create([
                        'Numero_Serie' => trim($datos['numeroSerie']),
                        'Fecha_Ingreso' => now(),
                        'Estado' => self::ESTADO_SERIE_DISPONIBLE,
                        'Observacion' => null,
                    ]);

                    $producto->increment('Stock_Actual');
                }

                return;
            }

            $stockInicial = (int) $datos['stockActual'];

            if (! empty($datos['numeroSerie']) && $stockInicial < 1) {
                $stockInicial = 1;
            }

            $producto = Producto::create([
                'Id_Categoria' => (int) $datos['idCategoria'],
                'Id_Marca' => $datos['idMarca'] !== '' ? (int) $datos['idMarca'] : null,
                'Nombre_Producto' => trim($datos['nombreProducto']),
                'Modelo' => $datos['modelo'] !== '' ? trim($datos['modelo']) : null,
                'Stock_Actual' => $stockInicial,
                'Stock_Minimo' => (int) $datos['stockMinimo'],
                'Precio_Venta' => $precioVentaLimpio,
                'Fecha_Vencimiento' => $datos['fechaVencimiento'] !== '' ? $datos['fechaVencimiento'] : null,
                'Meses_Garantia_Nuevo' => $datos['garantiaNuevo'] !== '' ? (int) $datos['garantiaNuevo'] : null,
                'Meses_Garantia_Usado' => $datos['garantiaUsado'] !== '' ? (int) $datos['garantiaUsado'] : null,
                'Estado' => (int) $datos['estado'],
            ]);

            if (! empty($datos['numeroSerie'])) {
                $producto->series()->create([
                    'Numero_Serie' => trim($datos['numeroSerie']),
                    'Fecha_Ingreso' => now(),
                    'Estado' => self::ESTADO_SERIE_DISPONIBLE,
                    'Observacion' => null,
                ]);
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

    public function cancelarFormularioProducto(): void
    {
        $this->resetFormularioProducto();
        $this->mostrarToast('Formulario limpiado correctamente.');
    }

    public function verSeries(int $idProducto): void
    {
        $producto = Producto::query()
            ->select([
                'Id_Producto',
                'Nombre_Producto',
                'Modelo',
                'Estado',
                'Stock_Actual',
            ])
            ->where('Estado', 1)
            ->where('Stock_Actual', '>', 0)
            ->find($idProducto);

        if (! $producto) {
            $this->mostrarToast('Este producto ya no está disponible en inventario.', 'error');
            return;
        }

        $this->productoNombreSeries = trim(
            $producto->Nombre_Producto . ($producto->Modelo ? ' - ' . $producto->Modelo : '')
        );

        $this->seriesProducto = $producto->series()
            ->where('Estado', self::ESTADO_SERIE_DISPONIBLE)
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

        $producto = Producto::query()
            ->with('marca:Id_Marca,Nombre_Marca')
            ->select([
                'Id_Producto',
                'Id_Marca',
                'Nombre_Producto',
                'Modelo',
                'Estado',
            ])
            ->where('Estado', 1)
            ->find($idProducto);

        if (! $producto) {
            $this->mostrarToast('Este producto no está disponible para agregar series.', 'error');
            return;
        }

        $this->productoIdAgregarSerie = (int) $producto->Id_Producto;
        $this->productoNombreAgregarSerie = trim(
            ($producto->marca?->Nombre_Marca ? $producto->marca->Nombre_Marca . ' ' : '') .
            $producto->Nombre_Producto .
            ($producto->Modelo ? ' - ' . $producto->Modelo : '')
        );

        $this->numeroSerieExtra = '';
        $this->observacionSerieExtra = '';
        $this->estadoSerieExtra = self::ESTADO_SERIE_DISPONIBLE;
        $this->modalAgregarSerie = true;
    }

    public function cerrarModalAgregarSerie(): void
    {
        $this->modalAgregarSerie = false;
        $this->productoIdAgregarSerie = 0;
        $this->productoNombreAgregarSerie = '';
        $this->numeroSerieExtra = '';
        $this->observacionSerieExtra = '';
        $this->estadoSerieExtra = self::ESTADO_SERIE_DISPONIBLE;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function guardarSerieProducto(): void
    {
        $this->resetErrorBag();

        $datos = $this->validate([
            'productoIdAgregarSerie' => 'required|integer|exists:producto,Id_Producto',
            'numeroSerieExtra' => 'required|string|max:100|unique:producto_serie,Numero_Serie',
            'estadoSerieExtra' => 'required|in:DISPONIBLE,RESERVADO,DAÑADO',
            'observacionSerieExtra' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($datos) {
            $producto = Producto::query()
                ->where('Estado', 1)
                ->findOrFail((int) $datos['productoIdAgregarSerie']);

            $producto->series()->create([
                'Numero_Serie' => trim($datos['numeroSerieExtra']),
                'Fecha_Ingreso' => now(),
                'Estado' => trim($datos['estadoSerieExtra']),
                'Observacion' => $datos['observacionSerieExtra'] !== ''
                    ? trim($datos['observacionSerieExtra'])
                    : null,
            ]);

            $producto->increment('Stock_Actual');
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
        $this->precioVenta = '0';
        $this->garantiaNuevo = '';
        $this->garantiaUsado = '';
        $this->bloquearGarantiaNuevo = false;
        $this->bloquearGarantiaUsado = false;
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
        $this->categorias = CategoriaProducto::query()
            ->select(['Id_Categoria', 'Nombre_Categoria'])
            ->orderBy('Nombre_Categoria')
            ->get()
            ->map(fn ($categoria) => [
                'id' => $categoria->Id_Categoria,
                'nombre' => $categoria->Nombre_Categoria,
            ])
            ->toArray();

        $this->marcas = Marca::query()
            ->select(['Id_Marca', 'Nombre_Marca'])
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

        $query = Producto::query()
            ->with([
                'categoria:Id_Categoria,Nombre_Categoria',
                'marca:Id_Marca,Nombre_Marca',
            ])
            ->select([
                'Id_Producto',
                'Id_Categoria',
                'Id_Marca',
                'Nombre_Producto',
                'Modelo',
                'Stock_Actual',
                'Precio_Venta',
                'Estado',
            ])
            ->where('Estado', 1)
            ->where('Stock_Actual', '>', 0)
            ->where(function ($query) {
                $query->doesntHave('series')
                    ->orWhereHas('series', function ($serie) {
                        $serie->where('Estado', self::ESTADO_SERIE_DISPONIBLE);
                    });
            })
            ->orderByDesc('Id_Producto');

        if ($busqueda !== '') {
            $query->where(function ($query) use ($busqueda) {
                $query->where('Nombre_Producto', 'like', "%{$busqueda}%")
                    ->orWhere('Modelo', 'like', "%{$busqueda}%")
                    ->orWhereHas('categoria', function ($categoria) use ($busqueda) {
                        $categoria->where('Nombre_Categoria', 'like', "%{$busqueda}%");
                    })
                    ->orWhereHas('marca', function ($marca) use ($busqueda) {
                        $marca->where('Nombre_Marca', 'like', "%{$busqueda}%");
                    })
                    ->orWhereHas('series', function ($serie) use ($busqueda) {
                        $serie->where('Estado', self::ESTADO_SERIE_DISPONIBLE)
                            ->where('Numero_Serie', 'like', "%{$busqueda}%");
                    });
            });
        }

        $this->productos = $query->limit(25)
            ->get()
            ->map(function ($producto) {
                return [
                    'id_producto' => (int) $producto->Id_Producto,
                    'codigo' => (int) $producto->Id_Producto,
                    'producto' => $producto->Nombre_Producto,
                    'categoria' => $producto->categoria?->Nombre_Categoria ?: '—',
                    'marca' => $producto->marca?->Nombre_Marca ?: '—',
                    'modelo' => $producto->Modelo ?: '—',
                    'stock' => (int) $producto->Stock_Actual,
                    'precio_venta' => 'C$ ' . number_format((float) $producto->Precio_Venta, 0, '.', ','),
                    'estado' => 'Activo',
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
                        <input wire:model.live.debounce.250ms="garantiaNuevo" type="number" min="0" placeholder="Meses"
                            @readonly($bloquearGarantiaNuevo)
                            class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none placeholder:text-[#7B8794] focus:ring-2 focus:ring-[#2E8BC0] {{ $bloquearGarantiaNuevo ? 'cursor-not-allowed opacity-60' : '' }}" />

                        @if ($bloquearGarantiaNuevo)
                        <span class="mt-1 block text-xs font-medium text-[#5F6B7A]">
                            Bloqueado porque está usando garantía para producto usado.
                        </span>
                        @endif

                        @error('garantiaNuevo')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Garantía usado</label>
                        <input wire:model.live.debounce.250ms="garantiaUsado" type="number" min="0" placeholder="Meses"
                            @readonly($bloquearGarantiaUsado)
                            class="h-10 min-h-10 w-full rounded-lg border-0 bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none placeholder:text-[#7B8794] focus:ring-2 focus:ring-[#2E8BC0] {{ $bloquearGarantiaUsado ? 'cursor-not-allowed opacity-60' : '' }}" />

                        @if ($bloquearGarantiaUsado)
                        <span class="mt-1 block text-xs font-medium text-[#5F6B7A]">
                            Bloqueado porque está usando garantía para producto nuevo.
                        </span>
                        @endif

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
                    <x-button label="{{ $productoBaseSeleccionado ? 'Cancelar edición' : 'Cancelar' }}" type="button"
                        wire:click="cancelarFormularioProducto"
                        class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]" />

                    <x-button label="Guardar producto" type="submit"
                        class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]" />
                </x-slot:actions>
            </x-card>
        </form>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-[#1A2B42]">Resumen de productos con stock</h2>
                    <p class="text-sm text-[#5F6B7A]">
                        No se muestran productos inactivos ni sin stock.
                    </p>
                </div>

                <div class="w-full md:w-80">
                    <x-input wire:model.live.debounce.250ms="buscar" type="text" placeholder="Buscar productos"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-[#D7E4F3] bg-white">
                <div class="max-h-104 overflow-x-auto overflow-y-auto">
                    <table class="min-w-262.5 w-full border-separate border-spacing-0 text-[13px] text-[#1A2B42]">
                        <thead class="sticky top-0 z-10">
                            <tr>
                                <th
                                    class="rounded-tl-xl bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">
                                    Código
                                </th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">
                                    Producto
                                </th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">
                                    Marca
                                </th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">
                                    Modelo
                                </th>
                                <th
                                    class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white whitespace-nowrap">
                                    Stock
                                </th>
                                <th
                                    class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white whitespace-nowrap">
                                    Precio venta
                                </th>
                                <th
                                    class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white whitespace-nowrap">
                                    Estado
                                </th>
                                <th
                                    class="rounded-tr-xl bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white whitespace-nowrap">
                                    Acciones
                                </th>
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
                                    {{ $producto['stock'] }}
                                </td>
                                <td class="px-3 py-3 text-right align-middle whitespace-nowrap">
                                    {{ $producto['precio_venta'] }}
                                </td>
                                <td class="px-3 py-3 text-center align-middle whitespace-nowrap">
                                    <span
                                        class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
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
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-[#7B8794]">
                                    No hay productos con stock.
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
                <h3 class="text-2xl font-bold text-[#1A2B42]">Series disponibles del producto</h3>
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
