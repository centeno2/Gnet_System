<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#1A2B42]">Compras</h1>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Registro visual de compras realizadas a proveedores.
        </p>
    </div>

    {{-- Formulario --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Registrar compra</h2>
            <p class="text-base text-[#5F6B7A]">
                Ingrese los datos generales y el detalle de la compra.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Proveedor
                </label>
                <x-select
                    placeholder="Seleccione un proveedor"
                    :options="[
                        ['id' => 1, 'name' => 'Distribuidora Central'],
                        ['id' => 2, 'name' => 'Tech Import'],
                        ['id' => 3, 'name' => 'Suministros del Norte'],
                    ]"
                    option-value="id"
                    option-label="name"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Número de factura
                </label>
                <x-input
                    placeholder="Ingrese el número de factura"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Fecha de compra
                </label>
                <x-datetime
                    type="date"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Categoría
                </label>
                <x-select
                    placeholder="Seleccione una categoría"
                    :options="[
                        ['id' => 1, 'name' => 'Laptops'],
                        ['id' => 2, 'name' => 'Impresoras'],
                        ['id' => 3, 'name' => 'Accesorios'],
                    ]"
                    option-value="id"
                    option-label="name"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Marca
                </label>
                <x-select
                    placeholder="Seleccione una marca"
                    :options="[
                        ['id' => 1, 'name' => 'HP'],
                        ['id' => 2, 'name' => 'Dell'],
                        ['id' => 3, 'name' => 'Canon'],
                    ]"
                    option-value="id"
                    option-label="name"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Nombre de producto
                </label>
                <x-select
                    placeholder="Seleccione un producto"
                    :options="[
                        ['id' => 1, 'name' => 'Laptop HP 15'],
                        ['id' => 2, 'name' => 'Impresora Canon G3110'],
                        ['id' => 3, 'name' => 'Mouse inalámbrico'],
                    ]"
                    option-value="id"
                    option-label="name"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Cantidad
                </label>
                <x-input
                    placeholder="Ingrese la cantidad"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Precio de compra
                </label>
                <x-input
                    placeholder="Ingrese el precio de compra"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Retención
                </label>
                <x-input
                    placeholder="Ingrese la retención"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    IVA
                </label>
                <x-input
                    placeholder="Ingrese el IVA"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Tipo de pago
                </label>
                <x-select
                    placeholder="Seleccione un tipo de pago"
                    :options="[
                        ['id' => 'efectivo', 'name' => 'Efectivo'],
                        ['id' => 'transferencia', 'name' => 'Transferencia'],
                        ['id' => 'credito', 'name' => 'Crédito'],
                    ]"
                    option-value="id"
                    option-label="name"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                />
            </div>

            <div class="md:col-span-2 xl:col-span-3">
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Observación
                </label>
                <x-textarea
                    placeholder="Ingrese una observación"
                    rows="4"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>
        </div>

        <x-slot:actions>
            <div class="flex flex-wrap gap-3">
                <x-button
                    label="Agregar"
                    icon="o-plus"
                    class="border-0 bg-[#2E8BC0] text-white hover:opacity-90"
                />
                <x-button
                    label="Guardar compra"
                    icon="o-check"
                    class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]"
                />
            </div>
        </x-slot:actions>
    </x-card>

    @php
        $headers = [
            ['key' => 'provider', 'label' => 'Proveedor'],
            ['key' => 'invoice', 'label' => 'No. Factura'],
            ['key' => 'date', 'label' => 'Fecha'],
            ['key' => 'category', 'label' => 'Categoría'],
            ['key' => 'brand', 'label' => 'Marca'],
            ['key' => 'product', 'label' => 'Producto'],
            ['key' => 'quantity', 'label' => 'Cantidad'],
            ['key' => 'purchase_price', 'label' => 'Precio compra'],
            ['key' => 'retention', 'label' => 'Retención'],
            ['key' => 'iva', 'label' => 'IVA'],
            ['key' => 'payment_type', 'label' => 'Tipo de pago'],
            ['key' => 'observation', 'label' => 'Observación'],
            ['key' => 'subtotal', 'label' => 'Subtotal'],
        ];

        $compras = [
            
        ];
    @endphp

    {{-- Tabla --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Detalle de compra</h2>
            <p class="text-base text-[#5F6B7A]">
                Aquí se mostrarán los productos agregados a la compra.
            </p>
        </div>

        <x-table
            :headers="$headers"
            :rows="$compras"
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
        >
        </x-table>

        <div class="mt-5 flex justify-end">
            <div class="w-full max-w-xs">
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Total
                </label>
                <x-input
                    value="C$ 0.00"
                    readonly
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] font-semibold"
                />
            </div>
        </div>
    </x-card>
</div>