<?php

use Livewire\Component;

new class extends Component
{
    public string $busqueda = '';
    public bool $clienteTraeFactura = false;

    public array $venta = [
        'numero_factura' => '',
        'cliente' => '',
        'total' => 0,
    ];

    public array $detalle = [];

    public function mount(): void
    {
        $this->limpiarFormulario();
    }

    public function buscarVenta(): void
    {
        $this->venta = [
            'numero_factura' => 'FAC-000245',
            'cliente' => 'María Fernanda López',
            'total' => 1785.00,
        ];

        $this->detalle = [
            [
                'id' => 1,
                'codigo' => 'P-001',
                'producto' => 'Memoria USB 16GB',
                'precio' => 320.00,
                'cantidad_vendida' => 2,
                'total_linea' => 640.00,
                'aplica' => false,
                'cantidad_devuelve' => 0,
                'monto_devuelve' => 0,
            ],
            [
                'id' => 2,
                'codigo' => 'P-002',
                'producto' => 'Cable UTP Cat6 5m',
                'precio' => 185.00,
                'cantidad_vendida' => 3,
                'total_linea' => 555.00,
                'aplica' => false,
                'cantidad_devuelve' => 0,
                'monto_devuelve' => 0,
            ],
            [
                'id' => 3,
                'codigo' => 'P-003',
                'producto' => 'Disco duro externo 1TB',
                'precio' => 118.00,
                'cantidad_vendida' => 5,
                'total_linea' => 590.00,
                'aplica' => false,
                'cantidad_devuelve' => 0,
                'monto_devuelve' => 0,
            ],
        ];

        $this->recalcularDevolucion();
    }

    public function limpiarFormulario(): void
    {
        $this->busqueda = '';
        $this->clienteTraeFactura = false;

        $this->venta = [
            'numero_factura' => '',
            'cliente' => '',
            'total' => 0,
        ];

        $this->detalle = [];
    }

    public function confirmarDevolucion(): void
    {
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'detalle.')) {
            $this->recalcularDevolucion();
        }
    }

    protected function recalcularDevolucion(): void
    {
        foreach ($this->detalle as $index => $item) {
            $cantidadVendida = (int) ($item['cantidad_vendida'] ?? 0);
            $cantidadDevuelve = (int) ($item['cantidad_devuelve'] ?? 0);
            $precio = (float) ($item['precio'] ?? 0);
            $aplica = (bool) ($item['aplica'] ?? false);

            if (! $aplica) {
                $cantidadDevuelve = 0;
            }

            $cantidadDevuelve = max(0, min($cantidadVendida, $cantidadDevuelve));
            $montoDevuelve = $aplica ? ($cantidadDevuelve * $precio) : 0;

            $this->detalle[$index]['cantidad_devuelve'] = $cantidadDevuelve;
            $this->detalle[$index]['monto_devuelve'] = $montoDevuelve;
        }
    }

    public function obtenerTotalDevolucion(): float
    {
        return collect($this->detalle)->sum(fn ($item) => (float) ($item['monto_devuelve'] ?? 0));
    }
};
?>

@php
$fieldClass = 'rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#000000] placeholder:text-[#000000]
[&_.fieldset-legend]:text-[#000000] [&_.label]:text-[#000000] [&_label]:text-[#000000]';

$readonlyFieldClass = 'rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#000000] [&_.fieldset-legend]:text-[#000000]
[&_.label]:text-[#000000] [&_label]:text-[#000000]';

$cardClass = 'border border-[#D7E4F3] bg-white [&_.text-base-content\\/70]:text-[#000000] [&_.text-sm]:text-[#000000]
[&_.text-base-content]:text-[#000000] [&_.card-title]:text-[#000000] [&_label]:text-[#000000]
[&_.fieldset-legend]:text-[#000000]';

$primaryButtonClass = 'btn-sm border-0 bg-[#2E8BC0] text-white hover:bg-[#256f99]';
$outlineButtonClass = 'btn-sm border border-[#2E8BC0] bg-white text-[#2E8BC0] hover:bg-[#EAF5FB]';

$tableInputClass = 'w-28 rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-3 py-2 text-sm text-[#000000] outline-none
transition focus:border-[#2E8BC0] focus:ring-0 disabled:cursor-not-allowed disabled:opacity-60';
@endphp

<div class="min-h-screen w-full bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="mx-auto flex w-full max-w-400 flex-col gap-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div class="max-w-4xl">
                <h1 class="text-2xl font-bold text-[#000000] md:text-3xl">Proceso de devoluciones</h1>
                <p class="mt-1 text-sm text-[#000000] md:text-base">
                    Consulta una venta por número de factura o cliente y selecciona los productos aplicables para
                    devolución.
                </p>
            </div>

            <div class="flex shrink-0">
                <x-button label="Historial" icon="o-clock" class="{{ $primaryButtonClass }} h-11 px-5" />
            </div>
        </div>

        <x-card title="Consulta de venta"
            subtitle="Ubica la factura y visualiza la información general antes de procesar la devolución." shadow
            separator class="{{ $cardClass }}">
            <x-form wire:submit="buscarVenta" no-separator>
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-5">
                        <x-input label="Buscar factura o cliente" wire:model.live.debounce.500ms="busqueda"
                            placeholder="Ej. FAC-000245 o María López" icon="o-magnifying-glass"
                            class="{{ $fieldClass }}" />
                    </div>

                    <div class="xl:col-span-3">
                        <x-input label="Número de factura" wire:model="venta.numero_factura" readonly
                            class="{{ $readonlyFieldClass }}" />
                    </div>

                    <div class="xl:col-span-4">
                        <x-input label="Nombre del cliente" wire:model="venta.cliente" readonly
                            class="{{ $readonlyFieldClass }}" />
                    </div>

                    <div class="xl:col-span-5">
                        <label class="mb-2 block text-sm font-semibold text-transparent">Acciones</label>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <x-button label="Cancelar" wire:click="limpiarFormulario" icon="o-x-circle"
                                class="{{ $outlineButtonClass }} h-9.5 px-6" />

                            <x-button label="Confirmar devolución" wire:click="confirmarDevolucion"
                                icon="o-check-circle" class="{{ $primaryButtonClass }} h-9.5 px-6" />
                        </div>
                    </div>
                </div>
            </x-form>
        </x-card>
        <br>
        <x-card title="Detalle de la factura"
            subtitle="Marca si la devolución aplica al producto, indica la cantidad y el monto se calculará automáticamente."
            shadow separator class="{{ $cardClass }}">
            <div class="overflow-hidden rounded-2xl border border-[#D7E4F3]">
                <div class="max-h-115 overflow-auto">
                    <table class="min-w-full table-auto">
                        <thead class="sticky top-0 z-10 bg-[#2E8BC0] text-sm font-semibold">
                            <tr>
                                <th class="px-4 py-4 text-left text-white!">Código</th>
                                <th class="px-4 py-4 text-left text-white!">Producto</th>
                                <th class="px-4 py-4 text-left text-white!">Precio (C$)</th>
                                <th class="px-4 py-4 text-left text-white!">Cantidad vendida</th>
                                <th class="px-4 py-4 text-left text-white!">Total venta (C$)</th>
                                <th class="px-4 py-4 text-center text-white!">Aplica devolución</th>
                                <th class="px-4 py-4 text-left text-white!">Cantidad a devolver</th>
                                <th class="px-4 py-4 text-left text-white!">Total a devolver (C$)</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[#E6EEF8] bg-white">
                            @forelse ($detalle as $index => $item)
                            <tr class="hover:bg-[#F8FBFF]">
                                <td class="px-4 py-3 text-sm font-medium text-[#000000]">
                                    {{ $item['codigo'] }}
                                </td>

                                <td class="px-4 py-3 text-sm text-[#000000]">
                                    {{ $item['producto'] }}
                                </td>

                                <td class="px-4 py-3 text-sm text-[#000000]">
                                    C$ {{ number_format((float) $item['precio'], 2) }}
                                </td>

                                <td class="px-4 py-3 text-sm text-[#000000]">
                                    {{ $item['cantidad_vendida'] }}
                                </td>

                                <td class="px-4 py-3 text-sm font-medium text-[#000000]">
                                    C$ {{ number_format((float) $item['total_linea'], 2) }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <label class="inline-flex cursor-pointer items-center justify-center select-none">
                                        <input type="checkbox" wire:model.live="detalle.{{ $index }}.aplica"
                                            class="peer sr-only">

                                        <span
                                            class="flex h-5 w-5 items-center justify-center rounded-md border-2 border-[#2E8BC0] bg-white transition peer-checked:border-[#2E8BC0] peer-checked:bg-[#2E8BC0]">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                fill="currentColor"
                                                class="h-3.5 w-3.5 text-white opacity-0 transition peer-checked:opacity-100">
                                                <path fill-rule="evenodd"
                                                    d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.2 7.261a1 1 0 0 1-1.42.007L3.296 9.178a1 1 0 1 1 1.414-1.414l4.09 4.09 6.49-6.544a1 1 0 0 1 1.414-.02Z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </label>
                                </td>

                                <td class="px-4 py-3">
                                    <input type="number" min="0" max="{{ $item['cantidad_vendida'] }}"
                                        wire:model.live="detalle.{{ $index }}.cantidad_devuelve" @disabled(!
                                        $item['aplica']) class="{{ $tableInputClass }}">
                                </td>

                                <td class="px-4 py-3 text-sm font-bold text-[#000000]">
                                    C$ {{ number_format((float) $item['monto_devuelve'], 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-sm font-medium text-[#000000]">
                                    Busca una factura o un cliente para cargar el detalle de la venta.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>

                        @if (count($detalle))
                        <tfoot class="bg-[#F8FBFF]">
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-right text-sm font-bold text-[#000000]">
                                    Totales
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-[#000000]">
                                    C$ {{ number_format((float) ($venta['total'] ?? 0), 2) }}
                                </td>
                                <td class="px-4 py-4"></td>
                                <td class="px-4 py-4 text-sm font-bold text-[#000000]">
                                    {{ collect($detalle)->sum('cantidad_devuelve') }}
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-[#2E8BC0]">
                                    C$ {{ number_format($this->obtenerTotalDevolucion(), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </x-card>


    </div>
</div>
