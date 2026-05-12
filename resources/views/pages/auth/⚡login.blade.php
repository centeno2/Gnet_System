<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.blank')]
class extends Component {
    //
};
?>

<div class="flex min-h-screen items-center justify-center bg-[#F0F3F7] px-4 py-6">
    <div class="w-full max-w-md">
        <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-8 flex flex-col items-center text-center">
                <div class="mb-4 flex h-24 w-24 items-center justify-center rounded-2xl bg-[#EAF2FB]">
                    <img src="{{ asset('img/gnetlogo.png') }}" alt="Logo GNET" class="h-16 w-16 object-contain" />
                </div>

                <h2 class="text-3xl font-bold text-[#1A2B42]">Iniciar sesión</h2>
                <p class="mt-2 text-sm text-[#5F6B7A]">
                    Ingrese sus credenciales para continuar.
                </p>
            </div>

            <div class="space-y-5">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Correo electrónico
                    </label>
                    <x-input type="email" placeholder="Ingrese su correo"
                        class="h-11 min-h-11 w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="block text-sm font-semibold text-[#1A2B42]">
                            Contraseña
                        </label>


                        <a href="{{ route('password.request') }}"
                            class="text-sm font-semibold text-[#2E8BC0] hover:underline">
                            ¿Olvidó su contraseña?
                        </a>
                    </div>

                    <x-input type="password" placeholder="Ingrese su contraseña"
                        class="h-11 min-h-11 w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]" />
                </div>

                <div class="flex items-center justify-between rounded-xl bg-[#F0F3F7] px-4 py-3">
                    <label class="inline-flex items-center gap-2 text-sm text-[#5F6B7A]">
                        <input type="checkbox" class="rounded border-[#D7E4F3] text-[#0E48A1] focus:ring-[#0E48A1]">
                        Recordarme
                    </label>

                    <span class="text-xs font-medium text-[#7B8794]">
                        Acceso seguro
                    </span>
                </div>
            </div>

            <x-slot:actions>
                <div class="flex w-full flex-col gap-3">
                    <x-button label="Iniciar sesión" :link="route('main')"
                        class="h-11 min-h-11 w-full border-0 bg-[#2E8BC0] px-8 text-sm text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30" />

                    <p class="text-center text-sm text-[#5F6B7A]">
                        Solo para usuarios autorizados.
                    </p>
                </div>
            </x-slot:actions>
        </x-card>
    </div>
</div>
