<?php

use Livewire\Component;

new class extends Component
{
    public array $equipo = [
        [
            'nombre' => 'Integrante 1',
            'rol' => 'Full Stack Developer',
            'enfoque' => 'Interfaz y lógica',
            'descripcion' => 'Desarrolla la experiencia visual, la lógica principal y la integración de módulos dentro de GNET System.',
            'iniciales' => 'I1',
            'foto' => 'BananCat4.jpg',
            'github' => 'https://github.com/centeno2',
            'color' => 'from-[#EAF2FB] via-white to-[#D7EAFB]',
        ],
        [
            'nombre' => 'Integrante 2',
            'rol' => 'Full Stack Developer',
            'enfoque' => 'Módulos y navegación',
            'descripcion' => 'Construye pantallas, rutas, navegación y soporte funcional para que el sistema fluya de forma clara.',
            'iniciales' => 'I2',
            'foto' => null,
            'github' => 'https://github.com/integrante2',
            'color' => 'from-[#F8FBFF] via-[#EAF2FB] to-white',
        ],
        [
            'nombre' => 'Integrante 3',
            'rol' => 'Full Stack Developer',
            'enfoque' => 'Frontend y backend',
            'descripcion' => 'Trabaja componentes visuales, validaciones, backend y consistencia general entre módulos.',
            'iniciales' => 'I3',
            'foto' => null,
            'github' => 'https://github.com/integrante3',
            'color' => 'from-white via-[#EAF2FB] to-[#F0F3F7]',
        ],
        [
            'nombre' => 'Integrante 4',
            'rol' => 'Full Stack Developer',
            'enfoque' => 'Integración',
            'descripcion' => 'Integra datos, componentes, estructura funcional y organización interna del proyecto.',
            'iniciales' => 'I4',
            'foto' => null,
            'github' => 'https://github.com/integrante4',
            'color' => 'from-[#EAF2FB] via-white to-[#F8FBFF]',
        ],
        [
            'nombre' => 'Integrante 5',
            'rol' => 'Full Stack Developer',
            'enfoque' => 'Pruebas y mejoras',
            'descripcion' => 'Revisa detalles visuales, pruebas de uso, comportamiento de pantallas y mejora continua.',
            'iniciales' => 'I5',
            'foto' => null,
            'github' => 'https://github.com/integrante5',
            'color' => 'from-[#F7F9FC] via-white to-[#EAF2FB]',
        ],
    ];

    // Todos estos archivos se leen desde public/img/
    public array $tecnologias = [
        [
            'nombre' => 'VS Code',
            'archivo' => 'vscode.png',
            'posicion' => 'left-[5%] top-[13%]',
            'tamano' => 'h-20 w-20',
            'animacion' => 'tech-path-a',
            'delay' => '0s',
            'fallback' => 'VS',
        ],
        [
            'nombre' => 'Laravel',
            'archivo' => 'laravel.png',
            'posicion' => 'right-[8%] top-[14%]',
            'tamano' => 'h-24 w-24',
            'animacion' => 'tech-path-b',
            'delay' => '-4s',
            'fallback' => 'LV',
        ],
        [
            'nombre' => 'Livewire',
            'archivo' => 'livewire.png',
            'posicion' => 'left-[9%] bottom-[15%]',
            'tamano' => 'h-24 w-24',
            'animacion' => 'tech-path-c',
            'delay' => '-8s',
            'fallback' => 'LW',
        ],
        [
            'nombre' => 'GNET',
            'archivo' => 'gnetlogo.png',
            'posicion' => 'right-[15%] bottom-[16%]',
            'tamano' => 'h-20 w-20',
            'animacion' => 'tech-path-a',
            'delay' => '-12s',
            'fallback' => 'GN',
        ],
        [
            'nombre' => 'Tailwind',
            'archivo' => 'tailwind.png',
            'posicion' => 'left-[42%] top-[8%]',
            'tamano' => 'h-16 w-16',
            'animacion' => 'tech-path-b',
            'delay' => '-16s',
            'fallback' => 'TW',
        ],
        [
            'nombre' => 'MySQL',
            'archivo' => 'mysql.png',
            'posicion' => 'right-[40%] bottom-[9%]',
            'tamano' => 'h-16 w-16',
            'animacion' => 'tech-path-c',
            'delay' => '-20s',
            'fallback' => 'DB',
        ],
        [
            'nombre' => 'PHP',
            'archivo' => 'php.png',
            'posicion' => 'right-[27%] top-[20%]',
            'tamano' => 'h-16 w-16',
            'animacion' => 'tech-path-a',
            'delay' => '-24s',
            'fallback' => 'PHP',
        ],
        [
            'nombre' => 'GitHub',
            'archivo' => 'github.png',
            'posicion' => 'left-[29%] bottom-[8%]',
            'tamano' => 'h-16 w-16',
            'animacion' => 'tech-path-b',
            'delay' => '-28s',
            'fallback' => 'GH',
        ],
        [
            'nombre' => 'Linux',
            'archivo' => 'Linux.png',
            'posicion' => 'left-[58%] bottom-[14%]',
            'tamano' => 'h-16 w-16',
            'animacion' => 'tech-path-c',
            'delay' => '-32s',
            'fallback' => 'LX',
        ],
    ];
};
?>

<div class="relative h-[calc(100vh-3rem)] min-h-0 overflow-hidden bg-[#F0F3F7] px-3 py-3 md:px-5">
    <div x-data="{
            active: 0,
            total: {{ count($equipo) }},
            paused: false,
            timer: null,

            init() {
                this.timer = setInterval(() => {
                    if (!this.paused) {
                        this.next();
                    }
                }, 2300);
            },

            next() {
                this.active = (this.active + 1) % this.total;
            },

            prev() {
                this.active = (this.active - 1 + this.total) % this.total;
            },

            go(index) {
                this.active = index;
            },

            offset(index) {
                let value = (index - this.active + this.total) % this.total;

                if (value > this.total / 2) {
                    value -= this.total;
                }

                return value;
            },

            cardStyle(index) {
                const offset = this.offset(index);
                const abs = Math.abs(offset);

                if (abs === 0) {
                    return `
                        transform: translate(-50%, -50%) translateX(0%) translateZ(0px) rotateY(0deg) scale(1);
                        opacity: 1;
                        filter: blur(0px) saturate(1.08);
                        z-index: 70;
                        pointer-events: auto;
                    `;
                }

                if (abs === 1) {
                    return `
                        transform: translate(-50%, -50%) translateX(${offset * 42}%) translateZ(-170px) rotateY(${offset * -30}deg) scale(.76);
                        opacity: .48;
                        filter: blur(2.2px) saturate(.84);
                        z-index: 38;
                        pointer-events: auto;
                    `;
                }

                if (abs === 2) {
                    return `
                        transform: translate(-50%, -50%) translateX(${offset * 60}%) translateZ(-340px) rotateY(${offset * -42}deg) scale(.55);
                        opacity: .15;
                        filter: blur(6px) saturate(.72);
                        z-index: 18;
                        pointer-events: none;
                    `;
                }

                return `
                    transform: translate(-50%, -50%) translateX(${offset * 75}%) translateZ(-460px) rotateY(${offset * -48}deg) scale(.42);
                    opacity: 0;
                    filter: blur(10px);
                    z-index: 0;
                    pointer-events: none;
                `;
            }
        }" x-init="init()" class="relative h-full w-full">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-40 top-8 h-96 w-96 rounded-full bg-[#2E8BC0]/22 blur-3xl"></div>
            <div class="absolute -right-40 bottom-6 h-96 w-96 rounded-full bg-[#0B6FE4]/18 blur-3xl"></div>
            <div class="absolute left-[32%] top-[20%] h-80 w-80 rounded-full bg-white/75 blur-3xl"></div>
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_18%_35%,rgba(46,139,192,.16),transparent_28%),radial-gradient(circle_at_82%_70%,rgba(11,111,228,.14),transparent_28%)]">
            </div>
        </div>


        @foreach($tecnologias as $tech)
        @php($techPath = public_path('img/' . $tech['archivo']))

        {{-- MODIFICADO: íconos flotantes limpios, sin cuadro ni nombre visible. --}}
        <div class="pointer-events-none absolute {{ $tech['posicion'] }} {{ $tech['tamano'] }} {{ $tech['animacion'] }} z-10 hidden items-center justify-center xl:flex"
            style="animation-delay: {{ $tech['delay'] }};" title="{{ $tech['nombre'] }}">
            <div class="absolute inset-0 rounded-full bg-[#2E8BC0]/10 blur-2xl"></div>

            @if(file_exists($techPath))
            <img src="{{ asset('img/' . $tech['archivo']) }}" alt="{{ $tech['nombre'] }}"
                class="relative h-12 w-12 object-contain opacity-90 drop-shadow-[0_16px_32px_rgba(26,43,66,.20)]" />
            @else
            <span class="relative text-lg font-black text-[#0B6FE4] drop-shadow-[0_16px_32px_rgba(26,43,66,.20)]">
                {{ $tech['fallback'] }}
            </span>
            @endif
        </div>
        @endforeach

        <header
            class="pointer-events-none absolute left-4 right-4 top-3 z-50 flex items-center justify-between gap-3 md:left-7 md:right-7">
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="rounded-full bg-white/75 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-[#0B6FE4] ring-1 ring-white/70 backdrop-blur-xl">
                    Acerca de GNET
                </span>

                <span x-show="paused" x-transition.opacity
                    class="rounded-full bg-white/75 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-[#5F6B7A] ring-1 ring-white/70 backdrop-blur-xl">
                    Pausado
                </span>
            </div>

            <div class="pointer-events-auto hidden items-center gap-2 sm:flex">
                <button type="button" @click="prev()"
                    class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/70 bg-white/70 text-[#1A2B42] shadow-sm backdrop-blur-xl transition hover:border-[#2E8BC0] hover:bg-[#EAF2FB] hover:text-[#0B6FE4]"
                    title="Anterior">
                    <x-icon name="o-chevron-left" class="h-5 w-5" />
                </button>

                <button type="button" @click="next()"
                    class="flex h-11 w-11 items-center justify-center rounded-2xl border border-[#2E8BC0]/20 bg-[#2E8BC0] text-white shadow-sm transition hover:bg-[#0B6FE4]"
                    title="Siguiente">
                    <x-icon name="o-chevron-right" class="h-5 w-5" />
                </button>
            </div>
        </header>

        <main class="absolute inset-x-0 bottom-10 top-12 z-20 overflow-visible [perspective:1900px]">
            <div class="absolute inset-0 [transform-style:preserve-3d]">
                @foreach($equipo as $index => $persona)
                <article @mouseenter="if (active === {{ $index }}) paused = true" @mouseleave="paused = false"
                    class="absolute left-1/2 top-[48%] h-[74%] w-[66%] min-w-[380px] max-w-[900px] origin-center overflow-hidden rounded-[2.4rem] border border-white/85 bg-white/50 p-4 shadow-[0_28px_90px_rgba(26,43,66,.20)] ring-1 ring-[#D7E4F3]/70 backdrop-blur-2xl transition-all duration-700 ease-out [backface-visibility:hidden] [transform-style:preserve-3d]"
                    :style="cardStyle({{ $index }})" @click="go({{ $index }})">
                    <div class="pointer-events-none absolute inset-0 rounded-[2.4rem]">
                        <div class="absolute -right-24 -top-24 h-64 w-64 rounded-full bg-[#2E8BC0]/18 blur-3xl"></div>
                        <div class="absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-[#0B6FE4]/10 blur-3xl"></div>
                        <div
                            class="absolute inset-0 rounded-[2.4rem] bg-gradient-to-br from-white/62 via-white/18 to-[#EAF2FB]/22">
                        </div>
                    </div>

                    <div class="relative grid h-full min-h-0 grid-cols-1 gap-4 lg:grid-cols-[.76fr_1.24fr]">
                        <div
                            class="relative flex items-center justify-center overflow-hidden rounded-[2rem] border border-white/85 bg-gradient-to-br {{ $persona['color'] }} p-4 shadow-inner">
                            <div
                                class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(46,139,192,.28),_transparent_36%),radial-gradient(circle_at_bottom_left,_rgba(11,111,228,.16),_transparent_42%)]">
                            </div>

                            <div
                                class="relative flex h-44 w-44 items-center justify-center overflow-hidden rounded-[2.1rem] border border-white/95 bg-white/65 shadow-xl backdrop-blur-xl xl:h-56 xl:w-56">
                                @php($fotoPath = $persona['foto'] ? public_path('img/' . $persona['foto']) : null)

                                @if($persona['foto'] && file_exists($fotoPath))
                                <img src="{{ asset('img/' . $persona['foto']) }}" alt="{{ $persona['nombre'] }}"
                                    class="h-full w-full object-cover" />
                                @else
                                <span class="text-6xl font-black text-[#0B6FE4] xl:text-8xl">
                                    {{ $persona['iniciales'] }}
                                </span>
                                @endif
                            </div>

                            <div
                                class="absolute bottom-4 left-4 right-4 rounded-2xl border border-white/80 bg-white/70 px-4 py-3 shadow-sm backdrop-blur-xl">
                                <p class="text-[11px] font-black uppercase tracking-wide text-[#0B6FE4]">
                                    {{ $persona['enfoque'] }}
                                </p>
                                <p class="mt-1 truncate text-sm font-black text-[#1A2B42]">
                                    {{ $persona['rol'] }}
                                </p>
                            </div>
                        </div>

                        <div class="flex min-h-0 flex-col justify-center overflow-hidden pr-1">
                            <span
                                class="w-fit rounded-full bg-[#EAF2FB]/95 px-3 py-1 text-xs font-black text-[#0B6FE4]">
                                Integrante {{ $index + 1 }}
                            </span>

                            <h2 class="mt-3 text-4xl font-black leading-tight text-[#1A2B42] xl:text-5xl">
                                {{ $persona['nombre'] }}
                            </h2>

                            <p class="mt-1 text-sm font-black uppercase tracking-wide text-[#0B6FE4]">
                                {{ $persona['rol'] }}
                            </p>

                            <p class="mt-4 max-w-2xl text-sm leading-7 text-[#5F6B7A] xl:text-base xl:leading-8">
                                {{ $persona['descripcion'] }}
                            </p>

                            <div class="mt-5">
                                <a href="{{ $persona['github'] }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex h-10 items-center justify-center gap-2 rounded-2xl bg-[#1A2B42] px-5 text-sm font-black text-white shadow-sm transition hover:bg-[#0B6FE4] xl:h-11">
                                    <x-icon name="o-link" class="h-5 w-5" />
                                    Ver GitHub
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
        </main>

        <footer class="absolute bottom-4 left-0 right-0 z-50 flex items-center justify-center gap-2">
            @foreach($equipo as $index => $persona)
            <button type="button" @click="go({{ $index }})" class="h-2.5 rounded-full transition-all"
                :class="active === {{ $index }} ? 'w-12 bg-[#0B6FE4]' : 'w-2.5 bg-[#C8DAEE] hover:bg-[#2E8BC0]'"
                title="{{ $persona['nombre'] }}"></button>
            @endforeach
        </footer>

        <div class="pointer-events-auto absolute bottom-3 right-4 z-50 flex items-center gap-2 sm:hidden">
            <button type="button" @click="prev()"
                class="flex h-10 w-10 items-center justify-center rounded-2xl border border-white/70 bg-white/70 text-[#1A2B42] shadow-sm backdrop-blur-xl">
                <x-icon name="o-chevron-left" class="h-5 w-5" />
            </button>

            <button type="button" @click="next()"
                class="flex h-10 w-10 items-center justify-center rounded-2xl border border-[#2E8BC0]/20 bg-[#2E8BC0] text-white shadow-sm">
                <x-icon name="o-chevron-right" class="h-5 w-5" />
            </button>
        </div>

        <style>
            .tech-path-a {
                animation: techPathA 18s ease-in-out infinite;
            }

            .tech-path-b {
                animation: techPathB 22s ease-in-out infinite;
            }

            .tech-path-c {
                animation: techPathC 20s ease-in-out infinite;
            }

            @keyframes techPathA {

                0%,
                100% {
                    transform: translate3d(0, 0, 0) rotate(0deg);
                }

                25% {
                    transform: translate3d(110px, 62px, 0) rotate(8deg);
                }

                50% {
                    transform: translate3d(26px, 142px, 0) rotate(-5deg);
                }

                75% {
                    transform: translate3d(-96px, 58px, 0) rotate(6deg);
                }
            }

            @keyframes techPathB {

                0%,
                100% {
                    transform: translate3d(0, 0, 0) rotate(0deg);
                }

                25% {
                    transform: translate3d(-120px, 74px, 0) rotate(-7deg);
                }

                50% {
                    transform: translate3d(-38px, 150px, 0) rotate(6deg);
                }

                75% {
                    transform: translate3d(112px, 48px, 0) rotate(-4deg);
                }
            }

            @keyframes techPathC {

                0%,
                100% {
                    transform: translate3d(0, 0, 0) rotate(0deg);
                }

                25% {
                    transform: translate3d(92px, -64px, 0) rotate(6deg);
                }

                50% {
                    transform: translate3d(-48px, -134px, 0) rotate(-8deg);
                }

                75% {
                    transform: translate3d(-118px, -44px, 0) rotate(4deg);
                }
            }
        </style>
    </div>
</div>
