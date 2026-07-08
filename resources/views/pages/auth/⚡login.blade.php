<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
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
    public bool $confirmarCerrarSesionActiva = false;
    public string $mensajeSesionActiva = '';

    public function login(bool $cerrarOtraSesion = false)
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

            if (! $cerrarOtraSesion && $this->tieneOtraSesionActiva((int) $usuarioData->Id_Usuario)) {
                $this->mensajeSesionActiva = 'Este usuario ya tiene una sesión activa en otra máquina o navegador. Si continúa, se cerrará la sesión anterior y entrará en esta máquina.';
                $this->confirmarCerrarSesionActiva = true;
                $this->errorVisible = false;
                return;
            }

            if ($cerrarOtraSesion) {
                $this->cerrarOtrasSesiones((int) $usuarioData->Id_Usuario);
            }

            $usuarioData->resetearIntentosFallidos();
            auth()->login($usuarioData, $this->recordarme);
            session()->regenerate();

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

    public function confirmarIngresoCerrandoSesionActiva()
    {
        $this->confirmarCerrarSesionActiva = false;

        return $this->login(true);
    }

    public function cancelarIngresoCerrandoSesionActiva(): void
    {
        $this->confirmarCerrarSesionActiva = false;
        $this->mensajeSesionActiva = '';
        $this->password = '';
    }

    private function tieneOtraSesionActiva(int $usuarioId): bool
    {
        if (config('session.driver') !== 'database') {
            return false;
        }

        $limiteActividad = now()->subMinutes((int) config('session.lifetime', 30))->timestamp;

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $usuarioId)
            ->where('id', '<>', session()->getId())
            ->where('last_activity', '>=', $limiteActividad)
            ->exists();
    }

    private function cerrarOtrasSesiones(int $usuarioId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $usuarioId)
            ->where('id', '<>', session()->getId())
            ->delete();
    }

    private function mostrarError(string $mensaje): void
    {
        $this->mensajeError = $mensaje;
        $this->errorVisible = true;
        $this->confirmarCerrarSesionActiva = false;
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

        <x-modal
            wire:model="confirmarCerrarSesionActiva"
            class="backdrop-blur-sm"
            box-class="max-w-md rounded-2xl border border-[#D7E4F3] bg-white p-0 shadow-2xl"
        >
            <div class="p-5">
                <h3 class="text-xl font-bold text-[#1A2B42]">Sesión activa detectada</h3>
                <p class="mt-2 text-sm leading-6 text-[#5F6B7A]">
                    {{ $mensajeSesionActiva }}
                </p>
                <p class="mt-3 rounded-xl bg-[#EAF2FB] px-3 py-2 text-sm font-semibold text-[#1A2B42]">
                    Si tenía una caja abierta con este usuario, al entrar aquí se cargará para continuar el cierre o los movimientos.
                </p>
            </div>

            <x-slot:actions>
                <div class="flex w-full flex-col-reverse gap-3 border-t border-[#D7E4F3] bg-white px-5 py-4 sm:flex-row sm:justify-end">
                    <x-button
                        label="Cancelar"
                        type="button"
                        wire:click="cancelarIngresoCerrandoSesionActiva"
                        class="rounded-xl border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]"
                    />

                    <x-button
                        label="Cerrar la otra sesión y entrar"
                        type="button"
                        wire:click="confirmarIngresoCerrandoSesionActiva"
                        spinner="confirmarIngresoCerrandoSesionActiva"
                        class="rounded-xl border-0 bg-[#0B6FE4] text-white hover:bg-[#2E8BC0]"
                    />
                </div>
            </x-slot:actions>
        </x-modal>
    </div>
</div>
