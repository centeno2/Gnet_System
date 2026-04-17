<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    {{-- Configuración básica del documento --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">


    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen font-sans antialiased bg-[#F0F3F7] text-[#1A2B42]">
    {{--
    MAIN del layout.
    x-main organiza sidebar + contenido.
    --}}
    <x-main>
<<<<<<< HEAD
        <x-slot:sidebar
            drawer="main-drawer"
            collapsible
            class="bg-[#0E48A1] text-white lg:bg-[#F0F3F7] lg:text-[#1A2B42] border-r border-[#D7E4F3] shadow-xl"
        >

            {{-- 
                Cabecera del sidebar.
            --}}
            <div class="rounded-2xl">
                <img src="{{ asset('img/gnetlogo.png') }}" alt="Logo" class="mt-4 w-32 mx-auto rounded-lg object-cover">
            </div>

            {{-- 
                MENU principal vertical.
                activate-by-route hace que MaryUI marque automaticamente
                el elemento activo según la ruta actual.
            --}}
            <x-menu activate-by-route class="mt-4 px-2">

                {{--
                    Opciones del navegador vertical.
                    Se usan submenús con la lógica de MaryUI solo donde corresponde.
                --}}

                <x-menu-sub title="Ventas" icon="o-shopping-cart">
                    <x-menu-item title="Facturación" icon="o-receipt-refund" link="{{ route('ventas.index') }}" />
                    <x-menu-item title="Servicio técnico" icon="o-wrench-screwdriver" link="{{ route('ventas.index') }}" />
                    <x-menu-item title="Instalación de camaras" icon="o-wrench-screwdriver" link="{{ route('ventas.index') }}" />
                </x-menu-sub>

                <x-menu-item title="Crédito" icon="o-credit-card" link="{{ route('creditos') }}" />
                <x-menu-item title="Compras" icon="o-shopping-bag" link="{{ route('compras.index') }}" />
                <x-menu-item title="Productos" icon="o-cube" link="{{ route('productos.index') }}" />
                <x-menu-item title="Salidas de inventario" icon="o-arrow-up-tray" link="{{ route('otras_salidas') }}" />
                <x-menu-item title="Devoluciones" icon="o-arrow-uturn-left" link="{{ route('devoluciones') }}" />

                <x-menu-sub title="Gestión de trabajadores" icon="o-users">
                    <x-menu-item title="Planilla de pago" icon="o-document-text" link="{{ route('trabajadores.index') }}" />
                </x-menu-sub>

                <x-menu-sub title="Catalogo" icon="o-wrench-screwdriver">
                    <x-menu-item title="Proveedores" icon="o-truck" link="{{ route('servicios.index') }}" />
                    <x-menu-item title="Clientes" icon="o-user-group" link="{{ route('servicios.index') }}" />
                    <x-menu-item title="Usuario" icon="o-user" link="{{ route('servicios.index') }}" />
                    <x-menu-item title="Trabajadores" icon="o-users" link="{{ route('servicios.index') }}" />
                </x-menu-sub>

                <x-menu-item title="Arqueo de caja" icon="o-calculator" link="{{ route('arqueo.index') }}" />
                <x-menu-item title="Mantenimiento" icon="o-cog-6-tooth" link="{{ route('mantenimiento') }}" />
                <x-menu-item title="Informes" icon="o-document-text" link="{{ route('informes.index') }}" />
                <x-menu-item title="Acerca de" icon="o-information-circle" link="{{ route('acerca.index') }}" />

                <x-menu-separator />

                <x-menu-item
                    title="Salir"
                    icon="o-arrow-left-on-rectangle"
                    link="/logout"
                    no-wire-navigate
                />
            </x-menu>
=======
        <x-slot:sidebar drawer="main-drawer" collapsible>
            @include('layouts.partials.sidebar')
>>>>>>> 1a51bde2d51d1cf87fbf138639605d11933be68d
        </x-slot:sidebar>


        <x-slot:content>
            <div class="min-h-screen p-4 md:p-6">
                {{ $slot }}
            </div>
        </x-slot:content>
    </x-main>

    {{--
    area de notificaciones toast
    --}}
    <x-toast />
</body>

</html>