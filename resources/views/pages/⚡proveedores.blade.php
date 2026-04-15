<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#1A2B42]">Proveedores</h1>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Registro y gestión de proveedores del sistema.
        </p>
    </div>

    {{-- Formulario --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Registrar proveedor</h2>
            <p class="text-base text-[#5F6B7A]">
                Ingrese los datos del proveedor.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Nombre
                </label>
                <x-input
                    placeholder="Ingrese el nombre"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Apellido
                </label>
                <x-input
                    placeholder="Ingrese el apellido"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Teléfono
                </label>
                <x-input
                    placeholder="Ingrese el número de teléfono"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Código RUC
                </label>
                <x-input
                    placeholder="Ingrese el código RUC"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Nacionalidad
                </label>
                <x-input
                    placeholder="Ingrese la nacionalidad"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>
        </div>

        <x-slot:actions>
            <x-button
                label="Guardar proveedor"
                class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30"
            />
        </x-slot:actions>
    </x-card>

    @php
        $headers = [
            ['key' => 'full_name', 'label' => 'Nombre completo'],
            ['key' => 'phone', 'label' => 'Teléfono'],
            ['key' => 'ruc', 'label' => 'Código RUC'],
            ['key' => 'nationality', 'label' => 'Nacionalidad'],
            ['key' => 'status', 'label' => 'Estado del proveedor'],
        ];

        $proveedores = [];
    @endphp

    {{-- Tabla --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de proveedores</h2>
            <x-input
                placeholder="Buscar proveedores"
                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
            />
        </div>

        <x-table
            :headers="$headers"
            :rows="$proveedores"
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
        >
            @scope('cell_full_name', $proveedor)
                <span class="font-semibold text-[#1A2B42]">{{ $proveedor['full_name'] }}</span>
            @endscope

            @scope('cell_phone', $proveedor)
                <span class="text-[#1A2B42]">{{ $proveedor['phone'] }}</span>
            @endscope

            @scope('cell_ruc', $proveedor)
                <span class="text-[#1A2B42]">{{ $proveedor['ruc'] }}</span>
            @endscope

            @scope('cell_nationality', $proveedor)
                <span class="text-[#1A2B42]">{{ $proveedor['nationality'] }}</span>
            @endscope
            @scope('actions', $proveedor)
                <div class="flex gap-2">
                    <x-button
                        label="Editar"
                        icon="o-pencil-square"
                        class="btn-sm border-0 bg-[#E67E22] text-white hover:opacity-90"
                    />
                    <x-button
                        label="Eliminar"
                        icon="o-trash"
                        class="btn-sm border-0 bg-[#E74C3C] text-white hover:opacity-90"
                    />
                </div>
            @endscope
        </x-table>
    </x-card>
</div>