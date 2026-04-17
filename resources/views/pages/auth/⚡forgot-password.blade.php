<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.blank')] class extends Component {
    //
};
?>

<div class="flex min-h-screen items-center justify-center bg-[#F0F3F7] px-4 py-6">
    <div class="w-full max-w-md">
        <x-card class="rounded-3xl border border-[#D7E4F3] bg-white p-6 shadow-sm md:p-8">
            <div class="mb-6 text-center">
                <div class="mb-4 flex justify-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-[#EAF2FB]">
                        <img
                            src="{{ asset('img/gnetlogo.png') }}"
                            alt="Logo GNET"
                            class="h-14 w-14 object-contain"
                        />
                    </div>
                </div>

                <h1 class="text-3xl font-bold text-[#1A2B42]">Recuperar contraseña</h1>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    Ingrese su correo para enviarle el enlace de recuperación.
                </p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Correo electrónico
                </label>
                <x-input
                    type="email"
                    placeholder="Ingrese su correo"
                    class="h-11 min-h-11 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div class="mt-6 flex flex-col gap-3">
                <x-button
                    label="Enviar enlace"
                    class="h-11 min-h-11 w-full border-0 bg-[#0E48A1] text-sm text-white hover:bg-[#0B6FE4]"
                />

                <x-button
                    label="Volver al login"
                    :link="route('login')"
                    class="h-11 min-h-11 w-full border border-[#D7E4F3] bg-white text-sm text-[#1A2B42] hover:bg-[#F0F3F7]"
                />
            </div>
        </x-card>
    </div>
</div>