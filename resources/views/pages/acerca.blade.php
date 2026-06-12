<?php

use Livewire\Component;

new class extends Component
{
    public array $equipo = [
        [
            'nombre' => 'Elking Gallegos',
            'rol' => 'Project Manager',
            'stack' => 'Full Stack Developer',
            'descripcion' => 'Gestión, diseño, programación y base de datos.',
            'iniciales' => 'EG',
            'foto' => 'Elking.jpg',
            'github' => 'https://github.com/ryuunxo',
            'color' => 'from-[#EAF2FB] via-white to-[#D7EAFB]',
            'aportes' => ['Gestión', 'Diseño', 'Programación', 'Base de datos'],
        ],
        [
            'nombre' => 'Guillermo Matamoros',
            'rol' => 'Developer',
            'stack' => 'Programmer',
            'descripcion' => 'Diseño de interfaz y programación.',
            'iniciales' => 'GM',
            'foto' => 'Guillermo.jpg',
            'github' => 'https://github.com/Ghost-1945',
            'color' => 'from-[#F8FBFF] via-[#EAF2FB] to-white',
            'aportes' => ['Diseño', 'Programación'],
        ],
        [
            'nombre' => 'Engel Chavarría',
            'rol' => 'Developer',
            'stack' => 'Full Stack Developer',
            'descripcion' => 'Diseño, programación y base de datos.',
            'iniciales' => 'EC',
            'foto' => 'Engel.jpg',
            'github' => 'https://github.com/Engel-s',
            'color' => 'from-white via-[#EAF2FB] to-[#F0F3F7]',
            'aportes' => ['Diseño', 'Programación', 'Base de datos'],
        ],
        [
            'nombre' => 'Heyner Centeno',
            'rol' => 'Developer',
            'stack' => 'Full Stack Developer',
            'descripcion' => 'Diseño, programación y base de datos.',
            'iniciales' => 'HC',
            'foto' => 'Centeno.jpg',
            'github' => 'https://github.com/centeno2',
            'color' => 'from-[#EAF2FB] via-white to-[#F8FBFF]',
            'aportes' => ['Diseño', 'Programación', 'Base de datos'],
        ],
        [
            'nombre' => 'Martha Barrera',
            'rol' => 'Developer',
            'stack' => 'Programmer',
            'descripcion' => 'Programación y apoyo funcional.',
            'iniciales' => 'MB',
            'foto' => 'Martha.png',
            'github' => 'https://github.com/Martha111206',
            'color' => 'from-[#F7F9FC] via-white to-[#EAF2FB]',
            'aportes' => ['Programación'],
        ],
    ];

    public array $tecnologias = [
        [
            'nombre' => 'VS Code',
            'archivo' => 'vscode.png',
            'posicion' => 'left-[5%] top-[13%]',
            'tamano' => 'h-20 w-20',
            'ruta' => 'a',
            'duracion' => 18,
            'delay' => 0,
            'fallback' => 'VS',
        ],
        [
            'nombre' => 'Laravel',
            'archivo' => 'laravel.png',
            'posicion' => 'right-[8%] top-[14%]',
            'tamano' => 'h-24 w-24',
            'ruta' => 'b',
            'duracion' => 22,
            'delay' => -4,
            'fallback' => 'LV',
        ],
        [
            'nombre' => 'Livewire',
            'archivo' => 'livewire.png',
            'posicion' => 'left-[9%] bottom-[15%]',
            'tamano' => 'h-24 w-24',
            'ruta' => 'c',
            'duracion' => 20,
            'delay' => -8,
            'fallback' => 'LW',
        ],
        [
            'nombre' => 'GNET',
            'archivo' => 'gnetlogo.png',
            'posicion' => 'right-[15%] bottom-[16%]',
            'tamano' => 'h-20 w-20',
            'ruta' => 'a',
            'duracion' => 18,
            'delay' => -12,
            'fallback' => 'GN',
        ],
        [
            'nombre' => 'Tailwind',
            'archivo' => 'tailwind.png',
            'posicion' => 'left-[42%] top-[8%]',
            'tamano' => 'h-16 w-16',
            'ruta' => 'b',
            'duracion' => 22,
            'delay' => -16,
            'fallback' => 'TW',
        ],
        [
            'nombre' => 'MySQL',
            'archivo' => 'mysql.png',
            'posicion' => 'right-[40%] bottom-[9%]',
            'tamano' => 'h-16 w-16',
            'ruta' => 'c',
            'duracion' => 20,
            'delay' => -20,
            'fallback' => 'DB',
        ],
        [
            'nombre' => 'PHP',
            'archivo' => 'php.png',
            'posicion' => 'right-[27%] top-[20%]',
            'tamano' => 'h-16 w-16',
            'ruta' => 'a',
            'duracion' => 18,
            'delay' => -24,
            'fallback' => 'PHP',
        ],
        [
            'nombre' => 'GitHub',
            'archivo' => 'github.png',
            'posicion' => 'left-[29%] bottom-[8%]',
            'tamano' => 'h-16 w-16',
            'ruta' => 'b',
            'duracion' => 22,
            'delay' => -28,
            'fallback' => 'GH',
        ],
        [
            'nombre' => 'Linux',
            'archivo' => 'Linux.png',
            'posicion' => 'left-[58%] bottom-[14%]',
            'tamano' => 'h-16 w-16',
            'ruta' => 'c',
            'duracion' => 20,
            'delay' => -32,
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
            frame: null,
            lastFrame: null,
            techTime: 0,

            init() {
                this.timer = setInterval(() => {
                    if (!this.paused) {
                        this.next();
                    }
                }, 6000);

                const loop = (time) => {
                    if (this.lastFrame === null) {
                        this.lastFrame = time;
                    }

                    this.techTime += (time - this.lastFrame) / 1000;
                    this.lastFrame = time;
                    this.frame = requestAnimationFrame(loop);
                };

                this.frame = requestAnimationFrame(loop);
            },

            destroy() {
                clearInterval(this.timer);

                if (this.frame) {
                    cancelAnimationFrame(this.frame);
                }
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

            cardClass(index) {
                const offset = this.offset(index);
                const abs = Math.abs(offset);

                if (abs === 0) {
                    return '[transform:translate(-50%,-50%)_translateX(0%)_translateZ(0px)_rotateY(0deg)_scale(1)] opacity-100 blur-0 saturate-[1.1] z-[70] pointer-events-auto';
                }

                if (abs === 1 && offset < 0) {
                    return '[transform:translate(-50%,-50%)_translateX(-42%)_translateZ(-170px)_rotateY(30deg)_scale(0.76)] opacity-60 blur-[1.6px] saturate-[.98] z-[38] pointer-events-auto';
                }

                if (abs === 1 && offset > 0) {
                    return '[transform:translate(-50%,-50%)_translateX(42%)_translateZ(-170px)_rotateY(-30deg)_scale(0.76)] opacity-60 blur-[1.6px] saturate-[.98] z-[38] pointer-events-auto';
                }

                if (abs === 2 && offset < 0) {
                    return '[transform:translate(-50%,-50%)_translateX(-60%)_translateZ(-340px)_rotateY(42deg)_scale(0.55)] opacity-20 blur-[5px] saturate-[.8] z-[18] pointer-events-none';
                }

                if (abs === 2 && offset > 0) {
                    return '[transform:translate(-50%,-50%)_translateX(60%)_translateZ(-340px)_rotateY(-42deg)_scale(0.55)] opacity-20 blur-[5px] saturate-[.8] z-[18] pointer-events-none';
                }

                return '[transform:translate(-50%,-50%)_translateX(75%)_translateZ(-460px)_rotateY(-48deg)_scale(0.42)] opacity-0 blur-[10px] z-0 pointer-events-none';
            },

            contentClass(index) {
                const abs = Math.abs(this.offset(index));

                if (abs === 0) {
                    return 'opacity-100';
                }

                if (abs === 1) {
                    return 'opacity-80';
                }

                return 'opacity-45';
            },

            pathPoints(path) {
                if (path === 'a') {
                    return [
                        { p: 0, x: 0, y: 0, r: 0 },
                        { p: 25, x: 110, y: 62, r: 8 },
                        { p: 50, x: 26, y: 142, r: -5 },
                        { p: 75, x: -96, y: 58, r: 6 },
                        { p: 100, x: 0, y: 0, r: 0 }
                    ];
                }

                if (path === 'b') {
                    return [
                        { p: 0, x: 0, y: 0, r: 0 },
                        { p: 25, x: -120, y: 74, r: -7 },
                        { p: 50, x: -38, y: 150, r: 6 },
                        { p: 75, x: 112, y: 48, r: -4 },
                        { p: 100, x: 0, y: 0, r: 0 }
                    ];
                }

                return [
                    { p: 0, x: 0, y: 0, r: 0 },
                    { p: 25, x: 92, y: -64, r: 6 },
                    { p: 50, x: -48, y: -134, r: -8 },
                    { p: 75, x: -118, y: -44, r: 4 },
                    { p: 100, x: 0, y: 0, r: 0 }
                ];
            },

            smooth(value) {
                return value * value * (3 - (2 * value));
            },

            techStyle(path, duration, delay) {
                const points = this.pathPoints(path);
                let seconds = (this.techTime + delay) % duration;

                if (seconds < 0) {
                    seconds += duration;
                }

                const percent = (seconds / duration) * 100;
                let start = points[0];
                let end = points[points.length - 1];

                for (let i = 0; i < points.length - 1; i++) {
                    if (percent >= points[i].p && percent <= points[i + 1].p) {
                        start = points[i];
                        end = points[i + 1];
                        break;
                    }
                }

                const range = end.p - start.p;
                const local = range === 0 ? 0 : (percent - start.p) / range;
                const eased = this.smooth(local);

                const x = start.x + ((end.x - start.x) * eased);
                const y = start.y + ((end.y - start.y) * eased);
                const r = start.r + ((end.r - start.r) * eased);

                return `transform: translate3d(${x}px, ${y}px, 0) rotate(${r}deg);`;
            }
        }" x-init="init()" class="relative h-full w-full">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-40 top-8 h-96 w-96 rounded-full bg-[#2E8BC0]/22 blur-3xl"></div>
            <div class="absolute -right-40 bottom-6 h-96 w-96 rounded-full bg-[#0B6FE4]/18 blur-3xl"></div>
            <div class="absolute left-[32%] top-[20%] h-80 w-80 rounded-full bg-white/75 blur-3xl"></div>
            <div class="absolute left-[46%] top-[45%] h-72 w-72 rounded-full bg-[#D7E4F3]/60 blur-3xl"></div>
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_18%_35%,rgba(46,139,192,.16),transparent_28%),radial-gradient(circle_at_82%_70%,rgba(11,111,228,.14),transparent_28%),linear-gradient(135deg,rgba(255,255,255,.40),transparent_42%,rgba(255,255,255,.25))]">
            </div>
        </div>

        @foreach($tecnologias as $tech)
        @php($techPath = public_path('img/' . $tech['archivo']))

        <div class="pointer-events-none absolute {{ $tech['posicion'] }} {{ $tech['tamano'] }} z-10 hidden items-center justify-center xl:flex will-change-transform"
            :style="techStyle('{{ $tech['ruta'] }}', {{ $tech['duracion'] }}, {{ $tech['delay'] }})"
            title="{{ $tech['nombre'] }}">
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
                    class="rounded-full border border-white/80 bg-white/65 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-[#0B6FE4] shadow-sm ring-1 ring-white/70 backdrop-blur-2xl">
                    Acerca de GNET
                </span>

                <span x-show="paused" x-transition.opacity
                    class="rounded-full border border-white/80 bg-white/65 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-[#5F6B7A] shadow-sm ring-1 ring-white/70 backdrop-blur-2xl">
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

        <main class="absolute inset-x-0 bottom-10 top-12 z-20 overflow-visible perspective-[1900px]">
            <div class="absolute inset-0 transform-3d">
                @foreach($equipo as $index => $persona)
                <article @mouseenter="if (active === {{ $index }}) paused = true" @mouseleave="paused = false"
                    class="absolute left-1/2 top-[48%] h-[76%] w-[68%] min-w-97.5 max-w-232.5 origin-center cursor-pointer overflow-hidden rounded-[2.4rem] border border-white/90 bg-white/30 p-4 shadow-[0_34px_110px_rgba(26,43,66,.22)] ring-1 ring-white/70 backdrop-blur-[38px] transition-all duration-1200 ease-out backface-hidden transform-3d"
                    :class="cardClass({{ $index }})" @click="go({{ $index }})">
                    <div class="pointer-events-none absolute inset-0 rounded-[2.4rem]">
                        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#2E8BC0]/20 blur-3xl"></div>
                        <div class="absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-[#0B6FE4]/12 blur-3xl"></div>
                        <div class="absolute left-8 right-8 top-5 h-20 rounded-full bg-white/55 blur-2xl"></div>
                        <div
                            class="absolute inset-0 rounded-[2.4rem] bg-linear-to-br from-white/75 via-white/22 to-[#EAF2FB]/30">
                        </div>
                        <div
                            class="absolute inset-px rounded-[2.35rem] border border-white/60 shadow-[inset_0_1px_0_rgba(255,255,255,.95),inset_0_-32px_80px_rgba(255,255,255,.24)]">
                        </div>
                        <div class="absolute left-6 top-6 h-24 w-1/2 rounded-full bg-white/45 blur-2xl"></div>
                    </div>

                    <div class="relative grid h-full min-h-0 grid-cols-1 gap-4 transition-opacity duration-1200 lg:grid-cols-[.74fr_1.26fr]"
                        :class="contentClass({{ $index }})">
                        <div
                            class="relative min-h-90 overflow-hidden rounded-4xl border border-white/85 bg-white/35 shadow-[0_18px_50px_rgba(26,43,66,.12),inset_0_1px_0_rgba(255,255,255,.90)] ring-1 ring-white/60 backdrop-blur-2xl lg:min-h-0">
                            <div class="absolute inset-0 bg-linear-to-br {{ $persona['color'] }}"></div>
                            <div
                                class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(46,139,192,.24),transparent_36%),radial-gradient(circle_at_bottom_left,rgba(11,111,228,.16),transparent_42%),linear-gradient(135deg,rgba(255,255,255,.55),transparent_42%,rgba(255,255,255,.24))]">
                            </div>

                            @php($fotoPath = $persona['foto'] ? public_path('img/' . $persona['foto']) : null)

                            @if($persona['foto'] && file_exists($fotoPath))
                            <img src="{{ asset('img/' . $persona['foto']) }}" alt="{{ $persona['nombre'] }}"
                                class="absolute inset-0 h-full w-full object-cover object-center" />
                            <div
                                class="absolute inset-0 bg-linear-to-t from-[#1A2B42]/38 via-transparent to-white/10">
                            </div>
                            @else
                            <div
                                class="absolute inset-0 flex items-center justify-center bg-linear-to-br from-white/75 via-[#EAF2FB]/85 to-[#D7E4F3]/80 backdrop-blur-2xl">
                                <span
                                    class="text-6xl font-black text-[#0B6FE4] drop-shadow-[0_18px_35px_rgba(11,111,228,.18)] xl:text-8xl">
                                    {{ $persona['iniciales'] }}
                                </span>
                            </div>
                            @endif

                            <div
                                class="absolute inset-x-4 bottom-4 rounded-2xl border border-white/85 bg-white/72 px-4 py-3 shadow-[0_12px_35px_rgba(26,43,66,.12)] ring-1 ring-white/70 backdrop-blur-2xl">
                                <p class="text-[11px] font-black uppercase tracking-wide text-[#0B6FE4]">
                                    {{ $persona['rol'] }}
                                </p>
                                <p class="mt-1 truncate text-sm font-black text-[#1A2B42]">
                                    {{ $persona['stack'] }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="relative flex min-h-0 flex-col justify-center overflow-hidden rounded-4xl border border-white/70 bg-white/25 px-5 py-6 shadow-[inset_0_1px_0_rgba(255,255,255,.75)] backdrop-blur-2xl">
                            <div
                                class="pointer-events-none absolute inset-0 rounded-4xl bg-linear-to-br from-white/65 via-white/10 to-[#EAF2FB]/20">
                            </div>
                            <div
                                class="pointer-events-none absolute right-0 top-0 h-40 w-40 rounded-full bg-[#2E8BC0]/12 blur-3xl">
                            </div>

                            <div class="relative">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-full border border-white/75 bg-white/65 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-[#0B6FE4] shadow-sm backdrop-blur-2xl">
                                        {{ $persona['rol'] }}
                                    </span>

                                    <span
                                        class="rounded-full border border-white/75 bg-[#EAF2FB]/90 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-[#1A2B42] shadow-sm backdrop-blur-2xl">
                                        {{ $persona['stack'] }}
                                    </span>
                                </div>

                                <h2 class="mt-3 text-4xl font-black leading-tight text-[#1A2B42] xl:text-5xl">
                                    {{ $persona['nombre'] }}
                                </h2>

                                <p class="mt-3 text-sm font-semibold leading-7 text-[#5F6B7A] xl:text-base">
                                    {{ $persona['descripcion'] }}
                                </p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($persona['aportes'] as $aporte)
                                    <span
                                        class="rounded-full border border-white/75 bg-white/62 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-[#0B6FE4] shadow-sm backdrop-blur-xl">
                                        {{ $aporte }}
                                    </span>
                                    @endforeach
                                </div>

                                <div class="mt-5">
                                    <a href="{{ $persona['github'] }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex h-10 items-center justify-center gap-2 rounded-2xl bg-[#1A2B42] px-5 text-sm font-black text-white shadow-sm transition hover:bg-[#0B6FE4] xl:h-11">
                                        <x-icon name="o-link" class="h-5 w-5" />
                                        Ver GitHub
                                    </a>
                                </div>
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
    </div>
</div>
