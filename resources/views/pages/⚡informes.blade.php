<?php

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public string $ventasDesde = '';
    public string $ventasHasta = '';
    public string $ventasUsuarioId = '';
    public array $ventasUsuariosOpciones = [];

    public string $devDesde = '';
    public string $devHasta = '';

    public string $provDesde = '';
    public string $provHasta = '';
    public string $provFiltro = '';

    public string $arqDesde = '';
    public string $arqHasta = '';
    public string $arqUsuarioId = '0';
    public array $arqCajerosOpciones = [];

    public string $salDesde = '';
    public string $salHasta = '';
    public string $salTipoSalida = '';
    public array $salTiposSalidaOpciones = [];

    public string $credDesde = '';
    public string $credHasta = '';
    public string $credClienteId = '';
    public array $credInstitucionesOpciones = [];

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
        $this->arqUsuarioId = '0';

        $this->salDesde = $inicioMes;
        $this->salHasta = $hoy;

        $this->credDesde = $inicioMes;
        $this->credHasta = $hoy;

        $this->cargarUsuariosVentas();
        $this->cargarCajerosArqueo();
        $this->cargarTiposSalidas();
        $this->cargarInstitucionesCredito();
    }

    private function cargarUsuariosVentas(): void
    {
        $usuarios = DB::table('usuario as u')
            ->join('trabajador as t', 't.Id_Trabajador', '=', 'u.Id_Trabajador')
            ->join('cargo as ca', 'ca.Id_Cargo', '=', 't.Id_Cargo')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 't.Id_Persona')
            ->where('u.Estado', 1)
            ->where('t.Estado', 1)
            ->where('ca.Cargo_Asignado', 'like', 'Cajer%')
            ->orderBy('u.Nombre_Usuario')
            ->selectRaw("
                u.Id_Usuario as id,
                COALESCE(
                    NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                    u.Nombre_Usuario
                ) as name
            ")
            ->get()
            ->map(fn ($usuario) => [
                'id' => (string) $usuario->id,
                'name' => (string) $usuario->name,
            ])
            ->values()
            ->toArray();

        array_unshift($usuarios, [
            'id' => '',
            'name' => 'General / todos los cajeros',
        ]);

        $this->ventasUsuariosOpciones = $usuarios;
    }

    private function cargarCajerosArqueo(): void
    {
        $cajeros = DB::table('usuario as u')
            ->join('trabajador as t', 't.Id_Trabajador', '=', 'u.Id_Trabajador')
            ->join('cargo as ca', 'ca.Id_Cargo', '=', 't.Id_Cargo')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 't.Id_Persona')
            ->where('u.Estado', 1)
            ->where('t.Estado', 1)
            ->where('ca.Cargo_Asignado', 'like', 'Cajer%')
            ->orderBy('u.Nombre_Usuario')
            ->selectRaw("
                u.Id_Usuario as id,
                COALESCE(
                    NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                    u.Nombre_Usuario
                ) as name
            ")
            ->get()
            ->map(fn ($cajero) => [
                'id' => (string) $cajero->id,
                'name' => (string) $cajero->name,
            ])
            ->values()
            ->toArray();

        array_unshift($cajeros, [
            'id' => '0',
            'name' => 'General / todos los cajeros',
        ]);

        $this->arqCajerosOpciones = $cajeros;
    }

    private function cargarTiposSalidas(): void
    {
        try {
            $tipos = DB::table('vw_reporte_otras_salidas')
                ->select('Tipo_Movimiento as id', 'Tipo_Movimiento_Nombre as name')
                ->distinct()
                ->orderBy('Tipo_Movimiento_Nombre')
                ->get()
                ->map(fn ($tipo) => [
                    'id' => (string) $tipo->id,
                    'name' => (string) $tipo->name,
                ])
                ->values()
                ->toArray();
        } catch (\Throwable) {
            $tipos = [];
        }

        array_unshift($tipos, [
            'id' => '',
            'name' => 'General / todos los tipos',
        ]);

        $this->salTiposSalidaOpciones = $tipos;
    }

    private function cargarInstitucionesCredito(): void
    {
        $instituciones = DB::table('cliente as c')
            ->where('c.Estado', 1)
            ->where('c.Tipo_Cliente', 2)
            ->where('c.Tipo_pago', 2)
            ->orderBy('c.Institucion')
            ->selectRaw("
                c.Id_Cliente as id,
                COALESCE(NULLIF(TRIM(c.Institucion), ''), CONCAT('Institución #', c.Id_Cliente)) as name
            ")
            ->get()
            ->map(fn ($institucion) => [
                'id' => (string) $institucion->id,
                'name' => (string) $institucion->name,
            ])
            ->values()
            ->toArray();

        array_unshift($instituciones, [
            'id' => '',
            'name' => 'Selecciona una institución',
        ]);

        $this->credInstitucionesOpciones = $instituciones;
    }

    public function generarReporte(string $reporte, string $formato = 'pdf')
    {
        return match ($reporte) {
            'ventas' => $this->generarVentas($formato),
            'devoluciones' => $this->generarDevoluciones($formato),
            'proveedores' => $this->generarProveedores($formato),
            'arqueo' => $this->generarArqueo($formato),
            'inventario' => $this->generarInventario($formato),
            'stock-proximo' => $this->generarStockProximoAgotarse($formato),
            'salidas' => $this->generarSalidas($formato),
            'creditos' => $this->generarCreditos($formato),
            default => $this->mostrarToast('Reporte no disponible.', 'error'),
        };
    }

    public function limpiarFiltros(): void
    {
        $this->mount();

        $this->ventasUsuarioId = '';
        $this->provFiltro = '';
        $this->arqUsuarioId = '0';
        $this->salTipoSalida = '';
        $this->credClienteId = '';

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
                'descripcion' => 'Ventas por periodo y cajero.',
                'icono' => 'o-chart-bar',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'ventasDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'ventasHasta'],
                    [
                        'tipo' => 'select',
                        'label' => 'Cajero',
                        'model' => 'ventasUsuarioId',
                        'opciones' => 'ventasUsuariosOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'devoluciones',
                'titulo' => 'Devoluciones',
                'descripcion' => 'Devoluciones por rango de fechas.',
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
                    [
                        'tipo' => 'text',
                        'label' => 'Proveedor',
                        'model' => 'provFiltro',
                        'placeholder' => 'Nombre o RUC',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'arqueo',
                'titulo' => 'Arqueos de caja',
                'descripcion' => 'Caja por periodo y cajero.',
                'icono' => 'o-banknotes',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'arqDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'arqHasta'],
                    [
                        'tipo' => 'select',
                        'label' => 'Cajero',
                        'model' => 'arqUsuarioId',
                        'opciones' => 'arqCajerosOpciones',
                        'span' => 'sm:col-span-2',
                    ],
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
                'id' => 'stock-proximo',
                'titulo' => 'Stock próximo',
                'descripcion' => 'Productos con stock igual o menor al mínimo.',
                'icono' => 'o-exclamation-triangle',
                'color' => 'ambar',
                'boton' => 'Generar',
                'sin_filtros' => 'Productos próximos a agotarse',
                'campos' => [],
            ],
            [
                'id' => 'salidas',
                'titulo' => 'Otras salidas',
                'descripcion' => 'Salidas por fecha y tipo de movimiento.',
                'icono' => 'o-arrow-up-tray',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'salDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'salHasta'],
                    [
                        'tipo' => 'select',
                        'label' => 'Tipo movimiento',
                        'model' => 'salTipoSalida',
                        'opciones' => 'salTiposSalidaOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
            [
                'id' => 'creditos',
                'titulo' => 'Créditos institucionales',
                'descripcion' => 'Detalle por institución y periodo.',
                'icono' => 'o-credit-card',
                'color' => 'azul',
                'boton' => 'Generar',
                'campos' => [
                    ['tipo' => 'date', 'label' => 'Inicial', 'model' => 'credDesde'],
                    ['tipo' => 'date', 'label' => 'Final', 'model' => 'credHasta'],
                    [
                        'tipo' => 'select',
                        'label' => 'Institución',
                        'model' => 'credClienteId',
                        'opciones' => 'credInstitucionesOpciones',
                        'span' => 'sm:col-span-2',
                    ],
                ],
            ],
        ];
    }

    private function generarVentas(string $formato)
    {
        if (! $this->rangoValido($this->ventasDesde, $this->ventasHasta, 'ventas')) {
            return null;
        }

        if (trim($this->ventasUsuarioId) !== '' && ! ctype_digit(trim($this->ventasUsuarioId))) {
            $this->mostrarToast('Seleccione un cajero válido.', 'error');
            return null;
        }

        $parametros = $this->parametrosLimpios([
            'desde' => $this->ventasDesde,
            'hasta' => $this->ventasHasta,
            'usuarioId' => trim($this->ventasUsuarioId),
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Ventas por periodo',
                route('reportes.ventas-periodo.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.ventas-periodo.excel', $parametros)),
            'word' => redirect()->to(route('reportes.ventas-periodo.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarDevoluciones(string $formato)
    {
        if (! $this->rangoValido($this->devDesde, $this->devHasta, 'devoluciones')) {
            return null;
        }

        $parametros = $this->parametrosLimpios([
            'desde' => $this->devDesde,
            'hasta' => $this->devHasta,
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Devoluciones',
                route('reportes.devoluciones.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.devoluciones.excel', $parametros)),
            'word' => redirect()->to(route('reportes.devoluciones.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarArqueo(string $formato)
    {
        if (! $this->rangoValido($this->arqDesde, $this->arqHasta, 'arqueo de caja')) {
            return null;
        }

        $usuarioId = trim($this->arqUsuarioId);

        if ($usuarioId !== '' && $usuarioId !== '0' && ! ctype_digit($usuarioId)) {
            $this->mostrarToast('Seleccione un cajero válido.', 'error');
            return null;
        }

        $parametros = $this->parametrosLimpios([
            'desde' => $this->arqDesde,
            'hasta' => $this->arqHasta,
            'usuarioId' => $usuarioId !== '0' ? $usuarioId : '',
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Arqueos de caja',
                route('reportes.arqueos-caja.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.arqueos-caja.excel', $parametros)),
            'word' => redirect()->to(route('reportes.arqueos-caja.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarInventario(string $formato)
    {
        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Inventario',
                route('reportes.inventario.pdf') . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.inventario.excel')),
            'word' => redirect()->to(route('reportes.inventario.word')),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarStockProximoAgotarse(string $formato)
    {
        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Stock próximo a agotarse',
                route('reportes.stock-proximo-agotarse.pdf') . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.stock-proximo-agotarse.excel')),
            'word' => redirect()->to(route('reportes.stock-proximo-agotarse.word')),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarSalidas(string $formato)
    {
        if (! $this->rangoValido($this->salDesde, $this->salHasta, 'otras salidas')) {
            return null;
        }

        $parametros = $this->parametrosLimpios([
            'desde' => $this->salDesde,
            'hasta' => $this->salHasta,
            'tipoSalida' => trim($this->salTipoSalida),
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Otras salidas',
                route('reportes.otras-salidas.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.otras-salidas.excel', $parametros)),
            'word' => redirect()->to(route('reportes.otras-salidas.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
    }

    private function generarCreditos(string $formato)
    {
        if (! $this->rangoValido($this->credDesde, $this->credHasta, 'créditos institucionales')) {
            return null;
        }

        if (trim($this->credClienteId) === '') {
            $this->mostrarToast('Selecciona una institución para generar el reporte.', 'error');
            return null;
        }

        if (! ctype_digit(trim($this->credClienteId))) {
            $this->mostrarToast('La institución seleccionada no es válida.', 'error');
            return null;
        }

        $parametros = $this->parametrosLimpios([
            'desde' => $this->credDesde,
            'hasta' => $this->credHasta,
            'clienteId' => trim($this->credClienteId),
        ]);

        return match ($formato) {
            'pdf' => $this->abrirVisor(
                'Créditos institucionales',
                route('reportes.creditos-institucionales.pdf', $parametros) . '#toolbar=1&navpanes=0&view=FitH'
            ),
            'excel' => redirect()->to(route('reportes.creditos-institucionales.excel', $parametros)),
            'word' => redirect()->to(route('reportes.creditos-institucionales.word', $parametros)),
            default => $this->mostrarToast('Formato no disponible.', 'error'),
        };
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

    private function soloPdfDisponible(string $formato): bool
    {
        if ($formato === 'pdf') {
            return true;
        }

        $this->mostrarToast(
            'Por ahora Excel y Word están habilitados para Inventario, Stock próximo, Ventas, Otras salidas, Créditos institucionales, Devoluciones y Arqueos de caja.',
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
                <iframe src="{{ $visorUrl }}" class="h-[calc(100vh-210px)] min-h-[480px] w-full bg-white"></iframe>
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

                        @if (($campo['tipo'] ?? '') === 'select')
                        @php
                        $opcionesCampo = $campo['opciones'] ?? '';
                        $opcionesSelect = [];

                        if (is_string($opcionesCampo) && property_exists($this, $opcionesCampo)) {
                        $opcionesSelect = $this->{$opcionesCampo};
                        }
                        @endphp

                        <x-select wire:model="{{ $campo['model'] }}" :options="$opcionesSelect" option-value="id"
                            option-label="name"
                            class="h-9 min-h-9 rounded-xl bg-[#F7F9FC] text-xs font-bold text-[#1A2B42]" />
                        @else
                        <input type="{{ $campo['tipo'] }}" wire:model="{{ $campo['model'] }}"
                            placeholder="{{ $campo['placeholder'] ?? '' }}" @if(($campo['tipo'] ?? '' )==='number' )
                            min="1" @endif
                            class="h-9 min-h-9 w-full rounded-xl border border-[#D7E4F3] bg-[#F7F9FC] px-3 text-xs font-bold text-[#1A2B42] outline-none transition scheme-light placeholder:text-[#7B8794] focus:border-[#2E8BC0] focus:bg-white focus:ring-2 focus:ring-[#2E8BC0]/15" />
                        @endif
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
