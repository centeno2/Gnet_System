<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public array $clientes = [];
    public array $tecnicos = [];
    public array $productosDisponibles = [];
    public array $seriesDisponibles = [];
    public array $productosUsados = [];

    public ?int $clienteId = null;
    public string $telefonoCliente = '';
    public string $municipio = '';
    public ?int $tecnicoId = null;
    public int|string $cantidadCamaras = 0;
    public float|string $metrosCableado = 0;
    public float|string $costoManoObra = 0;
    public float|string $porcentajeAnticipo = 30;
    public ?string $fechaEstimada = null;
    public string $direccionInstalacion = '';
    public string $detalleContrato = '';
    public string $estadoContrato = 'PENDIENTE';

    public array $checklist = [
        'incluye_instalacion_fisica' => true,
        'incluye_configuracion_app' => false,
        'incluye_pruebas_sistema' => false,
        'incluye_capacitacion_basica' => false,
        'incluye_garantia' => false,
        'anticipo_recibido' => false,
        'contrato_firmado' => false,
        'cliente_aprueba_recorrido' => false,
        'sistema_energizado' => false,
        'observacion_checklist' => '',
    ];

    public bool $modalProducto = false;
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
    }

    public function cargarCombos(): void
    {
        $this->clientes = DB::table('cliente as c')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->where('c.Estado', 1)
            ->selectRaw("c.Id_Cliente as id, TRIM(CONCAT(COALESCE(c.Institucion, ''), CASE WHEN c.Institucion IS NOT NULL AND c.Institucion <> '' THEN ' - ' ELSE '' END, COALESCE(p.Primer_Nombre, ''), ' ', COALESCE(p.Segundo_Nombre, ''), ' ', COALESCE(p.Primer_Apellido, ''), ' ', COALESCE(p.Segundo_Apellido, ''), ' | Tel: ', COALESCE(p.Telefono, ''))) as name")
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => ['id' => (int) $item->id, 'name' => trim(preg_replace('/\s+/', ' ', $item->name))])
            ->toArray();

        $this->tecnicos = DB::table('trabajador as t')
            ->join('persona as p', 'p.Id_Persona', '=', 't.Id_Persona')
            ->leftJoin('cargo as cg', 'cg.Id_Cargo', '=', 't.Id_Cargo')
            ->where('t.Estado', 1)
            ->selectRaw("t.Id_Trabajador as id, TRIM(CONCAT(p.Primer_Nombre, ' ', COALESCE(p.Segundo_Nombre, ''), ' ', p.Primer_Apellido, ' ', COALESCE(p.Segundo_Apellido, ''), ' - ', COALESCE(cg.Cargo_Asignado, 'Trabajador'))) as name")
            ->orderBy('name')
            ->get()
            ->map(fn ($item) => ['id' => (int) $item->id, 'name' => trim(preg_replace('/\s+/', ' ', $item->name))])
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
            ->selectRaw("p.Id_Producto as id, CONCAT(p.Nombre_Producto, ' ', COALESCE(p.Modelo, ''), ' - Stock: ', p.Stock_Actual, CASE WHEN COUNT(ps.id_producto_serie) > 0 THEN CONCAT(' | Series: ', COUNT(ps.id_producto_serie)) ELSE '' END) as name, p.Precio_Venta as precio, COUNT(ps.id_producto_serie) as series_disponibles")
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => trim($item->name),
                'precio' => (float) $item->precio,
                'series_disponibles' => (int) $item->series_disponibles,
            ])
            ->toArray();
    }

    public function updatedClienteId($value): void
    {
        $this->telefonoCliente = '';
        $this->municipio = '';

        if (!$value) {
            return;
        }

        $cliente = DB::table('cliente as c')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->where('c.Id_Cliente', $value)
            ->select('p.Telefono', 'c.Municipio')
            ->first();

        $this->telefonoCliente = (string) ($cliente->Telefono ?? '');
        $this->municipio = (string) ($cliente->Municipio ?? '');
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

        $producto = DB::table('producto')->where('Id_Producto', $value)->first();
        if (!$producto) {
            return;
        }

        $this->productoPrecio = (float) $producto->Precio_Venta;

        $seriesUsadas = collect($this->productosUsados)->pluck('producto_serie_id')->filter()->values()->all();

        $query = DB::table('producto_serie')
            ->where('Id_Producto', $value)
            ->where('Estado', 'DISPONIBLE');

        if (!empty($seriesUsadas)) {
            $query->whereNotIn('id_producto_serie', $seriesUsadas);
        }

        $this->seriesDisponibles = $query
            ->orderBy('Numero_Serie')
            ->get(['id_producto_serie as id', 'Numero_Serie as name'])
            ->map(fn ($item) => ['id' => (int) $item->id, 'name' => $item->name])
            ->toArray();

        $this->productoTieneSeries = count($this->seriesDisponibles) > 0;
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

        if (!$producto || (int) $producto->Estado !== 1) {
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

        $cantidadYaAgregada = collect($this->productosUsados)
            ->where('producto_id', $this->productoId)
            ->sum('cantidad');

        if (!$this->productoTieneSeries && ($cantidadYaAgregada + $cantidad) > (float) $producto->Stock_Actual) {
            $this->addError('productoCantidad', 'La cantidad supera el stock disponible.');
            return;
        }

        if ($serie && collect($this->productosUsados)->contains('producto_serie_id', (int) $serie->id_producto_serie)) {
            $this->addError('productoSerieId', 'Esta serie ya fue agregada.');
            return;
        }

        $precio = round((float) $this->productoPrecio, 2);
        $subtotal = round($cantidad * $precio, 2);

        $this->productosUsados[] = [
            'tmp_id' => uniqid('prod_', true),
            'producto_id' => (int) $producto->Id_Producto,
            'producto_serie_id' => $serie?->id_producto_serie ? (int) $serie->id_producto_serie : null,
            'codigo' => (string) $producto->Id_Producto,
            'descripcion' => trim(($producto->Nombre_Marca ? $producto->Nombre_Marca . ' ' : '') . $producto->Nombre_Producto . ' ' . ($producto->Modelo ?? '')),
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
        $this->productosUsados = array_values(array_filter($this->productosUsados, fn ($item) => $item['tmp_id'] !== $tmpId));
        $this->cargarCombos();
    }

    public function guardar(): void
    {
        $this->validate([
            'clienteId' => ['required', 'integer'],
            'tecnicoId' => ['nullable', 'integer'],
            'municipio' => ['nullable', 'string', 'max:100'],
            'direccionInstalacion' => ['required', 'string', 'max:255'],
            'cantidadCamaras' => ['required', 'integer', 'min:1'],
            'metrosCableado' => ['required', 'numeric', 'min:0'],
            'costoManoObra' => ['required', 'numeric', 'min:0'],
            'porcentajeAnticipo' => ['required', 'numeric', 'min:0', 'max:100'],
            'fechaEstimada' => ['nullable', 'date'],
            'detalleContrato' => ['nullable', 'string', 'max:1000'],
            'estadoContrato' => ['required', 'in:PENDIENTE,EN_PROCESO,FINALIZADO,CANCELADO'],
        ], [
            'clienteId.required' => 'Seleccione el cliente.',
            'direccionInstalacion.required' => 'Ingrese la dirección de instalación.',
            'cantidadCamaras.min' => 'Ingrese al menos una cámara.',
        ]);

        try {
            DB::transaction(function () {
                $usuarioId = $this->usuarioActualId();
                $numeroContrato = $this->generarNumeroUnico('IC', 'contrato_instalacion_camara', 'Numero_Contrato');
                $servicioId = $this->servicioPorTipo('INSTALACION');

                $totalMateriales = collect($this->productosUsados)->sum('subtotal');
                $totalContrato = round($totalMateriales + (float) $this->costoManoObra, 2);
                $montoAnticipo = round($totalContrato * ((float) $this->porcentajeAnticipo / 100), 2);
                $saldoPendiente = round($totalContrato - $montoAnticipo, 2);

                $contratoId = DB::table('contrato_instalacion_camara')->insertGetId([
                    'Numero_Contrato' => $numeroContrato,
                    'Fecha_Contrato' => now(),
                    'Id_Cliente' => $this->clienteId,
                    'Id_Usuario' => $usuarioId,
                    'Id_Servicio' => $servicioId,
                    'Id_Trabajador' => $this->tecnicoId,
                    'Municipio' => $this->municipio ?: null,
                    'Direccion_Instalacion' => $this->direccionInstalacion,
                    'Cantidad_Camaras' => (int) $this->cantidadCamaras,
                    'Metros_Cableado' => (float) $this->metrosCableado,
                    'Costo_Mano_Obra' => (float) $this->costoManoObra,
                    'Porcentaje_Anticipo' => (float) $this->porcentajeAnticipo,
                    'Monto_Anticipo' => $montoAnticipo,
                    'Fecha_Estimada' => $this->fechaEstimada ?: null,
                    'Detalle_Contrato' => $this->detalleContrato ?: null,
                    'Estado_Contrato' => $this->estadoContrato,
                    'Total_Materiales' => $totalMateriales,
                    'Total_Contrato' => $totalContrato,
                    'Saldo_Pendiente' => $saldoPendiente,
                ]);

                DB::table('contrato_instalacion_camara_checklist')->insert([
                    'Id_Contrato_Instalacion_Camara' => $contratoId,
                    'Incluye_Instalacion_Fisica' => (bool) $this->checklist['incluye_instalacion_fisica'],
                    'Incluye_Configuracion_App' => (bool) $this->checklist['incluye_configuracion_app'],
                    'Incluye_Pruebas_Sistema' => (bool) $this->checklist['incluye_pruebas_sistema'],
                    'Incluye_Capacitacion_Basica' => (bool) $this->checklist['incluye_capacitacion_basica'],
                    'Incluye_Garantia' => (bool) $this->checklist['incluye_garantia'],
                    'Anticipo_Recibido' => (bool) $this->checklist['anticipo_recibido'],
                    'Contrato_Firmado' => (bool) $this->checklist['contrato_firmado'],
                    'Cliente_Aprueba_Recorrido' => (bool) $this->checklist['cliente_aprueba_recorrido'],
                    'Sistema_Energizado' => (bool) $this->checklist['sistema_energizado'],
                    'Observacion_Checklist' => $this->checklist['observacion_checklist'] ?: null,
                ]);

                foreach ($this->productosUsados as $item) {
                    $this->descontarInventario($item, 'INSTALADO', 'SALIDA_INSTALACION');

                    DB::table('contrato_instalacion_camara_producto')->insert([
                        'Id_Contrato_Instalacion_Camara' => $contratoId,
                        'Id_Producto' => $item['producto_id'],
                        'Id_Producto_Serie' => $item['producto_serie_id'],
                        'Cantidad' => $item['cantidad'],
                        'Precio_Unitario' => $item['precio'],
                        'Subtotal' => $item['subtotal'],
                        'Observacion' => null,
                    ]);
                }
            }, 3);

            $this->limpiarFormulario();
            $this->cargarCombos();
            toast(type: 'success', title: 'Contrato guardado', description: 'La instalación de cámaras se registró correctamente.', position: 'toast-top toast-end');
        } catch (Throwable $e) {
            report($e);
            toast(type: 'error', title: 'No se pudo guardar', description: $e->getMessage(), position: 'toast-top toast-end');
        }
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
        $this->clienteId = null;
        $this->telefonoCliente = '';
        $this->municipio = '';
        $this->tecnicoId = null;
        $this->cantidadCamaras = 0;
        $this->metrosCableado = 0;
        $this->costoManoObra = 0;
        $this->porcentajeAnticipo = 30;
        $this->fechaEstimada = null;
        $this->direccionInstalacion = '';
        $this->detalleContrato = '';
        $this->estadoContrato = 'PENDIENTE';
        $this->productosUsados = [];
        $this->checklist = array_merge($this->checklist, array_fill_keys(array_keys($this->checklist), false));
        $this->checklist['incluye_instalacion_fisica'] = true;
        $this->checklist['observacion_checklist'] = '';
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

    private function servicioPorTipo(string $tipo): ?int
    {
        $id = DB::table('servicio')->where('Tipo_Servicio', $tipo)->where('Estado', 1)->value('Id_Servicio');
        return $id ? (int) $id : null;
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
        $producto = DB::table('producto')->where('Id_Producto', $item['producto_id'])->lockForUpdate()->first();

        if (!$producto || (int) $producto->Estado !== 1) {
            throw new RuntimeException('Producto no disponible: ' . $item['descripcion']);
        }

        $cantidad = (int) ceil((float) $item['cantidad']);

        if ((int) $producto->Stock_Actual < $cantidad) {
            throw new RuntimeException('Stock insuficiente para: ' . $item['descripcion']);
        }

        if ($item['producto_serie_id']) {
            $serie = DB::table('producto_serie')->where('id_producto_serie', $item['producto_serie_id'])->lockForUpdate()->first();

            if (!$serie || $serie->Estado !== 'DISPONIBLE') {
                throw new RuntimeException('La serie ya no está disponible: ' . $item['serie']);
            }

            DB::table('producto_serie')->where('id_producto_serie', $item['producto_serie_id'])->update(['Estado' => $estadoSerie]);
        }

        DB::table('producto')->where('Id_Producto', $item['producto_id'])->update(['Stock_Actual' => DB::raw('Stock_Actual - ' . $cantidad)]);

        DB::table('movimiento_inventario')->insert([
            'Id_Producto' => $item['producto_id'],
            'Id_Producto_Serie' => $item['producto_serie_id'],
            'Fecha_Movimiento' => now(),
            'Tipo_Movimiento' => $tipoMovimiento,
            'Cantidad' => $cantidad,
            'Motivo_Movimiento' => 1,
        ]);
    }
};
?>

<div
    class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-y-auto bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="flex shrink-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1A2B42]">Instalación de cámaras</h1>
            <p class="mt-1 text-sm text-[#5F6B7A]">Registro del contrato, condiciones y materiales utilizados.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-button label="Buscar contratos"
                class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]" />
            <x-button label="Guardar contrato" wire:click="guardar" spinner="guardar"
                class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
        <x-card class="xl:col-span-3 rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Datos del contrato</h2>
                <p class="text-sm text-[#5F6B7A]">Seleccione cliente y registre las condiciones de instalación.</p>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cliente / institución</label>
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
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Municipio</label>
                    <x-input wire:model="municipio"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cantidad de cámaras</label>
                    <x-input wire:model.live="cantidadCamaras" type="number"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                    @error('cantidadCamaras') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Metros de cableado</label>
                    <x-input wire:model.live="metrosCableado" type="number" step="0.01"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Costo mano de obra</label>
                    <x-input wire:model.live="costoManoObra" type="number" step="0.01" prefix="C$"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Porcentaje anticipo</label>
                    <x-input wire:model.live="porcentajeAnticipo" type="number" step="0.01" suffix="%"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Técnico asignado</label>
                    <x-select wire:model="tecnicoId" :options="$tecnicos" option-value="id" option-label="name"
                        placeholder="Opcional"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Fecha estimada</label>
                    <x-input wire:model="fechaEstimada" type="date"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Dirección de instalación</label>
                    <x-input wire:model="direccionInstalacion"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                    @error('direccionInstalacion') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="xl:col-span-4">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Detalle del contrato</label>
                    <x-textarea wire:model="detalleContrato" rows="4"
                        class="w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>
            </div>
        </x-card>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Resumen económico</h2>
                <p class="text-sm text-[#5F6B7A]">Totales calculados del contrato.</p>
            </div>

            @php
            $totalMateriales = collect($productosUsados)->sum('subtotal');
            $totalContrato = $totalMateriales + (float) $costoManoObra;
            $anticipo = $totalContrato * ((float) $porcentajeAnticipo / 100);
            $saldo = $totalContrato - $anticipo;
            @endphp

            <div class="space-y-3">
                <div class="rounded-xl bg-[#F0F3F7] px-4 py-4 text-[#1A2B42]"><span
                        class="block text-sm">Materiales</span><strong>C$ {{ number_format($totalMateriales, 2)
                        }}</strong></div>
                <div class="rounded-xl bg-[#F0F3F7] px-4 py-4 text-[#1A2B42]"><span class="block text-sm">Mano de
                        obra</span><strong>C$ {{ number_format((float) $costoManoObra, 2) }}</strong></div>
                <div class="rounded-xl bg-[#F0F3F7] px-4 py-4 text-[#1A2B42]"><span
                        class="block text-sm">Anticipo</span><strong>C$ {{ number_format($anticipo, 2) }}</strong></div>
                <div class="rounded-xl bg-[#EAF2FB] px-4 py-4 text-[#0B6FE4]"><span
                        class="block text-sm font-semibold">Total contrato</span><strong>C$ {{
                        number_format($totalContrato, 2) }}</strong></div>
                <div class="rounded-xl bg-[#F5EEDF] px-4 py-4 text-[#9A6B00]"><span
                        class="block text-sm font-semibold">Saldo pendiente</span><strong>C$ {{ number_format($saldo, 2)
                        }}</strong></div>
            </div>
        </x-card>
    </div>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-[#1A2B42]">Condiciones del servicio</h2>
            <p class="text-sm text-[#5F6B7A]">Confirmaciones rápidas del contrato.</p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.incluye_instalacion_fisica" /> Incluye instalación física
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.incluye_configuracion_app" /> Incluye configuración en app
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.incluye_pruebas_sistema" /> Incluye pruebas del sistema
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.incluye_capacitacion_basica" /> Capacitación básica
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.incluye_garantia" /> Incluye garantía
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.anticipo_recibido" /> Anticipo recibido
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.contrato_firmado" /> Contrato firmado
            </label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]">
                <x-checkbox wire:model="checklist.cliente_aprueba_recorrido" /> Cliente aprueba recorrido
            </label>
        </div>
    </x-card>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-3 flex shrink-0 flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-bold text-[#1A2B42]">Productos usados</h2>
                <p class="text-sm text-[#5F6B7A]">Las series instaladas quedan fuera del inventario disponible.</p>
            </div>
            <x-button label="Agregar producto" wire:click="abrirProducto"
                class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]" />
        </div>

        <div class="overflow-hidden rounded-xl border border-[#D7E4F3]">
            <x-table :headers="$headers" :rows="$productosUsados"
                class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0]">
                @scope('cell_precio', $row)
                C$ {{ number_format((float) $row['precio'], 2) }}
                @endscope
                @scope('cell_subtotal', $row)
                C$ {{ number_format((float) $row['subtotal'], 2) }}
                @endscope
                @scope('cell_acciones', $row)
                <x-button icon="o-trash" wire:click="quitarProducto('{{ $row['tmp_id'] }}')"
                    class="btn-ghost btn-sm text-red-600" />
                @endscope
            </x-table>
        </div>
    </x-card>

    <x-modal wire:model="modalProducto" title="Agregar producto usado" separator>
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
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalProducto', false)"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42]" />
            <x-button label="Agregar" wire:click="agregarProducto"
                class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" />
        </x-slot:actions>
    </x-modal>
</div>
