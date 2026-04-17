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
        <x-slot:sidebar drawer="main-drawer" collapsible>
            @include('layouts.partials.sidebar')
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