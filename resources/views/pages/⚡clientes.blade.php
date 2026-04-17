<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#1A2B42]">Clientes</h1>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Registro y gestión de clientes del sistema.
        </p>
    </div>

    {{-- Formulario --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Registrar cliente</h2>
            <p class="text-base text-[#5F6B7A]">
                Ingrese los datos del cliente. El correo es opcional.
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
                    placeholder="Ingrese el teléfono"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Correo electrónico
                </label>
                <x-input
                    placeholder="Ingrese el correo electrónico (opcional)"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>
        </div>

        <x-slot:actions>
            <x-button
                label="Guardar cliente"
                class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30"
            />
        </x-slot:actions>
    </x-card>

    @php
        $headers = [
            ['key' => 'full_name', 'label' => 'Nombre completo'],
            ['key' => 'phone', 'label' => 'Teléfono'],
            ['key' => 'email', 'label' => 'Correo'],
            ['key' => 'status', 'label' => 'Estado'],
        ];

        $clientes = [
         
        ];
    @endphp

    {{-- Tabla --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de clientes</h2>

            <x-input
                placeholder="Buscar clientes"
                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
            />
        </div>

        <x-table
            :headers="$headers"
            :rows="$clientes"
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
        >

        </x-table>
    </x-card>
</div>