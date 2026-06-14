<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $rangoVentas = 'mes';

    public array $periodosOpciones = [
        ['id' => 'hoy', 'name' => 'Hoy'],
        ['id' => 'semana', 'name' => 'Semana actual'],
        ['id' => 'mes', 'name' => 'Mes actual'],
        ['id' => 'anio', 'name' => 'Año actual'],
    ];

    public array $usuarioActual = [];
    public array $metricas = [];
    public array $ultimasVentas = [];
    public array $topProductos = [];

    public array $ventasChart = [];
    public array $tipoVentaChart = [];
    public array $operacionesChart = [];
    public array $stockCategoriaChart = [];

    public string $ultimaActualizacion = '';

    public function mount(): void
    {
        $this->refrescarDashboard();
    }

    public function updatedRangoVentas(): void
    {
        if (! in_array($this->rangoVentas, ['hoy', 'semana', 'mes', 'anio'], true)) {
            $this->rangoVentas = 'mes';
        }

        $this->refrescarDashboard();
    }

    public function refrescarDashboard(): void
    {
        $this->cargarUsuarioActual();
        $this->cargarMetricas();
        $this->cargarGraficos();
        $this->cargarTablas();

        $this->ultimaActualizacion = now()->format('d/m/Y h:i A');
    }

    private function cargarUsuarioActual(): void
    {
        $usuarioId = auth()->user()?->Id_Usuario ?? auth()->id();

        $usuario = DB::table('usuario as u')
            ->leftJoin('trabajador as t', 't.Id_Trabajador', '=', 'u.Id_Trabajador')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 't.Id_Persona')
            ->leftJoin('cargo as c', 'c.Id_Cargo', '=', 't.Id_Cargo')
            ->where('u.Id_Usuario', $usuarioId)
            ->selectRaw("
                u.Id_Usuario,
                u.Nombre_Usuario,
                u.Correo,
                COALESCE(
                    NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                    u.Nombre_Usuario
                ) as Nombre_Completo,
                COALESCE(c.Cargo_Asignado, 'Sin cargo asignado') as Cargo
            ")
            ->first();

        $this->usuarioActual = [
            'nombre' => (string) ($usuario?->Nombre_Completo ?? 'Usuario'),
            'usuario' => (string) ($usuario?->Nombre_Usuario ?? 'system'),
            'cargo' => (string) ($usuario?->Cargo ?? 'Sin cargo asignado'),
            'correo' => (string) ($usuario?->Correo ?? ''),
        ];
    }

    private function cargarMetricas(): void
    {
        [$inicio, $fin] = $this->periodoVentas();

        $usuarioId = auth()->user()?->Id_Usuario ?? auth()->id();

        $totalVentas = (float) DB::table('venta')
            ->where('Estado', 1)
            ->whereBetween('Fecha_venta', [$inicio->toDateTimeString(), $fin->toDateTimeString()])
            ->sum('Total');

        $facturas = (int) DB::table('venta')
            ->where('Estado', 1)
            ->whereBetween('Fecha_venta', [$inicio->toDateTimeString(), $fin->toDateTimeString()])
            ->count();

        $tasaCambio = DB::table('tasa_cambio')
            ->orderByDesc('Fecha_Modificacion')
            ->orderByDesc('Id_Tasa_Cambio')
            ->value('Valor_Cambio');

        $creditoPendiente = (float) DB::table('credito')
            ->whereIn('Estado', ['PENDIENTE', 'PARCIAL', 'VENCIDO'])
            ->sum('Saldo_Actual');

        $serviciosActivos = (int) DB::table('servicio_tecnico')
            ->whereNotIn('Estado_Servicio', ['ENTREGADO', 'CANCELADO'])
            ->count();

        $instalacionesActivas = (int) DB::table('contrato_instalacion_camara')
            ->whereNotIn('Estado_Contrato', ['FINALIZADO', 'CANCELADO'])
            ->count();

        $stockBajo = (int) DB::table('producto')
            ->where('Estado', 1)
            ->whereColumn('Stock_Actual', '<=', 'Stock_Minimo')
            ->count();

        $cajaAbierta = DB::table('apertura_caja')
            ->where('Id_Usuario', $usuarioId)
            ->where('Estado_Apertura', 'ABIERTA')
            ->orderByDesc('Fecha_Apertura')
            ->first();

        $this->metricas = [
            [
                'titulo' => 'Ventas',
                'valor' => $this->dinero($totalVentas),
                'detalle' => $this->nombrePeriodoVentas(),
                'icono' => 'o-banknotes',
                'color' => 'azul',
            ],
            [
                'titulo' => 'Facturas',
                'valor' => number_format($facturas),
                'detalle' => 'Emitidas en el periodo',
                'icono' => 'o-receipt-percent',
                'color' => 'azul',
            ],
            [
                'titulo' => 'Tasa cambio',
                'valor' => $tasaCambio ? 'C$ ' . number_format((float) $tasaCambio, 2) : 'Sin tasa',
                'detalle' => 'Última tasa activa',
                'icono' => 'o-currency-dollar',
                'color' => 'verde',
            ],
            [
                'titulo' => 'Caja',
                'valor' => $cajaAbierta ? 'Abierta' : 'Sin apertura',
                'detalle' => $cajaAbierta
                    ? 'Caja #' . $cajaAbierta->Numero_Caja . ' · ' . $this->dinero((float) $cajaAbierta->Monto_Apertura)
                    : 'No hay caja activa',
                'icono' => 'o-building-storefront',
                'color' => $cajaAbierta ? 'verde' : 'ambar',
            ],
            [
                'titulo' => 'Crédito',
                'valor' => $this->dinero($creditoPendiente),
                'detalle' => 'Saldo pendiente',
                'icono' => 'o-credit-card',
                'color' => 'ambar',
            ],
            [
                'titulo' => 'Servicios',
                'valor' => number_format($serviciosActivos),
                'detalle' => 'Técnicos activos',
                'icono' => 'o-wrench-screwdriver',
                'color' => 'azul',
            ],
            [
                'titulo' => 'Instalaciones',
                'valor' => number_format($instalacionesActivas),
                'detalle' => 'Cámaras activas',
                'icono' => 'o-video-camera',
                'color' => 'azul',
            ],
            [
                'titulo' => 'Stock bajo',
                'valor' => number_format($stockBajo),
                'detalle' => 'Productos bajo mínimo',
                'icono' => 'o-exclamation-triangle',
                'color' => $stockBajo > 0 ? 'rojo' : 'verde',
            ],
        ];
    }

    private function cargarGraficos(): void
    {
        [$inicio, $fin] = $this->periodoVentas();

        $ventas = DB::table('venta')
            ->where('Estado', 1)
            ->whereBetween('Fecha_venta', [$inicio->toDateTimeString(), $fin->toDateTimeString()])
            ->select('Id_Venta', 'Fecha_venta', 'Tipo_Venta', 'Total')
            ->get();

        $estructura = $this->estructuraPeriodo($inicio, $fin);

        $totalesPorPeriodo = array_fill_keys($estructura['keys'], 0.0);
        $conteoPorPeriodo = array_fill_keys($estructura['keys'], 0);

        foreach ($ventas as $venta) {
            $clave = $this->clavePeriodo((string) $venta->Fecha_venta);

            if (array_key_exists($clave, $totalesPorPeriodo)) {
                $totalesPorPeriodo[$clave] += (float) $venta->Total;
                $conteoPorPeriodo[$clave]++;
            }
        }

        $this->ventasChart = [
            'type' => 'line',
            'data' => [
                'labels' => $estructura['labels'],
                'datasets' => [
                    [
                        'type' => 'line',
                        'label' => 'Ventas C$',
                        'data' => array_values($totalesPorPeriodo),
                        'borderColor' => '#0B6FE4',
                        'backgroundColor' => 'rgba(46, 139, 192, 0.14)',
                        'fill' => true,
                        'tension' => 0.35,
                        'yAxisID' => 'y',
                    ],
                    [
                        'type' => 'bar',
                        'label' => 'Facturas',
                        'data' => array_values($conteoPorPeriodo),
                        'backgroundColor' => 'rgba(14, 72, 161, 0.24)',
                        'borderColor' => '#0E48A1',
                        'borderRadius' => 8,
                        'yAxisID' => 'y1',
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'bottom'],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'position' => 'left',
                    ],
                    'y1' => [
                        'beginAtZero' => true,
                        'position' => 'right',
                        'grid' => ['drawOnChartArea' => false],
                    ],
                ],
            ],
        ];

        $contado = (float) $ventas->where('Tipo_Venta', 'CONTADO')->sum('Total');
        $credito = (float) $ventas->where('Tipo_Venta', 'CREDITO')->sum('Total');

        $this->tipoVentaChart = [
            'type' => 'doughnut',
            'data' => [
                'labels' => ['Contado', 'Crédito'],
                'datasets' => [
                    [
                        'label' => 'Ventas por tipo',
                        'data' => [$contado, $credito],
                        'backgroundColor' => ['#2E8BC0', '#0E48A1'],
                        'borderColor' => ['#FFFFFF', '#FFFFFF'],
                        'borderWidth' => 3,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'cutout' => '68%',
                'plugins' => [
                    'legend' => ['position' => 'bottom'],
                ],
            ],
        ];

        $serviciosActivos = (int) DB::table('servicio_tecnico')
            ->whereNotIn('Estado_Servicio', ['ENTREGADO', 'CANCELADO'])
            ->count();

        $instalacionesActivas = (int) DB::table('contrato_instalacion_camara')
            ->whereNotIn('Estado_Contrato', ['FINALIZADO', 'CANCELADO'])
            ->count();

        $creditosPendientes = (int) DB::table('credito')
            ->whereIn('Estado', ['PENDIENTE', 'PARCIAL', 'VENCIDO'])
            ->count();

        $productosStockBajo = (int) DB::table('producto')
            ->where('Estado', 1)
            ->whereColumn('Stock_Actual', '<=', 'Stock_Minimo')
            ->count();

        $this->operacionesChart = [
            'type' => 'bar',
            'data' => [
                'labels' => ['Servicios', 'Instalaciones', 'Créditos', 'Stock bajo'],
                'datasets' => [
                    [
                        'label' => 'Registros activos',
                        'data' => [
                            $serviciosActivos,
                            $instalacionesActivas,
                            $creditosPendientes,
                            $productosStockBajo,
                        ],
                        'backgroundColor' => ['#2E8BC0', '#0E48A1', '#F59E0B', '#EF4444'],
                        'borderRadius' => 10,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                ],
                'scales' => [
                    'y' => ['beginAtZero' => true],
                ],
            ],
        ];

        $stockCategorias = DB::table('producto as pr')
            ->join('categoria_producto as cat', 'cat.Id_Categoria', '=', 'pr.Id_Categoria')
            ->where('pr.Estado', 1)
            ->selectRaw('cat.Nombre_Categoria as Categoria, SUM(pr.Stock_Actual) as Stock')
            ->groupBy('cat.Nombre_Categoria')
            ->orderByDesc('Stock')
            ->limit(8)
            ->get();

        $this->stockCategoriaChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $stockCategorias->pluck('Categoria')->map(fn ($valor) => (string) $valor)->toArray(),
                'datasets' => [
                    [
                        'label' => 'Stock actual',
                        'data' => $stockCategorias->pluck('Stock')->map(fn ($valor) => (float) $valor)->toArray(),
                        'backgroundColor' => '#BFD9F6',
                        'borderColor' => '#2E8BC0',
                        'borderWidth' => 1,
                        'borderRadius' => 10,
                    ],
                ],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                ],
                'scales' => [
                    'x' => ['beginAtZero' => true],
                ],
            ],
        ];
    }

    private function cargarTablas(): void
    {
        $this->ultimasVentas = DB::table('venta as v')
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', 'v.Id_Cliente')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->leftJoin('usuario as u', 'u.Id_Usuario', '=', 'v.Id_Usuario')
            ->where('v.Estado', 1)
            ->orderByDesc('v.Fecha_venta')
            ->limit(6)
            ->selectRaw("
                v.Numero_Factura,
                v.Fecha_venta,
                v.Tipo_Venta,
                v.Total,
                u.Nombre_Usuario as Usuario,
                COALESCE(
                    NULLIF(TRIM(c.Institucion), ''),
                    NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                    'Consumidor final'
                ) as Cliente
            ")
            ->get()
            ->map(fn ($venta) => [
                'factura' => (string) $venta->Numero_Factura,
                'fecha' => Carbon::parse($venta->Fecha_venta)->format('d/m/Y h:i A'),
                'tipo' => (string) $venta->Tipo_Venta,
                'cliente' => (string) $venta->Cliente,
                'usuario' => (string) $venta->Usuario,
                'total' => $this->dinero((float) $venta->Total),
            ])
            ->toArray();

        [$inicio, $fin] = $this->periodoVentas();

        $this->topProductos = DB::table('detalle_venta as dv')
            ->join('venta as v', 'v.Id_Venta', '=', 'dv.Id_Venta')
            ->join('producto as pr', 'pr.Id_Producto', '=', 'dv.Id_Producto')
            ->where('v.Estado', 1)
            ->where('dv.Tipo_Detalle', 'PRODUCTO')
            ->whereBetween('v.Fecha_venta', [$inicio->toDateTimeString(), $fin->toDateTimeString()])
            ->selectRaw('
                pr.Nombre_Producto,
                COALESCE(pr.Modelo, "") as Modelo,
                SUM(dv.Cantidad) as Cantidad,
                SUM(dv.Subtotal) as Total
            ')
            ->groupBy('pr.Id_Producto', 'pr.Nombre_Producto', 'pr.Modelo')
            ->orderByDesc('Cantidad')
            ->limit(5)
            ->get()
            ->map(fn ($producto) => [
                'producto' => trim((string) $producto->Nombre_Producto . ' ' . (string) $producto->Modelo),
                'cantidad' => number_format((float) $producto->Cantidad, 2),
                'total' => $this->dinero((float) $producto->Total),
            ])
            ->toArray();
    }

    private function periodoVentas(): array
    {
        $hoy = now();

        return match ($this->rangoVentas) {
            'hoy' => [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()],
            'semana' => [$hoy->copy()->startOfWeek(), $hoy->copy()->endOfWeek()],
            'anio' => [$hoy->copy()->startOfYear(), $hoy->copy()->endOfYear()],
            default => [$hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth()],
        };
    }

    private function nombrePeriodoVentas(): string
    {
        return match ($this->rangoVentas) {
            'hoy' => 'Hoy',
            'semana' => 'Semana actual',
            'anio' => 'Año actual',
            default => 'Mes actual',
        };
    }

    private function estructuraPeriodo(CarbonInterface $inicio, CarbonInterface $fin): array
    {
        $keys = [];
        $labels = [];

        if ($this->rangoVentas === 'hoy') {
            for ($hora = 0; $hora <= 23; $hora++) {
                $clave = str_pad((string) $hora, 2, '0', STR_PAD_LEFT);

                $keys[] = $clave;
                $labels[] = $clave . ':00';
            }

            return compact('keys', 'labels');
        }

        if ($this->rangoVentas === 'anio') {
            $meses = [
                '01' => 'Ene',
                '02' => 'Feb',
                '03' => 'Mar',
                '04' => 'Abr',
                '05' => 'May',
                '06' => 'Jun',
                '07' => 'Jul',
                '08' => 'Ago',
                '09' => 'Sep',
                '10' => 'Oct',
                '11' => 'Nov',
                '12' => 'Dic',
            ];

            foreach ($meses as $clave => $label) {
                $keys[] = $clave;
                $labels[] = $label;
            }

            return compact('keys', 'labels');
        }

        $cursor = $inicio->copy()->startOfDay();
        $limite = $fin->copy()->endOfDay();

        while ($cursor->lte($limite)) {
            $keys[] = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d/m');

            $cursor = $cursor->addDay();
        }

        return compact('keys', 'labels');
    }

    private function clavePeriodo(string $fecha): string
    {
        $fecha = Carbon::parse($fecha);

        return match ($this->rangoVentas) {
            'hoy' => $fecha->format('H'),
            'anio' => $fecha->format('m'),
            default => $fecha->format('Y-m-d'),
        };
    }

    private function dinero(float $valor): string
    {
        return 'C$ ' . number_format($valor, 2);
    }
};
?>

<div wire:poll.60s="refrescarDashboard" class="min-h-screen bg-[#F0F3F7] px-3 py-4 md:px-6 md:py-6">
    <div class="mx-auto flex w-full max-w-385 flex-col gap-4">

        <section class="relative overflow-hidden rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="absolute inset-0 bg-gradient-to-br from-[#EAF2FB] via-white to-[#F7F9FC]"></div>
            <div class="absolute -right-20 -top-20 h-56 w-56 rounded-full bg-[#2E8BC0]/10"></div>
            <div class="absolute right-24 top-10 h-16 w-16 rounded-full bg-[#0B6FE4]/10"></div>
            <div class="absolute -bottom-24 left-10 h-64 w-64 rounded-full bg-[#BFD9F6]/35"></div>

            <div class="relative grid gap-5 p-5 lg:grid-cols-[1fr_360px] lg:p-6">
                <div class="flex min-w-0 flex-col justify-between gap-6">
                    <div>
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-[#2E8BC0] px-3 py-1 text-[11px] font-black uppercase tracking-wide text-white shadow-sm">
                                Panel principal
                            </span>

                            <span class="rounded-full border border-[#D7E4F3] bg-white/80 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-[#1A2B42] shadow-sm">
                                {{ $this->nombrePeriodoVentas() }}
                            </span>
                        </div>

                        <h1 class="text-3xl font-black tracking-tight text-[#1A2B42] md:text-5xl">
                            Bienvenido a GNET System
                        </h1>

                        <p class="mt-3 max-w-3xl text-sm font-semibold leading-6 text-[#5F6B7A] md:text-base">
                            Resumen general de ventas, inventario, crédito, caja y operaciones activas del sistema.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-[#D7E4F3] bg-white/85 p-4 shadow-sm backdrop-blur">
                            <p class="text-[11px] font-black uppercase tracking-wide text-[#5F6B7A]">
                                Usuario
                            </p>
                            <p class="mt-1 truncate text-sm font-black text-[#1A2B42]">
                                {{ $usuarioActual['usuario'] ?? 'system' }}
                            </p>
                            <p class="mt-1 truncate text-xs font-semibold text-[#7B8794]">
                                {{ $usuarioActual['cargo'] ?? 'Sin cargo' }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-[#D7E4F3] bg-white/85 p-4 shadow-sm backdrop-blur">
                            <p class="text-[11px] font-black uppercase tracking-wide text-[#5F6B7A]">
                                Fecha y hora
                            </p>
                            <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                {{ now()->format('d/m/Y') }}
                            </p>
                            <p class="mt-1 text-xs font-semibold text-[#7B8794]">
                                {{ now()->format('h:i A') }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-[#D7E4F3] bg-white/85 p-4 shadow-sm backdrop-blur">
                            <p class="text-[11px] font-black uppercase tracking-wide text-[#5F6B7A]">
                                Actualizado
                            </p>
                            <p class="mt-1 text-sm font-black text-[#1A2B42]">
                                {{ $ultimaActualizacion }}
                            </p>
                            <p class="mt-1 text-xs font-semibold text-[#7B8794]">
                                Auto cada 60 segundos
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-[#D7E4F3] bg-white/90 p-4 shadow-sm backdrop-blur">
                    <p class="text-[11px] font-black uppercase tracking-wide text-[#5F6B7A]">
                        Control del dashboard
                    </p>

                    <h2 class="mt-1 text-xl font-black text-[#1A2B42]">
                        Filtro de análisis
                    </h2>

                    <p class="mt-2 text-xs font-semibold leading-5 text-[#7B8794]">
                        Cambiá el periodo para actualizar ventas, productos más vendidos y gráficos principales.
                    </p>

                    <div class="mt-4">
                        <label class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#1A2B42]">
                            Periodo
                        </label>

                        <x-select
                            wire:model.live="rangoVentas"
                            :options="$periodosOpciones"
                            option-value="id"
                            option-label="name"
                            class="h-11 min-h-11 rounded-xl bg-[#F7F9FC] text-xs font-bold text-[#1A2B42]"
                        />
                    </div>

                    <x-button
                        icon="o-arrow-path"
                        label="Actualizar ahora"
                        wire:click="refrescarDashboard"
                        spinner="refrescarDashboard"
                        class="mt-4 h-11 min-h-11 w-full rounded-xl border-0 bg-[#2E8BC0] text-xs font-black text-white shadow-sm hover:bg-[#0B6FE4]"
                    />
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($metricas as $index => $metrica)
                @php
                    $iconoClass = match ($metrica['color']) {
                        'verde' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                        'ambar' => 'bg-amber-50 text-amber-700 ring-amber-100',
                        'rojo' => 'bg-red-50 text-red-600 ring-red-100',
                        default => 'bg-[#EAF2FB] text-[#0B6FE4] ring-[#D7E4F3]',
                    };
                @endphp

                <article
                    wire:key="metrica-dashboard-{{ $index }}"
                    class="group rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#B7D6F2] hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-black uppercase tracking-wide text-[#5F6B7A]">
                                {{ $metrica['titulo'] }}
                            </p>

                            <p class="mt-2 truncate text-2xl font-black text-[#1A2B42]">
                                {{ $metrica['valor'] }}
                            </p>

                            <p class="mt-1 truncate text-xs font-bold text-[#7B8794]">
                                {{ $metrica['detalle'] }}
                            </p>
                        </div>

                        <div class="{{ $iconoClass }} flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1 transition group-hover:scale-105">
                            <x-icon :name="$metrica['icono']" class="h-5 w-5" />
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm xl:col-span-2">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-[#1A2B42]">
                            Rendimiento de ventas
                        </h2>

                        <p class="text-xs font-semibold text-[#5F6B7A]">
                            Total vendido y cantidad de facturas según {{ strtolower($this->nombrePeriodoVentas()) }}.
                        </p>
                    </div>

                    <span class="rounded-full bg-[#EAF2FB] px-3 py-1 text-[11px] font-black text-[#0B6FE4]">
                        {{ $this->nombrePeriodoVentas() }}
                    </span>
                </div>

                <div class="h-80">
                    <x-chart wire:model="ventasChart" class="h-full" />
                </div>
            </x-card>

            <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                <div class="mb-4">
                    <h2 class="text-lg font-black text-[#1A2B42]">
                        Tipo de venta
                    </h2>

                    <p class="text-xs font-semibold text-[#5F6B7A]">
                        Comparativa entre contado y crédito.
                    </p>
                </div>

                <div class="h-80">
                    <x-chart wire:model="tipoVentaChart" class="h-full" />
                </div>
            </x-card>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                <div class="mb-4">
                    <h2 class="text-lg font-black text-[#1A2B42]">
                        Seguimiento operativo
                    </h2>

                    <p class="text-xs font-semibold text-[#5F6B7A]">
                        Registros activos que requieren atención.
                    </p>
                </div>

                <div class="h-80">
                    <x-chart wire:model="operacionesChart" class="h-full" />
                </div>
            </x-card>

            <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                <div class="mb-4">
                    <h2 class="text-lg font-black text-[#1A2B42]">
                        Inventario por categoría
                    </h2>

                    <p class="text-xs font-semibold text-[#5F6B7A]">
                        Categorías con mayor stock actual.
                    </p>
                </div>

                <div class="h-80">
                    <x-chart wire:model="stockCategoriaChart" class="h-full" />
                </div>
            </x-card>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-[#1A2B42]">
                            Últimas ventas
                        </h2>

                        <p class="text-xs font-semibold text-[#5F6B7A]">
                            Facturas recientes del sistema.
                        </p>
                    </div>

                    <x-icon name="o-receipt-refund" class="h-6 w-6 text-[#2E8BC0]" />
                </div>

                <div class="overflow-hidden rounded-2xl border border-[#D7E4F3]">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-xs">
                            <thead class="bg-[#2E8BC0] text-white">
                                <tr>
                                    <th class="px-3 py-3 font-black">Factura</th>
                                    <th class="px-3 py-3 font-black">Cliente</th>
                                    <th class="px-3 py-3 font-black">Tipo</th>
                                    <th class="px-3 py-3 text-right font-black">Total</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-[#EEF3F8] bg-white">
                                @forelse ($ultimasVentas as $venta)
                                    <tr class="hover:bg-[#F7F9FC]">
                                        <td class="px-3 py-3">
                                            <p class="font-black text-[#1A2B42]">{{ $venta['factura'] }}</p>
                                            <p class="text-[11px] font-semibold text-[#7B8794]">{{ $venta['fecha'] }}</p>
                                        </td>

                                        <td class="max-w-48 px-3 py-3">
                                            <p class="truncate font-bold text-[#1A2B42]">{{ $venta['cliente'] }}</p>
                                            <p class="truncate text-[11px] font-semibold text-[#7B8794]">{{ $venta['usuario'] }}</p>
                                        </td>

                                        <td class="px-3 py-3">
                                            <span class="rounded-full bg-[#EAF2FB] px-2 py-1 text-[11px] font-black text-[#0B6FE4]">
                                                {{ $venta['tipo'] }}
                                            </span>
                                        </td>

                                        <td class="px-3 py-3 text-right font-black text-[#1A2B42]">
                                            {{ $venta['total'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-center text-xs font-bold text-[#7B8794]">
                                            No hay ventas registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-card>

            <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-[#1A2B42]">
                            Productos más vendidos
                        </h2>

                        <p class="text-xs font-semibold text-[#5F6B7A]">
                            Según el periodo seleccionado.
                        </p>
                    </div>

                    <x-icon name="o-cube" class="h-6 w-6 text-[#2E8BC0]" />
                </div>

                <div class="space-y-3">
                    @forelse ($topProductos as $index => $producto)
                        <div class="rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-3 transition hover:bg-white hover:shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-[#1A2B42]">
                                        #{{ $index + 1 }} · {{ $producto['producto'] }}
                                    </p>

                                    <p class="mt-1 text-xs font-semibold text-[#5F6B7A]">
                                        Cantidad vendida: {{ $producto['cantidad'] }}
                                    </p>
                                </div>

                                <div class="shrink-0 rounded-xl bg-white px-3 py-2 text-right shadow-sm">
                                    <p class="text-xs font-black text-[#0B6FE4]">
                                        {{ $producto['total'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-[#D7E4F3] bg-[#F7F9FC] px-4 py-8 text-center">
                            <p class="text-sm font-black text-[#1A2B42]">
                                No hay productos vendidos en este periodo.
                            </p>

                            <p class="mt-1 text-xs font-semibold text-[#7B8794]">
                                Cambiá el filtro de periodo para revisar más datos.
                            </p>
                        </div>
                    @endforelse
                </div>
            </x-card>
        </section>
    </div>
</div>