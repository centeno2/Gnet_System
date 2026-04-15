<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>
<div class="min-h-screen bg-[#F0F3F7] p-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#1A2B42]">Usuarios</h1>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Gestión e ingreso de usuarios del sistema.
        </p>
    </div>

    {{-- Formulario --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Registrar usuario</h2>
            <p class="text-base text-[#5F6B7A]">
                Complete los campos para crear un nuevo usuario.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Trabajador
                </label>
                <x-select
                    placeholder="Seleccione un trabajador"
                    :options="[
                        ['id' => 1, 'name' => 'Carlos Hernández'],
                        ['id' => 2, 'name' => 'María López'],
                        ['id' => 3, 'name' => 'Juan Pérez'],
                    ]"
                    option-value="id"
                    option-label="name"
                    
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Nombre de usuario
                </label>
                <x-input
                    placeholder="Ingrese el nombre de usuario"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Rol de usuario
                </label>
                <x-select
                    placeholder="Seleccione un rol"
                    :options="[
                        ['id' => 'cajero', 'name' => 'Cajero'],
                        ['id' => 'administrador', 'name' => 'Administrador'],
                        ['id' => 'gerente', 'name' => 'Gerente'],
                    ]"
                    option-value="id"
                    option-label="name"
                    class="w-full rounded-xl border border-[#B8CBE3] bg-white text-[#1A2B42] shadow-sm focus:border-[#0E48A1] focus:ring-2 focus:ring-[#0E48A1]/20"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Contraseña
                </label>
                <x-password
                    placeholder="Ingrese la contraseña"
                    clearable
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>
        </div>

        <x-slot:actions>
            <x-button
                label="Guardar usuario"
                class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30"
            />
        </x-slot:actions>
    </x-card>

    @php
        $headers = [
            ['key' => 'username', 'label' => 'Nombre usuario'],
            ['key' => 'password', 'label' => 'Contraseña'],
            ['key' => 'role', 'label' => 'Rol'],
            ['key' => 'status', 'label' => 'Estado del usuario'],
        ];

        $users = [

        ];
    @endphp

    {{-- Tabla --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de usuarios</h2>
            <x-input
                placeholder="Buscar usuario"
                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
            />
        </div>

        <x-table
            :headers="$headers"
            :rows="$users"
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
        >
            @scope('cell_username', $user)
                <span class="font-semibold text-[#1A2B42]">{{ $user['username'] }}</span>
            @endscope

            @scope('cell_password', $user)
                <span class="tracking-widest text-[#5F6B7A]">{{ $user['password'] }}</span>
            @endscope

            @scope('cell_role', $user)
                <span class="font-medium text-[#1A2B42]">{{ $user['role'] }}</span>
            @endscope

            @scope('cell_status', $user)
                @if ($user['status'] === 'Activo')
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold text-white bg-[#2ECC71]">
                        Activo
                    </span>
                @else
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold text-white bg-[#757E8D]">
                        Inactivo
                    </span>
                @endif
            @endscope

            @scope('actions', $user)
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