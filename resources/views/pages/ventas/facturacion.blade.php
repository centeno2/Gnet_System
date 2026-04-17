<?php

use Livewire\Component;

new class extends Component
{
    public bool $modalCredito = false;

    public function abrirModalCredito(): void
    {
        $this->modalCredito = true;
    }

    public function cerrarModalCredito(): void
    {
        $this->modalCredito = false;
    }
};
?>

<div class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-y-auto bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="flex shrink-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1A2B42]">Facturación</h1>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Registro de ventas, clientes y detalle de facturación.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-button
                label="Crédito"
                wire:click="abrirModalCredito"
                class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]"
            />

            <x-button
                label="Buscar productos"
                class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]"
            />

            <x-button
                label="Guardar venta"
                class="h-10 min-h-10 border-0 bg-[#0E48A1] px-4 text-sm text-white hover:bg-[#0B6FE4]"
            />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
        <x-card class="xl:col-span-3 rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Datos de la factura</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Complete la información general de la venta y del cliente.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">No. factura</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Fecha</label>
                    <x-input type="datetime-local" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo de venta</label>
                    <x-select
                        :options="[
                            ['id' => 'CONTADO', 'name' => 'Contado'],
                            ['id' => 'CREDITO', 'name' => 'Crédito']
                        ]"
                        option-value="id"
                        option-label="name"
                        placeholder="Seleccione"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Moneda</label>
                    <x-select
                        :options="[
                            ['id' => 'NIO', 'name' => 'Córdobas'],
                            ['id' => 'USD', 'name' => 'Dólares']
                        ]"
                        option-value="id"
                        option-label="name"
                        placeholder="Seleccione"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cliente / institución</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Documento</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Teléfono</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Dirección</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Correo</label>
                    <x-input type="email" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Descuento</label>
                    <x-input type="number" step="0.01" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div class="xl:col-span-4">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Observación</label>
                    <x-textarea rows="3" class="w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>
            </div>
        </x-card>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Resumen</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Totales de la venta.
                </p>
            </div>

            <div class="space-y-3">
                <div class="rounded-xl bg-[#F0F3F7] px-4 py-4 text-[#1A2B42]">
                    <span class="block text-sm">Subtotal</span>
                </div>

                <div class="rounded-xl bg-[#F0F3F7] px-4 py-4 text-[#1A2B42]">
                    <span class="block text-sm">Descuento</span>
                </div>

                <div class="rounded-xl bg-[#EAF2FB] px-4 py-4 text-[#0B6FE4]">
                    <span class="block text-sm font-semibold">Total</span>
                </div>
            </div>
        </x-card>
    </div>

    @php
        $headers = [
            ['key' => 'codigo', 'label' => 'Código'],
            ['key' => 'descripcion', 'label' => 'Descripción'],
            ['key' => 'tipo', 'label' => 'Tipo'],
            ['key' => 'cantidad', 'label' => 'Cantidad'],
            ['key' => 'precio', 'label' => 'Precio'],
            ['key' => 'subtotal', 'label' => 'Subtotal'],
        ];

        $detalleVenta = [];
    @endphp

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-3 flex shrink-0 flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-bold text-[#1A2B42]">Detalle de la venta</h2>

            <div class="flex flex-wrap gap-2">
                <x-button
                    label="Agregar servicio"
                    class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]"
                />

                <x-button
                    label="Agregar producto"
                    class="h-10 min-h-10 border-0 bg-[#0E48A1] px-4 text-sm text-white hover:bg-[#0B6FE4]"
                />
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-hidden rounded-xl border border-[#D7E4F3]">
            <div class="h-full overflow-auto">
                <x-table
                    :headers="$headers"
                    :rows="$detalleVenta"
                    class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
                >
                </x-table>
            </div>
        </div>
    </x-card>

    <x-modal
    wire:model="modalCredito"
    class="backdrop-blur-sm"
    box-class="w-full max-w-7xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl"
>
    <div class="mb-5">
        <h3 class="text-2xl font-bold text-[#1A2B42]">Crédito institucional</h3>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Complete los datos del crédito.
        </p>
    </div>

    <x-ventas.credito-institucional />

    <x-slot:actions>
        <x-button
            label="Cerrar"
            wire:click="cerrarModalCredito"
            class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]"
        />

        <x-button
            label="Guardar crédito"
            class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]"
        />
    </x-slot:actions>
</x-modal>
</div>