<?php

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoSerie;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;
    use WithPagination;

    public string $buscarProducto = '';
    public array $coincidenciasProducto = [];

    public ?int $productoId = null;

    public string $productoNombre = '';
    public string $marca = '';
    public string $categoria = '';
    public string $modelo = '';

    public int $stockActual = 0;
    public string $cantidad = '1';

    public bool $productoTieneSeries = false;
    public array $seriesDisponiblesOptions = [];
    public string $serieParaAgregar = '';
    public array $seriesSeleccionadas = [];
    public array $seriesSeleccionadasDetalle = [];

    public string $tipoMovimiento = MovimientoInventario::TIPO_SALIDA_AJUSTE;
    public string $motivoMovimiento = '';

    public string $buscarSalida = '';
    public string $filtroTipo = '';
    public string $filtroDesde = '';
    public string $filtroHasta = '';

    public array $tipoMovimientoOptions = [
        ['id' => MovimientoInventario::TIPO_SALIDA_AJUSTE, 'name' => 'Ajuste de inventario'],
        ['id' => MovimientoInventario::TIPO_SALIDA_DANO, 'name' => 'Producto dañado'],
        ['id' => MovimientoInventario::TIPO_SALIDA_DEFECTO, 'name' => 'Producto con defecto'],
        ['id' => MovimientoInventario::TIPO_SALIDA_USO_PERSONAL, 'name' => 'Uso interno / personal'],
        ['id' => MovimientoInventario::TIPO_SALIDA_PERDIDA, 'name' => 'Producto perdido'],
        ['id' => MovimientoInventario::TIPO_SALIDA_MERMA, 'name' => 'Merma de inventario'],
    ];

    public array $filtroTipoOptions = [
        ['id' => '', 'name' => 'Todos'],
        ['id' => MovimientoInventario::TIPO_SALIDA_INSTALACION, 'name' => 'Instalación'],
        ['id' => MovimientoInventario::TIPO_SALIDA_SERVICIO_TECNICO, 'name' => 'Servicio técnico'],
        ['id' => MovimientoInventario::TIPO_SALIDA_CAMBIO_PRODUCTO, 'name' => 'Cambio de producto'],
        ['id' => MovimientoInventario::TIPO_SALIDA_AJUSTE, 'name' => 'Ajuste'],
        ['id' => MovimientoInventario::TIPO_SALIDA_DANO, 'name' => 'Dañado'],
        ['id' => MovimientoInventario::TIPO_SALIDA_DEFECTO, 'name' => 'Defecto'],
        ['id' => MovimientoInventario::TIPO_SALIDA_USO_PERSONAL, 'name' => 'Uso interno'],
        ['id' => MovimientoInventario::TIPO_SALIDA_PERDIDA, 'name' => 'Perdido'],
        ['id' => MovimientoInventario::TIPO_SALIDA_MERMA, 'name' => 'Merma'],
        ['id' => MovimientoInventario::TIPO_SALIDA_VENTA, 'name' => 'Venta'],
        ['id' => MovimientoInventario::TIPO_SALIDA_DEVOLUCION_PROVEEDOR, 'name' => 'Devolución proveedor'],
    ];

    public array $tipoMovimientoLabels = [
        MovimientoInventario::TIPO_SALIDA_INSTALACION => 'Instalación',
        MovimientoInventario::TIPO_SALIDA_SERVICIO_TECNICO => 'Servicio técnico',
        MovimientoInventario::TIPO_SALIDA_CAMBIO_PRODUCTO => 'Cambio de producto',
        MovimientoInventario::TIPO_SALIDA_AJUSTE => 'Ajuste de inventario',
        MovimientoInventario::TIPO_SALIDA_DANO => 'Producto dañado',
        MovimientoInventario::TIPO_SALIDA_DEFECTO => 'Producto con defecto',
        MovimientoInventario::TIPO_SALIDA_USO_PERSONAL => 'Uso interno / personal',
        MovimientoInventario::TIPO_SALIDA_PERDIDA => 'Producto perdido',
        MovimientoInventario::TIPO_SALIDA_MERMA => 'Merma de inventario',
        MovimientoInventario::TIPO_SALIDA_VENTA => 'Venta',
        MovimientoInventario::TIPO_SALIDA_DEVOLUCION_PROVEEDOR => 'Devolución proveedor',
    ];

    protected function rules(): array
    {
        return [
            'productoId' => ['required', 'integer', 'exists:producto,Id_Producto'],
            'tipoMovimiento' => ['required', Rule::in($this->tiposSalidaFormulario())],
            'cantidad' => ['required', 'integer', 'min:1'],
            'motivoMovimiento' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    public function paginationView(): string
{
    return 'vendor.pagination.gnet';
}

    protected function messages(): array
    {
        return [
            'productoId.required' => 'Selecciona un producto.',
            'productoId.exists' => 'El producto seleccionado no existe.',
            'tipoMovimiento.required' => 'Selecciona el tipo de salida.',
            'tipoMovimiento.in' => 'El tipo de salida no es válido.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser mayor o igual a 1.',
            'motivoMovimiento.required' => 'El motivo es obligatorio.',
            'motivoMovimiento.min' => 'El motivo debe tener al menos 3 caracteres.',
            'motivoMovimiento.max' => 'El motivo no debe superar los 255 caracteres.',
        ];
    }

    private function tiposSalidaFormulario(): array
    {
        return [
            MovimientoInventario::TIPO_SALIDA_AJUSTE,
            MovimientoInventario::TIPO_SALIDA_DANO,
            MovimientoInventario::TIPO_SALIDA_DEFECTO,
            MovimientoInventario::TIPO_SALIDA_USO_PERSONAL,
            MovimientoInventario::TIPO_SALIDA_PERDIDA,
            MovimientoInventario::TIPO_SALIDA_MERMA,
        ];
    }

    private function tiposSalidaTabla(): array
    {
        return [
            MovimientoInventario::TIPO_SALIDA_INSTALACION,
            MovimientoInventario::TIPO_SALIDA_SERVICIO_TECNICO,
            MovimientoInventario::TIPO_SALIDA_CAMBIO_PRODUCTO,
            MovimientoInventario::TIPO_SALIDA_AJUSTE,
            MovimientoInventario::TIPO_SALIDA_DANO,
            MovimientoInventario::TIPO_SALIDA_DEFECTO,
            MovimientoInventario::TIPO_SALIDA_USO_PERSONAL,
            MovimientoInventario::TIPO_SALIDA_PERDIDA,
            MovimientoInventario::TIPO_SALIDA_MERMA,
            MovimientoInventario::TIPO_SALIDA_VENTA,
            MovimientoInventario::TIPO_SALIDA_DEVOLUCION_PROVEEDOR,
        ];
    }

    public function updatedBuscarProducto(): void
    {
        $termino = trim($this->buscarProducto);

        $this->limpiarProductoSeleccionado();

        if (mb_strlen($termino) < 2) {
            return;
        }

        $like = '%' . $termino . '%';

        $resultados = [];

        $productos = Producto::query()
            ->with(['categoria', 'marca'])
            ->activos()
            ->conStock()
            ->where(function ($query) use ($like) {
                $query
                    ->where('Nombre_Producto', 'like', $like)
                    ->orWhere('Modelo', 'like', $like)
                    ->orWhereHas('series', function ($serieQuery) use ($like) {
                        $serieQuery
                            ->where('Estado', ProductoSerie::ESTADO_DISPONIBLE)
                            ->where('Numero_Serie', 'like', $like);
                    });
            })
            ->orderBy('Nombre_Producto')
            ->limit(8)
            ->get();

        foreach ($productos as $producto) {
            $resultados[$producto->Id_Producto . '-producto'] = $this->formatearResultadoProducto($producto);
        }

        $series = ProductoSerie::query()
            ->with(['producto.categoria', 'producto.marca'])
            ->disponibles()
            ->whereHas('producto', function ($productoQuery) {
                $productoQuery
                    ->activos()
                    ->conStock();
            })
            ->where(function ($query) use ($like) {
                $query
                    ->where('Numero_Serie', 'like', $like)
                    ->orWhereHas('producto', function ($productoQuery) use ($like) {
                        $productoQuery
                            ->where('Nombre_Producto', 'like', $like)
                            ->orWhere('Modelo', 'like', $like);
                    });
            })
            ->orderBy('Numero_Serie')
            ->limit(8)
            ->get();

        foreach ($series as $serie) {
            if (! $serie->producto) {
                continue;
            }

            $resultados[$serie->producto->Id_Producto . '-' . $serie->id_producto_serie] =
                $this->formatearResultadoProducto($serie->producto, $serie);
        }

        $this->coincidenciasProducto = array_slice(array_values($resultados), 0, 12);
    }

    private function formatearResultadoProducto(Producto $producto, ?ProductoSerie $serie = null): array
    {
        return [
            'producto_id' => (int) $producto->Id_Producto,
            'serie_id' => $serie ? (int) $serie->id_producto_serie : null,
            'producto' => $producto->Nombre_Producto,
            'modelo' => $producto->Modelo ?: 'Sin modelo',
            'marca' => $producto->marca?->Nombre_Marca ?: 'Sin marca',
            'categoria' => $producto->categoria?->Nombre_Categoria ?: 'Sin categoría',
            'stock' => (int) $producto->Stock_Actual,
            'serie' => $serie?->Numero_Serie,
        ];
    }

    public function seleccionarProducto(int $productoId, ?int $serieId = null): void
    {
        $producto = Producto::query()
            ->with(['categoria', 'marca'])
            ->activos()
            ->find($productoId);

        if (! $producto) {
            $this->error('No se encontró el producto seleccionado.');
            return;
        }

        if ((int) $producto->Stock_Actual <= 0) {
            $this->error('Este producto no tiene stock disponible.');
            return;
        }

        $productoTieneSeries = ProductoSerie::query()
            ->where('Id_Producto', $producto->Id_Producto)
            ->exists();

        $seriesDisponibles = ProductoSerie::query()
            ->where('Id_Producto', $producto->Id_Producto)
            ->disponibles()
            ->orderBy('Numero_Serie')
            ->get();

        $this->productoId = (int) $producto->Id_Producto;
        $this->productoNombre = $producto->Nombre_Producto;
        $this->marca = $producto->marca?->Nombre_Marca ?: 'Sin marca';
        $this->categoria = $producto->categoria?->Nombre_Categoria ?: 'Sin categoría';
        $this->modelo = $producto->Modelo ?: 'Sin modelo';
        $this->stockActual = (int) $producto->Stock_Actual;

        $this->productoTieneSeries = $productoTieneSeries;

        $this->seriesDisponiblesOptions = $seriesDisponibles
            ->map(fn (ProductoSerie $serie) => [
                'id' => (int) $serie->id_producto_serie,
                'name' => $serie->Numero_Serie,
            ])
            ->values()
            ->all();

        $this->cantidad = '1';
        $this->serieParaAgregar = '';
        $this->seriesSeleccionadas = [];
        $this->seriesSeleccionadasDetalle = [];

        if ($serieId && $productoTieneSeries) {
            $seriePreseleccionada = $seriesDisponibles->firstWhere('id_producto_serie', $serieId);

            if ($seriePreseleccionada) {
                $this->seriesSeleccionadas[] = (int) $seriePreseleccionada->id_producto_serie;

                $this->seriesSeleccionadasDetalle[] = [
                    'id' => (int) $seriePreseleccionada->id_producto_serie,
                    'numero' => $seriePreseleccionada->Numero_Serie,
                ];
            }
        }

        $this->buscarProducto = $producto->Nombre_Producto;
        $this->coincidenciasProducto = [];

        $this->resetErrorBag();
    }

    public function updatedCantidad(): void
    {
        if ($this->cantidad === '') {
            return;
        }

        $cantidad = (int) $this->cantidad;

        if ($cantidad < 1) {
            $this->cantidad = '1';
            $cantidad = 1;
        }

        if (! $this->productoTieneSeries) {
            return;
        }

        $this->seriesSeleccionadas = array_slice($this->seriesSeleccionadasIds(), 0, $cantidad);

        $idsPermitidos = $this->seriesSeleccionadas;

        $this->seriesSeleccionadasDetalle = array_values(array_filter(
            $this->seriesSeleccionadasDetalle,
            fn ($serie) => in_array((int) $serie['id'], $idsPermitidos, true)
        ));
    }

    private function seriesSeleccionadasIds(): array
    {
        return array_values(array_map(
            'intval',
            array_filter($this->seriesSeleccionadas, fn ($id) => $id !== null && $id !== '')
        ));
    }

    public function seriesRestantesPorSeleccionar(): int
    {
        return max((int) $this->cantidad - count($this->seriesSeleccionadasIds()), 0);
    }

    public function seriesParaAgregarOptions(): array
    {
        $opciones = [
            ['id' => '', 'name' => 'Selecciona una serie'],
        ];

        if (! $this->productoTieneSeries) {
            return $opciones;
        }

        if ($this->seriesRestantesPorSeleccionar() <= 0) {
            return [
                ['id' => '', 'name' => 'Cantidad de series completa'],
            ];
        }

        $seleccionadas = $this->seriesSeleccionadasIds();

        foreach ($this->seriesDisponiblesOptions as $opcion) {
            $id = (int) $opcion['id'];

            if (! in_array($id, $seleccionadas, true)) {
                $opciones[] = $opcion;
            }
        }

        return $opciones;
    }

    public function agregarSerie(): void
    {
        if (! $this->productoId || ! $this->productoTieneSeries) {
            $this->error('Selecciona un producto con números de serie.');
            return;
        }

        if ($this->seriesRestantesPorSeleccionar() <= 0) {
            $this->error('Ya seleccionaste la cantidad de series requerida.');
            return;
        }

        if ($this->serieParaAgregar === '' || $this->serieParaAgregar === null) {
            throw ValidationException::withMessages([
                'serieParaAgregar' => 'Selecciona un número de serie.',
            ]);
        }

        $serieId = (int) $this->serieParaAgregar;
        $seleccionadas = $this->seriesSeleccionadasIds();

        if (in_array($serieId, $seleccionadas, true)) {
            throw ValidationException::withMessages([
                'serieParaAgregar' => 'Este número de serie ya fue agregado.',
            ]);
        }

        $opcion = collect($this->seriesDisponiblesOptions)->firstWhere('id', $serieId);

        if (! $opcion) {
            throw ValidationException::withMessages([
                'serieParaAgregar' => 'El número de serie seleccionado no está disponible.',
            ]);
        }

        $this->seriesSeleccionadas[] = $serieId;

        $this->seriesSeleccionadasDetalle[] = [
            'id' => $serieId,
            'numero' => $opcion['name'],
        ];

        $this->serieParaAgregar = '';

        $this->resetErrorBag('serieParaAgregar');
        $this->resetErrorBag('seriesSeleccionadas');
    }

    public function quitarSerie(int $serieId): void
    {
        $this->seriesSeleccionadas = array_values(array_filter(
            $this->seriesSeleccionadasIds(),
            fn ($id) => (int) $id !== (int) $serieId
        ));

        $this->seriesSeleccionadasDetalle = array_values(array_filter(
            $this->seriesSeleccionadasDetalle,
            fn ($serie) => (int) $serie['id'] !== (int) $serieId
        ));

        if ((int) $this->serieParaAgregar === (int) $serieId) {
            $this->serieParaAgregar = '';
        }

        $this->resetErrorBag('seriesSeleccionadas');
    }

    public function registrarSalida(): void
    {
        $this->validate();

        $cantidadSalida = (int) $this->cantidad;

        try {
            DB::transaction(function () use ($cantidadSalida) {
                $producto = Producto::query()
                    ->whereKey($this->productoId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! (bool) $producto->Estado) {
                    throw ValidationException::withMessages([
                        'productoId' => 'El producto seleccionado no está activo.',
                    ]);
                }

                if ((int) $producto->Stock_Actual < $cantidadSalida) {
                    throw ValidationException::withMessages([
                        'cantidad' => 'La cantidad no puede ser mayor al stock actual.',
                    ]);
                }

                $productoManejaSeries = ProductoSerie::query()
                    ->where('Id_Producto', $producto->Id_Producto)
                    ->exists();

                if ($productoManejaSeries) {
                    $seriesIds = $this->seriesSeleccionadasIds();
                    $seriesIdsUnicas = array_values(array_unique($seriesIds));

                    if (count($seriesIds) !== $cantidadSalida) {
                        throw ValidationException::withMessages([
                            'seriesSeleccionadas' => "Debes seleccionar {$cantidadSalida} número(s) de serie antes de registrar la salida.",
                        ]);
                    }

                    if (count($seriesIdsUnicas) !== count($seriesIds)) {
                        throw ValidationException::withMessages([
                            'seriesSeleccionadas' => 'No puedes agregar el mismo número de serie más de una vez.',
                        ]);
                    }

                    $seriesDisponibles = ProductoSerie::query()
                        ->where('Id_Producto', $producto->Id_Producto)
                        ->where('Estado', ProductoSerie::ESTADO_DISPONIBLE)
                        ->lockForUpdate()
                        ->get();

                    if ($cantidadSalida > $seriesDisponibles->count()) {
                        throw ValidationException::withMessages([
                            'cantidad' => 'La cantidad no puede ser mayor a las series disponibles.',
                        ]);
                    }

                    $seriesSeleccionadas = ProductoSerie::query()
                        ->where('Id_Producto', $producto->Id_Producto)
                        ->where('Estado', ProductoSerie::ESTADO_DISPONIBLE)
                        ->whereIn('id_producto_serie', $seriesIdsUnicas)
                        ->lockForUpdate()
                        ->get();

                    if ($seriesSeleccionadas->count() !== $cantidadSalida) {
                        throw ValidationException::withMessages([
                            'seriesSeleccionadas' => 'Una o más series seleccionadas ya no están disponibles.',
                        ]);
                    }

                    foreach ($seriesSeleccionadas as $serie) {
                        MovimientoInventario::query()->create([
                            'Id_Producto' => $producto->Id_Producto,
                            'Id_Producto_Serie' => $serie->id_producto_serie,
                            'Fecha_Movimiento' => now(),
                            'Tipo_Movimiento' => $this->tipoMovimiento,
                            'Cantidad' => 1,
                            'Motivo_Movimiento' => trim($this->motivoMovimiento),
                        ]);

                        $serie->Estado = $this->estadoSeriePorTipoSalida($this->tipoMovimiento);
                        $serie->Observacion = trim($this->motivoMovimiento);
                        $serie->save();
                    }
                } else {
                    MovimientoInventario::query()->create([
                        'Id_Producto' => $producto->Id_Producto,
                        'Id_Producto_Serie' => null,
                        'Fecha_Movimiento' => now(),
                        'Tipo_Movimiento' => $this->tipoMovimiento,
                        'Cantidad' => $cantidadSalida,
                        'Motivo_Movimiento' => trim($this->motivoMovimiento),
                    ]);
                }

                $producto->Stock_Actual = (int) $producto->Stock_Actual - $cantidadSalida;
                $producto->save();
            });

            $this->success('Salida de inventario registrada correctamente.');

            $this->limpiarFormulario();
            $this->resetPage();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            $this->error('No se pudo registrar la salida de inventario.');
        }
    }

    private function estadoSeriePorTipoSalida(string $tipoMovimiento): string
    {
        return match ($tipoMovimiento) {
            MovimientoInventario::TIPO_SALIDA_DANO,
            MovimientoInventario::TIPO_SALIDA_DEFECTO => ProductoSerie::ESTADO_DANADO,
            MovimientoInventario::TIPO_SALIDA_USO_PERSONAL => ProductoSerie::ESTADO_USO_INTERNO,
            MovimientoInventario::TIPO_SALIDA_PERDIDA => ProductoSerie::ESTADO_PERDIDO,
            default => ProductoSerie::ESTADO_RETIRADO_INVENTARIO,
        };
    }

    private function limpiarProductoSeleccionado(): void
    {
        $this->reset([
            'coincidenciasProducto',
            'productoId',
            'productoNombre',
            'marca',
            'categoria',
            'modelo',
            'stockActual',
            'productoTieneSeries',
            'seriesDisponiblesOptions',
            'serieParaAgregar',
            'seriesSeleccionadas',
            'seriesSeleccionadasDetalle',
        ]);

        $this->cantidad = '1';
    }

    public function limpiarFormulario(): void
    {
        $this->reset([
            'buscarProducto',
            'coincidenciasProducto',
            'productoId',
            'productoNombre',
            'marca',
            'categoria',
            'modelo',
            'stockActual',
            'cantidad',
            'productoTieneSeries',
            'seriesDisponiblesOptions',
            'serieParaAgregar',
            'seriesSeleccionadas',
            'seriesSeleccionadasDetalle',
            'motivoMovimiento',
        ]);

        $this->cantidad = '1';
        $this->tipoMovimiento = MovimientoInventario::TIPO_SALIDA_AJUSTE;

        $this->resetErrorBag();
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'buscarSalida',
            'filtroTipo',
            'filtroDesde',
            'filtroHasta',
        ]);

        $this->resetPage();
    }

    public function updatedBuscarSalida(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroTipo(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroHasta(): void
    {
        $this->resetPage();
    }

    public function salidas(): LengthAwarePaginator
    {
        $like = '%' . trim($this->buscarSalida) . '%';

        return MovimientoInventario::query()
            ->with([
                'producto.categoria',
                'producto.marca',
                'productoSerie',
            ])
            ->whereIn('Tipo_Movimiento', $this->tiposSalidaTabla())
            ->when(trim($this->buscarSalida) !== '', function ($query) use ($like) {
                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->where('Motivo_Movimiento', 'like', $like)
                        ->orWhereHas('producto', function ($productoQuery) use ($like) {
                            $productoQuery
                                ->where('Nombre_Producto', 'like', $like)
                                ->orWhere('Modelo', 'like', $like)
                                ->orWhereHas('categoria', function ($categoriaQuery) use ($like) {
                                    $categoriaQuery->where('Nombre_Categoria', 'like', $like);
                                })
                                ->orWhereHas('marca', function ($marcaQuery) use ($like) {
                                    $marcaQuery->where('Nombre_Marca', 'like', $like);
                                });
                        })
                        ->orWhereHas('productoSerie', function ($serieQuery) use ($like) {
                            $serieQuery->where('Numero_Serie', 'like', $like);
                        });
                });
            })
            ->when($this->filtroTipo !== '', function ($query) {
                $query->where('Tipo_Movimiento', $this->filtroTipo);
            })
            ->when($this->filtroDesde !== '', function ($query) {
                $query->whereDate('Fecha_Movimiento', '>=', $this->filtroDesde);
            })
            ->when($this->filtroHasta !== '', function ($query) {
                $query->whereDate('Fecha_Movimiento', '<=', $this->filtroHasta);
            })
            ->orderByDesc('Fecha_Movimiento')
            ->paginate(8);
    }

    public function with(): array
    {
        return [
            'salidas' => $this->salidas(),
        ];
    }
};
?>

<div class="min-h-screen bg-[#F0F3F7] px-4 py-4 text-[#1A2B42] md:px-6 md:py-5">
    <div class="mx-auto flex w-full max-w-[1450px] flex-col gap-4">

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#1A2B42]">Otras salidas</h1>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    Registra productos retirados del inventario y controla números de serie cuando aplique.
                </p>
            </div>

            <div class="rounded-2xl border border-[#D7E4F3] bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5F6B7A]">
                    Producto seleccionado
                </p>
                <p class="text-lg font-bold text-[#1A2B42]">
                    {{ $productoNombre !== '' ? $productoNombre : 'Sin producto seleccionado' }}
                </p>
            </div>
        </div>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Registrar salida</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Buscá el producto por nombre, modelo o número de serie. Si maneja series, agregalas una por una.
                </p>
            </div>

            <form wire:submit.prevent="registrarSalida" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="relative xl:col-span-4">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Buscar producto <span class="text-red-600">*</span>
                        </label>

                        <input
                            wire:model.live.debounce.350ms="buscarProducto"
                            type="text"
                            placeholder="Nombre, modelo o número de serie"
                            class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none placeholder:text-[#7B8794] focus:border-[#2E8BC0] focus:ring-2 focus:ring-[#2E8BC0]/20"
                        />

                        @error('productoId')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror

                        @if (! empty($coincidenciasProducto))
                            <div class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-[#D7E4F3] bg-white shadow-lg">
                                @foreach ($coincidenciasProducto as $item)
                                    <button
                                        type="button"
                                        wire:click="seleccionarProducto({{ $item['producto_id'] }}, {{ $item['serie_id'] ? (int) $item['serie_id'] : 'null' }})"
                                        class="block w-full border-b border-[#EEF3F8] px-4 py-3 text-left text-sm hover:bg-[#EAF2FB]"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <span class="block font-semibold text-[#1A2B42]">
                                                    {{ $item['producto'] }}
                                                </span>

                                                <span class="block text-xs text-[#5F6B7A]">
                                                    {{ $item['marca'] }} · {{ $item['categoria'] }} · Modelo: {{ $item['modelo'] }}
                                                </span>

                                                @if ($item['serie'])
                                                    <span class="mt-1 block text-xs font-semibold text-[#2E8BC0]">
                                                        Serie encontrada: {{ $item['serie'] }}
                                                    </span>
                                                @endif
                                            </div>

                                            <span class="rounded-full bg-[#F0F3F7] px-3 py-1 text-xs font-semibold text-[#1A2B42]">
                                                Stock: {{ number_format($item['stock']) }}
                                            </span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Marca</label>
                        <input
                            wire:model="marca"
                            readonly
                            class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Categoría</label>
                        <input
                            wire:model="categoria"
                            readonly
                            class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Modelo</label>
                        <input
                            wire:model="modelo"
                            readonly
                            class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none"
                        />
                    </div>

                    <div class="xl:col-span-1">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Stock</label>
                        <input
                            wire:model="stockActual"
                            readonly
                            class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none"
                        />
                    </div>

                    <div class="xl:col-span-1">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Cant. <span class="text-red-600">*</span>
                        </label>
                        <input
                            wire:model.live="cantidad"
                            type="number"
                            min="1"
                            class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none focus:border-[#2E8BC0] focus:ring-2 focus:ring-[#2E8BC0]/20"
                        />
                        @error('cantidad')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                @if ($productoId && $productoTieneSeries)
                    <div class="rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-4">
                        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-[#1A2B42]">Números de serie a retirar</h3>
                                <p class="text-sm text-[#5F6B7A]">
                                    Debes seleccionar {{ (int) $cantidad }} serie(s).
                                    Faltan <span class="font-semibold text-[#1A2B42]">{{ $this->seriesRestantesPorSeleccionar() }}</span>.
                                </p>
                            </div>

                            <div class="rounded-xl border border-[#D7E4F3] bg-white px-4 py-2 text-sm">
                                <span class="font-semibold text-[#1A2B42]">Series disponibles:</span>
                                <span class="text-[#5F6B7A]">{{ count($seriesDisponiblesOptions) }}</span>
                            </div>
                        </div>

                        @if (count($seriesDisponiblesOptions) === 0)
                            <div class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">
                                Este producto maneja números de serie, pero no tiene series disponibles para retirar.
                            </div>
                        @else
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                                <div class="lg:col-span-5">
                                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                                        Número de serie
                                    </label>

                                    <select
                                        wire:model.live="serieParaAgregar"
                                        class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-white px-3 text-sm text-[#1A2B42] outline-none focus:border-[#2E8BC0] focus:ring-2 focus:ring-[#2E8BC0]/20"
                                    >
                                        @foreach ($this->seriesParaAgregarOptions() as $serieOption)
                                            <option value="{{ $serieOption['id'] }}">{{ $serieOption['name'] }}</option>
                                        @endforeach
                                    </select>

                                    @error('serieParaAgregar')
                                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex items-end lg:col-span-2">
                                    <button
                                        type="button"
                                        wire:click="agregarSerie"
                                        class="h-10 w-full rounded-lg bg-[#2E8BC0] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#0B6FE4]"
                                    >
                                        Agregar
                                    </button>
                                </div>

                                <div class="lg:col-span-5">
                                    <div class="rounded-xl border border-[#D7E4F3] bg-white px-4 py-3">
                                        <p class="text-sm font-semibold text-[#1A2B42]">
                                            Series agregadas: {{ count($seriesSeleccionadasDetalle) }}
                                        </p>
                                        <p class="text-xs text-[#5F6B7A]">
                                            Cada serie representa una unidad específica del producto.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @error('seriesSeleccionadas')
                                <span class="mt-2 block text-xs text-red-600">{{ $message }}</span>
                            @enderror

                            <div class="mt-4">
                                @if (count($seriesSeleccionadasDetalle) > 0)
                                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($seriesSeleccionadasDetalle as $serie)
                                            <div
                                                wire:key="serie-agregada-{{ $serie['id'] }}"
                                                class="flex items-center justify-between gap-3 rounded-xl border border-[#D7E4F3] bg-white px-3 py-2"
                                            >
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold text-[#1A2B42]">
                                                        {{ $serie['numero'] }}
                                                    </p>
                                                    <p class="text-xs text-[#5F6B7A]">Lista para salida</p>
                                                </div>

                                                <button
                                                    type="button"
                                                    wire:click="quitarSerie({{ (int) $serie['id'] }})"
                                                    class="rounded-lg border border-red-200 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"
                                                >
                                                    Quitar
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="rounded-xl border border-dashed border-[#D7E4F3] bg-white px-4 py-5 text-center text-sm text-[#5F6B7A]">
                                        Todavía no has agregado números de serie.
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                @if ($productoId && ! $productoTieneSeries)
                    <div class="rounded-xl border border-[#D7E4F3] bg-[#F7F9FC] px-4 py-3 text-sm text-[#5F6B7A]">
                        Este producto no maneja números de serie, así que la salida se registrará por cantidad.
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-3">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Tipo de salida <span class="text-red-600">*</span>
                        </label>

                        <select
                            wire:model="tipoMovimiento"
                            class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none focus:border-[#2E8BC0] focus:ring-2 focus:ring-[#2E8BC0]/20"
                        >
                            @foreach ($tipoMovimientoOptions as $tipoOption)
                                <option value="{{ $tipoOption['id'] }}">{{ $tipoOption['name'] }}</option>
                            @endforeach
                        </select>

                        @error('tipoMovimiento')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="xl:col-span-9">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                            Motivo <span class="text-red-600">*</span>
                        </label>

                        <textarea
                            wire:model.defer="motivoMovimiento"
                            rows="2"
                            maxlength="255"
                            placeholder="Describe por qué sale este producto del inventario"
                            class="w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 py-2 text-sm text-[#1A2B42] outline-none placeholder:text-[#7B8794] focus:border-[#2E8BC0] focus:ring-2 focus:ring-[#2E8BC0]/20"
                        ></textarea>

                        @error('motivoMovimiento')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        wire:click="limpiarFormulario"
                        class="rounded-lg border border-[#D7E4F3] px-4 py-2 text-sm font-semibold text-[#1A2B42] hover:bg-[#F0F3F7]"
                    >
                        Limpiar
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="registrarSalida"
                        class="rounded-lg bg-[#2E8BC0] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#0B6FE4] disabled:opacity-60"
                    >
                        Registrar salida
                    </button>
                </div>
            </form>
        </x-card>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-[#1A2B42]">Salidas registradas</h2>
                    <p class="text-sm text-[#5F6B7A]">
                        Consulta los movimientos de salida y filtra por tipo, fecha o producto.
                    </p>
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-12">
                <div class="xl:col-span-4">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Buscar</label>
                    <input
                        wire:model.live.debounce.350ms="buscarSalida"
                        type="text"
                        placeholder="Producto, modelo, serie o motivo"
                        class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none placeholder:text-[#7B8794] focus:border-[#2E8BC0] focus:ring-2 focus:ring-[#2E8BC0]/20"
                    />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo</label>
                    <select
                        wire:model.live="filtroTipo"
                        class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none focus:border-[#2E8BC0] focus:ring-2 focus:ring-[#2E8BC0]/20"
                    >
                        @foreach ($filtroTipoOptions as $filtro)
                            <option value="{{ $filtro['id'] }}">{{ $filtro['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Desde</label>
                    <input
                        wire:model.live="filtroDesde"
                        type="date"
                        class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none focus:border-[#2E8BC0] focus:ring-2 focus:ring-[#2E8BC0]/20"
                    />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Hasta</label>
                    <input
                        wire:model.live="filtroHasta"
                        type="date"
                        class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-[#F0F3F7] px-3 text-sm text-[#1A2B42] outline-none focus:border-[#2E8BC0] focus:ring-2 focus:ring-[#2E8BC0]/20"
                    />
                </div>

                <div class="flex items-end xl:col-span-2">
                    <button
                        type="button"
                        wire:click="limpiarFiltros"
                        class="h-10 w-full rounded-lg border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] shadow-sm hover:bg-[#F0F3F7]"
                    >
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-[#D7E4F3]">
                <div class="overflow-x-auto">
                    <table class="min-w-[980px] w-full border-separate border-spacing-0 text-[13px] text-[#1A2B42]">
                        <thead class="sticky top-0 z-10">
                            <tr>
                                <th class="rounded-tl-xl bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">Fecha</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">Producto</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">Serie</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">Tipo</th>
                                <th class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white whitespace-nowrap">Cant.</th>
                                <th class="rounded-tr-xl bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white whitespace-nowrap">Motivo</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($salidas as $salida)
                                <tr class="odd:bg-white even:bg-[#F8FBFF] hover:bg-[#EAF4FD]">
                                    <td class="px-3 py-3 align-middle whitespace-nowrap text-[#5F6B7A]">
                                        {{ $salida->Fecha_Movimiento?->format('d/m/Y h:i A') }}
                                    </td>

                                    <td class="px-3 py-3 align-middle">
                                        <p class="font-semibold text-[#1A2B42]">
                                            {{ $salida->producto?->Nombre_Producto ?? 'Producto no disponible' }}
                                        </p>

                                        <p class="text-xs text-[#5F6B7A]">
                                            {{ $salida->producto?->marca?->Nombre_Marca ?? 'Sin marca' }}
                                            ·
                                            {{ $salida->producto?->categoria?->Nombre_Categoria ?? 'Sin categoría' }}
                                            ·
                                            {{ $salida->producto?->Modelo ?? 'Sin modelo' }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-3 align-middle whitespace-nowrap">
                                        @if ($salida->productoSerie)
                                            <p class="font-semibold text-[#1A2B42]">
                                                {{ $salida->productoSerie->Numero_Serie }}
                                            </p>
                                            <p class="text-xs text-[#5F6B7A]">
                                                {{ $salida->productoSerie->Estado }}
                                            </p>
                                        @else
                                            <span class="text-[#5F6B7A]">Sin serie</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-3 align-middle">
                                        <span class="inline-flex rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-semibold text-[#1A2B42]">
                                            {{ $tipoMovimientoLabels[$salida->Tipo_Movimiento] ?? $salida->Tipo_Movimiento }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-3 text-center align-middle font-semibold text-[#1A2B42] whitespace-nowrap">
                                        {{ number_format((int) $salida->Cantidad) }}
                                    </td>

                                    <td class="max-w-sm px-3 py-3 align-middle text-[#5F6B7A]">
                                        {{ $salida->Motivo_Movimiento ?: 'Sin motivo registrado' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-[#7B8794]">
                                        No hay salidas de inventario registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $salidas->links(data: ['scrollTo' => false]) }}
            </div>
        </x-card>

    </div>
</div>