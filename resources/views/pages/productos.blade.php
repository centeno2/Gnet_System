<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<x-layouts.app title="Productos">
<div class="min-h-screen space-y-6 bg-[#F0F3F7] p-4 md:p-6">

    <!-- Encabezado -->
    <section class="rounded-3xl border border-[#D7E4F3] bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#1A2B42]">Gestión de productos</h1>
                <p class="mt-1 text-sm text-[#757E8D]">
                    Registro y administración de productos
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <button class="rounded-xl bg-[#0E48A1] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-95">
                    Nuevo producto
                </button>
                <button class="rounded-xl border border-[#D7E4F3] bg-white px-5 py-3 text-sm font-semibold text-[#1A2B42] transition hover:bg-[#F0F3F7]">
                    Exportar
                </button>
            </div>
        </div>
    </section>

    <!-- Resumen -->
    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-[#D7E4F3] bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-[#757E8D]">Total de productos</p>
            <h2 class="mt-2 text-3xl font-bold text-[#1A2B42]"></h2>
        </div>

        <div class="rounded-2xl border border-[#D7E4F3] bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-[#757E8D]">Stock bajo</p>
            <h2 class="mt-2 text-3xl font-bold text-[#1A2B42]"></h2>
        </div>

        <div class="rounded-2xl border border-[#D7E4F3] bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-[#757E8D]">Activos</p>
            <h2 class="mt-2 text-3xl font-bold text-[#1A2B42]"></h2>
        </div>

        <div class="rounded-2xl border border-[#D7E4F3] bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-[#757E8D]">Inactivos</p>
            <h2 class="mt-2 text-3xl font-bold text-[#1A2B42]"></h2>
        </div>
    </section>

    <!-- Formulario + panel lateral -->
    <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <!-- Formulario -->
        <div class="xl:col-span-2 rounded-3xl border border-[#D7E4F3] bg-white p-6 shadow-sm">
            <div class="mb-6">
                <h3 class="text-xl font-bold text-[#1A2B42]">Registro de producto</h3>
                <p class="mt-1 text-sm text-[#757E8D]">
                    Completa la información principal del producto
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">

                <div class="md:col-span-2 xl:col-span-1">
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Categoría</label>
                    <select class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4]">
                        <option selected></option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Nombre del producto</label>
                    <input
                        type="text"
                        class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Modelo</label>
                    <input
                        type="text"
                        class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Stock actual</label>
                    <input
                        type="number"
                        class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Stock mínimo</label>
                    <input
                        type="number"
                        class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Precio de compra</label>
                    <input
                        type="text"
                        class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Precio de venta</label>
                    <input
                        type="text"
                        class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Garantía nuevo</label>
                    <input
                        type="number"
                        class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Garantía usado</label>
                    <input
                        type="number"
                        class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4]"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Estado</label>
                    <select class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4]">
                        <option selected></option>
                    </select>
                </div>

                <div class="md:col-span-2 xl:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">Control de vencimiento</label>
                    <div class="flex h-12.5 items-center rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4">
                        <label class="flex items-center gap-3 text-sm font-medium text-[#1A2B42]">
                            <input type="checkbox" class="h-4 w-4 rounded border-[#D7E4F3] text-[#0B6FE4]">
                        </label>
                    </div>
                </div>

            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button class="rounded-xl border border-[#D7E4F3] bg-white px-5 py-3 text-sm font-semibold text-[#1A2B42] transition hover:bg-[#F0F3F7]">
                    Cancelar
                </button>
                <button class="rounded-xl bg-[#0B6FE4] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-95">
                    Guardar producto
                </button>
            </div>
        </div>

        <!-- Panel lateral -->
        <div class="space-y-6">
            <div class="rounded-3xl border border-[#D7E4F3] bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-[#1A2B42]">Detalle visual</h3>
                <div class="mt-5 flex h-56 items-center justify-center rounded-2xl border-2 border-dashed border-[#D7E4F3] bg-[#F0F3F7]"></div>
            </div>

            <div class="rounded-3xl border border-[#D7E4F3] bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-[#1A2B42]">Información adicional</h3>
                <div class="mt-4 space-y-3">
                    <div class="h-4 w-full rounded bg-[#F0F3F7]"></div>
                    <div class="h-4 w-5/6 rounded bg-[#F0F3F7]"></div>
                    <div class="h-4 w-4/6 rounded bg-[#F0F3F7]"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tabla -->
    <section class="rounded-3xl border border-[#D7E4F3] bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-xl font-bold text-[#1A2B42]">Listado de productos</h3>
                <p class="mt-1 text-sm text-[#757E8D]">
                    Consulta del inventario registrado
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <input
                    type="text"
                    class="w-full rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4] sm:w-72"
                >

                <select class="rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none transition focus:border-[#0B6FE4]">
                    <option selected></option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-[#D7E4F3]">
            <table class="min-w-full text-sm">
                <thead class="bg-[#0B6FE4] text-white">
                    <tr>
                        <th class="px-4 py-4 text-left font-semibold">Producto</th>
                        <th class="px-4 py-4 text-left font-semibold">Categoría</th>
                        <th class="px-4 py-4 text-left font-semibold">Modelo</th>
                        <th class="px-4 py-4 text-left font-semibold">Stock actual</th>
                        <th class="px-4 py-4 text-left font-semibold">Stock mínimo</th>
                        <th class="px-4 py-4 text-left font-semibold">Compra</th>
                        <th class="px-4 py-4 text-left font-semibold">Venta</th>
                        <th class="px-4 py-4 text-left font-semibold">Vencimiento</th>
                        <th class="px-4 py-4 text-left font-semibold">Garantía</th>
                        <th class="px-4 py-4 text-left font-semibold">Estado</th>
                        <th class="px-4 py-4 text-center font-semibold">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[#D7E4F3] bg-white text-[#1A2B42]">
                    <tr>
                        <td class="px-4 py-6"></td>
                        <td class="px-4 py-6"></td>
                        <td class="px-4 py-6"></td>
                        <td class="px-4 py-6"></td>
                        <td class="px-4 py-6"></td>
                        <td class="px-4 py-6"></td>
                        <td class="px-4 py-6"></td>
                        <td class="px-4 py-6"></td>
                        <td class="px-4 py-6"></td>
                        <td class="px-4 py-6"></td>
                        <td class="px-4 py-6"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
</x-layouts.app>