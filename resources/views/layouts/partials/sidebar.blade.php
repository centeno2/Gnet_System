{{-- 
    Sidebar vertical principal del sistema.
    Este archivo será un componente Blade anónimo, por eso luego lo podremos llamar con:
    <x-partials.sidebar />
--}}
@php
    /*
    |--------------------------------------------------------------------------
    | Ítems del menú
    |--------------------------------------------------------------------------
    | Cada elemento tiene:
    | - label: el texto visible
    | - route: el nombre de la ruta Laravel
    |
    | Usamos nombres de rutas en vez de URLs hardcodeadas para que luego
    | puedas mover rutas sin reescribir el menú completo.
    */
    $items = [
        ['label' => 'Ventas',                    'route' => 'ventas.index'],
        ['label' => 'Crédito',                   'route' => 'creditos'],
        ['label' => 'Compras',                   'route' => 'compras.index'],
        ['label' => 'Productos',                 'route' => 'productos.index'],
        ['label' => 'Salidas de inventario',     'route' => 'salidas.index'],
        ['label' => 'Devoluciones',              'route' => 'devoluciones'],
        ['label' => 'Gestión de trabajadores',   'route' => 'trabajadores.index'],
        ['label' => 'Servicios',                 'route' => 'servicios.index'],
        ['label' => 'Arqueo de caja',            'route' => 'arqueo.index'],
        ['label' => 'Mantenimiento',             'route' => 'mantenimiento'],
        ['label' => 'Informes',                  'route' => 'informes.index'],
        ['label' => 'Acerca de',                 'route' => 'acerca.index'],
    ];
@endphp

<aside class="sticky top-0 flex h-screen w-80 shrink-0 flex-col overflow-hidden border-r border-[#D7E4F3] bg-gradient-to-b from-[#0E48A1] via-[#0B6FE4] to-[#1A2B42] text-white shadow-2xl">

    <nav class="flex-1 overflow-y-auto px-4 py-5">
        <ul class="space-y-2">
            @foreach ($items as $item)
                @php
                    /*
                    |--------------------------------------------------------------------------
                    | Estado activo del ítem
                    |--------------------------------------------------------------------------
                    | request()->routeIs(...) nos dice si la ruta actual coincide
                    | con la del botón.
                    |
                    */
                    $isActive = request()->routeIs($item['route']);
                @endphp

                <li>
                    <a
                        href="{{ \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#' }}"
                        @class([
                            // Clases base que siempre tendrá cada botón
                            'group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold tracking-[0.01em] transition-all duration-200',

                            // Estilo cuando el boton esta activo
                            'bg-white text-[#0E48A1] shadow-lg shadow-[#1A2B42]/20' => $isActive,

                            // Estilo cuando NO está activo
                            'text-[#F0F3F7] hover:bg-white/10 hover:text-white' => ! $isActive,
                        ])
                    >

                        <span
                            @class([
                                'h-2.5 w-2.5 rounded-full transition-all duration-200',

                                'bg-[#0B6FE4] shadow-[0_0_0_4px_rgba(11,111,228,0.15)]' => $isActive,
                                'bg-white/70 group-hover:bg-white' => ! $isActive,
                            ])
                        ></span>

                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

        {{-- 
            Boton Salir
            Si existe la ruta logout, mostramos un formulario POST.
        --}}
        @if (\Illuminate\Support\Facades\Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="flex w-full items-center justify-between rounded-2xl border border-[#E74C3C]/30 bg-[#E74C3C]/15 px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#E74C3C]/25"
                >
                    <span>Salir</span>
                    <span class="rounded-full bg-white/10 px-2 py-1 text-xs">↩</span>
                </button>
            </form>
        @else
            <button
                type="button"
                class="flex w-full items-center justify-between rounded-2xl border border-[#E74C3C]/30 bg-[#E74C3C]/15 px-4 py-3 text-sm font-semibold text-white/80"
            >
                <span>Salir</span>
                <span class="rounded-full bg-white/10 px-2 py-1 text-xs">↩</span>
            </button>
        @endif
    </div>
</aside>