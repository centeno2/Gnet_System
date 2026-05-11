<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.blank')] class extends Component
{
    public string $correo = '';
    public string $mensaje = '';
    public string $tipoMensaje = 'error';

    public function enviarEnlaceRecuperacion(): void
    {
        $this->validate(
            [
                'correo' => ['required', 'email', 'max:150'],
            ],
            [
                'correo.required' => 'El correo electrónico es requerido.',
                'correo.email' => 'Ingrese un correo electrónico válido.',
            ]
        );
        
        try {
            $correo = trim($this->correo);

            $usuarioData = Usuario::with('persona')
                ->whereHas('persona', function ($query) use ($correo) {
                    $query->where('Correo', $correo);
                })
                ->first();

            /*
             * Mensaje genérico:
             * No conviene decir si el correo existe o no,
             * para no revelar información de usuarios.
             */
            if (! $usuarioData) {
                $this->mostrarMensaje(
                    'Si el correo está registrado, se enviará un enlace de recuperación.',
                    'exito'
                );

                return;
            }

            if (! $usuarioData->estaActivo()) {
                $this->mostrarMensaje(
                    'La cuenta asociada a este correo se encuentra bloqueada. Contacte a administración.',
                    'error'
                );

                return;
            }

            $tokenPlano = $usuarioData->generarTokenRecuperacion();

            $enlace = route('password.reset', [
                'token' => $tokenPlano,
                'correo' => $correo,
            ]);

            Mail::raw(
                "Hola.\n\nRecibimos una solicitud para restablecer su contraseña.\n\nUse este enlace para continuar:\n\n{$enlace}\n\nEste enlace vence en 2 horas.\n\nSi usted no solicitó este cambio, ignore este mensaje.",
                function ($message) use ($correo) {
                    $message
                        ->to($correo)
                        ->subject('Recuperación de contraseña - GNET');
                }
            );

            $this->correo = '';

            $this->mostrarMensaje(
                'Revisar correo electronico.',
                'exito'
            );

        } catch (\Throwable $e) {
            logger()->error('Error al enviar recuperación de contraseña: ' . $e->getMessage());

            $this->mostrarMensaje(
                'No se pudo enviar el correo de recuperación. Revise la configuración de correo.',
                'error'
            );
        }
    }

    private function mostrarMensaje(string $mensaje, string $tipo = 'error'): void
    {
        $this->mensaje = $mensaje;
        $this->tipoMensaje = $tipo;
    }
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
                    Ingrese su correo para recibir el enlace de recuperación.
                </p>
            </div>

            @if ($mensaje)
                <div
                    class="mb-5 rounded-xl border px-4 py-3 text-sm font-medium
                    {{ $tipoMensaje === 'exito'
                        ? 'border-green-200 bg-green-50 text-green-700'
                        : 'border-red-200 bg-red-50 text-red-700' }}"
                >
                    {{ $mensaje }}
                </div>
            @endif

            <form wire:submit="enviarEnlaceRecuperacion" class="space-y-5">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Correo electrónico
                    </label>

                    <x-input
                        type="email"
                        wire:model="correo"
                        placeholder="Ingrese su correo"
                        class="h-11 min-h-11 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    />

                    @error('correo')
                        <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <x-button
                        type="submit"
                        label="Enviar enlace"
                        wire:loading.attr="disabled"
                        wire:target="enviarEnlaceRecuperacion"
                        class="h-11 min-h-11 w-full border-0 bg-[#2E8BC0] text-sm font-semibold text-white hover:bg-[#0B6FE4] disabled:cursor-not-allowed disabled:opacity-70"
                    />

                    <x-button
                        label="Volver al login"
                        :link="route('login')"
                        class="h-11 min-h-11 w-full border border-[#D7E4F3] bg-white text-sm font-semibold text-[#1A2B42] hover:bg-[#F0F3F7]"
                    />
                </div>
            </form>
        </x-card>
    </div>
</div>