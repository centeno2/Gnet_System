<?php

use App\Models\AperturaCaja;
use App\Models\PagoVenta;
use App\Models\TasaCambio;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public bool $abrirCajaModal = false;
    public bool $registrarEgresoModal = false;
    public bool $modificarTasaModal = false;

    public string $caja = '1';

    public bool $cajaAbierta = false;
    public ?int $aperturaCajaId = null;

    public string $montoApertura = '0.00';
    public string $tasaOficial = '0.00';
    public string $nuevaTasaOficial = '';

    public string $monedaEgreso = 'cordoba';
    public string $cantidadEgreso = '';
    public string $motivoEgreso = '';

    public float $totalAbonoCordobas = 0;
    public float $totalAbonoDolares = 0;

    public float $totalVentaCordobas = 0;
    public float $totalVentaDolares = 0;

    public ?string $mensajeExito = null;

    public array $monedas = [
        ['id' => 'cordoba', 'name' => 'Córdoba'],
        ['id' => 'dolar', 'name' => 'Dólar'],
    ];

    public array $denominacionesCordobas = [1000, 500, 200, 100, 50, 20, 10, 5, 1];
    public array $denominacionesDolares = [100, 50, 20, 10, 5, 1];

    public array $conteoCordobas = [
        1000 => 0,
        500 => 0,
        200 => 0,
        100 => 0,
        50 => 0,
        20 => 0,
        10 => 0,
        5 => 0,
        1 => 0,
    ];

    public array $conteoDolares = [
        100 => 0,
        50 => 0,
        20 => 0,
        10 => 0,
        5 => 0,
        1 => 0,
    ];

    public function mount(): void
    {
        $this->cargarTasaCambio();
        $this->cargarAperturaAbierta();
        $this->cargarAbonosCreditoHoy();
        $this->cargarPagosVentaHoy();
    }

    public function cargarAperturaAbierta(): void
    {
        $apertura = AperturaCaja::actualCajaAbiertaHoy();

        if (! $apertura) {
            $this->cajaAbierta = false;
            $this->aperturaCajaId = null;
            $this->montoApertura = '0.00';

            return;
        }

        $this->cajaAbierta = true;
        $this->aperturaCajaId = (int) $apertura->Id_Apertura_Caja;
        $this->montoApertura = number_format((float) $apertura->Monto_Apertura, 2, '.', '');
    }

    public function cargarTasaCambio(): void
    {
        $tasaActual = TasaCambio::actual();

        $valorActual = $tasaActual
            ? (float) $tasaActual->Valor_Cambio
            : 0;

        $this->tasaOficial = number_format($valorActual, 2, '.', '');
        $this->nuevaTasaOficial = $this->tasaOficial;
    }

    public function cargarAbonosCreditoHoy(): void
    {
        $inicioDia = now()->startOfDay();
        $finDia = now()->endOfDay();

        $this->totalAbonoCordobas = (float) DB::table('abono_credito')
            ->whereBetween('Fecha_Abono', [$inicioDia, $finDia])
            ->whereRaw('UPPER(Moneda) = ?', ['NIO'])
            ->sum('Monto');

        $this->totalAbonoDolares = (float) DB::table('abono_credito')
            ->whereBetween('Fecha_Abono', [$inicioDia, $finDia])
            ->whereRaw('UPPER(Moneda) = ?', ['USD'])
            ->sum('Monto');
    }

    public function cargarPagosVentaHoy(): void
    {
        $inicioDia = now()->startOfDay();
        $finDia = now()->endOfDay();

        $this->totalVentaCordobas = (float) PagoVenta::query()
            ->whereBetween('Fecha_Pago', [$inicioDia, $finDia])
            ->where('Moneda', PagoVenta::MONEDA_CORDOBA)
            ->where('Tipo_Pago', PagoVenta::TIPO_EFECTIVO)
            ->sum('Monto');

        $this->totalVentaDolares = (float) PagoVenta::query()
            ->whereBetween('Fecha_Pago', [$inicioDia, $finDia])
            ->where('Moneda', PagoVenta::MONEDA_DOLAR)
            ->where('Tipo_Pago', PagoVenta::TIPO_EFECTIVO)
            ->sum('Monto');
    }

    public function abrirModalCaja(): void
    {
        $this->resetValidation();
        $this->mensajeExito = null;

        $existeCajaAbiertaHoy = AperturaCaja::query()
            ->abierta()
            ->deHoy()
            ->exists();

        if ($existeCajaAbiertaHoy) {
            $this->cargarAperturaAbierta();

            $this->mensajeExito = 'Ya existe una caja abierta. Debes cerrarla antes de abrir otra.';

            return;
        }

        $this->montoApertura = '0.00';
        $this->abrirCajaModal = true;
    }

    public function cerrarModalCaja(): void
    {
        $this->abrirCajaModal = false;
    }

    public function abrirModalEgreso(): void
    {
        $this->registrarEgresoModal = true;
    }

    public function cerrarModalEgreso(): void
    {
        $this->registrarEgresoModal = false;
    }

    public function abrirModalTasa(): void
    {
        $this->resetValidation();

        $this->cargarTasaCambio();

        $this->modificarTasaModal = true;
    }

    public function cerrarModalTasa(): void
    {
        $this->resetValidation();

        $this->modificarTasaModal = false;
    }

    public function guardarApertura(): void
    {
        $this->validate([
            'montoApertura' => ['required', 'numeric', 'min:0'],
        ], [
            'montoApertura.required' => 'El monto de apertura es obligatorio.',
            'montoApertura.numeric' => 'El monto de apertura debe ser numérico.',
            'montoApertura.min' => 'El monto de apertura no puede ser negativo.',
        ]);

        $usuario = Usuario::query()
            ->orderBy('Id_Usuario')
            ->first();

        if (! $usuario) {
            $this->addError('montoApertura', 'No existe ningún usuario registrado para asignar la apertura de caja.');
            return;
        }

        try {
            DB::transaction(function () use ($usuario) {
                $aperturaAbiertaHoy = AperturaCaja::query()
                    ->abierta()
                    ->deHoy()
                    ->lockForUpdate()
                    ->first();

                if ($aperturaAbiertaHoy) {
                    throw new \RuntimeException('Ya existe una caja abierta para hoy. Debes cerrarla antes de abrir otra.');
                }

                $apertura = AperturaCaja::create([
                    'Id_Usuario' => $usuario->Id_Usuario,
                    'Monto_Apertura' => number_format((float) $this->montoApertura, 2, '.', ''),
                    'Fecha_Apertura' => now(),
                    'Estado_Apertura' => AperturaCaja::ABIERTO,
                ]);

                $this->aperturaCajaId = (int) $apertura->Id_Apertura_Caja;
                $this->montoApertura = number_format((float) $apertura->Monto_Apertura, 2, '.', '');
                $this->cajaAbierta = true;
            });

            $this->abrirCajaModal = false;
            $this->mensajeExito = 'Caja abierta correctamente.';
        } catch (\RuntimeException $e) {
            $this->abrirCajaModal = false;
            $this->cargarAperturaAbierta();

            $this->mensajeExito = $e->getMessage();
        }
    }

    public function guardarTasaCambio(): void
    {
        $this->validate([
            'nuevaTasaOficial' => ['required', 'numeric', 'min:0.01'],
        ], [
            'nuevaTasaOficial.required' => 'La tasa de cambio es obligatoria.',
            'nuevaTasaOficial.numeric' => 'La tasa de cambio debe ser numérica.',
            'nuevaTasaOficial.min' => 'La tasa de cambio debe ser mayor a 0.',
        ]);

        TasaCambio::create([
            'Valor_Cambio' => number_format((float) $this->nuevaTasaOficial, 2, '.', ''),
        ]);

        $this->cargarTasaCambio();

        $this->modificarTasaModal = false;

        $this->mensajeExito = 'Tasa de cambio actualizada correctamente.';
    }

    public function guardarEgreso(): void
    {
        $this->validate([
            'monedaEgreso' => ['required', 'in:cordoba,dolar'],
            'cantidadEgreso' => ['required', 'numeric', 'min:0.01'],
            'motivoEgreso' => ['required', 'string', 'max:255'],
        ], [
            'monedaEgreso.required' => 'La moneda es obligatoria.',
            'cantidadEgreso.required' => 'La cantidad es obligatoria.',
            'cantidadEgreso.numeric' => 'La cantidad debe ser numérica.',
            'cantidadEgreso.min' => 'La cantidad debe ser mayor a 0.',
            'motivoEgreso.required' => 'El motivo del egreso es obligatorio.',
            'motivoEgreso.max' => 'El motivo no puede superar los 255 caracteres.',
        ]);

        $this->registrarEgresoModal = false;

        $this->cantidadEgreso = '';
        $this->motivoEgreso = '';

        $this->mensajeExito = 'Egreso registrado correctamente.';
    }

    public function cerrarCaja(): void
    {
        //
    }

    public function subtotalCordoba(int $denominacion): float
    {
        return ((int) ($this->conteoCordobas[$denominacion] ?? 0)) * $denominacion;
    }

    public function subtotalDolar(int $denominacion): float
    {
        return ((int) ($this->conteoDolares[$denominacion] ?? 0)) * $denominacion;
    }

    public function totalCordobas(): float
    {
        $total = 0;

        foreach ($this->denominacionesCordobas as $denominacion) {
            $total += $this->subtotalCordoba($denominacion);
        }

        return $total;
    }

    public function totalDolares(): float
    {
        $total = 0;

        foreach ($this->denominacionesDolares as $denominacion) {
            $total += $this->subtotalDolar($denominacion);
        }

        return $total;
    }

    public function totalEsperadoCordobas(): float
    {
        return (float) $this->montoApertura
            + (float) $this->totalVentaCordobas
            + (float) $this->totalAbonoCordobas;
    }

    public function totalEsperadoDolares(): float
    {
        return (float) $this->totalVentaDolares
            + (float) $this->totalAbonoDolares;
    }

    public function faltanteCordobas(): float
    {
        $faltante = $this->totalEsperadoCordobas() - $this->totalCordobas();

        return $faltante > 0 ? $faltante : 0;
    }

    public function sobranteCordobas(): float
    {
        $sobrante = $this->totalCordobas() - $this->totalEsperadoCordobas();

        return $sobrante > 0 ? $sobrante : 0;
    }

    public function faltanteDolares(): float
    {
        $faltante = $this->totalEsperadoDolares() - $this->totalDolares();

        return $faltante > 0 ? $faltante : 0;
    }

    public function sobranteDolares(): float
    {
        $sobrante = $this->totalDolares() - $this->totalEsperadoDolares();

        return $sobrante > 0 ? $sobrante : 0;
    }

    public function totalCajaDisponible(): string
    {
        $total = $this->monedaEgreso === 'dolar'
            ? $this->totalDolares()
            : $this->totalCordobas();

        return number_format($total, 2, '.', ',');
    }

    public function prefijoMonedaEgreso(): string
    {
        return $this->monedaEgreso === 'dolar' ? '$' : 'C$';
    }

    public function formatear(float|int|string $valor): string
    {
        return number_format((float) $valor, 2, '.', ',');
    }

    public function detallesCaja(): array
    {
        return [
            ['label' => 'Fondo inicial', 'valor' => 'C$ ' . $this->formatear($this->montoApertura)],

            ['label' => 'Total ventas C$', 'valor' => 'C$ ' . $this->formatear($this->totalVentaCordobas)],
            ['label' => 'Total ventas $', 'valor' => '$ ' . $this->formatear($this->totalVentaDolares)],

            ['label' => 'Total abono C$', 'valor' => 'C$ ' . $this->formatear($this->totalAbonoCordobas)],
            ['label' => 'Total abono $', 'valor' => '$ ' . $this->formatear($this->totalAbonoDolares)],

            ['label' => 'Total egresos C$', 'valor' => 'C$ 0.00'],
            ['label' => 'Total egresos $', 'valor' => '$ 0.00'],

            ['label' => 'Total en C$', 'valor' => 'C$ ' . $this->formatear($this->totalEsperadoCordobas())],
            ['label' => 'Faltante en C$', 'valor' => 'C$ ' . $this->formatear($this->faltanteCordobas())],
            ['label' => 'Sobrante en C$', 'valor' => 'C$ ' . $this->formatear($this->sobranteCordobas())],

            ['label' => 'Total en $', 'valor' => '$ ' . $this->formatear($this->totalEsperadoDolares())],
            ['label' => 'Faltante en $', 'valor' => '$ ' . $this->formatear($this->faltanteDolares())],
            ['label' => 'Sobrante en $', 'valor' => '$ ' . $this->formatear($this->sobranteDolares())],
        ];
    }
};
?>

<div class="flex min-h-screen w-full flex-col gap-6 bg-[#F0F3F7] p-4 md:p-6">
    @php
        $cardClass = 'rounded-2xl border border-[#D7E4F3] bg-white shadow-sm';
        $softCardClass = 'rounded-xl border border-[#D7E4F3] bg-[#F8FAFC]';
        $inputReadonlyClass = 'w-full rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#1A2B42]';
        $inputEditableClass = 'w-full rounded-xl border-[#D7E4F3] bg-white text-[#1A2B42]';
    @endphp

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
        @if ($mensajeExito)
            <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
                {{ $mensajeExito }}
            </div>
        @endif

        <div class="{{ $cardClass }} flex flex-col gap-5 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4] shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-19.5 0v3A2.25 2.25 0 0 0 4.5 18h15a2.25 2.25 0 0 0 2.25-2.25v-3m-19.5 0h19.5M6 15h.008v.008H6V15Zm3 0h.008v.008H9V15Z" />
                    </svg>
                </div>

                <div>
                    <h1 class="text-2xl font-bold text-[#1A2B42] md:text-3xl">Arqueo de caja</h1>
                    <p class="text-sm text-[#5F6B7A]">Registro de efectivo y movimientos de la caja</p>
                </div>
            </div>

            <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-md">
                <div class="rounded-xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                        Caja
                    </label>

                    <x-input
                        wire:model="caja"
                        readonly
                        class="{{ $inputReadonlyClass }}"
                    />
                </div>

                <div class="rounded-xl border border-[#D7E4F3] bg-[#F8FAFC] p-3">
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-[#5F6B7A]">
                        Tasa de cambio
                    </label>

                    <x-input
                        wire:model="tasaOficial"
                        readonly
                        prefix="TC"
                        class="{{ $inputReadonlyClass }}"
                    />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="{{ $cardClass }} p-5">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EAF2FB] text-[#0B6FE4]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-8.25A2.25 2.25 0 0 0 17.25 3.75h-10.5A2.25 2.25 0 0 0 4.5 6v12A2.25 2.25 0 0 0 6.75 20.25h7.5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h3.75m0 0V15m0 3.75L15 13.5" />
                        </svg>
                    </div>

                    <h2 class="text-lg font-bold text-[#1A2B42]">Detalles de caja</h2>
                </div>

                <div class="space-y-3">
                    @foreach ($this->detallesCaja() as $detalle)
                        <div class="flex items-center justify-between rounded-xl bg-[#F8FAFC] px-4 py-3">
                            <span class="text-sm font-medium text-[#5F6B7A]">
                                {{ $detalle['label'] }}
                            </span>

                            <span class="text-sm font-bold text-[#1A2B42]">
                                {{ $detalle['valor'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="{{ $cardClass }} p-5">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EAF2FB] text-[#0B6FE4]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-9h4.5a2.25 2.25 0 0 1 0 4.5H10.5a2.25 2.25 0 0 0 0 4.5H15" />
                        </svg>
                    </div>

                    <h2 class="text-lg font-bold text-[#1A2B42]">Efectivo (C$)</h2>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($denominacionesCordobas as $denominacion)
                        <div class="{{ $softCardClass }} p-3">
                            <span class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                C${{ $denominacion }}
                            </span>

                            <x-input
                                type="number"
                                min="0"
                                placeholder="0"
                                prefix="C$"
                                wire:model.live="conteoCordobas.{{ $denominacion }}"
                                class="{{ $inputReadonlyClass }}"
                            />

                            <span class="mt-2 block text-sm font-medium text-[#5F6B7A]">
                                Subtotal: C$ {{ $this->formatear($this->subtotalCordoba($denominacion)) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between rounded-xl bg-[#EAF2FB] px-4 py-3">
                    <span class="font-semibold text-[#1A2B42]">Efectivo contado C$</span>
                    <span class="text-lg font-bold text-[#0B6FE4]">
                        C$ {{ $this->formatear($this->totalCordobas()) }}
                    </span>
                </div>
            </div>

            <div class="{{ $cardClass }} p-5">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#EAF2FB] text-[#0B6FE4]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-9h4.5a2.25 2.25 0 0 1 0 4.5H10.5a2.25 2.25 0 0 0 0 4.5H15" />
                        </svg>
                    </div>

                    <h2 class="text-lg font-bold text-[#1A2B42]">Efectivo ($)</h2>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($denominacionesDolares as $denominacion)
                        <div class="{{ $softCardClass }} p-3">
                            <span class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                ${{ $denominacion }}
                            </span>

                            <x-input
                                type="number"
                                min="0"
                                placeholder="0"
                                prefix="$"
                                wire:model.live="conteoDolares.{{ $denominacion }}"
                                class="{{ $inputReadonlyClass }}"
                            />

                            <span class="mt-2 block text-sm font-medium text-[#5F6B7A]">
                                Subtotal: $ {{ $this->formatear($this->subtotalDolar($denominacion)) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between rounded-xl bg-[#EAF2FB] px-4 py-3">
                    <span class="font-semibold text-[#1A2B42]">Efectivo contado $</span>
                    <span class="text-lg font-bold text-[#0B6FE4]">
                        $ {{ $this->formatear($this->totalDolares()) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <x-button
                label="Registrar egreso"
                wire:click="abrirModalEgreso"
                class="border-0 bg-[#1A2B42] text-white hover:opacity-95"
            />

            <x-button
                label="Abrir caja"
                wire:click="abrirModalCaja"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F8FAFC]"
            />

            <x-button
                label="Modificar tasa de cambio"
                wire:click="abrirModalTasa"
                class="border border-[#D7E4F3] bg-white text-[#000000] hover:bg-[#EAF2FB]"
            />

            <x-button
                label="Cerrar caja"
                wire:click="cerrarCaja"
                class="border-0 bg-[#0B6FE4] text-white hover:opacity-95"
            />
        </div>
    </div>

    <x-modal
        wire:model="abrirCajaModal"
        class="backdrop-blur-sm"
        box-class="max-w-md rounded-2xl border border-[#D7E4F3] bg-white p-0 shadow-2xl"
    >
        <div class="p-6">
            <div class="mb-5 flex items-center justify-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v7.5m-3.75-3.75h7.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>

                <div class="text-center">
                    <h2 class="text-xl font-bold text-[#0B6FE4]">Apertura de Caja</h2>
                    <p class="text-sm text-[#5F6B7A]">Ingresa el monto inicial de la caja</p>
                </div>
            </div>

            <x-form wire:submit="guardarApertura" no-separator>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Monto de apertura
                    </label>

                    <x-input
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="montoApertura"
                        placeholder="0.00"
                        prefix="C$"
                        class="{{ $inputEditableClass }}"
                    />

                    @error('montoApertura')
                        <span class="mt-2 block text-sm font-semibold text-red-600">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <x-slot:actions>
                    <x-button
                        label="Cancelar"
                        type="button"
                        wire:click="cerrarModalCaja"
                        class="bg-[#6B7280] text-white"
                    />

                    <x-button
                        label="Abrir caja"
                        type="submit"
                        spinner="guardarApertura"
                        class="bg-[#16A34A] text-white"
                    />
                </x-slot:actions>
            </x-form>
        </div>
    </x-modal>

    <x-modal
        wire:model="modificarTasaModal"
        class="backdrop-blur-sm"
        box-class="max-w-md rounded-2xl border border-[#D7E4F3] bg-white p-0 shadow-2xl"
    >
        <div class="p-6">
            <div class="mb-5 flex items-center justify-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-9h4.5a2.25 2.25 0 0 1 0 4.5H10.5a2.25 2.25 0 0 0 0 4.5H15" />
                    </svg>
                </div>

                <div class="text-center">
                    <h2 class="text-xl font-bold text-[#0B6FE4]">Modificar tasa de cambio</h2>
                    <p class="text-sm text-[#5F6B7A]">Actualiza la tasa oficial utilizada por la caja</p>
                </div>
            </div>

            <x-form wire:submit="guardarTasaCambio" no-separator>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Nueva tasa de cambio
                    </label>

                    <x-input
                        type="number"
                        step="0.01"
                        min="0.01"
                        wire:model="nuevaTasaOficial"
                        placeholder="0.00"
                        prefix="TC"
                        class="{{ $inputEditableClass }}"
                    />

                    @error('nuevaTasaOficial')
                        <span class="mt-2 block text-sm font-semibold text-red-600">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <x-slot:actions>
                    <x-button
                        label="Cancelar"
                        type="button"
                        wire:click="cerrarModalTasa"
                        class="bg-[#6B7280] text-white"
                    />

                    <x-button
                        label="Guardar tasa"
                        type="submit"
                        spinner="guardarTasaCambio"
                        class="bg-[#0B6FE4] text-white"
                    />
                </x-slot:actions>
            </x-form>
        </div>
    </x-modal>

    <x-modal
        wire:model="registrarEgresoModal"
        class="backdrop-blur-sm"
        box-class="max-w-3xl rounded-2xl border border-[#D7E4F3] bg-white p-0 shadow-2xl"
    >
        <div class="p-6">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-19.5 0v3A2.25 2.25 0 0 0 4.5 18h15a2.25 2.25 0 0 0 2.25-2.25v-3m-19.5 0h19.5" />
                    </svg>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-[#1A2B42]">Egresos de Caja</h2>
                    <p class="text-sm text-[#5F6B7A]">Registra un nuevo egreso de forma visual</p>
                </div>
            </div>

            <div class="rounded-2xl border border-[#D7E4F3] bg-[#F8FAFC] p-5">
                <div class="mb-5">
                    <h3 class="text-lg font-bold text-[#1A2B42]">Nuevo egreso</h3>
                </div>

                <x-form wire:submit="guardarEgreso" no-separator>
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                    Moneda
                                </label>

                                <x-select
                                    wire:model.live="monedaEgreso"
                                    :options="$monedas"
                                    class="{{ $inputEditableClass }}"
                                />

                                @error('monedaEgreso')
                                    <span class="mt-2 block text-sm font-semibold text-red-600">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                    Efectivo contado
                                </label>

                                <x-input
                                    :value="$this->totalCajaDisponible()"
                                    readonly
                                    prefix="{{ $this->prefijoMonedaEgreso() }}"
                                    class="{{ $inputReadonlyClass }}"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                    Cantidad a egresar
                                </label>

                                <x-input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    wire:model="cantidadEgreso"
                                    placeholder="0.00"
                                    prefix="{{ $this->prefijoMonedaEgreso() }}"
                                    class="{{ $inputEditableClass }}"
                                />

                                @error('cantidadEgreso')
                                    <span class="mt-2 block text-sm font-semibold text-red-600">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                Motivo del egreso
                            </label>

                            <x-textarea
                                wire:model="motivoEgreso"
                                rows="4"
                                placeholder="Describa el motivo..."
                                class="rounded-xl border-[#D7E4F3] bg-white text-[#1A2B42] placeholder:text-[#7B8794]"
                            />

                            @error('motivoEgreso')
                                <span class="mt-2 block text-sm font-semibold text-red-600">
                                    {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                    <x-slot:actions>
                        <x-button
                            label="Volver"
                            type="button"
                            wire:click="cerrarModalEgreso"
                            class="bg-[#6B7280] text-white"
                        />

                        <x-button
                            label="Guardar egreso"
                            type="submit"
                            spinner="guardarEgreso"
                            class="bg-[#0B6FE4] text-white"
                        />
                    </x-slot:actions>
                </x-form>
            </div>
        </div>
    </x-modal>
</div>