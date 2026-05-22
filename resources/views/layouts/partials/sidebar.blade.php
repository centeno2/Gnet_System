{{--
    Cabecera del sidebar.
--}}
<div class="rounded-2xl">
    <img src="{{ asset('img/gnetlogo.png') }}" alt="Logo" class="mx-auto mt-4 w-32 rounded-lg object-cover">
</div>

@php
    $cargo = (int) (auth()->user()?->trabajador?->cargo?->Id_Cargo ?? 0);

    /**
     * Valida si el cargo actual está permitido.
     */
    $puede = fn (...$cargos) => in_array($cargo, array_map('intval', $cargos), true);

    $puedeVerVentas = $puede(1, 2, 3);
    $puedeVerCatalogo = $puede(1, 2, 3);
    $puedeVerGestionTrabajadores = $puede(1, 2);
@endphp

<x-menu activate-by-route active-bg-color="!bg-[#2E8BC0] !text-white font-semibold shadow-sm">

    {{-- Ventas: cargos 1 y 2  --}}
    @if ($puedeVerVentas)
        <x-menu-sub title="Ventas" icon="o-shopping-cart">
            <x-menu-item 
                title="Facturación" 
                icon="o-receipt-refund" 
                link="{{ route('ventas.facturacion') }}" 
            />

            <x-menu-item 
                title="Servicio técnico" 
                icon="o-wrench-screwdriver"
                link="{{ route('ventas.servicio-tecnico') }}" 
            />

            <x-menu-item 
                title="Instalación de cámaras" 
                icon="o-wrench-screwdriver"
                link="{{ route('ventas.instalacion-camaras') }}" 
            />
        </x-menu-sub>
    @endif

    {{-- Crédito: cargos 1 y 2 --}}
    @if ($puede(1, 2))
        <x-menu-item 
            title="Crédito" 
            icon="o-credit-card" 
            link="{{ route('creditos') }}" 
        />
    @endif

    {{-- Compras: cargos 1 --}}
    @if ($puede(1))
        <x-menu-item 
            title="Compras" 
            icon="o-shopping-bag" 
            link="{{ route('compras') }}" 
        />
    @endif

    {{-- Productos: cargos 1, 2 --}}
    @if ($puede(1, 2))
        <x-menu-item 
            title="Productos" 
            icon="o-cube" 
            link="{{ route('productos.index') }}" 
        />
    @endif

    {{-- Salidas de inventario: cargos 1  --}}
    @if ($puede(1 ))
        <x-menu-item 
            title="Salidas de inventario" 
            icon="o-arrow-up-tray" 
            link="{{ route('otras_salidas') }}" 
        />
    @endif

    {{-- Devoluciones: cargos 1, 2 y 3 --}}
    @if ($puede(1, 2, 3))
        <x-menu-item 
            title="Devoluciones" 
            icon="o-arrow-uturn-left" 
            link="{{ route('devoluciones') }}" 
        />
    @endif

    {{-- Gestión de trabajadores: cargos 1  --}}
    @if ($puedeVerGestionTrabajadores)
        <x-menu-sub title="Gestión de trabajadores" icon="o-users">
            <x-menu-item 
                title="Planilla de pago" 
                icon="o-document-text" 
                link="{{ route('planillapago') }}" 
            />
        </x-menu-sub>
    @endif

    {{-- Catálogo --}}
    @if ($puedeVerCatalogo)
        <x-menu-sub title="Catálogo" icon="o-wrench-screwdriver">

            {{-- Proveedores: cargos 1 y 2 --}}
            @if ($puede(2 ))
                <x-menu-item 
                    title="Proveedores" 
                    icon="o-truck" 
                    link="{{ route('proveedores') }}" 
                />
            @endif

            {{-- Clientes: cargos 1, 2 y 3 --}}
            @if ($puede(1, 2, 3))
                <x-menu-item 
                    title="Clientes" 
                    icon="o-user-group" 
                    link="{{ route('clientes') }}" 
                />
            @endif

            {{-- Usuario: cargos 1 y 2 --}}
            @if ($puede(1 ,2 ))
                <x-menu-item 
                    title="Usuario" 
                    icon="o-user" 
                    link="{{ route('usersystem') }}" 
                />
            @endif

            {{-- Trabajadores: cargos 1 y 2 --}}
            @if ($puede(1, 2))
                <x-menu-item 
                    title="Trabajadores" 
                    icon="o-users" 
                    link="{{ route('trabajadores') }}" 
                />
            @endif

        </x-menu-sub>
    @endif

    {{-- Arqueo de caja: cargos 1, 2 y 3 --}}
    @if ($puede(1, 2, 3))
        <x-menu-item 
            title="Arqueo de caja" 
            icon="o-calculator" 
            link="{{ route('arqueodecaja') }}" 
        />
    @endif

    {{-- Mantenimiento: cargos 1 y 2 --}}
    @if ($puede(1, 2))
        <x-menu-item 
            title="Mantenimiento" 
            icon="o-cog-6-tooth" 
            link="{{ route('mantenimiento') }}" 
        />
    @endif

    {{-- Informes: cargos 1 y 2 --}}
    @if ($puede(1, 2))
        <x-menu-item 
            title="Informes" 
            icon="o-document-text" 
            link="{{ route('mantenimiento') }}" 
        />
    @endif

    {{-- Acerca de: cargos 1 y 2 --}}
    @if ($puede(1, 2))
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