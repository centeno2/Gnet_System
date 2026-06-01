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
        {{--
            Sidebar tipo rail:
            - Cerrado en escritorio: solo iconos.
            - Al pasar el mouse: se expande completo.
            - En móvil mantiene comportamiento drawer normal.
        --}}
    <x-slot:sidebar drawer="main-drawer" class="group/sidebar overflow-hidden bg-white border-r border-[#D7E4F3]
                   transition-[width,min-width,max-width] duration-500 ease-in-out
                   will-change-[width]
        
                   w-[18rem] min-w-[18rem] max-w-[18rem]
        
                   lg:!w-[5rem] lg:!min-w-[5rem] lg:!max-w-[5rem]
                   lg:hover:!w-[18rem] lg:hover:!min-w-[18rem] lg:hover:!max-w-[18rem]
        
                   lg:[&_span.mary-hideable]:!max-w-0
                   lg:[&_span.mary-hideable]:!opacity-0
                   lg:[&_span.mary-hideable]:!-translate-x-2
                   lg:[&_span.mary-hideable]:!overflow-hidden
                   lg:[&_span.mary-hideable]:!whitespace-nowrap
                   lg:[&_span.mary-hideable]:!transition-all
                   lg:[&_span.mary-hideable]:!duration-300
                   lg:[&_span.mary-hideable]:!ease-in-out
        
                   lg:hover:[&_span.mary-hideable]:!max-w-48
                   lg:hover:[&_span.mary-hideable]:!opacity-100
                   lg:hover:[&_span.mary-hideable]:!translate-x-0
        
                   lg:[&_ul]:!flex-col">
            @include('layouts.partials.sidebar')
        </x-slot:sidebar>

        <x-slot:content>
            <div class="min-h-screen p-4 md:p-6">
                {{ $slot }}
            </div>
        </x-slot:content>
    </x-main>

    {{--
        Área de notificaciones toast.
    --}}
    <x-toast />
</body>

</html>