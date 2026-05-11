<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.blank')] class extends Component {

    public string $token = '';

    public string $correo = '';
    public string $password = '';
    public string $password_confirmation = '';

    public string $mensaje = '';
    public string $tipoMensaje = 'error';

    public function mount(string $token = ''): void
    {
        $this->token = $token;
        $this->correo = request('correo', '');
    }

    public function resetearPassword(): void
    {
        $this->validate([
            'correo' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ], [
            'correo.required' => 'El correo es requerido.',
            'correo.email' => 'Ingrese un correo válido.',

            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $usuario = Usuario::with('persona')
            ->whereHas('persona', function ($query) {
                $query->where('Correo', $this->correo);
            })
            ->first();

        if (!$usuario) {
            $this->mostrarMensaje('Usuario no válido.');
            return;
        }

        if (!$usuario->validarTokenRecuperacion($this->token)) {
            $this->mostrarMensaje('El enlace es inválido o expiró.');
            return;
        }

        $usuario->update([
            'Contraseña_Usuario' => Hash::make($this->password),
        ]);

        $usuario->limpiarRecuperacion();

        $this->mostrarMensaje(
            'Contraseña actualizada correctamente.',
            'exito'
        );

        $this->reset([
            'password',
            'password_confirmation',
        ]);
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

                <h1 class="text-3xl font-bold text-[#1A2B42]">
                    Restablecer contraseña
                </h1>

                <p class="mt-1 text-sm text-[#5F6B7A]">
                    Cree una nueva contraseña para su cuenta.
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

            <form wire:submit="resetearPassword" class="space-y-4">

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Correo electrónico
                    </label>

                    <x-input
                        type="email"
                        wire:model="correo"
                        readonly
                        class="h-11 min-h-11 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />

                    @error('correo')
                        <p class="mt-1 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Nueva contraseña
                    </label>

                    <x-input
                        type="password"
                        wire:model="password"
                        placeholder="Ingrese la nueva contraseña"
                        class="h-11 min-h-11 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />

                    @error('password')
                        <p class="mt-1 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Confirmar contraseña
                    </label>

                    <x-input
                        type="password"
                        wire:model="password_confirmation"
                        placeholder="Confirme la nueva contraseña"
                        class="h-11 min-h-11 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                </div>

                <div class="mt-6 flex flex-col gap-3">

                    <x-button
                        type="submit"
                        label="Guardar nueva contraseña"
                        wire:loading.attr="disabled"
                        wire:target="resetearPassword"
                        class="h-11 min-h-11 w-full border-0 bg-[#0E48A1] text-sm text-white hover:bg-[#0B6FE4]"
                    />

                    <x-button
                        label="Volver al login"
                        :link="route('login')"
                        class="h-11 min-h-11 w-full border border-[#D7E4F3] bg-white text-sm text-[#1A2B42] hover:bg-[#F0F3F7]"
                    />

                </div>

            </form>

        </x-card>

    </div>
</div>