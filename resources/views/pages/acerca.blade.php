<?php

use Livewire\Component;


new class extends Component
{
    public array $equipo = [
        [
            'nombre' => 'Integrante 1',
            'cargo' => 'Full Stack Developer',
            'descripcion' => 'Participa en el desarrollo visual y funcional del sistema, integrando interfaz, lógica y estructura de datos.',
            'iniciales' => 'I1',
            'foto' => 'img/BananCat4.jpg',
        ],
        [
            'nombre' => 'Integrante 2',
            'cargo' => 'Full Stack Developer',
            'descripcion' => 'Apoya en módulos, diseño de pantallas, flujo de navegación y desarrollo general del sistema.',
            'iniciales' => 'I2',
            'foto' => 'img/equipo/integrante2.jpg',
        ],
        [
            'nombre' => 'Integrante 3',
            'cargo' => 'Full Stack Developer',
            'descripcion' => 'Colabora en el frontend y backend, manteniendo consistencia visual y estructura funcional.',
            'iniciales' => 'I3',
            'foto' => 'img/equipo/integrante3.jpg',
        ],
        [
            'nombre' => 'Integrante 4',
            'cargo' => 'Full Stack Developer',
            'descripcion' => 'Trabaja en la integración de componentes, manejo de datos y organización general del proyecto.',
            'iniciales' => 'I4',
            'foto' => 'img/equipo/integrante4.jpg',
        ],
        [
            'nombre' => 'Integrante 5',
            'cargo' => 'Full Stack Developer',
            'descripcion' => 'Apoya en el desarrollo completo del sistema, pruebas visuales y mejora continua de la aplicación.',
            'iniciales' => 'I5',
            'foto' => 'img/equipo/integrante5.jpg',
        ],
    ];
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-6 space-y-6">
    <x-card class="relative overflow-hidden rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="absolute -top-12 -right-12 h-40 w-40 rounded-full bg-[#EAF2FB] opacity-80 animate-pulse"></div>
        <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-[#D7EAFB] opacity-70 animate-pulse"></div>
        <div class="absolute top-10 right-40 h-4 w-4 rounded-full bg-[#BFD9F6] animate-pulse"></div>

        <div class="relative grid grid-cols-1 gap-8 lg:grid-cols-[1.2fr_.8fr] lg:items-center">
            <div class="space-y-4">
                <div
                    class="inline-flex items-center rounded-full bg-[#EAF2FB] px-4 py-2 text-sm font-semibold text-[#0E48A1]">
                    Acerca del equipo
                </div>

                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-[#1A2B42] md:text-4xl">
                        Equipo de desarrollo
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-[#5F6B7A]">
                        Presentación del grupo encargado del desarrollo del sistema GNET.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-center">
                <div class="relative">
                    <div class="absolute inset-0 rounded-full bg-[#EAF2FB] blur-3xl"></div>
                    <div
                        class="relative flex h-44 w-44 items-center justify-center rounded-4xl border border-[#D7E4F3] bg-linear-to-br from-[#F8FBFF] to-[#EAF2FB] shadow-md md:h-52 md:w-52">
                        <img src="{{ asset('img/gnetlogo.png') }}" alt="Logo GNET"
                            class="h-28 w-28 object-contain md:h-36 md:w-36" />
                    </div>
                </div>
            </div>
        </div>
    </x-card>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($equipo as $persona)
        <x-card
            class="group rounded-3xl border border-[#D7E4F3] bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
            <div class="flex items-start gap-4">
                <div class="relative shrink-0">
                    <div
                        class="absolute inset-0 rounded-2xl bg-[#EAF2FB] blur-md opacity-70 transition duration-300 group-hover:opacity-100">
                    </div>

                    <div class="relative h-20 w-20 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-[#EAF2FB]">
                        <img alt="{{ $persona['nombre'] }}"
                            class="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
                    </div>
                </div>

                <div class="min-w-0">
                    <h3 class="text-xl font-bold text-[#1A2B42]">
                        {{ $persona['nombre'] }}
                    </h3>

                    <p class="mt-1 text-sm font-semibold text-[#0E48A1]">
                        {{ $persona['cargo'] }}
                    </p>

                    <div
                        class="mt-3 inline-flex items-center rounded-full bg-[#F0F3F7] px-3 py-1 text-xs font-semibold text-[#5F6B7A]">
                        {{ $persona['iniciales'] }}
                    </div>
                </div>
            </div>

            <div class="mt-5 rounded-2xl bg-[#F0F3F7] p-4">
                <p class="text-sm leading-6 text-[#5F6B7A]">
                    {{ $persona['descripcion'] }}
                </p>
            </div>
        </x-card>
        @endforeach
    </div>
</div>
