<?php

use Livewire\Component;

new class extends Component
{
    public string $tipoBusqueda = 'cliente';
    public string $valorBusqueda = '';

    public string $cliente = '';
    public string $cedula = '';
    public string $codigoCredito = '';
    public string $estadoCredito = 'Pendiente';
    public string $fechaCredito = '';
    public string $proximoPago = '';

    public string $saldoOriginal = '0.00';
    public string $saldoPendiente = '0.00';

    public string $abonarCordobas = '0.00';
    public string $abonarDolares = '0.00';
    public string $tasaCambio = '0.0000';
    public string $cambioCordobas = '0.00';

    public string $metodoPago = 'efectivo';
    public string $referenciaPago = '';
    public string $fechaPago = '';
    public string $observacion = '';
    public string $filtroSaldoFavor = '';

    public array $tiposBusqueda = [];
    public array $metodosPagoOptions = [];
    public array $detalleCredito = [];
    public array $clientesSaldoFavor = [];
    public array $headersDetalle = [];
    public array $headersSaldoFavor = [];

    public function mount(): void
    {
        $this->tiposBusqueda = [
            ['id' => 'cliente', 'name' => 'Cliente (cédula / teléfono)'],
            ['id' => 'credito', 'name' => 'Número de crédito'],
            ['id' => 'factura', 'name' => 'Número de factura'],
        ];

        $this->metodosPagoOptions = [
            ['id' => 'efectivo', 'name' => 'Efectivo'],
            ['id' => 'transferencia', 'name' => 'Transferencia'],
            ['id' => 'tarjeta', 'name' => 'Tarjeta'],
            ['id' => 'deposito', 'name' => 'Depósito'],
        ];

        $this->headersDetalle = [
            ['key' => 'numero', 'label' => 'No.', 'class' => 'w-14'],
            ['key' => 'fecha_pago', 'label' => 'Fecha', 'class' => 'min-w-[120px]'],
            ['key' => 'cuota', 'label' => 'Cuota (C$)', 'class' => 'min-w-[120px]'],
            ['key' => 'abono_capital', 'label' => 'Capital', 'class' => 'hidden lg:table-cell min-w-[120px]'],
              ['key' => 'pagado_dolares', 'label' => 'Pagado US$', 'class' => 'hidden md:table-cell min-w-[120px]'],
            ['key' => 'estado', 'label' => 'Estado', 'class' => 'min-w-[110px]'],
        ];

        $this->headersSaldoFavor = [
            ['key' => 'cliente', 'label' => 'Cliente'],
            ['key' => 'documento', 'label' => 'Documento', 'class' => 'hidden md:table-cell'],
            ['key' => 'saldo', 'label' => 'Saldo', 'class' => 'w-28'],
        ];
    }

    public function buscarCredito(): void
    {
    }

    public function registrarPago(): void
    {
    }

    public function limpiarBusqueda(): void
    {
        $this->reset([
            'valorBusqueda',
            'cliente',
            'cedula',
            'codigoCredito',
            'fechaCredito',
            'proximoPago',
            'saldoOriginal',
            'saldoPendiente',
            'detalleCredito',
        ]);

        $this->estadoCredito = 'Pendiente';
        $this->saldoOriginal = '0.00';
        $this->saldoPendiente = '0.00';
    }

    public function limpiarPago(): void
    {
        $this->reset([
            'abonarCordobas',
            'abonarDolares',
            'tasaCambio',
            'cambioCordobas',
            'referenciaPago',
            'fechaPago',
            'observacion',
        ]);

        $this->abonarCordobas = '0.00';
        $this->abonarDolares = '0.00';
        $this->tasaCambio = '0.0000';
        $this->cambioCordobas = '0.00';
    }
};
?>

@php
    $fieldClass = 'rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#000000] placeholder:text-[#000000] [&_.fieldset-legend]:text-[#000000] [&_.label]:text-[#000000] [&_label]:text-[#000000]';

    $readonlyFieldClass = 'rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#000000] [&_.fieldset-legend]:text-[#000000] [&_.label]:text-[#000000] [&_label]:text-[#000000]';

    $cardClass = 'border border-[#D7E4F3] bg-white [&_.text-base-content\\/70]:text-[#000000] [&_.text-sm]:text-[#000000] [&_.text-base-content]:text-[#000000] [&_.card-title]:text-[#000000] [&_label]:text-[#000000] [&_.fieldset-legend]:text-[#000000]';

    $primaryButtonClass = 'btn-sm border-0 bg-[#2E8BC0] text-white hover:bg-[#256f99]';
@endphp

<div class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-hidden bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="flex shrink-0 items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-[#000000]">Gestión de Créditos</h1>
            <p class="text-sm text-[#000000]">Consulta créditos y registra pagos en una sola vista.</p>
        </div>

        <div class="hidden md:flex items-center gap-2">
            <x-button
                label="Historial"
                icon="o-clock"
                class="{{ $primaryButtonClass }}"
            />
        </div>
    </div>

    <div class="grid min-h-0 grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="flex min-h-0 flex-col gap-4 overflow-hidden">
            <x-card
                title="Búsqueda y resumen"
                subtitle="Ubica el crédito y visualiza sus datos principales."
                shadow
                separator
                class="{{ $cardClass }}"
            >
                <x-form wire:submit="buscarCredito" no-separator>
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                       

                        <div class="lg:col-span-6">
                            <x-input
                                label="Buscar crédito"
                                wire:model="valorBusqueda"
                                placeholder="Cédula, teléfono, crédito o factura"
                                icon="o-magnifying-glass"
                                class="{{ $fieldClass }}"
                            />
                        </div>

                        
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 xl:grid-cols-4">
                        <div class="rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#000000]">Cliente</p>
                            <p class="mt-1 truncate text-sm font-semibold text-[#000000]">{{ $cliente ?: '—' }}</p>
                            <p class="mt-1 text-xs text-[#000000]">{{ $cedula ?: 'Sin documento cargado' }}</p>
                        </div>

                        <div class="rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#000000]">Crédito</p>
                            <p class="mt-1 text-sm font-semibold text-[#000000]">{{ $codigoCredito ?: '—' }}</p>
                            <p class="mt-1 text-xs text-[#000000]">Próximo pago: {{ $proximoPago ?: '—' }}</p>
                        </div>

                        <div class="rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#000000]">Saldo original</p>
                            <p class="mt-1 text-base font-bold text-[#000000]">C$ {{ $saldoOriginal }}</p>
                            <p class="mt-1 text-xs text-[#000000]">Fecha: {{ $fechaCredito ?: '—' }}</p>
                        </div>

                        <div class="rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#000000]">Saldo pendiente</p>
                            <p class="mt-1 text-base font-bold text-[#000000]">C$ {{ $saldoPendiente }}</p>
                            <span class="mt-2 inline-flex rounded-full bg-[#2ECC71]/10 px-2.5 py-1 text-xs font-semibold text-[#1C7C45]">
                                {{ $estadoCredito }}
                            </span>
                        </div>
                    </div>
                </x-form>
            </x-card>

            <x-card
                title="Detalle del crédito"
                subtitle="Cuotas registradas, pagos aplicados y estado actual."
                shadow
                separator
                class="flex min-h-0 flex-1 flex-col {{ $cardClass }}"
            >
                <div class="min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#D7E4F3]">
                    <div class="h-full overflow-auto overscroll-contain">
                        <x-table
                            :headers="$headersDetalle"
                            :rows="$detalleCredito"
                            class="[&_thead_th]:sticky [&_thead_th]:top-0 [&_thead_th]:z-10 [&_thead_th]:border-0 [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_tbody_td]:border-[#D7E4F3] [&_tbody_td]:text-[#000000]"
                        />
                    </div>
                </div>

                @if (! count($detalleCredito))
                    <div class="mt-3 rounded-2xl border border-dashed border-[#D7E4F3] bg-[#F8FAFC] px-4 py-6 text-center text-sm text-[#000000]">
                        Aún no hay cuotas cargadas para este crédito.
                    </div>
                @endif
            </x-card>
        </div>

        <aside class="xl:sticky xl:top-4 flex max-h-[calc(100vh-5rem)] flex-col gap-4 overflow-hidden">
            <x-card
                title="Registrar pago"
                subtitle="Formulario compacto para aplicar el abono del cliente."
                shadow
                separator
                class="{{ $cardClass }}"
            >
                <x-form wire:submit="registrarPago" no-separator>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-2">
                        <x-input
                            label="Saldo pendiente"
                            wire:model="saldoPendiente"
                            prefix="C$"
                            readonly
                            class="{{ $readonlyFieldClass }}"
                        />

                        <x-input
                            label="Cambio"
                            wire:model="cambioCordobas"
                            prefix="C$"
                            readonly
                            class="rounded-xl border-[#D7E4F3] bg-[#F0F3F7] font-semibold text-[#1C7C45] [&_.fieldset-legend]:text-[#000000] [&_.label]:text-[#000000] [&_label]:text-[#000000]"
                        />

                        <x-input
                            label="Abonar en C$"
                            wire:model="abonarCordobas"
                            prefix="C$"
                            class="{{ $fieldClass }}"
                        />

                        <x-input
                            label="Abonar en US$"
                            wire:model="abonarDolares"
                            prefix="US$"
                            class="{{ $fieldClass }}"
                        />

                        <x-select
                            label="Método"
                            wire:model="metodoPago"
                            :options="$metodosPagoOptions"
                            option-value="id"
                            option-label="name"
                            class="{{ $fieldClass }}"
                        />

                        <x-input
                            label="Tasa"
                            wire:model="tasaCambio"
                            placeholder="0.0000"
                            class="{{ $fieldClass }}"
                        />

                        <x-input
                            label="Referencia"
                            wire:model="referenciaPago"
                            placeholder="Voucher, recibo o transferencia"
                            class="{{ $fieldClass }} md:col-span-2"
                        />

                        <x-input
                            label="Fecha de pago"
                            type="date"
                            wire:model="fechaPago"
                            class="{{ $fieldClass }} md:col-span-2"
                        />
                    </div>

                    <div class="mt-3">
                        <x-textarea
                            label="Observación"
                            wire:model="observacion"
                            rows="3"
                            placeholder="Detalle breve del pago realizado..."
                            class="{{ $fieldClass }}"
                        />
                    </div>

                    <x-slot:actions>
                        <div class="grid w-full grid-cols-2 gap-2">
                            <x-button
                                label="Limpiar"
                                icon="o-arrow-path"
                                wire:click="limpiarPago"
                                class="{{ $primaryButtonClass }}"
                            />

                            <x-button
                                label="Guardar"
                                type="submit"
                                spinner="registrarPago"
                                icon="o-check-circle"
                                class="{{ $primaryButtonClass }}"
                            />
                        </div>
                    </x-slot:actions>
                </x-form>
            </x-card>

            <x-card
                title="Clientes con saldo a favor"
                subtitle="Panel fijo de consulta rápida."
                shadow
                separator
                class="flex min-h-0 flex-1 flex-col {{ $cardClass }}"
            >
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#000000]">Clientes</p>
                        <p class="mt-1 text-xl font-bold text-[#000000]">{{ count($clientesSaldoFavor) }}</p>
                    </div>

                    <div class="rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#000000]">Saldo total</p>
                        <p class="mt-1 text-xl font-bold text-[#000000]">C$ 0.00</p>
                    </div>
                </div>

                <div class="mt-3">
                    <x-input
                        label="Filtrar"
                        wire:model="filtroSaldoFavor"
                        placeholder="Buscar cliente..."
                        icon="o-magnifying-glass"
                        class="{{ $fieldClass }}"
                    />
                </div>

                <div class="mt-3 min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#D7E4F3]">
                    <div class="h-full overflow-auto overscroll-contain">
                        <x-table
                            :headers="$headersSaldoFavor"
                            :rows="$clientesSaldoFavor"
                            class="[&_thead_th]:sticky [&_thead_th]:top-0 [&_thead_th]:z-10 [&_thead_th]:border-0 [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_tbody_td]:border-[#D7E4F3] [&_tbody_td]:text-[#000000]"
                        />
                    </div>
                </div>

                @if (! count($clientesSaldoFavor))
                    <div class="mt-3 rounded-2xl border border-dashed border-[#D7E4F3] bg-[#F8FAFC] px-4 py-5 text-center text-sm text-[#000000]">
                        Aún no hay clientes con saldo a favor cargados.
                    </div>
                @endif

                <x-slot:actions>
                    <div class="grid w-full grid-cols-2 gap-2">
                        <x-button
                            label="Historial"
                            icon="o-clock"
                            class="{{ $primaryButtonClass }}"
                        />

                        <x-button
                            label="Aplicar saldo"
                            icon="o-banknotes"
                            class="{{ $primaryButtonClass }}"
                        />
                    </div>
                </x-slot:actions>
            </x-card>
        </aside>
    </div>
</div>