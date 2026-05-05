<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

new class extends Component
{
    public string $idPersona = '';
    public string $nombreUsuario = '';
    public string $rol = '';
    public string $password = '';

    public string $buscar = '';

    public string $toastMensaje = '';
    public string $toastTipo = 'success';
    public bool $mostrarToast = false;

    public array $trabajadores = [];
    public array $usuarios = [];

    public array $headers = [
        ['key' => 'trabajador', 'label' => 'Trabajador'],
        ['key' => 'username', 'label' => 'Nombre usuario'],
        ['key' => 'password', 'label' => 'Contraseña'],
        ['key' => 'role', 'label' => 'Rol'],
        ['key' => 'status', 'label' => 'Estado del usuario'],
    ];

    public function mount(): void
    {
        $this->cargarTrabajadores();
        $this->cargarUsuarios();
    }

    public function updatedBuscar(): void
    {
        $this->cargarUsuarios();
    }

    /**
     * Guarda un nuevo usuario del sistema.
     * Se valida que el trabajador exista, que no tenga ya un usuario asignado,
     * y que el nombre de usuario no se repita en la tabla usuario.
     */
    public function guardarUsuario(): void
    {
        $this->resetErrorBag();

        $datos = $this->validate(
            [
                'idPersona' => 'required|integer|exists:persona,Id_Persona|unique:usuario,Id_Persona',
                'nombreUsuario' => 'required|string|min:4|max:60|alpha_dash|unique:usuario,Nombre_Usuario',
                'rol' => 'required|in:cajero,administrador,gerente',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:255',
                    'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
                ],
            ],
            [
                'idPersona.required' => 'Debe seleccionar un trabajador.',
                'idPersona.exists' => 'El trabajador seleccionado no existe.',
                'idPersona.unique' => 'Este trabajador ya tiene un usuario asignado.',

                'nombreUsuario.required' => 'Debe ingresar el nombre de usuario.',
                'nombreUsuario.min' => 'El nombre de usuario debe tener al menos 4 caracteres.',
                'nombreUsuario.max' => 'El nombre de usuario no debe superar los 60 caracteres.',
                'nombreUsuario.alpha_dash' => 'El nombre de usuario solo puede contener letras, números, guiones y guion bajo.',
                'nombreUsuario.unique' => 'Este nombre de usuario ya está registrado.',

                'rol.required' => 'Debe seleccionar un rol de usuario.',
                'rol.in' => 'El rol seleccionado no es válido.',

                'password.required' => 'Debe ingresar una contraseña.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.max' => 'La contraseña no debe superar los 255 caracteres.',
                'password.regex' => 'La contraseña debe incluir letras y números.',
            ]
        );

        DB::transaction(function () use ($datos) {
            $usuario = new Usuario();

            $usuario->Id_Persona = (int) $datos['idPersona'];
            $usuario->Nombre_Usuario = trim($datos['nombreUsuario']);

            // La contraseña se guarda cifrada. Para validar el login se debe usar Hash::check().
            $usuario->Contraseña_Usuario = Hash::make($datos['password']);

            $usuario->Rol = $datos['rol'];
            $usuario->Estado = 1;

            // Valores iniciales para control de recuperación y bloqueo de acceso.
            $usuario->Token_Recuperacion = null;
            $usuario->Fecha_Recuperacion = null;
            $usuario->Intentos_Fallidos = 0;

            $usuario->save();
        });

        $this->resetFormularioUsuario();
        $this->cargarTrabajadores();
        $this->cargarUsuarios();

        $this->mostrarToast('Usuario guardado correctamente.');
    }

    /**
     * Carga los trabajadores que todavía no tienen usuario.
     * Esto evita que una misma persona tenga dos cuentas asignadas.
     */
    protected function cargarTrabajadores(): void
    {
        $this->trabajadores = DB::table('persona as p')
            ->leftJoin('usuario as u', 'p.Id_Persona', '=', 'u.Id_Persona')
            ->whereNull('u.Id_Usuario')
            ->select(
                'p.Id_Persona',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido'
            )
            ->orderBy('p.Primer_Nombre')
            ->orderBy('p.Primer_Apellido')
            ->get()
            ->map(function ($persona) {
                $nombreCompleto = trim(
                    $persona->Primer_Nombre . ' ' .
                    ($persona->Segundo_Nombre ? $persona->Segundo_Nombre . ' ' : '') .
                    $persona->Primer_Apellido . ' ' .
                    ($persona->Segundo_Apellido ?? '')
                );

                return [
                    'id' => $persona->Id_Persona,
                    'name' => $nombreCompleto,
                ];
            })
            ->toArray();
    }

    /**
     * Carga el listado de usuarios para la tabla principal.
     * La búsqueda se aplica sobre el nombre del trabajador, usuario, rol y estado.
     */
    protected function cargarUsuarios(): void
    {
        $busqueda = trim($this->buscar);

        $query = DB::table('usuario as u')
            ->join('persona as p', 'u.Id_Persona', '=', 'p.Id_Persona')
            ->select(
                'u.Id_Usuario',
                'u.Nombre_Usuario',
                'u.Rol',
                'u.Estado',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido'
            )
            ->orderByDesc('u.Id_Usuario');

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('u.Nombre_Usuario', 'like', "%{$busqueda}%")
                    ->orWhere('u.Rol', 'like', "%{$busqueda}%")
                    ->orWhere('p.Primer_Nombre', 'like', "%{$busqueda}%")
                    ->orWhere('p.Segundo_Nombre', 'like', "%{$busqueda}%")
                    ->orWhere('p.Primer_Apellido', 'like', "%{$busqueda}%")
                    ->orWhere('p.Segundo_Apellido', 'like', "%{$busqueda}%");
            });
        }

        $this->usuarios = $query->limit(25)->get()
            ->map(function ($usuario) {
                $trabajador = trim(
                    $usuario->Primer_Nombre . ' ' .
                    ($usuario->Segundo_Nombre ? $usuario->Segundo_Nombre . ' ' : '') .
                    $usuario->Primer_Apellido . ' ' .
                    ($usuario->Segundo_Apellido ?? '')
                );

                return [
                    'trabajador' => $trabajador,
                    'username' => $usuario->Nombre_Usuario,
                    'password' => '••••••••',
                    'role' => ucfirst($usuario->Rol),
                    'status' => (int) $usuario->Estado === 1 ? 'Activo' : 'Inactivo',
                ];
            })
            ->toArray();
    }

    /**
     * Limpia únicamente los campos del formulario.
     * No toca el listado para evitar recargas innecesarias.
     */
    protected function resetFormularioUsuario(): void
    {
        $this->idPersona = '';
        $this->nombreUsuario = '';
        $this->rol = '';
        $this->password = '';

        $this->resetErrorBag();
        $this->resetValidation();
    }

    protected function mostrarToast(string $mensaje, string $tipo = 'success'): void
    {
        $this->toastMensaje = $mensaje;
        $this->toastTipo = $tipo;
        $this->mostrarToast = true;
    }

    public function cerrarToast(): void
    {
        $this->mostrarToast = false;
        $this->toastMensaje = '';
        $this->toastTipo = 'success';
    }
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#1A2B42]">Usuarios</h1>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Gestión e ingreso de usuarios del sistema.
        </p>
    </div>

    @if ($mostrarToast)
    <div class="fixed right-5 top-5 z-999 w-full max-w-sm">
        <div
            class="{{ $toastTipo === 'success' ? 'border-[#B7D6F2] bg-[#EAF4FD] text-[#1A2B42]' : 'border-red-200 bg-red-50 text-red-700' }} rounded-2xl border px-4 py-4 shadow-lg">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium">{{ $toastMensaje }}</p>

                <button type="button" wire:click="cerrarToast"
                    class="text-lg leading-none text-[#5F6B7A] hover:text-[#1A2B42]">
                    ×
                </button>
            </div>
        </div>
    </div>
    @endif

    <form wire:submit.prevent="guardarUsuario">
        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-[#1A2B42]">Registrar usuario</h2>
                <p class="text-base text-[#5F6B7A]">
                    Complete los campos para crear un nuevo usuario.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Trabajador
                    </label>

                    <x-select wire:model.defer="idPersona" placeholder="Seleccione un trabajador"
                        :options="$trabajadores" option-value="id" option-label="name"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]" />

                    @error('idPersona')
                    <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Nombre de usuario
                    </label>

                    <x-input wire:model.defer="nombreUsuario" placeholder="Ingrese el nombre de usuario"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]" />

                    @error('nombreUsuario')
                    <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Rol de usuario
                    </label>

                    <x-select wire:model.defer="rol" placeholder="Seleccione un rol" :options="[
                            ['id' => 'cajero', 'name' => 'Cajero'],
                            ['id' => 'administrador', 'name' => 'Administrador'],
                            ['id' => 'gerente', 'name' => 'Gerente'],
                        ]" option-value="id" option-label="name"
                        class="w-full rounded-xl border border-[#B8CBE3] bg-white text-[#1A2B42] shadow-sm focus:border-[#0E48A1] focus:ring-2 focus:ring-[#0E48A1]/20" />

                    @error('rol')
                    <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Contraseña
                    </label>

                    <x-password wire:model.defer="password" placeholder="Ingrese la contraseña" clearable
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"/>

                    @error('password')
                    <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Guardar usuario" type="submit"
                    class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30" />
            </x-slot:actions>
        </x-card>
    </form>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de usuarios</h2>

            <x-input wire:model.live.debounce.250ms="buscar" placeholder="Buscar usuario"
                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]" />
        </div>

        <x-table :headers="$headers" :rows="$usuarios"
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl">
            @scope('cell_status', $usuario)
            <span
                class="{{ $usuario['status'] === 'Activo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                {{ $usuario['status'] }}
            </span>
            @endscope
        </x-table>
    </x-card>
</div>
