<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#1A2B42]">Trabajadores</h1>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Registro y gestión visual de trabajadores del sistema.
        </p>
    </div>

    {{-- Formulario --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Registrar trabajador</h2>
            <p class="text-base text-[#5F6B7A]">
                Ingrese los datos del trabajador.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
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
                    Segundo nombre
                </label>
                <x-input
                    placeholder="Ingrese el segundo nombre"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Primer apellido
                </label>
                <x-input
                    placeholder="Ingrese el primer apellido"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Segundo apellido
                </label>
                <x-input
                    placeholder="Ingrese el segundo apellido"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Cédula
                </label>
                <x-input
                    placeholder="Ingrese la cédula"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Teléfono
                </label>
                <x-input
                    placeholder="Ingrese el teléfono"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div class="md:col-span-2 xl:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Dirección
                </label>
                <x-input
                    placeholder="Ingrese la dirección"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Fecha de ingreso
                </label>
                <x-datetime
                    type="date"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Cargo
                </label>
                <x-select
                    placeholder="Seleccione un cargo"
                    :options="[
                        ['id' => 'empleador', 'name' => 'Empleador'],
                        ['id' => 'administrador', 'name' => 'Administrador'],
                    ]"
                    option-value="id"
                    option-label="name"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                />
            </div>
        </div>

        <x-slot:actions>
            <x-button
                label="Guardar trabajador"
                class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]"
            />
        </x-slot:actions>
    </x-card>

    @php
        $headers = [
            ['key' => 'full_name', 'label' => 'Nombre completo'],
            ['key' => 'position', 'label' => 'Cargo'],
            ['key' => 'phone', 'label' => 'Teléfono'],
            ['key' => 'address', 'label' => 'Dirección'],
            ['key' => 'entry_date', 'label' => 'Fecha de ingreso'],
        ];

        $trabajadores = [
        ];
    @endphp

    {{-- Tabla --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de trabajadores</h2>
            <p class="text-base text-[#5F6B7A]">
                Aquí se mostrarán los trabajadores registrados.
            </p>
        </div>

        <x-table
            :headers="$headers"
            :rows="$trabajadores"
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
        >
        </x-table>
    </x-card>
</div>