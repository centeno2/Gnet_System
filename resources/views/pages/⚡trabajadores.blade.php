<?php

use App\Models\Trabajador;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $nombres = '';
    public string $apellidos = '';
    public string $cedula = '';
    public string $correo = '';
    public string $telefono = '';
    public string $direccion = '';
    public string $fechaIngreso = '';
    public string $salario = '';

    public ?int $cargoId = null;

    public bool $modalCargo = false;
    public string $nuevoCargo = '';

    public bool $cedulaExiste = false;
    public bool $telefonoExiste = false;
    public bool $correoExiste = false;

    public array $cargos = [];
    public array $trabajadores = [];

    public array $headers = [
        ['key' => 'nombre_completo', 'label' => 'Nombre completo'],
        ['key' => 'cargo', 'label' => 'Cargo'],
        ['key' => 'cedula', 'label' => 'Cédula'],
        ['key' => 'telefono', 'label' => 'Teléfono'],
        ['key' => 'direccion', 'label' => 'Dirección'],
        ['key' => 'fecha_ingreso', 'label' => 'Fecha de ingreso'],
        ['key' => 'salario', 'label' => 'Salario'],
        ['key' => 'estado', 'label' => 'Estado'],
    ];

    public function mount(): void
    {
        $this->fechaIngreso = now()->format('Y-m-d');

        $this->cargarCargos();
        $this->cargarTrabajadores();
    }

    protected function rules(): array
    {
        return [
            'nombres' => [
                'required',
                'string',
                'max:100',
                'regex:/^\pL+(?:\s+\pL+)?$/u',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
                'regex:/^\pL+(?:\s+\pL+)?$/u',
            ],

            'cedula' => [
                'required',
                'string',
                'size:14',
                'regex:/^\d{13}[A-HJ-NPR-TVXY]$/',
                Rule::unique('trabajador', 'Cedula'),
                function ($attribute, $value, $fail) {
                    if (! $this->fechaCedulaValida($value)) {
                        $fail('Los dígitos del 4 al 9 de la cédula deben representar una fecha válida.');
                    }
                },
            ],

            'telefono' => [
                'required',
                'regex:/^\d{8}$/',
                Rule::unique('persona', 'Telefono'),
            ],

            'correo' => [
                'nullable',
                'email',
                'max:120',
                Rule::unique('persona', 'Correo'),
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:200',
            ],

            'fechaIngreso' => [
                'required',
                'date',
            ],

            'cargoId' => [
                'required',
                'integer',
                'exists:cargo,Id_Cargo',
            ],

            'salario' => [
                'required',
                'regex:/^\d+(\.\d{1,2})?$/',
                'numeric',
                'min:0',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'nombres.required' => 'Debe ingresar al menos un nombre.',
            'nombres.regex' => 'Solo se permiten letras y máximo 2 nombres.',
            'nombres.max' => 'Los nombres no deben exceder los 100 caracteres.',

            'apellidos.required' => 'Debe ingresar al menos un apellido.',
            'apellidos.regex' => 'Solo se permiten letras y máximo 2 apellidos.',
            'apellidos.max' => 'Los apellidos no deben exceder los 100 caracteres.',

            'cedula.required' => 'Debe ingresar la cédula.',
            'cedula.size' => 'La cédula debe tener exactamente 14 caracteres.',
            'cedula.regex' => 'La cédula debe tener 13 números y una letra mayúscula válida al final. No se permiten las letras I, O, Ñ, Q, U, W, Z.',
            'cedula.unique' => 'Esta cédula ya está registrada.',

            'telefono.required' => 'Debe ingresar el teléfono.',
            'telefono.regex' => 'El teléfono debe contener exactamente 8 dígitos.',
            'telefono.unique' => 'Este teléfono ya está registrado.',

            'correo.email' => 'Ingrese un correo válido.',
            'correo.max' => 'El correo no debe exceder los 120 caracteres.',
            'correo.unique' => 'Este correo ya está registrado.',

            'direccion.max' => 'La dirección no debe exceder los 200 caracteres.',

            'fechaIngreso.required' => 'Debe ingresar la fecha de ingreso.',
            'fechaIngreso.date' => 'Ingrese una fecha válida.',

            'cargoId.required' => 'Debe seleccionar un cargo.',
            'cargoId.exists' => 'El cargo seleccionado no existe.',

            'salario.required' => 'Debe ingresar el salario.',
            'salario.regex' => 'El salario solo puede contener números positivos y máximo 2 decimales.',
            'salario.numeric' => 'El salario debe ser un número válido.',
            'salario.min' => 'El salario no puede ser negativo.',

            'nuevoCargo.required' => 'Debe ingresar el nombre del cargo.',
            'nuevoCargo.unique' => 'Este cargo ya está registrado.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'correo' => 'correo',
            'telefono' => 'teléfono',
            'direccion' => 'dirección',
            'cedula' => 'cédula',
            'fechaIngreso' => 'fecha de ingreso',
            'cargoId' => 'cargo',
            'salario' => 'salario',
            'nuevoCargo' => 'cargo',
        ];
    }

    public function updatedCedula(): void
    {
        $this->cedula = strtoupper(trim($this->cedula));
        $this->verificarCedulaExistente();
    }

    public function updatedTelefono(): void
    {
        $this->telefono = trim($this->telefono);
        $this->verificarTelefonoExistente();
    }

    public function updatedCorreo(): void
    {
        $this->correo = trim($this->correo);
        $this->verificarCorreoExistente();
    }

    protected function verificarCedulaExistente(): void
    {
        $this->cedulaExiste = false;

        if (strlen($this->cedula) !== 14) {
            return;
        }

        $this->cedulaExiste = DB::table('trabajador')
            ->where('Cedula', $this->cedula)
            ->exists();
    }

    protected function verificarTelefonoExistente(): void
    {
        $this->telefonoExiste = false;

        if (! preg_match('/^\d{8}$/', $this->telefono)) {
            return;
        }

        $this->telefonoExiste = DB::table('persona')
            ->where('Telefono', $this->telefono)
            ->exists();
    }

    protected function verificarCorreoExistente(): void
    {
        $this->correoExiste = false;

        if ($this->correo === '' || ! filter_var($this->correo, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $this->correoExiste = DB::table('persona')
            ->where('Correo', $this->correo)
            ->exists();
    }

    protected function existenDatosDuplicados(): bool
    {
        $this->verificarCedulaExistente();
        $this->verificarTelefonoExistente();
        $this->verificarCorreoExistente();

        return $this->cedulaExiste || $this->telefonoExiste || $this->correoExiste;
    }

    protected function fechaCedulaValida(string $cedula): bool
    {
        $cedula = strtoupper(trim($cedula));

        if (! preg_match('/^\d{13}[A-HJ-NPR-TVXY]$/', $cedula)) {
            return false;
        }

        $fecha = substr($cedula, 3, 6);

        $dia = (int) substr($fecha, 0, 2);
        $mes = (int) substr($fecha, 2, 2);
        $anioDosDigitos = (int) substr($fecha, 4, 2);

        return checkdate($mes, $dia, 1900 + $anioDosDigitos)
            || checkdate($mes, $dia, 2000 + $anioDosDigitos);
    }

    protected function separarEnDosColumnas(string $valor): array
    {
        $valor = preg_replace('/\s+/', ' ', trim($valor));

        $partes = explode(' ', $valor, 2);

        return [
            $partes[0] ?? '',
            $partes[1] ?? null,
        ];
    }

    public function cargarCargos(): void
    {
        $this->cargos = DB::table('cargo')
            ->select('Id_Cargo as id', 'Cargo_Asignado as name')
            ->orderBy('Cargo_Asignado')
            ->get()
            ->map(fn ($cargo) => [
                'id' => (int) $cargo->id,
                'name' => $cargo->name,
            ])
            ->toArray();
    }

    public function cargarTrabajadores(): void
    {
        $this->trabajadores = DB::table('trabajador')
            ->join('persona', 'persona.Id_Persona', '=', 'trabajador.Id_Persona')
            ->leftJoin('cargo', 'cargo.Id_Cargo', '=', 'trabajador.Id_Cargo')
            ->select(
                'trabajador.Id_Trabajador',
                'trabajador.Cedula',
                'trabajador.Fecha_Ingreso',
                'trabajador.Salario',
                'trabajador.Estado',
                'persona.Primer_Nombre',
                'persona.Segundo_Nombre',
                'persona.Primer_Apellido',
                'persona.Segundo_Apellido',
                'persona.Telefono',
                'persona.Direccion',
                'cargo.Cargo_Asignado'
            )
            ->orderByDesc('trabajador.Id_Trabajador')
            ->get()
            ->map(function ($trabajador) {
                $nombreCompleto = trim(collect([
                    $trabajador->Primer_Nombre,
                    $trabajador->Segundo_Nombre,
                    $trabajador->Primer_Apellido,
                    $trabajador->Segundo_Apellido,
                ])->filter()->implode(' '));

                return [
                    'id' => $trabajador->Id_Trabajador,
                    'nombre_completo' => $nombreCompleto,
                    'cargo' => $trabajador->Cargo_Asignado ?? 'Sin cargo',
                    'cedula' => $trabajador->Cedula,
                    'telefono' => $trabajador->Telefono,
                    'direccion' => $trabajador->Direccion ?? 'No registrada',
                    'fecha_ingreso' => $trabajador->Fecha_Ingreso
                        ? date('d/m/Y', strtotime($trabajador->Fecha_Ingreso))
                        : 'No registrada',
                    'salario' => $trabajador->Salario !== null
                        ? 'C$ ' . number_format((float) $trabajador->Salario, 2)
                        : 'C$ 0.00',
                    'estado' => ((int) $trabajador->Estado === 1) ? 'Activo' : 'Inactivo',
                ];
            })
            ->toArray();
    }

    public function guardarTrabajador(): void
    {
        $this->nombres = preg_replace('/\s+/', ' ', trim($this->nombres));
        $this->apellidos = preg_replace('/\s+/', ' ', trim($this->apellidos));
        $this->cedula = strtoupper(trim($this->cedula));
        $this->correo = trim($this->correo);
        $this->telefono = trim($this->telefono);
        $this->direccion = trim($this->direccion);
        $this->salario = trim($this->salario);

        $this->validate();

        if ($this->existenDatosDuplicados()) {
            return;
        }

        [$primerNombre, $segundoNombre] = $this->separarEnDosColumnas($this->nombres);
        [$primerApellido, $segundoApellido] = $this->separarEnDosColumnas($this->apellidos);

        DB::transaction(function () use ($primerNombre, $segundoNombre, $primerApellido, $segundoApellido) {
            $personaId = DB::table('persona')->insertGetId([
                'Primer_Nombre' => $primerNombre,
                'Segundo_Nombre' => $segundoNombre,
                'Primer_Apellido' => $primerApellido,
                'Segundo_Apellido' => $segundoApellido,
                'Correo' => $this->correo !== '' ? $this->correo : null,
                'Direccion' => $this->direccion !== '' ? $this->direccion : null,
                'Telefono' => $this->telefono,
            ], 'Id_Persona');

            Trabajador::create([
                'Id_Persona' => $personaId,
                'Fecha_Ingreso' => $this->fechaIngreso,
                'Estado' => 1,
                'Id_Cargo' => $this->cargoId,
                'Cedula' => $this->cedula,
                'Salario' => $this->salario,
            ]);
        });

        $this->limpiarFormulario();
        $this->cargarTrabajadores();

        session()->flash('success', 'Trabajador registrado correctamente.');
    }

    public function guardarCargo(): void
    {
        $this->nuevoCargo = preg_replace('/\s+/', ' ', trim($this->nuevoCargo));

        $this->validate([
            'nuevoCargo' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cargo', 'Cargo_Asignado'),
            ],
        ]);

        $cargoId = DB::table('cargo')->insertGetId([
            'Cargo_Asignado' => $this->nuevoCargo,
        ], 'Id_Cargo');

        $this->cargoId = (int) $cargoId;
        $this->nuevoCargo = '';
        $this->modalCargo = false;

        $this->cargarCargos();

        session()->flash('success', 'Cargo agregado correctamente.');
    }

    public function limpiarFormulario(): void
    {
        $this->reset([
            'nombres',
            'apellidos',
            'cedula',
            'correo',
            'telefono',
            'direccion',
            'salario',
            'cargoId',
            'cedulaExiste',
            'telefonoExiste',
            'correoExiste',
        ]);

        $this->fechaIngreso = now()->format('Y-m-d');

        $this->resetValidation();
    }
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#1A2B42]">Trabajadores</h1>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Registro y gestión de trabajadores del sistema.
        </p>
    </div>

    @if (session('success'))
        <x-alert
            icon="o-check-circle"
            class="border border-green-200 bg-green-50 text-green-800"
        >
            {{ session('success') }}
        </x-alert>
    @endif

    @if ($cedulaExiste || $telefonoExiste || $correoExiste)
        <x-alert
            icon="o-exclamation-triangle"
            class="border border-yellow-200 bg-yellow-50 text-yellow-800"
        >
            Hay datos que ya existen en la base de datos. Corrígelos antes de guardar.
        </x-alert>
    @endif

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Registrar trabajador</h2>
            <p class="text-base text-[#5F6B7A]">
                Ingrese los datos del trabajador.
            </p>
        </div>

        <x-form wire:submit="guardarTrabajador" no-separator>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Nombres
                    </label>
                    <x-input
                        wire:model="nombres"
                        placeholder="Ejemplo: Juan Carlos"
                        maxlength="100"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('nombres')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Apellidos
                    </label>
                    <x-input
                        wire:model="apellidos"
                        placeholder="Ejemplo: Pérez López"
                        maxlength="100"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('apellidos')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Cédula
                    </label>
                    <x-input
                        wire:model.live.debounce.300ms="cedula"
                        placeholder="Ejemplo: 0012512870000A"
                        maxlength="14"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @if ($cedulaExiste)
                        <span class="mt-1 block text-sm font-semibold text-red-600">
                            Esta cédula ya está registrada.
                        </span>
                    @endif
                    @error('cedula')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Teléfono
                    </label>
                    <x-input
                        wire:model.live.debounce.300ms="telefono"
                        placeholder="Ejemplo: 88887777"
                        maxlength="8"
                        inputmode="numeric"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @if ($telefonoExiste)
                        <span class="mt-1 block text-sm font-semibold text-red-600">
                            Este teléfono ya está registrado.
                        </span>
                    @endif
                    @error('telefono')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Correo
                    </label>
                    <x-input
                        wire:model.live.debounce.300ms="correo"
                        type="email"
                        placeholder="Ejemplo: trabajador@gnet.com"
                        maxlength="120"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @if ($correoExiste)
                        <span class="mt-1 block text-sm font-semibold text-red-600">
                            Este correo ya está registrado.
                        </span>
                    @endif
                    @error('correo')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Fecha de ingreso
                    </label>
                    <x-datetime
                        wire:model="fechaIngreso"
                        type="date"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                    />
                    @error('fechaIngreso')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Dirección
                    </label>
                    <x-input
                        wire:model="direccion"
                        placeholder="Ingrese la dirección"
                        maxlength="200"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('direccion')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Cargo
                    </label>

                    <div class="flex items-start gap-2">
                        <div class="w-full">
                            <x-select
                                wire:model="cargoId"
                                placeholder="Seleccione un cargo"
                                :options="$cargos"
                                option-value="id"
                                option-label="name"
                                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                            />
                        </div>

                        <x-button
                            icon="o-plus"
                            type="button"
                            wire:click="$set('modalCargo', true)"
                            class="mt-px h-12 min-h-12 rounded-xl border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]"
                        />
                    </div>

                    @error('cargoId')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Salario
                    </label>
                    <x-input
                        wire:model="salario"
                        prefix="C$"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('salario')
                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <x-slot:actions>
                <div class="flex w-full justify-end gap-3 pt-2">
                    <x-button
                        label="Limpiar"
                        type="button"
                        wire:click="limpiarFormulario"
                        class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#EAF2FB]"
                    />

                    <x-button
                        label="Guardar trabajador"
                        type="submit"
                        spinner="guardarTrabajador"
                        :disabled="$cedulaExiste || $telefonoExiste || $correoExiste"
                        class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4] disabled:cursor-not-allowed disabled:opacity-50"
                    />
                </div>
            </x-slot:actions>
        </x-form>
    </x-card>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de trabajadores</h2>
            <p class="text-base text-[#5F6B7A]">
                Visualice y gestione los trabajadores registrados en el sistema.
            </p>
        </div>

        <x-table
            :headers="$headers"
            :rows="$trabajadores"
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
        />
    </x-card>

    <x-modal
        wire:model="modalCargo"
        box-class="rounded-2xl border border-[#D7E4F3] bg-white"
    >
        <div class="mb-5">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Agregar cargo</h2>
            <p class="text-base text-[#5F6B7A]">
                Registre un nuevo cargo para los trabajadores.
            </p>
        </div>

        <x-form wire:submit="guardarCargo" no-separator>
            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Nombre del cargo
                </label>
                <x-input
                    wire:model="nuevoCargo"
                    placeholder="Ejemplo: Técnico"
                    maxlength="100"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
                @error('nuevoCargo')
                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>

            <x-slot:actions>
                <x-button
                    label="Cancelar"
                    type="button"
                    wire:click="$set('modalCargo', false)"
                    class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#EAF2FB]"
                />

                <x-button
                    label="Guardar cargo"
                    type="submit"
                    spinner="guardarCargo"
                    class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]"
                />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>