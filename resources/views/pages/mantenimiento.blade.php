<?php

use Livewire\Component;

new class extends Component
{
    public function crearRespaldo(): void
    {
    }

    public function restaurarBaseDatos(): void
    {
    }
};
?>

@php
    $pageClass = 'min-h-screen w-full bg-[#F0F3F7] px-4 py-6 md:px-6 md:py-8';
    $cardClass = 'border border-[#D7E4F3] bg-white shadow-sm [&_.text-base-content\\/70]:text-[#000000] [&_.text-sm]:text-[#000000] [&_.text-base-content]:text-[#000000] [&_.card-title]:text-[#000000]';
    $primaryButtonClass = 'btn-sm h-[46px] border-0 bg-[#2E8BC0] text-white hover:bg-[#256f99]';
    $softInfoClass = 'rounded-2xl border border-[#D7E4F3] bg-[#F7FAFD] px-4 py-3';
@endphp

<div class="{{ $pageClass }}">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        <div class="flex flex-col items-center justify-center text-center">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#2E8BC0]/10">
                    <x-icon name="o-wrench-screwdriver" class="h-7 w-7 text-[#2E8BC0]" />
                </span>

                <div>
                    <h1 class="text-left text-2xl font-bold text-[#000000] md:text-3xl">
                        Mantenimiento
                    </h1>
                    <p class="text-left text-sm text-[#000000] md:text-base">
                        Gestión de la base de datos y copias de seguridad
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-card class="{{ $cardClass }} rounded-3xl">
                <div class="flex h-full flex-col items-center text-center">
                    <div class="mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-[#2E8BC0]/10">
                        <x-icon name="o-circle-stack" class="h-10 w-10 text-[#2E8BC0]" />
                    </div>

                    <h2 class="text-xl font-bold text-[#000000]">
                        Crear copia de seguridad
                    </h2>

                    <p class="mt-3 max-w-md text-sm leading-6 text-[#000000]">
                        Realiza un respaldo completo de la base de datos actual para conservar la información del sistema antes de cualquier cambio importante.
                    </p>

                    <div class="mt-5 grid w-full grid-cols-1 gap-3">
                        <div class="{{ $softInfoClass }}">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#000000]">
                                Acción recomendada
                            </p>
                            <p class="mt-1 text-sm text-[#000000]">
                                Crear respaldo antes de restaurar o actualizar datos.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 w-full">
                        <x-button
                            label="Crear"
                            wire:click="crearRespaldo"
                            spinner="crearRespaldo"
                            icon="o-plus"
                            class="w-full {{ $primaryButtonClass }}"
                        />
                    </div>
                </div>
            </x-card>

            <x-card class="{{ $cardClass }} rounded-3xl">
                <div class="flex h-full flex-col items-center text-center">
                    <div class="mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-[#2E8BC0]/10">
                        <x-icon name="o-arrow-path-rounded-square" class="h-10 w-10 text-[#2E8BC0]" />
                    </div>

                    <h2 class="text-xl font-bold text-[#000000]">
                        Modificar la base de datos
                    </h2>

                    <p class="mt-3 max-w-md text-sm leading-6 text-[#000000]">
                        Permite restaurar o actualizar la base de datos según sea necesario, manteniendo el control sobre los cambios administrativos del sistema.
                    </p>

                    <div class="mt-5 grid w-full grid-cols-1 gap-3">
                        <div class="{{ $softInfoClass }}">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#000000]">
                                Precaución
                            </p>
                            <p class="mt-1 text-sm text-[#000000]">
                                Verifica que exista una copia reciente antes de restaurar.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 w-full">
                        <x-button
                            label="Restaurar"
                            wire:click="restaurarBaseDatos"
                            spinner="restaurarBaseDatos"
                            icon="o-arrow-up-tray"
                            class="w-full {{ $primaryButtonClass }}"
                        />
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>