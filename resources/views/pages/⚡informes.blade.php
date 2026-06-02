<?php

use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public string $ventasDesde = '';
    public string $ventasHasta = '';
    public string $ventasUsuarioId = '';

    public string $devDesde = '';
    public string $devHasta = '';

    public string $provDesde = '';
    public string $provHasta = '';
    public string $provFiltro = '';

    public string $arqDesde = '';
    public string $arqHasta = '';
    public string $arqUsuarioId = '';

    public string $morososFecha = '';
    public string $morososMin = '1';

    public string $salDesde = '';
    public string $salHasta = '';
    public string $salMotivo = '';

    public string $credDesde = '';
    public string $credHasta = '';

    public bool $visorActivo = false;
    public string $visorTitulo = '';
    public string $visorUrl = '';

    public function mount(): void
    {
        $inicioMes = now()->startOfMonth()->toDateString();
        $hoy = now()->toDateString();

        $this->ventasDesde = $inicioMes;
        $this->ventasHasta = $hoy;

        $this->devDesde = $inicioMes;
        $this->devHasta = $hoy;

        $this->provDesde = $inicioMes;
        $this->provHasta = $hoy;

        $this->arqDesde = $inicioMes;
        $this->arqHasta = $hoy;

        $this->morososFecha = $hoy;

        $this->salDesde = $inicioMes;
        $this->salHasta = $hoy;

        $this->credDesde = $inicioMes;
        $this->credHasta = $hoy;
    }

    public function generarReporte(string $reporte, string $formato = 'pdf')
    {
        return match ($reporte) {
            'ventas' => $this->generarVentas($formato),
            'devoluciones' => $this->generarDevoluciones($formato),
            'proveedores' => $this->generarProveedores($formato),
            'arqueo' => $this->generarArqueo($formato),
            'morosos' => $this->generarMorosos($formato),
            'inventario' => $this->generarInventario($formato),
            'salidas' => $this->generarSalidas($formato),
            'agotados' => $this->generarAgotados($formato),
            'creditos' => $this->generarCreditos($formato),
            default => $this->mostrarToast('Reporte no disponible.', 'error'),
        };
    }

    public function limpiarFiltros(): void
    {
        $this->mount();

        $this->ventasUsuarioId = '';
        $this->provFiltro = '';
        $this->arqUsuarioId = '';
        $this->morososMin = '1';
        $this->salMotivo = '';

        $this->cerrarVisor();

        $this->mostrarToast('Filtros restablecidos.', 'success');
    }

    public function cerrarVisor(): void
    {
        $this->visorActivo = false;
        $this->visorTitulo = '';
        $this->visorUrl = '';
    }

    public function tarjetasReportes(): array
    {
        return [
            [
                'id' => 'ventas',
                'titulo' => 'Ventas',
                'descripcion' => 'Ventas por fecha y usuario.',
                'icono' => 'o-chart-bar',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'ventasDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'ventasHasta'],
                    ['tipo' => 'text', 'label' => 'Usuario', 'model' => 'ventasUsuarioId', 'placeholder' => 'ID opcional', 'span' => 'sm:col-span-2'],
                ],
            ],
            [
                'id' => 'devoluciones',
                'titulo' => 'Devoluciones',
                'descripcion' => 'Control de devoluciones.',
                'icono' => 'o-arrow-path',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'devDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'devHasta'],
                ],
            ],
            [
                'id' => 'proveedores',
                'titulo' => 'Compras proveedor',
                'descripcion' => 'Compras por proveedor o RUC.',
                'icono' => 'o-shopping-cart',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'provDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'provHasta'],
                    ['tipo' => 'text', 'label' => 'Proveedor', 'model' => 'provFiltro', 'placeholder' => 'Nombre o RUC', 'span' => 'sm:col-span-2'],
                ],
            ],
            [
                'id' => 'arqueo',
                'titulo' => 'Arqueos de caja',
                'descripcion' => 'Caja por fecha y usuario.',
                'icono' => 'o-banknotes',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'arqDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'arqHasta'],
                    ['tipo' => 'text', 'label' => 'Usuario', 'model' => 'arqUsuarioId', 'placeholder' => 'ID opcional', 'span' => 'sm:col-span-2'],
                ],
            ],
            [
                'id' => 'morosos',
                'titulo' => 'Clientes morosos',
                'descripcion' => 'Créditos pendientes.',
                'icono' => 'o-exclamation-triangle',
                'color' => 'rojo',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Corte', 'model' => 'morososFecha'],
                    ['tipo' => 'number', 'label' => 'Días mora', 'model' => 'morososMin', 'placeholder' => '1'],
                ],
            ],
            [
                'id' => 'inventario',
                'titulo' => 'Inventario',
                'descripcion' => 'Existencias generales.',
                'icono' => 'o-cube',
                'color' => 'azul',
                'boton' => 'Generar',
                'sin_filtros' => 'Inventario completo',
                'campos' => [],
            ],
            [
                'id' => 'salidas',
                'titulo' => 'Otras salidas',
                'descripcion' => 'Salidas por motivo.',
                'icono' => 'o-arrow-up-tray',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'salDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'salHasta'],
                    ['tipo' => 'text', 'label' => 'Motivo', 'model' => 'salMotivo', 'placeholder' => 'Vacío = todos', 'span' => 'sm:col-span-2'],
                ],
            ],
            [
                'id' => 'agotados',
                'titulo' => 'Stock',
                'descripcion' => 'Productos agotados.',
                'icono' => 'o-archive-box',
                'color' => 'ambar',
                'boton' => 'Generar',
                'sin_filtros' => 'Todos / Agotados',
                'campos' => [],
            ],
            [
                'id' => 'creditos',
                'titulo' => 'Ventas al crédito',
                'descripcion' => 'Créditos por periodo.',
                'icono' => 'o-credit-card',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'credDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'credHasta'],
                ],
            ],
        ];
    }

    private function generarInventario(string $formato)
    {
        return match ($formato) {
            // MODIFICADO: parámetros del visor PDF para reducir panel lateral/miniaturas cuando el navegador los respeta.
            'pdf' => $this->abrirVisor(
                'Inventario',
                route('reportes.inventario.pdf') . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.inventario.excel')),
            'word' => redirect()->to(route('reportes.inventario.word')),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarVentas(string $formato)
    {
        if (! $this->soloPdfDisponible($formato)) {
            return null;
        }

        if (! $this->rangoValido($this->ventasDesde, $this->ventasHasta, 'ventas')) {
            return null;
        }

        if (trim($this->ventasUsuarioId) !== '' && ! ctype_digit(trim($this->ventasUsuarioId))) {
            $this->mostrarToast('El usuario de ventas debe ser un ID numérico.', 'error');
            return null;
        }

        return $this->irViewer('Ventas', '/api/reportes/ventas', [
            'fechaInicio' => $this->ventasDesde,
            'fechaFin' => $this->ventasHasta,
            'usuarioId' => trim($this->ventasUsuarioId),
        ]);
    }

    private function generarDevoluciones(string $formato)
    {
        if (! $this->soloPdfDisponible($formato)) {
            return null;
        }

        if (! $this->rangoValido($this->devDesde, $this->devHasta, 'devoluciones')) {
            return null;
        }

        return $this->irViewer('Devoluciones', '/api/reportes/devoluciones', [
            'desde' => $this->devDesde,
            'hasta' => $this->devHasta,
        ]);
    }

    private function generarProveedores(string $formato)
    {
        if (! $this->soloPdfDisponible($formato)) {
            return null;
        }

        if (! $this->rangoValido($this->provDesde, $this->provHasta, 'compras de proveedor')) {
            return null;
        }

        return $this->irViewer('Compras de proveedor', '/api/reportes/proveedores', [
            'proveedor' => trim($this->provFiltro),
            'desde' => $this->provDesde,
            'hasta' => $this->provHasta,
        ]);
    }

    private function generarArqueo(string $formato)
    {
        if (! $this->soloPdfDisponible($formato)) {
            return null;
        }

        if (! $this->rangoValido($this->arqDesde, $this->arqHasta, 'arqueo de caja')) {
            return null;
        }

        if (trim($this->arqUsuarioId) !== '' && ! ctype_digit(trim($this->arqUsuarioId))) {
            $this->mostrarToast('El usuario del arqueo debe ser un ID numérico.', 'error');
            return null;
        }

        return $this->irViewer('Arqueo de caja', '/api/reportes/arqueo', [
            'desde' => $this->arqDesde,
            'hasta' => $this->arqHasta,
            'usuarioId' => trim($this->arqUsuarioId),
        ]);
    }

    private function generarMorosos(string $formato)
    {
        if (! $this->soloPdfDisponible($formato)) {
            return null;
        }

        if ($this->morososMin === '' || (int) $this->morososMin < 1) {
            $this->morososMin = '1';
        }

        return $this->irViewer('Clientes morosos', '/api/reportes/morosos', [
            'fechaCorte' => $this->morososFecha,
            'minDiasMora' => max(1, (int) $this->morososMin),
        ]);
    }

    private function generarSalidas(string $formato)
    {
        if (! $this->soloPdfDisponible($formato)) {
            return null;
        }

        if (! $this->rangoValido($this->salDesde, $this->salHasta, 'otras salidas')) {
            return null;
        }

        return $this->irViewer('Otras salidas', '/api/reportes/salidas', [
            'desde' => $this->salDesde,
            'hasta' => $this->salHasta,
            'motivo' => trim($this->salMotivo),
        ]);
    }

    private function generarAgotados(string $formato)
    {
        if (! $this->soloPdfDisponible($formato)) {
            return null;
        }

        return $this->irViewer('Agotados', '/api/reportes/agotados', [
            'format' => 'PDF',
        ]);
    }

    private function generarCreditos(string $formato)
    {
        if (! $this->soloPdfDisponible($formato)) {
            return null;
        }

        if (! $this->rangoValido($this->credDesde, $this->credHasta, 'ventas al crédito')) {
            return null;
        }

        return $this->irViewer('Créditos cancelados', '/api/reportes/creditos', [
            'desde' => $this->credDesde,
            'hasta' => $this->credHasta,
        ]);
    }

    private function soloPdfDisponible(string $formato): bool
    {
        if ($formato === 'pdf') {
            return true;
        }

        $this->mostrarToast(
            'Por ahora Excel y Word solo están habilitados para el reporte de inventario.',
            'warning'
        );

        return false;
    }

    private function irViewer(string $titulo, string $endpoint, array $parametros = []): null
    {
        $parametros = $this->parametrosLimpios($parametros);
        $separador = str_contains($endpoint, '?') ? '&' : '?';

        $this->visorTitulo = $titulo;
        $this->visorUrl = $endpoint . (count($parametros) ? $separador . http_build_query($parametros) : '');
        $this->visorActivo = true;

        return null;
    }

    private function abrirVisor(string $titulo, string $url): null
    {
        $this->visorTitulo = $titulo;
        $this->visorUrl = $url;
        $this->visorActivo = true;

        return null;
    }

    private function parametrosLimpios(array $parametros): array
    {
        return collect($parametros)
            ->filter(fn ($valor) => ! is_null($valor) && trim((string) $valor) !== '')
            ->toArray();
    }

    private function rangoValido(?string $desde, ?string $hasta, string $nombreReporte): bool
    {
        if ($desde && $hasta && $desde > $hasta) {
            $this->mostrarToast(
                'La fecha inicial no puede ser mayor que la fecha final en el reporte de ' . $nombreReporte . '.',
                'error'
            );

            return false;
        }

        return true;
    }

    private function mostrarToast(string $mensaje, string $tipo = 'success'): void
    {
        match ($tipo) {
            'error' => $this->error($mensaje, position: 'toast-top toast-end', timeout: 3500),
            'warning' => $this->warning($mensaje, position: 'toast-top toast-end', timeout: 3000),
            'info' => $this->info($mensaje, position: 'toast-top toast-end', timeout: 2500),
            default => $this->success($mensaje, position: 'toast-top toast-end', timeout: 2500),
        };
    }
};
?>

<div class="min-h-[calc(100vh-3rem)] bg-[#F0F3F7] px-3 py-3 md:px-4">
    <div class="mx-auto flex w-full max-w-385 flex-col gap-3">

        <section class="rounded-2xl border border-[#D7E4F3] bg-white px-4 py-4 shadow-sm">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <h1 class="mt-2 text-2xl font-black tracking-tight text-[#1A2B42] md:text-[28px]">
                        {{ $visorActivo ? $visorTitulo : 'Informes del sistema' }}
                    </h1>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($visorActivo)
                    <x-button icon="o-arrow-left" label="Volver a reportes" wire:click="cerrarVisor"
                        spinner="cerrarVisor"
                        class="h-10 min-h-10 rounded-xl border border-[#D7E4F3] bg-white text-xs font-black text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />

                    <x-button icon="o-arrow-top-right-on-square" label="Abrir aparte" link="{{ $visorUrl }}" external
                        class="h-10 min-h-10 rounded-xl border-0 bg-[#2E8BC0] text-xs font-black text-white shadow-sm hover:bg-[#0B6FE4]" />
                    @else
                    <div class="rounded-xl bg-[#2E8BC0] px-4 py-2 text-white shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-white/80">
                            Periodo sugerido
                        </p>

                        <p class="text-sm font-black">
                            {{ now()->startOfMonth()->format('d/m/Y') }} - {{ now()->format('d/m/Y') }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </section>

        @if ($visorActivo)
        <section class="rounded-2xl border border-[#D7E4F3] bg-white p-3 shadow-sm">
            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-black text-[#1A2B42]">
                        Vista previa del reporte
                    </p>
                </div>
            </div>

            <div wire:ignore class="overflow-hidden rounded-xl border border-[#D7E4F3] bg-[#F7F9FC]">
                <iframe src="{{ $visorUrl }}" class="h-[calc(100vh-210px)] min-h-120 w-full bg-white"></iframe>
            </div>
        </section>
        @else
        <section class="grid gap-3 grid-cols-[repeat(auto-fit,minmax(min(100%,268px),1fr))]">
            @foreach($this->tarjetasReportes() as $reporte)
            @php
            $iconoClass = match ($reporte['color']) {
            'rojo' => 'bg-red-50 text-red-600 ring-red-100',
            'ambar' => 'bg-amber-50 text-amber-700 ring-amber-100',
            default => 'bg-[#EAF2FB] text-[#0B6FE4] ring-[#D7E4F3]',
            };
            @endphp

            <article wire:key="reporte-{{ $reporte['id'] }}"
                class="group flex min-h-58 flex-col rounded-2xl border border-[#D7E4F3] bg-white p-3 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#B7D6F2] hover:shadow-md">
                <div class="mb-3 flex items-start gap-2.5">
                    <div
                        class="{{ $iconoClass }} flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1">
                        <x-icon :name="$reporte['icono']" class="h-5 w-5" />
                    </div>

                    <div class="min-w-0">
                        <h2 class="truncate text-base font-black leading-5 text-[#1A2B42]">
                            {{ $reporte['titulo'] }}
                        </h2>

                        <p class="mt-0.5 text-xs leading-5 text-[#5F6B7A]">
                            {{ $reporte['descripcion'] }}
                        </p>
                    </div>
                </div>

                @if(!empty($reporte['campos']))
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    @foreach($reporte['campos'] as $campo)
                    <div class="{{ $campo['span'] ?? '' }}">
                        <label class="mb-1 block text-[11px] font-black uppercase tracking-wide text-[#1A2B42]">
                            {{ $campo['label'] }}
                        </label>

                        <input type="{{ $campo['tipo'] }}" wire:model="{{ $campo['model'] }}"
                            placeholder="{{ $campo['placeholder'] ?? '' }}" @if(($campo['tipo'] ?? '' )==='number' )
                            min="1" @endif
                            class="h-9 min-h-9 w-full rounded-xl border border-[#D7E4F3] bg-[#F7F9FC] px-3 text-xs font-bold text-[#1A2B42] outline-none transition scheme-light placeholder:text-[#7B8794] focus:border-[#2E8BC0] focus:bg-white focus:ring-2 focus:ring-[#2E8BC0]/15" />
                    </div>
                    @endforeach
                </div>
                @else
                <div class="rounded-xl border border-dashed border-[#D7E4F3] bg-[#F7F9FC] px-3 py-4">
                    <p class="text-xs font-black text-[#1A2B42]">
                        {{ $reporte['sin_filtros'] }}
                    </p>

                    <p class="mt-0.5 text-[11px] font-semibold text-[#5F6B7A]">
                        No requiere filtros adicionales.
                    </p>
                </div>
                @endif

                <div class="mt-auto grid grid-cols-3 gap-2 pt-4">
                    <x-button icon="o-eye" label="PDF" wire:click="generarReporte('{{ $reporte['id'] }}', 'pdf')"
                        spinner="generarReporte"
                        class="h-9 min-h-9 rounded-xl border-0 bg-[#2E8BC0] text-xs font-black text-white shadow-sm hover:bg-[#0B6FE4]" />

                    <x-button icon="o-table-cells" label="Excel"
                        wire:click="generarReporte('{{ $reporte['id'] }}', 'excel')" spinner="generarReporte"
                        class="h-9 min-h-9 rounded-xl border border-[#D7E4F3] bg-white text-xs font-black text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />

                    <x-button icon="o-document-text" label="Word"
                        wire:click="generarReporte('{{ $reporte['id'] }}', 'word')" spinner="generarReporte"
                        class="h-9 min-h-9 rounded-xl border border-[#D7E4F3] bg-white text-xs font-black text-[#1A2B42] shadow-sm hover:bg-[#F7F9FC]" />
                </div>
            </article>
            @endforeach
        </section>
        @endif
    </div>
</div>
