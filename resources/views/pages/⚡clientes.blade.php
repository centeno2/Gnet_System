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
                    class="w-full rounded-xl border border-[#B8CBE3] bg-white text-[#1A2B42] placeholder:text-[#7B8794] shadow-sm focus:border-[#0E48A1] focus:ring-2 focus:ring-[#0E48A1]/20"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Apellido
                </label>
                <x-input
                    placeholder="Ingrese el apellido"
                    class="w-full rounded-xl border border-[#B8CBE3] bg-white text-[#1A2B42] placeholder:text-[#7B8794] shadow-sm focus:border-[#0E48A1] focus:ring-2 focus:ring-[#0E48A1]/20"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Teléfono
                </label>
                <x-input
                    placeholder="Ingrese el teléfono"
                    class="w-full rounded-xl border border-[#B8CBE3] bg-white text-[#1A2B42] placeholder:text-[#7B8794] shadow-sm focus:border-[#0E48A1] focus:ring-2 focus:ring-[#0E48A1]/20"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Correo electrónico
                </label>
                <x-input
                    placeholder="Ingrese el correo electrónico (opcional)"
                    class="w-full rounded-xl border border-[#B8CBE3] bg-white text-[#1A2B42] placeholder:text-[#7B8794] shadow-sm focus:border-[#0E48A1] focus:ring-2 focus:ring-[#0E48A1]/20"
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
            [
                'id' => 1,
                'full_name' => 'Juan Pérez',
                'phone' => '8888-1111',
                'email' => 'juanperez@email.com',
                'status' => 'Activo',
            ],
            [
                'id' => 2,
                'full_name' => 'María López',
                'phone' => '8888-2222',
                'email' => '',
                'status' => 'Inactivo',
            ],
            [
                'id' => 3,
                'full_name' => 'Carlos Hernández',
                'phone' => '8888-3333',
                'email' => 'carlosh@email.com',
                'status' => 'Activo',
            ],
        ];
    @endphp

    {{-- Tabla --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de clientes</h2>
            <p class="text-base text-[#5F6B7A]">
                Aquí se mostrarán los clientes registrados.
            </p>
        </div>

        <x-table :headers="$headers" :rows="$clientes">
            @scope('cell_full_name', $cliente)
                <span class="font-semibold text-[#1A2B42]">{{ $cliente['full_name'] }}</span>
            @endscope

            @scope('cell_phone', $cliente)
                <span class="text-[#1A2B42]">{{ $cliente['phone'] }}</span>
            @endscope

            @scope('cell_email', $cliente)
                @if ($cliente['email'])
                    <span class="text-[#5F6B7A]">{{ $cliente['email'] }}</span>
                @else
                    <span class="italic text-[#7B8794]">No registrado</span>
                @endif
            @endscope

            @scope('cell_status', $cliente)
                @if ($cliente['status'] === 'Activo')
                    <span class="inline-flex rounded-full bg-[#2ECC71] px-3 py-1 text-xs font-semibold text-white">
                        Activo
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-[#757E8D] px-3 py-1 text-xs font-semibold text-white">
                        Inactivo
                    </span>
                @endif
            @endscope

            @scope('actions', $cliente)
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