{{--
Cabecera del sidebar.
--}}
<div class="rounded-2xl">
    <img src="{{ asset('img/gnetlogo.png') }}" alt="Logo" class="mt-4 w-32 mx-auto rounded-lg object-cover">
</div>

{{--
menu principal vertical.
activate-by-route hace que MaryUI marque automaticamente
el elemento activo según la ruta actual
--}}
<x-menu activate-by-route class="mt-4 px-2" active="bg-[#D7E4F3] text-[#1A2B42]">

    {{--
    Opciones del navegador vertical.
    Se usan submenús con la logica de MaryUI solo donde corresponde
    --}}

    <x-menu-sub title="Ventas" icon="o-shopping-cart">
        <x-menu-item title="Facturación" icon="o-receipt-refund" link="{{ route('ventas.index') }}" />
        <x-menu-item title="Servicio técnico" icon="o-wrench-screwdriver" link="{{ route('ventas.index') }}" />
        <x-menu-item title="Instalación de camaras" icon="o-wrench-screwdriver" link="{{ route('ventas.index') }}" />
    </x-menu-sub>

    <x-menu-item title="Crédito" icon="o-credit-card" link="{{ route('credito.index') }}" />
    <x-menu-item title="Compras" icon="o-shopping-bag" link="{{ route('compras') }}" />
    <x-menu-item title="Productos" icon="o-cube" link="{{ route('productos.index') }}" />
    <x-menu-item title="Salidas de inventario" icon="o-arrow-up-tray" link="{{ route('salidas.index') }}" />
    <x-menu-item title="Devoluciones" icon="o-arrow-uturn-left" link="{{ route('devoluciones.index') }}" />

    <x-menu-sub title="Gestión de trabajadores" icon="o-users">
        <x-menu-item title="Planilla de pago" icon="o-document-text" link="{{ route('planillapago') }}" />
    </x-menu-sub>

    <x-menu-sub title="Catalogo" icon="o-wrench-screwdriver">
        <x-menu-item title="Proveedores" icon="o-truck" link="{{ route('proveedores') }}" />
        <x-menu-item title="Clientes" icon="o-user-group" link="{{ route('clientes') }}" />
        <x-menu-item title="Usuario" icon="o-user" link="{{ route('usersystem') }}" />
        <x-menu-item title="Trabajadores" icon="o-users" link="{{ route('trabajadores') }}" />
    </x-menu-sub>

    <x-menu-item title="Arqueo de caja" icon="o-calculator" link="{{ route('arqueo.index') }}" />
    <x-menu-item title="Mantenimiento" icon="o-cog-6-tooth" link="{{ route('mantenimiento.index') }}" />
    <x-menu-item title="Informes" icon="o-document-text" link="{{ route('informes.index') }}" />
    <x-menu-item title="Acerca de" icon="o-information-circle" link="{{ route('acerca.index') }}" />

    <x-menu-separator />

    <x-menu-item title="Salir" icon="o-arrow-left-on-rectangle" link="/logout" no-wire-navigate />
</x-menu>