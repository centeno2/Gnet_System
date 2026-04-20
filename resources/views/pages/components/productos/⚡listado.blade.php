<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-hidden bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="flex shrink-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1A2B42]">Listado completo de productos</h1>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Vista general del inventario completo.
            </p>
        </div>

        <a
            href="{{ route('productos.index') }}"
            class="inline-flex h-10 min-h-10 items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] transition hover:bg-[#F0F3F7]"
        >
            Volver a productos
        </a>
    </div>

    @php
        $headers = [
            ['key' => 'producto', 'label' => 'Producto'],
            ['key' => 'categoria', 'label' => 'Categoría'],
            ['key' => 'marca', 'label' => 'Marca'],
            ['key' => 'modelo', 'label' => 'Modelo'],
            ['key' => 'serie', 'label' => 'Número de serie'],
            ['key' => 'stock', 'label' => 'Stock'],
            ['key' => 'stock_minimo', 'label' => 'Stock mínimo'],
            ['key' => 'precio_compra', 'label' => 'Precio compra'],
            ['key' => 'precio_venta', 'label' => 'Precio venta'],
            ['key' => 'garantia_nuevo', 'label' => 'Garantía nuevo'],
            ['key' => 'garantia_usado', 'label' => 'Garantía usado'],
            ['key' => 'estado', 'label' => 'Estado'],
        ];

        $productos = [
            //
        ];
    @endphp

    <x-card class="flex min-h-0 flex-1 flex-col rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4 flex shrink-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <h2 class="text-xl font-bold text-[#1A2B42]">Inventario completo</h2>

            <div class="flex w-full flex-col gap-3 md:flex-row lg:w-auto">
                <div class="w-full md:w-72">
                    <x-input
                        type="text"
                        placeholder="Buscar producto"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                </div>

                <div class="w-full md:w-52">
                    <x-input
                        type="text"
                        placeholder="Filtrar estado"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                </div>
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-hidden rounded-xl border border-[#D7E4F3]">
            <div class="h-full overflow-auto">
                <x-table
                    :headers="$headers"
                    :rows="$productos"
                    class="[&_thead_th]:bg-[#2E8BC0] [&_thead_th]:py-3 [&_thead_th]:text-xs [&_thead_th]:font-semibold [&_thead_th]:text-[#feffff] [&_tbody_td]:py-2 [&_tbody_td]:text-sm [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
                >
                </x-table>
            </div>
        </div>
    </x-card>
</div>