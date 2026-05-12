<?php

use App\Models\Cargo;
use App\Models\Persona;
use App\Models\Trabajador;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $nombres = '';
    public string $apellidos = '';
    public string $cedula = '';
    public string $telefono = '';
    public string $direccion = '';
    public string $fechaIngreso = '';
    public string $salario = '';
    public string $estado = '1';

    public ?int $cargoId = null;
    public ?int $trabajadorEditandoId = null;
    public ?int $personaEditandoId = null;

    public string $cedulaOriginal = '';
    public string $telefonoOriginal = '';

    public bool $modalCargo = false;
    public string $nuevoCargo = '';

    public bool $modalConfirmarPersonaExistente = false;

    public bool $cedulaExiste = false;
    public bool $personaExiste = false;
    public bool $personaYaEsTrabajador = false;

    public ?int $personaExistenteId = null;
    public ?string $personaExistenteNombre = null;

    public array $cargos = [];
    public array $trabajadores = [];

    public array $estados = [
        ['id' => '1', 'name' => 'Activo'],
        ['id' => '0', 'name' => 'Inactivo'],
    ];

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
        $modoEdicion = $this->modoEdicion();
        $personaNueva = ! $this->personaExiste && ! $modoEdicion;
        $cedulaCambio = $this->cedulaCambio();
        $telefonoCambio = $this->telefonoCambio();

        $cedulaRules = [
            'required',
            'string',
            'max:20',
        ];

        if (! $modoEdicion || $cedulaCambio) {
            $cedulaRules[] = 'size:14';
            $cedulaRules[] = 'regex:/^\d{13}[A-HJ-NPR-TVXY]$/';
            $cedulaRules[] = Rule::unique('trabajador', 'Cedula')
                ->ignore($this->trabajadorEditandoId, 'Id_Trabajador');

            $cedulaRules[] = function ($attribute, $value, $fail) {
                if (! $this->fechaCedulaValida($value)) {
                    $fail('Los dígitos del 4 al 9 de la cédula deben representar una fecha válida.');
                }
            };
        }

        $telefonoRules = [
            'required',
            'string',
            'max:30',
        ];

        if (! $modoEdicion || $telefonoCambio) {
            $telefonoRules[] = 'regex:/^\d{8}$/';

            if ($modoEdicion) {
                if ($this->personaEditandoId) {
                    $telefonoRules[] = Rule::unique('persona', 'Telefono')
                        ->ignore($this->personaEditandoId, 'Id_Persona');
                } else {
                    $telefonoRules[] = Rule::unique('persona', 'Telefono');
                }
            } elseif (! $this->personaExiste) {
                $telefonoRules[] = Rule::unique('persona', 'Telefono');
            }
        }

        return [
            'nombres' => [
                Rule::requiredIf($personaNueva || $modoEdicion),
                'nullable',
                'string',
                'max:100',
                'regex:/^\pL+(?:\s+\pL+)?$/u',
            ],

            'apellidos' => [
                Rule::requiredIf($personaNueva || $modoEdicion),
                'nullable',
                'string',
                'max:100',
                'regex:/^\pL+(?:\s+\pL+)?$/u',
            ],

            'cedula' => $cedulaRules,

            'telefono' => $telefonoRules,

            'direccion' => [
                'nullable',
                'string',
                'max:200',
            ],

            'fechaIngreso' => [
                'required',
                'date',
                'before_or_equal:today',
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

            'estado' => [
                'required',
                Rule::in(['0', '1', 0, 1]),
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
            'cedula.unique' => 'Esta cédula ya está registrada en otro trabajador.',

            'telefono.required' => 'Debe ingresar el teléfono.',
            'telefono.regex' => 'El teléfono debe contener exactamente 8 dígitos.',
            'telefono.unique' => 'Este teléfono ya pertenece a otra persona registrada.',

            'direccion.max' => 'La dirección no debe exceder los 200 caracteres.',

            'fechaIngreso.required' => 'Debe ingresar la fecha de ingreso.',
            'fechaIngreso.date' => 'Ingrese una fecha válida.',
            'fechaIngreso.before_or_equal' => 'La fecha de ingreso no puede ser futura.',

            'cargoId.required' => 'Debe seleccionar un cargo.',
            'cargoId.exists' => 'El cargo seleccionado no existe.',

            'salario.required' => 'Debe ingresar el salario.',
            'salario.regex' => 'El salario solo puede contener números positivos y máximo 2 decimales.',
            'salario.numeric' => 'El salario debe ser un número válido.',
            'salario.min' => 'El salario no puede ser negativo.',

            'estado.required' => 'Debe seleccionar el estado del trabajador.',
            'estado.in' => 'El estado seleccionado no es válido.',

            'nuevoCargo.required' => 'Debe ingresar el nombre del cargo.',
            'nuevoCargo.unique' => 'Este cargo ya está registrado.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'telefono' => 'teléfono',
            'direccion' => 'dirección',
            'cedula' => 'cédula',
            'fechaIngreso' => 'fecha de ingreso',
            'cargoId' => 'cargo',
            'salario' => 'salario',
            'estado' => 'estado',
            'nuevoCargo' => 'cargo',
        ];
    }

    protected function modoEdicion(): bool
    {
        return $this->trabajadorEditandoId !== null;
    }

    protected function cedulaCambio(): bool
    {
        return strtoupper(trim($this->cedula)) !== strtoupper(trim($this->cedulaOriginal));
    }

    protected function telefonoCambio(): bool
    {
        $telefonoActual = preg_replace('/\D+/', '', trim($this->telefono));
        $telefonoOriginal = preg_replace('/\D+/', '', trim($this->telefonoOriginal));

        return $telefonoActual !== $telefonoOriginal;
    }

    public function updatedCedula(): void
    {
        $this->cedula = strtoupper(trim($this->cedula));

        if ($this->modoEdicion() && ! $this->cedulaCambio()) {
            $this->cedulaExiste = false;
            $this->resetErrorBag('cedula');
            return;
        }

        $this->verificarCedulaExistente();
    }

    public function updatedTelefono(): void
    {
        $this->telefono = preg_replace('/\D+/', '', trim($this->telefono));

        if ($this->modoEdicion()) {
            $this->limpiarPersonaEncontrada();

            if (! $this->telefonoCambio()) {
                $this->resetErrorBag('telefono');
            }

            return;
        }

        $this->buscarPersonaPorTelefono();
    }

    protected function normalizarFormulario(): void
    {
        $this->nombres = preg_replace('/\s+/', ' ', trim($this->nombres));
        $this->apellidos = preg_replace('/\s+/', ' ', trim($this->apellidos));
        $this->cedula = strtoupper(trim($this->cedula));
        $this->telefono = preg_replace('/\D+/', '', trim($this->telefono));
        $this->direccion = preg_replace('/\s+/', ' ', trim($this->direccion));
        $this->salario = str_replace(',', '.', trim($this->salario));
        $this->estado = (string) ((int) $this->estado);
    }

    protected function verificarCedulaExistente(): void
    {
        $this->cedulaExiste = false;

        if ($this->modoEdicion() && ! $this->cedulaCambio()) {
            return;
        }

        if (strlen($this->cedula) !== 14) {
            return;
        }

        $this->cedulaExiste = Trabajador::query()
            ->where('Cedula', $this->cedula)
            ->when($this->trabajadorEditandoId, function ($query) {
                $query->where('Id_Trabajador', '!=', $this->trabajadorEditandoId);
            })
            ->exists();
    }

    protected function limpiarPersonaEncontrada(): void
    {
        $this->personaExiste = false;
        $this->personaYaEsTrabajador = false;
        $this->personaExistenteId = null;
        $this->personaExistenteNombre = null;
    }

    protected function buscarPersonaPorTelefono(): void
    {
        $this->limpiarPersonaEncontrada();

        if (! preg_match('/^\d{8}$/', $this->telefono)) {
            return;
        }

        $persona = Persona::query()
            ->with('trabajador')
            ->where('Telefono', $this->telefono)
            ->first();

        if (! $persona) {
            return;
        }

        $this->personaExiste = true;
        $this->personaExistenteId = $persona->Id_Persona;
        $this->personaExistenteNombre = $persona->nombre_completo;
        $this->personaYaEsTrabajador = $persona->trabajador !== null;
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

    protected function unirDosColumnas(?string $primero, ?string $segundo): string
    {
        return preg_replace('/\s+/', ' ', trim(($primero ?? '') . ' ' . ($segundo ?? '')));
    }

    public function cargarCargos(): void
    {
        $this->cargos = Cargo::query()
            ->orderBy('Cargo_Asignado')
            ->get(['Id_Cargo', 'Cargo_Asignado'])
            ->map(fn (Cargo $cargo) => [
                'id' => (int) $cargo->Id_Cargo,
                'name' => $cargo->Cargo_Asignado,
            ])
            ->values()
            ->toArray();
    }

    public function cargarTrabajadores(): void
    {
        $this->trabajadores = Trabajador::query()
            ->with(['persona', 'cargo'])
            ->orderByDesc('Id_Trabajador')
            ->get()
            ->map(function (Trabajador $trabajador) {
                $persona = $trabajador->persona;

                return [
                    'id' => $trabajador->Id_Trabajador,
                    'nombre_completo' => $persona?->nombre_completo ?? 'Sin persona',
                    'cargo' => $trabajador->cargo?->Cargo_Asignado ?? 'Sin cargo',
                    'cedula' => $trabajador->Cedula,
                    'telefono' => $persona?->Telefono ?? 'No registrado',
                    'direccion' => $persona?->Direccion ?? 'No registrada',
                    'fecha_ingreso' => $trabajador->Fecha_Ingreso
                        ? $trabajador->Fecha_Ingreso->format('d/m/Y')
                        : 'No registrada',
                    'salario' => $trabajador->Salario !== null
                        ? 'C$ ' . number_format((float) $trabajador->Salario, 2)
                        : 'C$ 0.00',
                    'estado' => ((int) $trabajador->Estado === 1) ? 'Activo' : 'Inactivo',
                ];
            })
            ->values()
            ->toArray();
    }

    public function guardarTrabajador(): void
    {
        if ($this->modoEdicion()) {
            $this->actualizarTrabajador();
            return;
        }

        $this->normalizarFormulario();

        $this->verificarCedulaExistente();
        $this->buscarPersonaPorTelefono();

        $this->validate();

        if ($this->cedulaExiste) {
            $this->addError('cedula', 'Esta cédula ya está registrada.');
            return;
        }

        if ($this->personaYaEsTrabajador) {
            $this->addError('telefono', 'Esta persona ya está registrada como trabajador.');
            return;
        }

        if ($this->personaExiste) {
            $this->modalConfirmarPersonaExistente = true;
            return;
        }

        $this->registrarTrabajador(false);
    }

    public function confirmarRegistroPersonaExistente(): void
    {
        $this->normalizarFormulario();

        $this->verificarCedulaExistente();
        $this->buscarPersonaPorTelefono();

        $this->validate();

        if (! $this->personaExiste || ! $this->personaExistenteId) {
            $this->modalConfirmarPersonaExistente = false;
            $this->addError('telefono', 'No se encontró la persona. Verifique el número de teléfono.');
            return;
        }

        if ($this->personaYaEsTrabajador) {
            $this->modalConfirmarPersonaExistente = false;
            $this->addError('telefono', 'Esta persona ya está registrada como trabajador.');
            return;
        }

        if ($this->cedulaExiste) {
            $this->modalConfirmarPersonaExistente = false;
            $this->addError('cedula', 'Esta cédula ya está registrada.');
            return;
        }

        $this->registrarTrabajador(true);
    }

    protected function registrarTrabajador(bool $usarPersonaExistente): void
    {
        DB::transaction(function () use ($usarPersonaExistente) {
            if ($usarPersonaExistente) {
                $persona = Persona::query()->findOrFail($this->personaExistenteId);
            } else {
                [$primerNombre, $segundoNombre] = $this->separarEnDosColumnas($this->nombres);
                [$primerApellido, $segundoApellido] = $this->separarEnDosColumnas($this->apellidos);

                $persona = Persona::query()->create([
                    'Primer_Nombre' => $primerNombre,
                    'Segundo_Nombre' => $segundoNombre,
                    'Primer_Apellido' => $primerApellido,
                    'Segundo_Apellido' => $segundoApellido,
                    'Direccion' => $this->direccion !== '' ? $this->direccion : null,
                    'Telefono' => $this->telefono,
                ]);
            }

            Trabajador::query()->create([
                'Id_Persona' => $persona->Id_Persona,
                'Fecha_Ingreso' => $this->fechaIngreso,
                'Estado' => 1,
                'Id_Cargo' => $this->cargoId,
                'Cedula' => $this->cedula,
                'Salario' => $this->salario,
            ]);
        });

        $this->modalConfirmarPersonaExistente = false;

        $this->limpiarFormulario();
        $this->cargarTrabajadores();

        session()->flash('success', 'Trabajador registrado correctamente.');
    }

    public function editarTrabajador(int $trabajadorId): void
    {
        $trabajador = Trabajador::query()
            ->with(['persona', 'cargo'])
            ->findOrFail($trabajadorId);

        $persona = $trabajador->persona;

        $this->resetValidation();
        $this->limpiarPersonaEncontrada();

        $this->trabajadorEditandoId = (int) $trabajador->Id_Trabajador;
        $this->personaEditandoId = $persona?->Id_Persona ? (int) $persona->Id_Persona : null;

        $this->nombres = $persona
            ? $this->unirDosColumnas($persona->Primer_Nombre, $persona->Segundo_Nombre)
            : '';

        $this->apellidos = $persona
            ? $this->unirDosColumnas($persona->Primer_Apellido, $persona->Segundo_Apellido)
            : '';

        $this->cedulaOriginal = strtoupper(trim($trabajador->Cedula ?? ''));
        $this->telefonoOriginal = preg_replace('/\D+/', '', trim($persona?->Telefono ?? ''));

        $this->cedula = $this->cedulaOriginal;
        $this->telefono = $this->telefonoOriginal;
        $this->direccion = $persona?->Direccion ?? '';

        $this->fechaIngreso = $trabajador->Fecha_Ingreso
            ? $trabajador->Fecha_Ingreso->format('Y-m-d')
            : now()->format('Y-m-d');

        $this->cargoId = $trabajador->Id_Cargo ? (int) $trabajador->Id_Cargo : null;
        $this->salario = $trabajador->Salario !== null ? (string) ((float) $trabajador->Salario) : '0';
        $this->estado = (string) ((int) $trabajador->Estado);

        $this->cedulaExiste = false;

        session()->flash('success', 'Datos cargados para edición.');
    }

    public function actualizarTrabajador(): void
    {
        $this->normalizarFormulario();
        $this->verificarCedulaExistente();

        $this->validate();

        if ($this->cedulaCambio() && $this->cedulaExiste) {
            $this->addError('cedula', 'Esta cédula ya está registrada en otro trabajador.');
            return;
        }

        DB::transaction(function () {
            $trabajador = Trabajador::query()
                ->with('persona')
                ->findOrFail($this->trabajadorEditandoId);

            $persona = $trabajador->persona;

            [$primerNombre, $segundoNombre] = $this->separarEnDosColumnas($this->nombres);
            [$primerApellido, $segundoApellido] = $this->separarEnDosColumnas($this->apellidos);

            if (! $persona) {
                $persona = Persona::query()->create([
                    'Primer_Nombre' => $primerNombre,
                    'Segundo_Nombre' => $segundoNombre,
                    'Primer_Apellido' => $primerApellido,
                    'Segundo_Apellido' => $segundoApellido,
                    'Direccion' => $this->direccion !== '' ? $this->direccion : null,
                    'Telefono' => $this->telefono,
                ]);

                $trabajador->Id_Persona = $persona->Id_Persona;
            } else {
                $persona->update([
                    'Primer_Nombre' => $primerNombre,
                    'Segundo_Nombre' => $segundoNombre,
                    'Primer_Apellido' => $primerApellido,
                    'Segundo_Apellido' => $segundoApellido,
                    'Direccion' => $this->direccion !== '' ? $this->direccion : null,
                    'Telefono' => $this->telefono,
                ]);
            }

            $trabajador->Fecha_Ingreso = $this->fechaIngreso;
            $trabajador->Estado = (int) $this->estado;
            $trabajador->Id_Cargo = $this->cargoId;
            $trabajador->Cedula = $this->cedula;
            $trabajador->Salario = $this->salario;
            $trabajador->save();
        });

        $this->limpiarFormulario();
        $this->cargarTrabajadores();

        session()->flash('success', 'Trabajador actualizado correctamente.');
    }

    public function cancelarEdicion(): void
    {
        $this->limpiarFormulario();
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

        $cargo = Cargo::query()->create([
            'Cargo_Asignado' => $this->nuevoCargo,
        ]);

        $this->cargoId = (int) $cargo->Id_Cargo;
        $this->nuevoCargo = '';
        $this->modalCargo = false;

        $this->cargarCargos();

        session()->flash('success', 'Cargo agregado correctamente.');
    }

    public function cerrarModalConfirmacion(): void
    {
        $this->modalConfirmarPersonaExistente = false;
    }

    public function limpiarFormulario(): void
    {
        $this->reset([
            'nombres',
            'apellidos',
            'cedula',
            'telefono',
            'direccion',
            'salario',
            'cargoId',
            'trabajadorEditandoId',
            'personaEditandoId',
            'cedulaOriginal',
            'telefonoOriginal',
            'cedulaExiste',
            'personaExiste',
            'personaYaEsTrabajador',
            'personaExistenteId',
            'personaExistenteNombre',
            'modalConfirmarPersonaExistente',
        ]);

        $this->fechaIngreso = now()->format('Y-m-d');
        $this->estado = '1';

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

    @if ($trabajadorEditandoId)
        <x-alert
            icon="o-pencil-square"
            class="border border-blue-200 bg-blue-50 text-blue-800"
        >
            Está editando un trabajador existente. Valide los datos antes de guardar los cambios.
        </x-alert>
    @endif

    @if ($personaExiste && ! $personaYaEsTrabajador && ! $trabajadorEditandoId)
        <x-alert
            icon="o-information-circle"
            class="border border-blue-200 bg-blue-50 text-blue-800"
        >
            Esta persona ya existe en el sistema
            @if ($personaExistenteNombre)
                como <strong>{{ $personaExistenteNombre }}</strong>.
            @endif
            Al guardar, se pedirá confirmación para registrarla como trabajador sin duplicar sus datos personales.
        </x-alert>
    @endif

    @if ($personaYaEsTrabajador && ! $trabajadorEditandoId)
        <x-alert
            icon="o-exclamation-triangle"
            class="border border-red-200 bg-red-50 text-red-800"
        >
            Esta persona ya está registrada como trabajador.
        </x-alert>
    @endif

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-[#1A2B42]">
                    {{ $trabajadorEditandoId ? 'Editar trabajador' : 'Registrar trabajador' }}
                </h2>
                <p class="text-base text-[#5F6B7A]">
                    {{ $trabajadorEditandoId ? 'Modifique los datos del trabajador seleccionado.' : 'Ingrese los datos del trabajador.' }}
                </p>
            </div>

            @if ($trabajadorEditandoId)
                <x-button
                    label="Cancelar edición"
                    icon="o-x-mark"
                    type="button"
                    wire:click="cancelarEdicion"
                    class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#EAF2FB]"
                />
            @endif
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
                            Esta cédula ya está registrada en otro trabajador.
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
                        wire:model.live.debounce.400ms="telefono"
                        placeholder="Ejemplo: 88887777"
                        maxlength="8"
                        inputmode="numeric"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />

                    @if ($personaExiste && ! $personaYaEsTrabajador && ! $trabajadorEditandoId)
                        <span class="mt-1 block text-sm font-semibold text-blue-700">
                            Esta persona ya existe en el sistema.
                        </span>
                    @endif

                    @if ($personaYaEsTrabajador && ! $trabajadorEditandoId)
                        <span class="mt-1 block text-sm font-semibold text-red-600">
                            Esta persona ya está registrada como trabajador.
                        </span>
                    @endif

                    @error('telefono')
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
                            class="mt-[1px] h-12 min-h-12 rounded-xl border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]"
                        />
                    </div>

                    @error('cargoId')
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

                @if ($trabajadorEditandoId)
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                            Estado
                        </label>
                        <x-select
                            wire:model="estado"
                            placeholder="Seleccione estado"
                            :options="$estados"
                            option-value="id"
                            option-label="name"
                            class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                        />
                        @error('estado')
                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            </div>

            <x-slot:actions>
                <div class="flex w-full justify-end gap-3 pt-2">
                    <x-button
                        label="{{ $trabajadorEditandoId ? 'Cancelar' : 'Limpiar' }}"
                        type="button"
                        wire:click="{{ $trabajadorEditandoId ? 'cancelarEdicion' : 'limpiarFormulario' }}"
                        class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#EAF2FB]"
                    />

                    <x-button
                        label="{{ $trabajadorEditandoId ? 'Actualizar trabajador' : 'Guardar trabajador' }}"
                        type="submit"
                        spinner="guardarTrabajador"
                        :disabled="$cedulaExiste || ($personaYaEsTrabajador && ! $trabajadorEditandoId)"
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
        >
            @scope('actions', $trabajador)
                <x-button
                    icon="o-pencil-square"
                    tooltip="Editar trabajador"
                    type="button"
                    wire:click="editarTrabajador({{ data_get($trabajador, 'id') }})"
                    class="h-9 min-h-9 rounded-xl border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#EAF2FB]"
                />
            @endscope
        </x-table>
    </x-card>

    <x-modal
        wire:model="modalConfirmarPersonaExistente"
        box-class="rounded-2xl border border-[#D7E4F3] bg-white"
    >
        <div class="space-y-4">
            <div>
                <h2 class="text-2xl font-bold text-[#1A2B42]">Persona existente</h2>
                <p class="mt-1 text-base text-[#5F6B7A]">
                    Esta persona ya existe en el sistema. ¿Desea registrarla como trabajador?
                </p>
            </div>

            <div class="rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-4">
                <p class="text-sm font-semibold text-[#5F6B7A]">Persona encontrada</p>
                <p class="mt-1 text-lg font-bold text-[#1A2B42]">
                    {{ $personaExistenteNombre ?? 'Sin nombre registrado' }}
                </p>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    Teléfono: {{ $telefono }}
                </p>
            </div>

            <x-alert
                icon="o-information-circle"
                class="border border-blue-200 bg-blue-50 text-blue-800"
            >
                Si confirma, no se creará otra persona. Solo se agregará el registro en trabajador relacionado al Id_Persona existente.
            </x-alert>
        </div>

        <x-slot:actions>
            <x-button
                label="Cancelar"
                type="button"
                wire:click="cerrarModalConfirmacion"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#EAF2FB]"
            />

            <x-button
                label="Sí, registrar trabajador"
                type="button"
                wire:click="confirmarRegistroPersonaExistente"
                spinner="confirmarRegistroPersonaExistente"
                class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]"
            />
        </x-slot:actions>
    </x-modal>

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