
{{--
    Cabecera del sidebar.
--}}
<div class="flex h-24 items-center justify-center px-3 py-3 transition-[height] duration-500 ease-in-out lg:group-hover/sidebar:h-36">
    <img
        src="{{ asset('img/gnetlogo.png') }}"
        alt="Logo"
        class="max-h-full w-10 rounded-lg object-contain transition-all duration-500 ease-in-out lg:group-hover/sidebar:w-32"
    >
</div>
@php
    $cargo = (int) (auth()->user()?->trabajador?->cargo?->Id_Cargo ?? 0);

    /**
     * Valida si el cargo actual está permitido.
     */
    $puede = fn (...$cargos) => in_array($cargo, array_map('intval', $cargos), true);

    $puedeVerVentas = $puede(1, 2, 3, 5);
    $puedeVerCatalogo = $puede(1, 2, 3, 5);
    $puedeVerGestionTrabajadores = $puede(1, 5);
@endphp

<x-menu
    activate-by-route
    active-bg-color="!bg-[#2E8BC0] !text-white font-semibold shadow-sm"
    class="
        [&_li>*]:transition-colors
        [&_li>*]:duration-200

        [&_li>*:hover]:!bg-[#EAF2FB]
        [&_li>*:hover]:!text-[#1A2B42]

        [&_li>*:active]:!bg-[#EAF2FB]
        [&_li>*:active]:!text-[#1A2B42]

        [&_li>*:focus]:!bg-[#EAF2FB]
        [&_li>*:focus]:!text-[#1A2B42]

        [&_li>*:focus-visible]:!outline-none
        [&_li>*:focus-visible]:!ring-2
        [&_li>*:focus-visible]:!ring-[#2E8BC0]/25

        [&_summary]:transition-colors
        [&_summary]:duration-200

        [&_summary:hover]:!bg-[#EAF2FB]
        [&_summary:hover]:!text-[#1A2B42]

        [&_summary:active]:!bg-[#EAF2FB]
        [&_summary:active]:!text-[#1A2B42]

        [&_summary:focus]:!bg-[#EAF2FB]
        [&_summary:focus]:!text-[#1A2B42]

        [&_summary:focus-visible]:!outline-none
        [&_summary:focus-visible]:!ring-2
        [&_summary:focus-visible]:!ring-[#2E8BC0]/25

        [&_li>.active]:!bg-[#2E8BC0]
        [&_li>.active]:!text-white
        [&_li>.active:hover]:!bg-[#2E8BC0]
        [&_li>.active:active]:!bg-[#2E8BC0]
        [&_li>.active:focus]:!bg-[#2E8BC0]

        [&_summary.active]:!bg-[#2E8BC0]
        [&_summary.active]:!text-white
        [&_summary.active:hover]:!bg-[#2E8BC0]
        [&_summary.active:active]:!bg-[#2E8BC0]
        [&_summary.active:focus]:!bg-[#2E8BC0]
    "
>

    {{-- Menú principal: todos los cargos --}}
<x-menu-item
    title="Menú principal"
    icon="o-home"
    link="{{ route('main') }}"
/>

    {{-- Ventas: cargos 1  2 3  --}}
    @if ($puedeVerVentas)
        <x-menu-sub title="Ventas" icon="o-shopping-cart">
            <x-menu-item
                title="Facturación"
                icon="o-receipt-percent"
                link="{{ route('ventas.facturacion') }}"
            />

            <x-menu-item
                title="Servicio técnico"
                icon="o-computer-desktop"
                link="{{ route('ventas.servicio-tecnico') }}"
            />

            <x-menu-item
                title="Instalación de cámaras"
                 icon="o-camera"
                link="{{ route('ventas.instalacion-camaras') }}"
            />
        </x-menu-sub>
    @endif

    {{-- Crédito: administrador y super usuario --}}
    @if ($puede(1, 5))
        <x-menu-item
            title="Crédito"
            icon="o-banknotes"
            link="{{ route('creditos') }}"
        />
    @endif

    {{-- Compras: administrador, gerente y super usuario --}}
    @if ($puede(1, 2, 5))
        <x-menu-item
            title="Compras"
            icon="o-shopping-bag"
            link="{{ route('compras') }}"
        />
    @endif

    {{-- Productos: cargos 1, 2 y 3 --}}
    @if ($puede(1, 2, 3, 5))
        <x-menu-item
            title="Productos"
             icon="o-cube-transparent"
            link="{{ route('productos.index') }}"
        />
    @endif

    {{-- Salidas de inventario: cargos 1, 2 y 3 --}}
    @if ($puede(1, 2, 3, 5))
        <x-menu-item
            title="Salidas de inventario"
            icon="o-arrow-up-tray"
            link="{{ route('otras_salidas') }}"
        />
    @endif

    {{-- Devoluciones: cargos 1, 2 y 3 --}}
    @if ($puede(1, 2, 3, 5))
        <x-menu-item
            title="Devoluciones"
            icon="o-arrow-uturn-left"
            link="{{ route('devoluciones') }}"
        />
    @endif

    {{-- Gestión de trabajadores: administrador y super usuario --}}
    @if ($puedeVerGestionTrabajadores)
        <x-menu-sub title="Gestión de trabajadores" icon="o-identification">
            <x-menu-item
                title="Planilla de pago"
                icon="o-clipboard-document-list"
                link="{{ route('planillapago') }}"
            />
        </x-menu-sub>
    @endif

    {{-- Catálogo --}}
    @if ($puedeVerCatalogo)
        <x-menu-sub title="Catálogo" icon="o-rectangle-stack">

            {{-- Proveedores: administrador, gerente y super usuario --}}
            @if ($puede(1, 2, 5))
                <x-menu-item
                    title="Proveedores"
                    icon="o-truck"
                    link="{{ route('proveedores') }}"
                />
            @endif

            {{-- Clientes: cargos 1, 2 y 3 --}}
            @if ($puede(1, 2, 3, 5))
                <x-menu-item
                    title="Clientes"
                    icon="o-user-group"
                    link="{{ route('clientes') }}"
                />
            @endif

            {{-- Usuario: administrador y super usuario --}}
            @if ($puede(1, 5))
                <x-menu-item
                    title="Usuario"
                    icon="o-user"
                    link="{{ route('usersystem') }}"
                />
            @endif

            {{-- Trabajadores: administrador y super usuario --}}
            @if ($puede(1, 5))
                <x-menu-item
                    title="Trabajadores"
                    icon="o-users"
                    link="{{ route('trabajadores') }}"
                />
            @endif

        </x-menu-sub>
    @endif

    {{-- Arqueo de caja: cargos 1, 2 y 3 --}}
    @if ($puede(1, 2, 3, 5))
        <x-menu-item
            title="Arqueo de caja"
            icon="o-calculator"
            link="{{ route('arqueodecaja') }}"
        />
    @endif

    {{-- Mantenimiento: administrador y super usuario --}}
    @if ($puede(1, 5))
        <x-menu-item
            title="Mantenimiento"
            icon="o-cog-6-tooth"
            link="{{ route('mantenimiento') }}"
        />
    @endif

    {{-- Informes: administrador y super usuario --}}
    @if ($puede(1, 5))
        <x-menu-item
            title="Informes"
            icon="o-document-text"
            link="{{ route('Informes') }}"
        />
    @endif

    {{-- Acerca de: roles con acceso al sistema --}}
    @if ($puede(1, 2, 3, 5))
        <x-menu-item
            title="Acerca de"
            icon="o-information-circle"
            link="{{ route('acerca') }}"
        />
    @endif

    <x-menu-separator />

    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
        @csrf
    </form>

    <x-menu-item
        title="Cerrar sesión"
        icon="o-arrow-left-on-rectangle"
        onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
    />

</x-menu>
