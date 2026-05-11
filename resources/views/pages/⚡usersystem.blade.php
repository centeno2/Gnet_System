<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public string $idPersona = '';
    public string $nombreUsuario = '';
    public string $password = '';
    public string $cargoTrabajador = '';

    public string $buscar = '';

    public bool $modalEditarUsuario = false;
    public int $idUsuarioEditar = 0;
    public string $nombreUsuarioEditar = '';
    public string $trabajadorEditarNombre = '';
    public string $cargoEditarNombre = '';
    public string $estadoEditar = '1';

    public string $toastMensaje = '';
    public string $toastTipo = 'success';
    public bool $mostrarToast = false;

    public array $trabajadores = [];
    public array $usuarios = [];

    public array $headers = [
        ['key' => 'trabajador', 'label' => 'Trabajador'],
        ['key' => 'cargo', 'label' => 'Cargo'],
        ['key' => 'username', 'label' => 'Nombre usuario'],
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

    public function updatedIdPersona(): void
    {
        $this->cargarCargoTrabajador();
    }

    /**
     * Guarda un nuevo usuario del sistema.
     * El rol se asigna automáticamente desde el cargo del trabajador seleccionado.
     */
    public function guardarUsuario(): void
    {
        $this->resetErrorBag();

        $datos = $this->validate(
            [
                'idPersona' => 'required|integer|exists:trabajador,Id_Persona|unique:usuario,Id_Persona',
                'nombreUsuario' => 'required|string|min:4|max:60|alpha_dash|unique:usuario,Nombre_Usuario',
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

                'password.required' => 'Debe ingresar una contraseña.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.max' => 'La contraseña no debe superar los 255 caracteres.',
                'password.regex' => 'La contraseña debe incluir letras y números.',
            ]
        );

        $cargoUsuario = DB::table('trabajador as t')
            ->join('cargo as c', 't.Id_Cargo', '=', 'c.Id_Cargo')
            ->where('t.Id_Persona', (int) $datos['idPersona'])
            ->value('c.Cargo_Asignado');

        if (! $cargoUsuario) {
            throw ValidationException::withMessages([
                'idPersona' => 'El trabajador seleccionado no tiene cargo asignado.',
            ]);
        }

        DB::transaction(function () use ($datos, $cargoUsuario) {
            $usuario = new Usuario();

            $usuario->Id_Persona = (int) $datos['idPersona'];
            $usuario->Nombre_Usuario = trim($datos['nombreUsuario']);

            // La contraseña se guarda cifrada. Para validar el login se debe usar Hash::check().
            $usuario->Contraseña_Usuario = Hash::make($datos['password']);

            // El rol del usuario queda sincronizado con el cargo asignado al trabajador.
            $usuario->Rol = trim($cargoUsuario);

            $usuario->Estado = 1;

            // Valores iniciales para recuperación de acceso y control de intentos.
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
     * Abre el modal para editar datos permitidos del usuario.
     * Por seguridad, desde aquí no se cambia la contraseña.
     */
    public function abrirModalEditarUsuario(int $idUsuario): void
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $usuario = DB::table('usuario as u')
            ->join('persona as p', 'u.Id_Persona', '=', 'p.Id_Persona')
            ->leftJoin('trabajador as t', 'p.Id_Persona', '=', 't.Id_Persona')
            ->leftJoin('cargo as c', 't.Id_Cargo', '=', 'c.Id_Cargo')
            ->where('u.Id_Usuario', $idUsuario)
            ->select(
                'u.Id_Usuario',
                'u.Nombre_Usuario',
                'u.Estado',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido',
                'c.Cargo_Asignado'
            )
            ->first();

        if (! $usuario) {
            $this->mostrarToast('No se encontró el usuario seleccionado.', 'error');
            return;
        }

        $this->idUsuarioEditar = (int) $usuario->Id_Usuario;
        $this->nombreUsuarioEditar = $usuario->Nombre_Usuario;
        $this->estadoEditar = (string) $usuario->Estado;
        $this->cargoEditarNombre = $usuario->Cargo_Asignado ?: 'Sin cargo asignado';

        $this->trabajadorEditarNombre = trim(
            $usuario->Primer_Nombre . ' ' .
            ($usuario->Segundo_Nombre ? $usuario->Segundo_Nombre . ' ' : '') .
            $usuario->Primer_Apellido . ' ' .
            ($usuario->Segundo_Apellido ?? '')
        );

        $this->modalEditarUsuario = true;
    }

    public function cerrarModalEditarUsuario(): void
    {
        $this->modalEditarUsuario = false;
        $this->idUsuarioEditar = 0;
        $this->nombreUsuarioEditar = '';
        $this->trabajadorEditarNombre = '';
        $this->cargoEditarNombre = '';
        $this->estadoEditar = '1';

        $this->resetErrorBag();
        $this->resetValidation();
    }

    /**
     * Actualiza el nombre de usuario y el estado.
     * La regla unique ignora el usuario actual para permitir guardar sin cambiar el nombre.
     */
    public function actualizarUsuario(): void
    {
        $datos = $this->validate(
            [
                'idUsuarioEditar' => 'required|integer|exists:usuario,Id_Usuario',

                'nombreUsuarioEditar' => [
                    'required',
                    'string',
                    'min:4',
                    'max:60',
                    'alpha_dash',
                    Rule::unique('usuario', 'Nombre_Usuario')
                        ->ignore($this->idUsuarioEditar, 'Id_Usuario'),
                ],

                'estadoEditar' => 'required|in:0,1',
            ],
            [
                'nombreUsuarioEditar.required' => 'Debe ingresar el nombre de usuario.',
                'nombreUsuarioEditar.min' => 'El nombre de usuario debe tener al menos 4 caracteres.',
                'nombreUsuarioEditar.max' => 'El nombre de usuario no debe superar los 60 caracteres.',
                'nombreUsuarioEditar.alpha_dash' => 'El nombre de usuario solo puede contener letras, números, guiones y guion bajo.',
                'nombreUsuarioEditar.unique' => 'Este nombre de usuario ya está registrado.',

                'estadoEditar.required' => 'Debe seleccionar el estado del usuario.',
                'estadoEditar.in' => 'El estado seleccionado no es válido.',
            ]
        );

        Usuario::where('Id_Usuario', (int) $datos['idUsuarioEditar'])
            ->update([
                'Nombre_Usuario' => trim($datos['nombreUsuarioEditar']),
                'Estado' => (int) $datos['estadoEditar'],
            ]);

        $this->cerrarModalEditarUsuario();
        $this->cargarUsuarios();

        $this->mostrarToast('Usuario actualizado correctamente.');
    }

    /**
     * Cambia el estado directamente desde la tabla.
     * Si está activo lo inactiva; si está inactivo lo activa.
     */
    public function cambiarEstadoUsuario(int $idUsuario): void
    {
        $usuario = Usuario::find($idUsuario);

        if (! $usuario) {
            $this->mostrarToast('No se encontró el usuario seleccionado.', 'error');
            return;
        }

        $usuario->Estado = (int) $usuario->Estado === 1 ? 0 : 1;
        $usuario->save();

        $this->cargarUsuarios();

        $mensaje = (int) $usuario->Estado === 1
            ? 'Usuario activado correctamente.'
            : 'Usuario inactivado correctamente.';

        $this->mostrarToast($mensaje);
    }

    /**
     * Carga los trabajadores disponibles para crear usuario.
     * Solo muestra trabajadores que aún no tienen usuario asignado.
     */
    protected function cargarTrabajadores(): void
    {
        $this->trabajadores = DB::table('trabajador as t')
            ->join('persona as p', 't.Id_Persona', '=', 'p.Id_Persona')
            ->leftJoin('cargo as c', 't.Id_Cargo', '=', 'c.Id_Cargo')
            ->leftJoin('usuario as u', 'p.Id_Persona', '=', 'u.Id_Persona')
            ->whereNull('u.Id_Usuario')
            ->select(
                'p.Id_Persona',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido',
                'c.Cargo_Asignado'
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
                    'cargo' => $persona->Cargo_Asignado ?: 'Sin cargo asignado',
                ];
            })
            ->toArray();
    }

    protected function cargarCargoTrabajador(): void
    {
        $this->cargoTrabajador = '';

        if ($this->idPersona === '') {
            return;
        }

        foreach ($this->trabajadores as $trabajador) {
            if ((string) $trabajador['id'] === (string) $this->idPersona) {
                $this->cargoTrabajador = $trabajador['cargo'];
                return;
            }
        }
    }

    /**
     * Carga los usuarios registrados para la tabla.
     * La búsqueda incluye trabajador, cargo y nombre de usuario.
     */
    protected function cargarUsuarios(): void
    {
        $busqueda = trim($this->buscar);

        $query = DB::table('usuario as u')
            ->join('persona as p', 'u.Id_Persona', '=', 'p.Id_Persona')
            ->leftJoin('trabajador as t', 'p.Id_Persona', '=', 't.Id_Persona')
            ->leftJoin('cargo as c', 't.Id_Cargo', '=', 'c.Id_Cargo')
            ->select(
                'u.Id_Usuario',
                'u.Nombre_Usuario',
                'u.Estado',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido',
                'c.Cargo_Asignado'
            )
            ->orderByDesc('u.Id_Usuario');

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('u.Nombre_Usuario', 'like', "%{$busqueda}%")
                    ->orWhere('p.Primer_Nombre', 'like', "%{$busqueda}%")
                    ->orWhere('p.Segundo_Nombre', 'like', "%{$busqueda}%")
                    ->orWhere('p.Primer_Apellido', 'like', "%{$busqueda}%")
                    ->orWhere('p.Segundo_Apellido', 'like', "%{$busqueda}%")
                    ->orWhere('c.Cargo_Asignado', 'like', "%{$busqueda}%");
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
                    'id_usuario' => $usuario->Id_Usuario,
                    'trabajador' => $trabajador,
                    'cargo' => $usuario->Cargo_Asignado ?: '—',
                    'username' => $usuario->Nombre_Usuario,
                    'status' => (int) $usuario->Estado === 1 ? 'Activo' : 'Inactivo',
                ];
            })
            ->toArray();
    }

    protected function resetFormularioUsuario(): void
    {
        $this->idPersona = '';
        $this->nombreUsuario = '';
        $this->password = '';
        $this->cargoTrabajador = '';

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
                class="{{ $toastTipo === 'success' ? 'border-[#B7D6F2] bg-[#EAF4FD] text-[#1A2B42]' : 'border-red-200 bg-red-50 text-red-700' }} rounded-2xl border px-4 py-4 shadow-lg"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium">{{ $toastMensaje }}</p>

                    <button
                        type="button"
                        wire:click="cerrarToast"
                        class="text-lg leading-none text-[#5F6B7A] hover:text-[#1A2B42]"
                    >
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

                    <x-select
                        wire:model.live="idPersona"
                        placeholder="Seleccione un trabajador"
                        :options="$trabajadores"
                        option-value="id"
                        option-label="name"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />

                    @error('idPersona')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Cargo del trabajador
                    </label>

                    <x-input
                        wire:model="cargoTrabajador"
                        placeholder="Se carga al seleccionar trabajador"
                        readonly
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Nombre de usuario
                    </label>

                    <x-input
                        wire:model.defer="nombreUsuario"
                        placeholder="Ingrese el nombre de usuario"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />

                    @error('nombreUsuario')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Contraseña
                    </label>

                    <x-password
                        wire:model.defer="password"
                        placeholder="Ingrese la contraseña"
                        clearable
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />

                    @error('password')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <x-slot:actions>
                <x-button
                    label="Guardar usuario"
                    type="submit"
                    class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30"
                />
            </x-slot:actions>
        </x-card>
    </form>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de usuarios</h2>

            <x-input
                wire:model.live.debounce.250ms="buscar"
                placeholder="Buscar usuario"
                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
            />
        </div>

        <x-table
            :headers="$headers"
            :rows="$usuarios"
            no-hover
            show-empty-text
            empty-text="No hay usuarios registrados."
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl [&_tbody_tr]:!bg-white! [&_tbody_tr:nth-child(even)]:!bg-[#F8FBFF] [&_tbody_tr:hover]:!bg-[#EAF4FD] [&_tbody_tr]:!text-[#1A2B42]"
        >
            @scope('cell_status', $usuario)
                <span
                    class="{{ $usuario['status'] === 'Activo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                >
                    {{ $usuario['status'] }}
                </span>
            @endscope

            @scope('actions', $usuario)
                <div class="flex items-center justify-end gap-2">
                    <x-button
                        label="Editar"
                        icon="o-pencil-square"
                        wire:click="abrirModalEditarUsuario({{ $usuario['id_usuario'] }})"
                        class="h-8 min-h-8 border-0 bg-[#2E8BC0] px-3 text-xs text-white hover:bg-[#0B6FE4]"
                    />

                    <x-button
                        :label="$usuario['status'] === 'Activo' ? 'Inactivar' : 'Activar'"
                        :icon="$usuario['status'] === 'Activo' ? 'o-no-symbol' : 'o-check-circle'"
                        wire:click="cambiarEstadoUsuario({{ $usuario['id_usuario'] }})"
                        class="{{ $usuario['status'] === 'Activo' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} h-8 min-h-8 border-0 px-3 text-xs text-white"
                    />
                </div>
            @endscope
        </x-table>
    </x-card>

    <x-modal
        wire:model="modalEditarUsuario"
        class="backdrop-blur-sm"
        box-class="w-full max-w-xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl"
    >
        <div class="mb-5">
            <h3 class="text-2xl font-bold text-[#1A2B42]">Editar usuario</h3>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Modifique el nombre de usuario o el estado de acceso.
            </p>
        </div>

        <div class="space-y-4">
            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Trabajador
                </label>

                <x-input
                    wire:model="trabajadorEditarNombre"
                    readonly
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Cargo
                </label>

                <x-input
                    wire:model="cargoEditarNombre"
                    readonly
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Nombre de usuario
                </label>

                <x-input
                    wire:model.defer="nombreUsuarioEditar"
                    placeholder="Ingrese el nombre de usuario"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />

                @error('nombreUsuarioEditar')
                    <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Estado
                </label>

                <x-select
                    wire:model.defer="estadoEditar"
                    :options="[
                        ['id' => '1', 'name' => 'Activo'],
                        ['id' => '0', 'name' => 'Inactivo'],
                    ]"
                    option-value="id"
                    option-label="name"
                    class="w-full rounded-xl border border-[#B8CBE3] bg-white text-[#1A2B42] shadow-sm focus:border-[#0E48A1] focus:ring-2 focus:ring-[#0E48A1]/20"
                />

                @error('estadoEditar')
                    <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <x-slot:actions>
                <x-button
                    label="Cancelar"
                    type="button"
                    wire:click="cerrarModalEditarUsuario"
                    class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]"
                />

                <x-button
                    label="Guardar cambios"
                    type="button"
                    wire:click="actualizarUsuario"
                    class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]"
                />
            </x-slot:actions>
        </div>
    </x-modal>
</div>