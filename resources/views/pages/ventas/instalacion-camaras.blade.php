<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-y-auto bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="flex shrink-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1A2B42]">Instalación de cámaras</h1>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Registro del contrato, condiciones y materiales utilizados.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-button
                label="Buscar contratos"
                class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]"
            />

            <x-button
                label="Guardar contrato"
                class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]"
            />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
        <x-card class="xl:col-span-3 rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Datos del contrato</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Complete la información general del servicio de instalación.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-4">
                

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cliente / institución</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Teléfono</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Municipio</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cantidad de cámaras</label>
                    <x-input type="number" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Metros de cableado</label>
                    <x-input type="number" step="0.01" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Costo mano de obra</label>
                    <x-input type="number" step="0.01" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Porcentaje anticipo</label>
                    <x-input type="number" step="0.01" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Fecha estimada</label>
                    <x-input type="date" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Dirección de instalación</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div class="xl:col-span-4">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Detalle del contrato</label>
                    <x-textarea rows="4" class="w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>
            </div>
        </x-card>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Resumen económico</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Totales del contrato.
                </p>
            </div>

            <div class="space-y-3">
                <div class="rounded-xl bg-[#F0F3F7] px-4 py-4 text-[#1A2B42]">
                    <span class="block text-sm">Materiales</span>
                </div>

                <div class="rounded-xl bg-[#F0F3F7] px-4 py-4 text-[#1A2B42]">
                    <span class="block text-sm">Mano de obra</span>
                </div>

                <div class="rounded-xl bg-[#F0F3F7] px-4 py-4 text-[#1A2B42]">
                    <span class="block text-sm">Anticipo</span>
                </div>

                <div class="rounded-xl bg-[#EAF2FB] px-4 py-4 text-[#0B6FE4]">
                    <span class="block text-sm font-semibold">Total contrato</span>
                </div>

                <div class="rounded-xl bg-[#F5EEDF] px-4 py-4 text-[#9A6B00]">
                    <span class="block text-sm font-semibold">Saldo pendiente</span>
                </div>
            </div>
        </x-card>
    </div>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-[#1A2B42]">Condiciones del servicio</h2>
            <p class="text-sm text-[#5F6B7A]">
                Confirmaciones rápidas del contrato.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">

            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Incluye configuración en app</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Incluye garantía</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Anticipo recibido</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Contrato firmado</label>

        </div>
    </x-card>

    @php
        $headers = [
            ['key' => 'codigo', 'label' => 'Código'],
            ['key' => 'descripcion', 'label' => 'Descripción'],
            ['key' => 'cantidad', 'label' => 'Cantidad'],
            ['key' => 'precio', 'label' => 'Precio'],
            ['key' => 'subtotal', 'label' => 'Subtotal'],
        ];

        $productosUsados = [];
    @endphp

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-3 flex shrink-0 flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-bold text-[#1A2B42]">Productos usados</h2>

            <div class="flex flex-wrap gap-2">
                <x-button
                    label="Agregar producto"
                    class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]"
                />
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-hidden rounded-xl border border-[#D7E4F3]">
            <div class="h-full overflow-auto">
                <x-table
                    :headers="$headers"
                    :rows="$productosUsados"
                    class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
                >
                </x-table>
            </div>
        </div>
    </x-card>
</div>