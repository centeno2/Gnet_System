<?php

use App\Models\Usuario;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.blank')] class extends Component
{
    #[Validate('required', message: 'El usuario es requerido')]
    public string $usuario = '';

    #[Validate('required|min:6', message: 'La contraseña es requerida')]
    public string $password = '';

    public bool $recordarme = false;
    public string $mensajeError = '';
    public bool $errorVisible = false;

    public function login()
    {
        $this->validate();

        try {
            $usuarioData = Usuario::where('Nombre_Usuario', trim($this->usuario))->first();


            if (!$usuarioData) {
                $this->mostrarError('Usuario o contraseña incorrectos');
                return;
            }

            if (!$usuarioData->estaActivo()) {
                $this->mostrarError('Usuario Inactivo. Contacte a administración.');
                return;
            }

            if (!$usuarioData->validarContraseña($this->password)) {
                $usuarioData->incrementarIntentosFallidos();

                $intentosRestantes = 5 - $usuarioData->Intentos_Fallidos;

                if ($intentosRestantes <= 0) {
                    $this->mostrarError('Cuenta bloqueada por exceder intentos fallidos.');
                } else {
                    $this->mostrarError("Usuario o contraseña incorrectos. Intentos restantes: {$intentosRestantes}");
                }

                return;
            }

            $usuarioData->resetearIntentosFallidos();
            auth()->login($usuarioData, $this->recordarme);

            session([
                'usuario_id' => $usuarioData->Id_Usuario,
                'usuario_nombre' => $usuarioData->Nombre_Usuario,
                'usuario_rol' => $usuarioData->Rol,
                'autenticado' => true,
            ]);

            if ($this->recordarme) {
                session(['recordarme' => true]);
            }

            return $this->redirect(route('main'), navigate: true);

        } catch (\Exception $e) {
            $this->mostrarError('Error del sistema: ' . $e->getMessage());
            \Log::error('Error en login: ' . $e->getMessage());
        }
    }

    private function mostrarError(string $mensaje): void
    {
        $this->mensajeError = $mensaje;
        $this->errorVisible = true;
        $this->password = '';
    }

    public function cerrarError(): void
    {
        $this->errorVisible = false;
        $this->mensajeError = '';
    }
};
?>
<div class="flex min-h-screen items-center justify-center bg-[#F0F3F7] px-4 py-6">
    <div class="w-full max-w-md">

        <form wire:submit="login">
            <x-card class="rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
                <div class="mb-8 flex flex-col items-center text-center">
                    <div class="mb-4 flex h-24 w-24 items-center justify-center rounded-2xl bg-[#EAF2FB]">
                        <img
                            src="{{ asset('img/gnetlogo.png') }}"
                            alt="Logo GNET"
                            class="h-16 w-16 object-contain"
                        />
                    </div>

                    <h2 class="text-3xl font-bold text-[#1A2B42]">Iniciar sesión</h2>
                    <p class="mt-2 text-sm text-[#5F6B7A]">
                        Ingrese sus credenciales para continuar.
                    </p>
                </div>

                @if ($errorVisible)
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        <div class="flex items-center justify-between gap-3">
                            <span>{{ $mensajeError }}</span>

                            <button
                                type="button"
                                wire:click="cerrarError"
                                class="text-red-500 hover:text-red-700"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                @endif

                <div class="space-y-5">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                            Usuario
                        </label>

                        <x-input
                            type="text"
                            wire:model="usuario"
                            placeholder="Ingrese su usuario"
                            class="h-11 min-h-11 w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                        />

                        @error('usuario')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label class="block text-sm font-semibold text-[#1A2B42]">
                                Contraseña
                            </label>

                            <a
                                href="{{ route('password.request') }}"
                                class="text-sm font-semibold text-[#2E8BC0] hover:underline"
                            >
                                ¿Olvidó su contraseña?
                            </a>
                        </div>

                        <x-password
                            wire:model="password"
                            placeholder="Ingrese su contraseña"
                            clearable
                            class="h-11 min-h-11 w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                        />

                        @error('password')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between rounded-xl bg-[#F0F3F7] px-4 py-3">
                        <label class="inline-flex items-center gap-2 text-sm text-[#5F6B7A]">
                            <input
                                type="checkbox"
                                wire:model="recordarme"
                                class="rounded border-[#D7E4F3] text-[#0E48A1] focus:ring-[#0E48A1]"
                            >

                            Recordarme
                        </label>

                        <span class="text-xs font-medium text-[#7B8794]">
                            Acceso seguro
                        </span>
                    </div>
                </div>

                <x-slot:actions>
                    <div class="flex w-full flex-col gap-3">
                        <x-button
                            type="submit"
                            label="Iniciar sesión"
                            wire:loading.attr="disabled"
                            wire:target="login"
                            class="h-11 min-h-11 w-full border-0 bg-[#2E8BC0] px-8 text-sm font-semibold text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30 disabled:cursor-not-allowed disabled:opacity-70"
                        />

                        <p class="text-center text-sm text-[#5F6B7A]">
                            Solo para usuarios autorizados.
                        </p>
                    </div>
                </x-slot:actions>
            </x-card>
        </form>
    </div>
</div>
