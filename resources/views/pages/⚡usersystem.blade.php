<?php

use App\Models\Trabajador;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component
{
    use WithPagination;
    public string $idTrabajador = '';
    public string $nombreUsuario = '';
    public string $correo = '';
    public string $password = '';
    public string $cargoTrabajador = '';

    public string $buscar = '';

    public bool $modalEditarUsuario = false;
    public int $idUsuarioEditar = 0;
    public string $nombreUsuarioEditar = '';
    public string $correoEditar = '';
    public string $trabajadorEditarNombre = '';
    public string $cargoEditarNombre = '';
    public string $estadoEditar = '1';

    public string $toastMensaje = '';
    public string $toastTipo = 'success';
    public bool $mostrarToast = false;

    public array $trabajadores = [];

    public array $headers = [
        ['key' => 'trabajador', 'label' => 'Trabajador'],
        ['key' => 'cargo', 'label' => 'Cargo'],
        ['key' => 'username', 'label' => 'Nombre usuario'],
        ['key' => 'correo', 'label' => 'Correo'],
        ['key' => 'status', 'label' => 'Estado'],
    ];

    public function mount(): void
    {
        $this->cargarTrabajadores();
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedIdTrabajador(): void
    {
        $this->cargarCargoTrabajador();
    }

    public function guardarUsuario(): void
    {
        $this->resetErrorBag();

        $datos = $this->validate(
            [
                'idTrabajador' => [
                    'required',
                    'integer',
                    'exists:trabajador,Id_Trabajador',
                    Rule::unique('usuario', 'Id_Trabajador'),
                ],

                'nombreUsuario' => [
                    'required',
                    'string',
                    'min:4',
                    'max:60',
                    'alpha_dash',
                    Rule::unique('usuario', 'Nombre_Usuario'),
                ],

                'correo' => [
                    'required',
                    'email',
                    'max:150',
                    Rule::unique('usuario', 'Correo'),
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'max:255',
                    'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
                ],
            ],
            [
                'idTrabajador.required' => 'Debe seleccionar un trabajador.',
                'idTrabajador.exists' => 'El trabajador seleccionado no existe.',
                'idTrabajador.unique' => 'Este trabajador ya tiene un usuario asignado.',

                'nombreUsuario.required' => 'Debe ingresar el nombre de usuario.',
                'nombreUsuario.min' => 'El nombre de usuario debe tener al menos 4 caracteres.',
                'nombreUsuario.max' => 'El nombre de usuario no debe superar los 60 caracteres.',
                'nombreUsuario.alpha_dash' => 'El nombre de usuario solo puede contener letras, números, guiones y guion bajo.',
                'nombreUsuario.unique' => 'Este nombre de usuario ya está registrado.',

                'correo.required' => 'Debe ingresar el correo del usuario.',
                'correo.email' => 'Debe ingresar un correo válido.',
                'correo.max' => 'El correo no debe superar los 150 caracteres.',
                'correo.unique' => 'Este correo ya está registrado.',

                'password.required' => 'Debe ingresar una contraseña.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.max' => 'La contraseña no debe superar los 255 caracteres.',
                'password.regex' => 'La contraseña debe incluir letras y números.',
            ]
        );

        $trabajador = Trabajador::query()
            ->with(['persona', 'cargo', 'usuario'])
            ->find((int) $datos['idTrabajador']);

        if (! $trabajador) {
            throw ValidationException::withMessages([
                'idTrabajador' => 'El trabajador seleccionado no existe.',
            ]);
        }

        if ($trabajador->usuario) {
            throw ValidationException::withMessages([
                'idTrabajador' => 'Este trabajador ya tiene un usuario asignado.',
            ]);
        }

        if (! $trabajador->cargo) {
            throw ValidationException::withMessages([
                'idTrabajador' => 'El trabajador seleccionado no tiene cargo asignado.',
            ]);
        }

        Usuario::query()->create([
            'Id_Trabajador' => (int) $datos['idTrabajador'],
            'Nombre_Usuario' => trim($datos['nombreUsuario']),
            'Correo' => trim($datos['correo']),
            'Contraseña_Usuario' => Hash::make($datos['password']),
            'Estado' => 1,
            'Token_Recuperacion' => null,
            'Fecha_Recuperacion' => null,
            'Intentos_Fallidos' => 0,
        ]);

        $this->resetFormularioUsuario();
        $this->cargarTrabajadores();

        $this->mostrarToast('Usuario guardado correctamente.');
    }

    public function abrirModalEditarUsuario(int $idUsuario): void
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $usuario = Usuario::query()
            ->with(['trabajador.persona', 'trabajador.cargo'])
            ->find($idUsuario);

        if (! $usuario) {
            $this->mostrarToast('No se encontró el usuario seleccionado.', 'error');
            return;
        }

        $this->idUsuarioEditar = (int) $usuario->Id_Usuario;
        $this->nombreUsuarioEditar = $usuario->Nombre_Usuario;
        $this->correoEditar = $usuario->Correo ?? '';
        $this->estadoEditar = (string) $usuario->Estado;

        $this->trabajadorEditarNombre = $this->nombreTrabajador($usuario->trabajador);
        $this->cargoEditarNombre = $usuario->trabajador?->cargo?->Cargo_Asignado ?: 'Sin cargo asignado';

        $this->modalEditarUsuario = true;
    }

    public function cerrarModalEditarUsuario(): void
    {
        $this->modalEditarUsuario = false;
        $this->idUsuarioEditar = 0;
        $this->nombreUsuarioEditar = '';
        $this->correoEditar = '';
        $this->trabajadorEditarNombre = '';
        $this->cargoEditarNombre = '';
        $this->estadoEditar = '1';

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function actualizarUsuario(): void
    {
        $datos = $this->validate(
            [
                'idUsuarioEditar' => [
                    'required',
                    'integer',
                    'exists:usuario,Id_Usuario',
                ],

                'nombreUsuarioEditar' => [
                    'required',
                    'string',
                    'min:4',
                    'max:60',
                    'alpha_dash',
                    Rule::unique('usuario', 'Nombre_Usuario')
                        ->ignore($this->idUsuarioEditar, 'Id_Usuario'),
                ],

                'correoEditar' => [
                    'required',
                    'email',
                    'max:150',
                    Rule::unique('usuario', 'Correo')
                        ->ignore($this->idUsuarioEditar, 'Id_Usuario'),
                ],

                'estadoEditar' => [
                    'required',
                    'in:0,1',
                ],
            ],
            [
                'nombreUsuarioEditar.required' => 'Debe ingresar el nombre de usuario.',
                'nombreUsuarioEditar.min' => 'El nombre de usuario debe tener al menos 4 caracteres.',
                'nombreUsuarioEditar.max' => 'El nombre de usuario no debe superar los 60 caracteres.',
                'nombreUsuarioEditar.alpha_dash' => 'El nombre de usuario solo puede contener letras, números, guiones y guion bajo.',
                'nombreUsuarioEditar.unique' => 'Este nombre de usuario ya está registrado.',

                'correoEditar.required' => 'Debe ingresar el correo del usuario.',
                'correoEditar.email' => 'Debe ingresar un correo válido.',
                'correoEditar.max' => 'El correo no debe superar los 150 caracteres.',
                'correoEditar.unique' => 'Este correo ya está registrado.',

                'estadoEditar.required' => 'Debe seleccionar el estado del usuario.',
                'estadoEditar.in' => 'El estado seleccionado no es válido.',
            ]
        );

        $usuario = Usuario::query()->find((int) $datos['idUsuarioEditar']);

        if (! $usuario) {
            $this->mostrarToast('No se encontró el usuario seleccionado.', 'error');
            return;
        }

        $usuario->update([
            'Nombre_Usuario' => trim($datos['nombreUsuarioEditar']),
            'Correo' => trim($datos['correoEditar']),
            'Estado' => (int) $datos['estadoEditar'],
        ]);

        $this->cerrarModalEditarUsuario();

        $this->mostrarToast('Usuario actualizado correctamente.');
    }

    public function cambiarEstadoUsuario(int $idUsuario): void
    {
        $usuario = Usuario::query()->find($idUsuario);

        if (! $usuario) {
            $this->mostrarToast('No se encontró el usuario seleccionado.', 'error');
            return;
        }

        $usuario->Estado = (int) $usuario->Estado === 1 ? 0 : 1;
        $usuario->save();

        $mensaje = (int) $usuario->Estado === 1
            ? 'Usuario activado correctamente.'
            : 'Usuario inactivado correctamente.';

        $this->mostrarToast($mensaje);
    }

    protected function cargarTrabajadores(): void
    {
        $this->trabajadores = Trabajador::query()
            ->with(['persona', 'cargo'])
            ->where('Estado', 1)
            ->whereDoesntHave('usuario')
            ->get()
            ->sortBy(fn ($trabajador) => $this->nombreTrabajador($trabajador))
            ->values()
            ->map(function ($trabajador) {
                $cargo = $trabajador->cargo?->Cargo_Asignado;

                return [
                    'id' => $trabajador->Id_Trabajador,
                    'name' => $this->nombreTrabajador($trabajador),
                    'cargo' => $cargo ?: 'Sin cargo asignado',
                    'disabled' => ! $cargo,
                ];
            })
            ->toArray();
    }

    protected function cargarCargoTrabajador(): void
    {
        $this->cargoTrabajador = '';

        if ($this->idTrabajador === '') {
            return;
        }

        foreach ($this->trabajadores as $trabajador) {
            if ((string) $trabajador['id'] === (string) $this->idTrabajador) {
                $this->cargoTrabajador = $trabajador['cargo'];
                return;
            }
        }
    }

    public function usuarios(): LengthAwarePaginator
    {
        $busqueda = trim($this->buscar);

        return Usuario::query()
            ->with(['trabajador.persona', 'trabajador.cargo'])
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where('Nombre_Usuario', 'like', "%{$busqueda}%")
                        ->orWhere('Correo', 'like', "%{$busqueda}%")
                        ->orWhereHas('trabajador.persona', function ($personaQuery) use ($busqueda) {
                            $personaQuery->where('Primer_Nombre', 'like', "%{$busqueda}%")
                                ->orWhere('Segundo_Nombre', 'like', "%{$busqueda}%")
                                ->orWhere('Primer_Apellido', 'like', "%{$busqueda}%")
                                ->orWhere('Segundo_Apellido', 'like', "%{$busqueda}%");
                        })
                        ->orWhereHas('trabajador.cargo', function ($cargoQuery) use ($busqueda) {
                            $cargoQuery->where('Cargo_Asignado', 'like', "%{$busqueda}%");
                        });
                });
            })
            ->orderByDesc('Id_Usuario')
            ->paginate(10)
            ->through(function (Usuario $usuario) {
                return [
                    'id_usuario' => $usuario->Id_Usuario,
                    'trabajador' => $this->nombreTrabajador($usuario->trabajador),
                    'cargo' => $usuario->trabajador?->cargo?->Cargo_Asignado ?: '—',
                    'username' => $usuario->Nombre_Usuario,
                    'correo' => $usuario->Correo ?: '—',
                    'status' => (int) $usuario->Estado === 1 ? 'Activo' : 'Inactivo',
                ];
            });
    }

    protected function nombreTrabajador($trabajador): string
    {
        if (! $trabajador || ! $trabajador->persona) {
            return 'Sin trabajador vinculado';
        }

        $persona = $trabajador->persona;

        $nombre = collect([
            $persona->Primer_Nombre,
            $persona->Segundo_Nombre,
            $persona->Primer_Apellido,
            $persona->Segundo_Apellido,
        ])
            ->filter(fn ($valor) => filled($valor))
            ->implode(' ');

        return $nombre !== '' ? $nombre : 'Sin nombre registrado';
    }

    protected function resetFormularioUsuario(): void
    {
        $this->idTrabajador = '';
        $this->nombreUsuario = '';
        $this->correo = '';
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
                class="{{ $toastTipo === 'success'
                    ? 'border-[#B7D6F2] bg-[#EAF4FD] text-[#1A2B42]'
                    : 'border-red-200 bg-red-50 text-red-700' }} rounded-2xl border px-4 py-4 shadow-lg">
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
                        wire:model.live="idTrabajador"
                        placeholder="Seleccione un trabajador"
                        :options="$trabajadores"
                        option-value="id"
                        option-label="name"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />

                    @error('idTrabajador')
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
                        Correo electrónico
                    </label>

                    <x-input
                        wire:model.defer="correo"
                        type="email"
                        placeholder="Ingrese el correo del usuario"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />

                    @error('correo')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="md:col-span-2">
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
                    icon="o-check-circle"
                    class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30"
                />
            </x-slot:actions>
        </x-card>
    </form>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4 space-y-3">
            <div>
                <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de usuarios</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Usuarios creados a partir de trabajadores registrados.
                </p>
            </div>

            <x-input
                wire:model.live.debounce.250ms="buscar"
                placeholder="Buscar por trabajador, cargo, usuario o correo"
                icon="o-magnifying-glass"
                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
            />
        </div>

        @php
            $usuarios = $this->usuarios();
        @endphp

        <x-table
            :headers="$headers"
            :rows="$usuarios"
            no-hover
            with-pagination
            show-empty-text
            empty-text="No hay usuarios registrados."
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl [&_tbody_tr]:bg-white! [&_tbody_tr:nth-child(even)]:bg-[#F8FBFF]! [&_tbody_tr:hover]:bg-[#EAF4FD]! [&_tbody_tr]:text-[#1A2B42]!"
        >
            @scope('cell_status', $usuario)
                <span
                    class="{{ $usuario['status'] === 'Activo'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-red-100 text-red-700' }} inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                    {{ $usuario['status'] }}
                </span>
            @endscope

            @scope('actions', $usuario)
                <div class="flex items-center justify-end gap-2">
                    <x-button
                        icon="o-pencil-square"
                        wire:click="abrirModalEditarUsuario({{ $usuario['id_usuario'] }})"
                        title="Editar usuario"
                        aria-label="Editar usuario"
                        class="btn-sm h-10 w-10 min-h-0 rounded-xl border border-[#0B6FE4] bg-[#0B6FE4] p-0 text-white shadow-sm hover:bg-[#2E8BC0] hover:text-white"
                    />

                    <x-button
                        :label="$usuario['status'] === 'Activo' ? 'Inactivar' : 'Activar'"
                        :icon="$usuario['status'] === 'Activo' ? 'o-no-symbol' : 'o-check-circle'"
                        wire:click="cambiarEstadoUsuario({{ $usuario['id_usuario'] }})"
                        class="{{ $usuario['status'] === 'Activo'
                            ? 'bg-red-600 hover:bg-red-700'
                            : 'bg-green-600 hover:bg-green-700' }} h-8 min-h-8 border-0 px-3 text-xs text-white"
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
                Modifique el nombre de usuario, correo o estado de acceso.
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
                    Correo electrónico
                </label>

                <x-input
                    wire:model.defer="correoEditar"
                    type="email"
                    placeholder="Ingrese el correo del usuario"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />

                @error('correoEditar')
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
                    icon="o-check"
                    wire:click="actualizarUsuario"
                    class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]"
                />
            </x-slot:actions>
        </div>
    </x-modal>
</div>