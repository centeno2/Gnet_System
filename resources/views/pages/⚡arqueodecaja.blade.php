<?php

use Livewire\Component;

new class extends Component
{
    public bool $abrirCajaModal = false;
    public bool $registrarEgresoModal = false;

    public string $caja = '1';
    public string $fecha = '';
    public string $vendedor = 'Nombre del usuario';

    public string $montoApertura = '0.00';
    public string $tasaOficial = '0.00';

    public string $monedaEgreso = 'cordoba';
    public string $cantidadEgreso = '';
    public string $motivoEgreso = '';

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
        $this->fecha = now()->format('d/m/Y');
    }

    public function abrirModalCaja(): void
    {
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

    public function guardarApertura(): void
    {
        $this->abrirCajaModal = false;
    }

    public function guardarEgreso(): void
    {
        $this->registrarEgresoModal = false;
    }

    public function cerrarCaja(): void
    {
        
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

    public function totalCajaDisponible(): string
    {
        $total = $this->monedaEgreso === 'dolar'
            ? $this->totalDolares()
            : $this->totalCordobas();

        return number_format($total, 2, '.', ',');
    }

    public function formatear(float|int|string $valor): string
    {
        return number_format((float) $valor, 2, '.', ',');
    }

    public function detallesCaja(): array
    {
        return [
            ['label' => 'Fondo inicial', 'valor' => $this->formatear($this->montoApertura)],
            ['label' => 'Total ventas', 'valor' => '0.00'],
            ['label' => 'Total abono C$', 'valor' => '0.00'],
            ['label' => 'Total abono $', 'valor' => '0.00'],
            ['label' => 'Total egresos C$', 'valor' => '0.00'],
            ['label' => 'Total egresos $', 'valor' => '0.00'],
            ['label' => 'Total en C$', 'valor' => $this->formatear($this->totalCordobas())],
            ['label' => 'Faltante en C$', 'valor' => '0.00'],
            ['label' => 'Sobrante en C$', 'valor' => '0.00'],
            ['label' => 'Total en $', 'valor' => $this->formatear($this->totalDolares())],
            ['label' => 'Faltante en $', 'valor' => '0.00'],
            ['label' => 'Sobrante en $', 'valor' => '0.00'],
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
        <!-- Header -->
        <div class="{{ $cardClass }} flex items-center gap-4 p-5">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#EAF2FB] text-[#0B6FE4] shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-19.5 0v3A2.25 2.25 0 0 0 4.5 18h15a2.25 2.25 0 0 0 2.25-2.25v-3m-19.5 0h19.5M6 15h.008v.008H6V15Zm3 0h.008v.008H9V15Z" />
                </svg>
            </div>

            <div>
                <h1 class="text-2xl font-bold text-[#1A2B42] md:text-3xl">Arqueo de caja</h1>
                <p class="text-sm text-[#5F6B7A]">Registro de efectivo y movimientos de la caja</p>
            </div>
        </div>

        <!-- Datos superiores -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="{{ $cardClass }} p-4">
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Caja</label>
                <x-input :value="$caja" readonly class="{{ $inputReadonlyClass }}" />
            </div>

            <div class="{{ $cardClass }} p-4">
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Fecha</label>
                <x-input :value="$fecha" readonly class="{{ $inputReadonlyClass }}" />
            </div>

            <div class="{{ $cardClass }} p-4">
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Vendedor</label>
                <x-input :value="$vendedor" readonly class="{{ $inputReadonlyClass }}" />
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <!-- Detalles -->
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
                            <span class="text-sm font-medium text-[#5F6B7A]">{{ $detalle['label'] }}</span>
                            <span class="text-sm font-bold text-[#1A2B42]">{{ $detalle['valor'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Efectivo C$ -->
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
                            <span class="mb-2 block text-sm font-semibold text-[#1A2B42]">C${{ $denominacion }}</span>

                            <x-input
                                type="number"
                                min="0"
                                placeholder="0"
                                wire:model.live="conteoCordobas.{{ $denominacion }}"
                                class="{{ $inputReadonlyClass }}"
                            />

                            <span class="mt-2 block text-sm font-medium text-[#5F6B7A]">
                                Subtotal: {{ $this->formatear($this->subtotalCordoba($denominacion)) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between rounded-xl bg-[#EAF2FB] px-4 py-3">
                    <span class="font-semibold text-[#1A2B42]">Total efectivo C$</span>
                    <span class="text-lg font-bold text-[#0B6FE4]">{{ $this->formatear($this->totalCordobas()) }}</span>
                </div>
            </div>

            <!-- Efectivo USD -->
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
                            <span class="mb-2 block text-sm font-semibold text-[#1A2B42]">${{ $denominacion }}</span>

                            <x-input
                                type="number"
                                min="0"
                                placeholder="0"
                                wire:model.live="conteoDolares.{{ $denominacion }}"
                                class="{{ $inputReadonlyClass }}"
                            />

                            <span class="mt-2 block text-sm font-medium text-[#5F6B7A]">
                                Subtotal: {{ $this->formatear($this->subtotalDolar($denominacion)) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between rounded-xl bg-[#EAF2FB] px-4 py-3">
                    <span class="font-semibold text-[#1A2B42]">Total efectivo $</span>
                    <span class="text-lg font-bold text-[#0B6FE4]">{{ $this->formatear($this->totalDolares()) }}</span>
                </div>
            </div>
        </div>

        <!-- Botones -->
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
                label="Cerrar caja"
                wire:click="cerrarCaja"
                class="border-0 bg-[#0B6FE4] text-white hover:opacity-95"
            />
        </div>
    </div>

    <!-- Modal Apertura -->
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
                    <p class="text-sm text-[#5F6B7A]">Ingresa los datos iniciales del día</p>
                </div>
            </div>

            <x-form wire:submit="guardarApertura" no-separator>
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Monto de apertura</label>
                        <x-input
                            wire:model="montoApertura"
                            placeholder="0.00"
                            prefix="C$"
                            class="{{ $inputReadonlyClass }}"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Tasa oficial del día</label>
                        <x-input
                            wire:model="tasaOficial"
                            placeholder="0.00"
                            prefix="TC"
                            class="{{ $inputReadonlyClass }}"
                        />
                    </div>
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

    <!-- Modal Egreso -->
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
                                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Moneda</label>
                                <x-select
                                    wire:model.live="monedaEgreso"
                                    :options="$monedas"
                                    class="{{ $inputEditableClass }}"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Total en caja</label>
                                <x-input
                                    :value="$this->totalCajaDisponible()"
                                    readonly
                                    class="{{ $inputReadonlyClass }}"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Cantidad a egresar</label>
                                <x-input
                                    wire:model="cantidadEgreso"
                                    placeholder="0.00"
                                    class="{{ $inputEditableClass }}"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Motivo del egreso</label>
                            <x-textarea
                                wire:model="motivoEgreso"
                                rows="4"
                                placeholder="Describa el motivo..."
                                class="rounded-xl border-[#D7E4F3] bg-white text-[#1A2B42] placeholder:text-[#7B8794]"
                            />
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