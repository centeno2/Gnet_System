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
        // Datos de ejemplo para visualizar la interfaz.
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
        //
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

<div class="min-h-screen bg-[#F0F3F7] p-4 md:p-6">
    <div class="mx-auto max-w-7xl space-y-6">

        <div>
            <h1 class="text-2xl font-bold text-[#1A2B42] md:text-3xl">
                Proceso de devoluciones
            </h1>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Consulta una venta por número de factura o cliente y selecciona los productos aplicables para devolución.
            </p>
        </div>

        <div class="rounded-3xl border border-[#D7E4F3] bg-white p-5 shadow-sm">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-[#5F6B7A]">
                        Buscar factura o cliente
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-[#7B8794]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            wire:model.live.debounce.500ms="busqueda"
                            placeholder="Ej. FAC-000245 o María López"
                            class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] py-3 pl-11 pr-4 text-[#1A2B42] placeholder:text-[#7B8794] outline-none transition focus:border-[#0B6FE4] focus:ring-0"
                        >
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#5F6B7A]">
                        Número de factura
                    </label>
                    <input
                        type="text"
                        readonly
                        value="{{ $venta['numero_factura'] }}"
                        class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] placeholder:text-[#7B8794] outline-none"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#5F6B7A]">
                        Nombre del cliente
                    </label>
                    <input
                        type="text"
                        readonly
                        value="{{ $venta['cliente'] }}"
                        class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] placeholder:text-[#7B8794] outline-none"
                    >
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <div class="flex min-h-[74px] items-center rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] px-4">
                        <label class="flex cursor-pointer items-center gap-3 text-sm font-medium text-[#1A2B42]">
                            <input
                                type="checkbox"
                                wire:model.live="clienteTraeFactura"
                                class="h-5 w-5 rounded border-[#B8C7D9] text-[#0B6FE4] focus:ring-[#0B6FE4]"
                            >
                            <span>El cliente presenta la factura</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#5F6B7A]">
                        Total de la venta
                    </label>
                    <div class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-base font-semibold text-[#1A2B42]">
                        C$ {{ number_format((float) ($venta['total'] ?? 0), 2) }}
                    </div>
                </div>

                <div class="flex items-end justify-end gap-3">
                    <x-button
                        label="Limpiar"
                        wire:click="limpiarFormulario"
                        icon="o-arrow-path"
                        class="border-0 bg-[#8B95A5] text-white hover:bg-[#6F7A8A]"
                    />

                    <x-button
                        label="Buscar"
                        wire:click="buscarVenta"
                        spinner="buscarVenta"
                        icon="o-magnifying-glass"
                        class="border-0 bg-[#2ECC71] text-white hover:brightness-95"
                    />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-3xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
                <p class="text-sm font-medium text-[#5F6B7A]">Factura</p>
                <p class="mt-2 text-lg font-bold text-[#1A2B42]">
                    {{ $venta['numero_factura'] ?: 'Sin cargar' }}
                </p>
            </div>

            <div class="rounded-3xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
                <p class="text-sm font-medium text-[#5F6B7A]">Cliente</p>
                <p class="mt-2 text-lg font-bold text-[#1A2B42]">
                    {{ $venta['cliente'] ?: 'Sin cargar' }}
                </p>
            </div>

            <div class="rounded-3xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
                <p class="text-sm font-medium text-[#5F6B7A]">Estado de factura</p>
                <span class="mt-2 inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $clienteTraeFactura ? 'bg-[#2ECC71]/15 text-[#1D9E55]' : 'bg-[#E74C3C]/12 text-[#E74C3C]' }}">
                    {{ $clienteTraeFactura ? 'Factura presentada' : 'Sin factura física' }}
                </span>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="border-b border-[#D7E4F3] px-5 py-5">
                <h2 class="text-lg font-bold text-[#1A2B42]">
                    Detalle de la factura
                </h2>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    Marca si la devolución aplica al producto, indica la cantidad a devolver y el monto se calculará automáticamente.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-[#2E8BC0] text-sm font-semibold text-white">
                        <tr>
                            <th class="px-4 py-4 text-left">Código</th>
                            <th class="px-4 py-4 text-left">Producto</th>
                            <th class="px-4 py-4 text-left">Precio (C$)</th>
                            <th class="px-4 py-4 text-left">Cantidad vendida</th>
                            <th class="px-4 py-4 text-left">Total venta (C$)</th>
                            <th class="px-4 py-4 text-center">Aplica devolución</th>
                            <th class="px-4 py-4 text-left">Cantidad a devolver</th>
                            <th class="px-4 py-4 text-left">Total a devolver (C$)</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[#E6EEF8] bg-white">
                        @forelse ($detalle as $index => $item)
                            <tr class="hover:bg-[#F8FBFF]">
                                <td class="px-4 py-3 text-sm font-medium text-[#1A2B42]">
                                    {{ $item['codigo'] }}
                                </td>

                                <td class="px-4 py-3 text-sm text-[#1A2B42]">
                                    {{ $item['producto'] }}
                                </td>

                                <td class="px-4 py-3 text-sm text-[#1A2B42]">
                                    C$ {{ number_format((float) $item['precio'], 2) }}
                                </td>

                                <td class="px-4 py-3 text-sm text-[#1A2B42]">
                                    {{ $item['cantidad_vendida'] }}
                                </td>

                                <td class="px-4 py-3 text-sm font-medium text-[#1A2B42]">
                                    C$ {{ number_format((float) $item['total_linea'], 2) }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        wire:model.live="detalle.{{ $index }}.aplica"
                                        class="h-5 w-5 rounded border-[#B8C7D9] text-[#0B6FE4] focus:ring-[#0B6FE4]"
                                    >
                                </td>

                                <td class="px-4 py-3">
                                    <input
                                        type="number"
                                        min="0"
                                        max="{{ $item['cantidad_vendida'] }}"
                                        wire:model.live="detalle.{{ $index }}.cantidad_devuelve"
                                        @disabled(! $item['aplica'])
                                        class="w-28 rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-3 py-2 text-sm text-[#1A2B42] outline-none transition focus:border-[#0B6FE4] focus:ring-0 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                </td>

                                <td class="px-4 py-3 text-sm font-bold text-[#1A2B42]">
                                    C$ {{ number_format((float) $item['monto_devuelve'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-sm font-medium text-[#6B7280]">
                                    Busca una factura o un cliente para cargar el detalle de la venta.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if (count($detalle))
                        <tfoot class="bg-[#F8FBFF]">
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-right text-sm font-bold text-[#1A2B42]">
                                    Totales
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-[#1A2B42]">
                                    C$ {{ number_format((float) ($venta['total'] ?? 0), 2) }}
                                </td>
                                <td class="px-4 py-4"></td>
                                <td class="px-4 py-4 text-sm font-bold text-[#1A2B42]">
                                    {{ collect($detalle)->sum('cantidad_devuelve') }}
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-[#2ECC71]">
                                    C$ {{ number_format($this->obtenerTotalDevolucion(), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="min-w-[185px] rounded-3xl border border-[#D7E4F3] bg-white px-5 py-4 shadow-sm">
                <p class="text-sm font-medium text-[#5F6B7A]">Monto total a devolver</p>
                <p class="mt-2 text-2xl font-bold text-[#2ECC71]">
                    C$ {{ number_format($this->obtenerTotalDevolucion(), 2) }}
                </p>
            </div>

            <div class="flex gap-3">
                <x-button
                    label="Cancelar"
                    wire:click="limpiarFormulario"
                    icon="o-x-circle"
                    class="border-0 bg-[#E74C3C] text-white hover:brightness-95"
                />

                <x-button
                    label="Confirmar devolución"
                    wire:click="confirmarDevolucion"
                    icon="o-check-circle"
                    class="border-0 bg-[#2ECC71] text-white hover:brightness-95"
                />
            </div>
        </div>

    </div>
</div>