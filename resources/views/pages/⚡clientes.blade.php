<?php

use App\Models\Cliente;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast, WithPagination;

    public string $nombre = '';
    public string $apellido = '';
    public string $telefono = '';
    public ?string $direccion = '';

    public string $municipio = '';

    public int|string $tipoCliente = Cliente::TIPO_NATURAL;
    public int|string $tipoPago = Cliente::TIPO_PAGO_CONTADO;

    public ?string $institucion = '';
    public ?string $telefonoInstitucion = '';
    public ?string $correoInstitucion = '';
    public ?string $direccionInstitucion = '';

    public string $buscar = '';

    public ?int $clienteEditandoId = null;
    public ?int $personaEditandoId = null;

    public string $telefonoOriginal = '';

    public bool $modalConfirmarPersonaExistente = false;
    public bool $personaExiste = false;
    public bool $personaYaEsCliente = false;

    public ?int $personaExistenteId = null;
    public ?string $personaExistenteNombre = null;
    public ?string $personaExistenteTelefono = null;
    public ?string $personaExistenteDireccion = null;

    public array $headers = [
        ['key' => 'codigo', 'label' => 'Código', 'class' => 'w-24'],
        ['key' => 'full_name', 'label' => 'Nombre / Institución', 'class' => 'min-w-[220px]'],
        ['key' => 'telefono', 'label' => 'Teléfono', 'class' => 'min-w-[130px]'],
        ['key' => 'correo', 'label' => 'Correo', 'class' => 'hidden lg:table-cell min-w-[190px]'],
        ['key' => 'municipio', 'label' => 'Municipio', 'class' => 'hidden md:table-cell min-w-[130px]'],
        ['key' => 'tipo_cliente', 'label' => 'Tipo cliente', 'class' => 'min-w-[130px]'],
        ['key' => 'tipo_pago', 'label' => 'Tipo pago', 'class' => 'min-w-[110px]'],
        ['key' => 'estado', 'label' => 'Estado', 'class' => 'min-w-[110px]'],
        ['key' => 'actions', 'label' => 'Acciones', 'class' => 'w-32 text-right'],
    ];

    public array $tiposCliente = [
        ['id' => Cliente::TIPO_NATURAL, 'name' => 'Natural'],
        ['id' => Cliente::TIPO_INSTITUCION, 'name' => 'Institucional'],
    ];

    public array $tiposPago = [
        ['id' => Cliente::TIPO_PAGO_CONTADO, 'name' => 'Contado'],
        ['id' => Cliente::TIPO_PAGO_CREDITO, 'name' => 'Crédito'],
    ];

    public array $tiposPagoNatural = [
        ['id' => Cliente::TIPO_PAGO_CONTADO, 'name' => 'Contado'],
    ];

    public function mount(): void
    {
        // La tabla carga con paginación desde clientes().
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedTipoCliente($value): void
    {
        $this->resetValidation();
        $this->limpiarPersonaEncontrada();

        if ((int) $value === Cliente::TIPO_NATURAL) {
            $this->tipoPago = Cliente::TIPO_PAGO_CONTADO;

            $this->institucion = '';
            $this->telefonoInstitucion = '';
            $this->correoInstitucion = '';
            $this->direccionInstitucion = '';

            return;
        }

        $this->nombre = '';
        $this->apellido = '';
        $this->telefono = '';
        $this->direccion = '';
    }

    public function updatedTelefono(): void
    {
        $this->telefono = $this->limpiarSoloDigitos($this->telefono);

        if (! $this->esNaturalSeleccionado()) {
            $this->limpiarPersonaEncontrada();
            return;
        }

        if ($this->modoEdicion()) {
            $this->limpiarPersonaEncontrada();

            if (! $this->telefonoCambio()) {
                $this->resetErrorBag('telefono');
            }

            return;
        }

        $this->buscarPersonaPorTelefono();
    }

    public function updatedTelefonoInstitucion(): void
    {
        $this->telefonoInstitucion = $this->limpiarSoloDigitos($this->telefonoInstitucion);
    }

    protected function rules(): array
    {
        $esNatural = (int) $this->tipoCliente === Cliente::TIPO_NATURAL;
        $esInstitucion = (int) $this->tipoCliente === Cliente::TIPO_INSTITUCION;
        $modoEdicion = $this->modoEdicion();
        $personaNuevaNatural = $esNatural && ! $this->personaExiste && ! $modoEdicion;

        $telefonoRules = ['nullable'];

        if ($esNatural) {
            $telefonoRules = [
                'required',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
            ];

            if ($modoEdicion) {
                if ($this->telefonoCambio()) {
                    if ($this->personaEditandoId) {
                        $telefonoRules[] = Rule::unique('persona', 'Telefono')
                            ->ignore($this->personaEditandoId, 'Id_Persona');
                    } else {
                        $telefonoRules[] = Rule::unique('persona', 'Telefono');
                    }
                }
            } elseif (! $this->personaExiste) {
                $telefonoRules[] = Rule::unique('persona', 'Telefono');
            }
        }

        return [
            'tipoCliente' => [
                'required',
                'integer',
                Rule::in([Cliente::TIPO_NATURAL, Cliente::TIPO_INSTITUCION]),
            ],

            'municipio' => [
                'required',
                'string',
                'max:100',
                'regex:/^[\p{L}\s\.\-]+$/u',
            ],

            'nombre' => $esNatural
                ? [
                    Rule::requiredIf($personaNuevaNatural || $modoEdicion),
                    'nullable',
                    'string',
                    'max:80',
                    'regex:/^[\p{L}]+(?:\s+[\p{L}]+)?$/u',
                ]
                : ['nullable'],

            'apellido' => $esNatural
                ? [
                    Rule::requiredIf($personaNuevaNatural || $modoEdicion),
                    'nullable',
                    'string',
                    'max:80',
                    'regex:/^[\p{L}]+(?:\s+[\p{L}]+)?$/u',
                ]
                : ['nullable'],

            'telefono' => $telefonoRules,

            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'institucion' => $esInstitucion
                ? [
                    'required',
                    'string',
                    'max:150',
                ]
                : ['nullable'],

            'telefonoInstitucion' => $esInstitucion
                ? [
                    'required',
                    'string',
                    'size:8',
                    'regex:/^[0-9]{8}$/',
                    Rule::unique('cliente', 'Telefono_Institucion')->ignore($this->clienteEditandoId, 'Id_Cliente'),
                ]
                : ['nullable'],

            'correoInstitucion' => $esInstitucion
                ? [
                    'nullable',
                    'email',
                    'max:150',
                    Rule::unique('cliente', 'Correo_Institucion')->ignore($this->clienteEditandoId, 'Id_Cliente'),
                ]
                : ['nullable'],

            'direccionInstitucion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'tipoPago' => [
                'required',
                'integer',
                $esNatural
                    ? Rule::in([Cliente::TIPO_PAGO_CONTADO])
                    : Rule::in([Cliente::TIPO_PAGO_CONTADO, Cliente::TIPO_PAGO_CREDITO]),
            ],
        ];
    }

    protected array $messages = [
        'tipoCliente.required' => 'Seleccione el tipo de cliente.',
        'tipoCliente.in' => 'Seleccione un tipo de cliente válido.',

        'municipio.required' => 'El municipio es obligatorio.',
        'municipio.regex' => 'El municipio solo puede llevar letras, espacios, puntos o guiones.',
        'municipio.max' => 'El municipio no debe superar los 100 caracteres.',

        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.regex' => 'El nombre solo puede llevar letras y máximo 2 palabras.',
        'nombre.max' => 'El nombre no debe superar los 80 caracteres.',

        'apellido.required' => 'El apellido es obligatorio.',
        'apellido.regex' => 'El apellido solo puede llevar letras y máximo 2 palabras.',
        'apellido.max' => 'El apellido no debe superar los 80 caracteres.',

        'telefono.required' => 'El teléfono es obligatorio.',
        'telefono.regex' => 'Ingrese solo 8 dígitos. Ejemplo: 88887777.',
        'telefono.size' => 'El teléfono debe tener exactamente 8 dígitos.',
        'telefono.unique' => 'Este teléfono ya pertenece a otra persona registrada.',

        'direccion.max' => 'La dirección no debe superar los 255 caracteres.',

        'institucion.required' => 'La institución es obligatoria para clientes institucionales.',
        'institucion.max' => 'La institución no debe superar los 150 caracteres.',

        'telefonoInstitucion.required' => 'El teléfono institucional es obligatorio.',
        'telefonoInstitucion.regex' => 'Ingrese solo 8 dígitos. Ejemplo: 27720000.',
        'telefonoInstitucion.size' => 'El teléfono institucional debe tener exactamente 8 dígitos.',
        'telefonoInstitucion.unique' => 'Este teléfono institucional ya está registrado.',

        'correoInstitucion.email' => 'Ingrese un correo institucional válido.',
        'correoInstitucion.unique' => 'Este correo institucional ya está registrado.',
        'correoInstitucion.max' => 'El correo institucional no debe superar los 150 caracteres.',

        'direccionInstitucion.max' => 'La dirección institucional no debe superar los 255 caracteres.',

        'tipoPago.required' => 'Seleccione el tipo de pago.',
        'tipoPago.in' => 'El cliente natural solo puede tener pago de contado.',
    ];

    protected function modoEdicion(): bool
    {
        return $this->clienteEditandoId !== null;
    }

    protected function telefonoCambio(): bool
    {
        $telefonoActual = $this->limpiarSoloDigitos($this->telefono);
        $telefonoOriginal = $this->limpiarSoloDigitos($this->telefonoOriginal);

        return $telefonoActual !== $telefonoOriginal;
    }

    public function guardarCliente(): void
    {
        $this->normalizarCampos();

        if ($this->clienteEditandoId) {
            $this->validate();
            $this->actualizarCliente();
            return;
        }

        if ($this->esNaturalSeleccionado()) {
            $this->buscarPersonaPorTelefono();
        }

        $this->validate();

        if ($this->esNaturalSeleccionado() && $this->personaYaEsCliente) {
            $this->addError('telefono', 'Esta persona ya está registrada como cliente. Puede editarla desde el listado.');

            $this->error(
                'Esta persona ya es cliente.',
                'Busque el registro en el listado para actualizarlo o activarlo.',
                position: 'toast-top toast-end',
                timeout: 3500
            );

            return;
        }

        if ($this->esNaturalSeleccionado() && $this->personaExiste) {
            $this->modalConfirmarPersonaExistente = true;

            $this->info(
                'Persona existente encontrada.',
                'Confirme para registrarla como cliente sin duplicar datos personales.',
                position: 'toast-top toast-end',
                timeout: 3200
            );

            return;
        }

        $this->crearCliente(false);
    }

    public function confirmarRegistroPersonaExistente(): void
    {
        $this->normalizarCampos();
        $this->buscarPersonaPorTelefono();

        $this->validate();

        if (! $this->personaExiste || ! $this->personaExistenteId) {
            $this->modalConfirmarPersonaExistente = false;
            $this->addError('telefono', 'No se encontró la persona. Verifique el número de teléfono.');
            return;
        }

        if ($this->personaYaEsCliente) {
            $this->modalConfirmarPersonaExistente = false;
            $this->addError('telefono', 'Esta persona ya está registrada como cliente.');
            return;
        }

        $this->crearCliente(true);
    }

    public function cerrarModalConfirmacion(): void
    {
        $this->modalConfirmarPersonaExistente = false;
    }

    private function crearCliente(bool $usarPersonaExistente = false): void
    {
        try {
            DB::transaction(function () use ($usarPersonaExistente) {
                if ($this->esNaturalSeleccionado()) {
                    if ($usarPersonaExistente) {
                        $persona = Persona::query()->findOrFail($this->personaExistenteId);
                    } else {
                        [$primerNombre, $segundoNombre] = $this->separarEnDosPartes($this->nombre);
                        [$primerApellido, $segundoApellido] = $this->separarEnDosPartes($this->apellido);

                        $persona = Persona::query()->create([
                            'Primer_Nombre' => $primerNombre,
                            'Segundo_Nombre' => $segundoNombre,
                            'Primer_Apellido' => $primerApellido,
                            'Segundo_Apellido' => $segundoApellido,
                            'Direccion' => $this->direccion,
                            'Telefono' => $this->telefono,
                        ]);
                    }

                    Cliente::query()->create([
                        'Id_Persona' => $persona->Id_Persona,
                        'Tipo_Cliente' => Cliente::TIPO_NATURAL,
                        'Institucion' => null,
                        'Telefono_Institucion' => null,
                        'Direccion_Institucion' => null,
                        'Correo_Institucion' => null,
                        'Municipio' => $this->municipio,
                        'Estado' => Cliente::ESTADO_ACTIVO,
                        'Tipo_pago' => Cliente::TIPO_PAGO_CONTADO,
                    ]);

                    return;
                }

                Cliente::query()->create([
                    'Id_Persona' => null,
                    'Tipo_Cliente' => Cliente::TIPO_INSTITUCION,
                    'Institucion' => $this->institucion,
                    'Telefono_Institucion' => $this->telefonoInstitucion,
                    'Direccion_Institucion' => $this->direccionInstitucion,
                    'Correo_Institucion' => $this->correoInstitucion,
                    'Municipio' => $this->municipio,
                    'Estado' => Cliente::ESTADO_ACTIVO,
                    'Tipo_pago' => (int) $this->tipoPago,
                ]);
            });

            $this->modalConfirmarPersonaExistente = false;
            $this->limpiarFormulario();
            $this->resetPage();

            $this->success(
                'Cliente registrado correctamente.',
                position: 'toast-top toast-end',
                timeout: 2500
            );
        } catch (\Throwable $e) {
            report($e);

            $this->error(
                'No se pudo registrar el cliente.',
                'Revise los datos e intente nuevamente.',
                position: 'toast-top toast-end',
                timeout: 3500
            );
        }
    }

    private function actualizarCliente(): void
    {
        try {
            DB::transaction(function () {
                $cliente = Cliente::query()
                    ->with('persona')
                    ->findOrFail($this->clienteEditandoId);

                if ($this->esNaturalSeleccionado()) {
                    [$primerNombre, $segundoNombre] = $this->separarEnDosPartes($this->nombre);
                    [$primerApellido, $segundoApellido] = $this->separarEnDosPartes($this->apellido);

                    $persona = $cliente->persona;

                    if (! $persona) {
                        $persona = Persona::query()->create([
                            'Primer_Nombre' => $primerNombre,
                            'Segundo_Nombre' => $segundoNombre,
                            'Primer_Apellido' => $primerApellido,
                            'Segundo_Apellido' => $segundoApellido,
                            'Direccion' => $this->direccion,
                            'Telefono' => $this->telefono,
                        ]);
                    } else {
                        $persona->update([
                            'Primer_Nombre' => $primerNombre,
                            'Segundo_Nombre' => $segundoNombre,
                            'Primer_Apellido' => $primerApellido,
                            'Segundo_Apellido' => $segundoApellido,
                            'Direccion' => $this->direccion,
                            'Telefono' => $this->telefono,
                        ]);
                    }

                    $cliente->update([
                        'Id_Persona' => $persona->Id_Persona,
                        'Tipo_Cliente' => Cliente::TIPO_NATURAL,
                        'Institucion' => null,
                        'Telefono_Institucion' => null,
                        'Direccion_Institucion' => null,
                        'Correo_Institucion' => null,
                        'Municipio' => $this->municipio,
                        'Tipo_pago' => Cliente::TIPO_PAGO_CONTADO,
                    ]);

                    return;
                }

                $cliente->update([
                    'Id_Persona' => null,
                    'Tipo_Cliente' => Cliente::TIPO_INSTITUCION,
                    'Institucion' => $this->institucion,
                    'Telefono_Institucion' => $this->telefonoInstitucion,
                    'Direccion_Institucion' => $this->direccionInstitucion,
                    'Correo_Institucion' => $this->correoInstitucion,
                    'Municipio' => $this->municipio,
                    'Tipo_pago' => (int) $this->tipoPago,
                ]);
            });

            $this->limpiarFormulario();
            $this->resetPage();

            $this->success(
                'Cliente actualizado correctamente.',
                position: 'toast-top toast-end',
                timeout: 2500
            );
        } catch (\Throwable $e) {
            report($e);

            $this->error(
                'No se pudo actualizar el cliente.',
                'Revise los datos e intente nuevamente.',
                position: 'toast-top toast-end',
                timeout: 3500
            );
        }
    }

    public function cargarCliente(int $clienteId): void
    {
        $cliente = Cliente::query()
            ->with('persona')
            ->findOrFail($clienteId);

        $persona = $cliente->persona;

        $this->resetValidation();
        $this->limpiarPersonaEncontrada();

        $this->clienteEditandoId = (int) $cliente->Id_Cliente;
        $this->personaEditandoId = $persona?->Id_Persona ? (int) $persona->Id_Persona : null;

        $this->tipoCliente = (int) $cliente->Tipo_Cliente;
        $this->tipoPago = (int) $cliente->Tipo_pago;
        $this->municipio = $cliente->Municipio ?? '';

        if ((int) $cliente->Tipo_Cliente === Cliente::TIPO_NATURAL) {
            $this->nombre = $persona
                ? $this->unirDosColumnas($persona->Primer_Nombre, $persona->Segundo_Nombre)
                : '';

            $this->apellido = $persona
                ? $this->unirDosColumnas($persona->Primer_Apellido, $persona->Segundo_Apellido)
                : '';

            $this->telefonoOriginal = $this->limpiarSoloDigitos($persona?->Telefono ?? '');
            $this->telefono = $this->telefonoOriginal;
            $this->direccion = $persona?->Direccion;

            $this->tipoPago = Cliente::TIPO_PAGO_CONTADO;

            $this->institucion = '';
            $this->telefonoInstitucion = '';
            $this->correoInstitucion = '';
            $this->direccionInstitucion = '';
        } else {
            $this->nombre = '';
            $this->apellido = '';
            $this->telefono = '';
            $this->telefonoOriginal = '';
            $this->direccion = '';

            $this->institucion = $cliente->Institucion;
            $this->telefonoInstitucion = $cliente->Telefono_Institucion;
            $this->correoInstitucion = $cliente->Correo_Institucion;
            $this->direccionInstitucion = $cliente->Direccion_Institucion;
        }

        $this->resetValidation();

        $this->info(
            'Cliente cargado para edición.',
            position: 'toast-top toast-end',
            timeout: 2200
        );
    }

    public function cambiarEstadoCliente(int $clienteId): void
    {
        try {
            $cliente = Cliente::query()->findOrFail($clienteId);

            $cliente->Estado = $cliente->Estado
                ? Cliente::ESTADO_INACTIVO
                : Cliente::ESTADO_ACTIVO;

            $cliente->save();

            $this->resetPage();

            $this->success(
                $cliente->Estado ? 'Cliente activado.' : 'Cliente inactivado.',
                position: 'toast-top toast-end',
                timeout: 2200
            );
        } catch (\Throwable $e) {
            report($e);

            $this->error(
                'No se pudo cambiar el estado.',
                position: 'toast-top toast-end',
                timeout: 3000
            );
        }
    }

    public function cancelarEdicion(): void
    {
        $this->limpiarFormulario();

        $this->info(
            'Edición cancelada.',
            position: 'toast-top toast-end',
            timeout: 2200
        );
    }

    public function limpiarFormulario(): void
    {
        $this->reset([
            'nombre',
            'apellido',
            'telefono',
            'direccion',
            'municipio',
            'institucion',
            'telefonoInstitucion',
            'correoInstitucion',
            'direccionInstitucion',
            'clienteEditandoId',
            'personaEditandoId',
            'telefonoOriginal',
            'modalConfirmarPersonaExistente',
            'personaExiste',
            'personaYaEsCliente',
            'personaExistenteId',
            'personaExistenteNombre',
            'personaExistenteTelefono',
            'personaExistenteDireccion',
        ]);

        $this->tipoCliente = Cliente::TIPO_NATURAL;
        $this->tipoPago = Cliente::TIPO_PAGO_CONTADO;

        $this->resetValidation();
    }

    public function limpiarBusqueda(): void
    {
        $this->buscar = '';
        $this->resetPage();
    }

    public function clientes(): LengthAwarePaginator
    {
        $busqueda = trim($this->buscar);

        return Cliente::query()
            ->with('persona')
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $like = "%{$busqueda}%";

                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->where('Institucion', 'like', $like)
                        ->orWhere('Telefono_Institucion', 'like', $like)
                        ->orWhere('Correo_Institucion', 'like', $like)
                        ->orWhere('Direccion_Institucion', 'like', $like)
                        ->orWhere('Municipio', 'like', $like)
                        ->orWhereHas('persona', function ($personaQuery) use ($like) {
                            $personaQuery
                                ->where('Primer_Nombre', 'like', $like)
                                ->orWhere('Segundo_Nombre', 'like', $like)
                                ->orWhere('Primer_Apellido', 'like', $like)
                                ->orWhere('Segundo_Apellido', 'like', $like)
                                ->orWhere('Telefono', 'like', $like)
                                ->orWhere('Direccion', 'like', $like);
                        });
                });
            })
            ->orderByDesc('Id_Cliente')
            ->paginate(10)
            ->through(function (Cliente $cliente) {
                return [
                    'id' => (int) $cliente->Id_Cliente,
                    'codigo' => 'CL-' . str_pad((string) $cliente->Id_Cliente, 4, '0', STR_PAD_LEFT),
                    'full_name' => $this->nombreClienteTabla($cliente),
                    'telefono' => $this->telefonoClienteTabla($cliente),
                    'correo' => $this->correoClienteTabla($cliente),
                    'municipio' => $cliente->Municipio ?: 'No registrado',
                    'tipo_cliente' => $this->obtenerNombreTipoCliente($cliente->Tipo_Cliente),
                    'tipo_pago' => $this->obtenerNombreTipoPago($cliente->Tipo_pago),
                    'estado' => $cliente->Estado ? 'Activo' : 'Inactivo',
                    'activo' => (bool) $cliente->Estado,
                ];
            });
    }

    private function normalizarCampos(): void
    {
        $this->tipoCliente = (int) $this->tipoCliente;
        $this->tipoPago = (int) $this->tipoPago;

        $this->municipio = $this->limpiarTextoObligatorio($this->municipio);

        $this->nombre = $this->limpiarTextoObligatorio($this->nombre);
        $this->apellido = $this->limpiarTextoObligatorio($this->apellido);
        $this->telefono = $this->limpiarSoloDigitos($this->telefono);
        $this->direccion = $this->limpiarTextoOpcional($this->direccion);

        $this->institucion = $this->limpiarTextoOpcional($this->institucion);
        $this->telefonoInstitucion = $this->limpiarSoloDigitos($this->telefonoInstitucion);
        $this->correoInstitucion = $this->limpiarTextoOpcional($this->correoInstitucion);
        $this->direccionInstitucion = $this->limpiarTextoOpcional($this->direccionInstitucion);

        if ($this->correoInstitucion !== null) {
            $this->correoInstitucion = mb_strtolower($this->correoInstitucion);
        }

        if ($this->esNaturalSeleccionado()) {
            $this->tipoPago = Cliente::TIPO_PAGO_CONTADO;

            $this->institucion = null;
            $this->telefonoInstitucion = null;
            $this->correoInstitucion = null;
            $this->direccionInstitucion = null;

            return;
        }

        $this->nombre = '';
        $this->apellido = '';
        $this->telefono = '';
        $this->direccion = null;
        $this->limpiarPersonaEncontrada();
    }

    private function limpiarPersonaEncontrada(): void
    {
        $this->modalConfirmarPersonaExistente = false;
        $this->personaExiste = false;
        $this->personaYaEsCliente = false;
        $this->personaExistenteId = null;
        $this->personaExistenteNombre = null;
        $this->personaExistenteTelefono = null;
        $this->personaExistenteDireccion = null;
    }

    private function buscarPersonaPorTelefono(): void
    {
        $this->limpiarPersonaEncontrada();

        if (! $this->esNaturalSeleccionado()) {
            return;
        }

        if ($this->modoEdicion()) {
            return;
        }

        if (! preg_match('/^\d{8}$/', $this->telefono)) {
            return;
        }

        $persona = Persona::query()
            ->with('cliente')
            ->where('Telefono', $this->telefono)
            ->first();

        if (! $persona) {
            return;
        }

        $this->personaExiste = true;
        $this->personaExistenteId = (int) $persona->Id_Persona;
        $this->personaExistenteNombre = $this->nombrePersona($persona);
        $this->personaExistenteTelefono = $persona->Telefono;
        $this->personaExistenteDireccion = $persona->Direccion;
        $this->personaYaEsCliente = $persona->cliente !== null;

        $this->nombre = $this->unirDosColumnas($persona->Primer_Nombre, $persona->Segundo_Nombre);
        $this->apellido = $this->unirDosColumnas($persona->Primer_Apellido, $persona->Segundo_Apellido);
        $this->direccion = $persona->Direccion;
    }

    private function limpiarTextoObligatorio(?string $valor): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $valor));
    }

    private function limpiarTextoOpcional(?string $valor): ?string
    {
        $valor = trim(preg_replace('/\s+/', ' ', (string) $valor));

        return $valor === '' ? null : $valor;
    }

    private function limpiarSoloDigitos(?string $valor): string
    {
        return preg_replace('/\D+/', '', (string) $valor);
    }

    private function separarEnDosPartes(string $valor): array
    {
        $partes = preg_split('/\s+/', trim($valor), 2, PREG_SPLIT_NO_EMPTY);

        return [
            $partes[0] ?? null,
            $partes[1] ?? null,
        ];
    }

    private function unirDosColumnas(?string $primero, ?string $segundo): string
    {
        return preg_replace('/\s+/', ' ', trim(($primero ?? '') . ' ' . ($segundo ?? '')));
    }

    private function nombrePersona(Persona $persona): string
    {
        $nombre = trim(implode(' ', array_filter([
            $persona->Primer_Nombre,
            $persona->Segundo_Nombre,
            $persona->Primer_Apellido,
            $persona->Segundo_Apellido,
        ])));

        return $nombre !== '' ? $nombre : 'Sin nombre registrado';
    }

    private function esNaturalSeleccionado(): bool
    {
        return (int) $this->tipoCliente === Cliente::TIPO_NATURAL;
    }

    private function nombreClienteTabla(Cliente $cliente): string
    {
        if ((int) $cliente->Tipo_Cliente === Cliente::TIPO_INSTITUCION) {
            return filled($cliente->Institucion)
                ? trim((string) $cliente->Institucion)
                : 'Institución sin nombre';
        }

        $persona = $cliente->persona;

        $nombre = trim(implode(' ', array_filter([
            $persona?->Primer_Nombre,
            $persona?->Segundo_Nombre,
            $persona?->Primer_Apellido,
            $persona?->Segundo_Apellido,
        ])));

        return $nombre !== '' ? $nombre : 'Sin nombre';
    }

    private function telefonoClienteTabla(Cliente $cliente): string
    {
        if ((int) $cliente->Tipo_Cliente === Cliente::TIPO_INSTITUCION) {
            return $cliente->Telefono_Institucion ?: 'Sin teléfono';
        }

        return $cliente->persona?->Telefono ?: 'Sin teléfono';
    }

    private function correoClienteTabla(Cliente $cliente): string
    {
        if ((int) $cliente->Tipo_Cliente === Cliente::TIPO_INSTITUCION) {
            return $cliente->Correo_Institucion ?: 'Sin correo';
        }

        return 'Sin correo';
    }

    private function obtenerNombreTipoCliente(int|string|null $tipo): string
    {
        return (int) $tipo === Cliente::TIPO_INSTITUCION ? 'Institucional' : 'Natural';
    }

    private function obtenerNombreTipoPago(int|string|null $tipo): string
    {
        return (int) $tipo === Cliente::TIPO_PAGO_CREDITO ? 'Crédito' : 'Contado';
    }
};
?>

@php
    $fieldClass = 'rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794] [&_.fieldset-legend]:text-[#1A2B42] [&_.label]:text-[#1A2B42] [&_label]:text-[#1A2B42]';
    $readonlyClass = 'rounded-xl border-[#D7E4F3] bg-[#EAF2FB] font-semibold text-[#1A2B42] [&_.fieldset-legend]:text-[#1A2B42] [&_.label]:text-[#1A2B42] [&_label]:text-[#1A2B42]';
    $cardClass = 'rounded-2xl border border-[#D7E4F3] bg-white shadow-sm [&_.card-title]:text-[#1A2B42] [&_.text-base-content\\/70]:text-[#5F6B7A] [&_label]:text-[#1A2B42] [&_.fieldset-legend]:text-[#1A2B42]';
    $primaryButtonClass = 'rounded-xl border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30';
    $secondaryButtonClass = 'rounded-xl border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#EAF2FB]';

    $isNatural = (int) $tipoCliente === \App\Models\Cliente::TIPO_NATURAL;
    $isInstitucion = (int) $tipoCliente === \App\Models\Cliente::TIPO_INSTITUCION;
@endphp

<div class="min-h-screen overflow-x-hidden bg-[#F0F3F7] p-4 md:p-6">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-[#1A2B42]">Clientes</h1>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    Registro y gestión de clientes del sistema.
                </p>
            </div>

            @if ($clienteEditandoId)
                <div class="rounded-xl border border-[#D7E4F3] bg-[#EAF2FB] px-4 py-2 text-sm font-semibold text-[#1A2B42]">
                    Editando cliente CL-{{ str_pad((string) $clienteEditandoId, 4, '0', STR_PAD_LEFT) }}
                </div>
            @endif
        </div>

        @if ($personaExiste && ! $personaYaEsCliente && ! $clienteEditandoId && $isNatural)
            <x-alert
                icon="o-information-circle"
                class="border border-blue-200 bg-blue-50 text-blue-800"
            >
                Esta persona ya existe en el sistema
                @if ($personaExistenteNombre)
                    como <strong>{{ $personaExistenteNombre }}</strong>.
                @endif
                Al guardar, se pedirá confirmación para registrarla como cliente sin duplicar sus datos personales.
            </x-alert>
        @endif

        @if ($personaYaEsCliente && ! $clienteEditandoId && $isNatural)
            <x-alert
                icon="o-exclamation-triangle"
                class="border border-red-200 bg-red-50 text-red-800"
            >
                Esta persona ya está registrada como cliente. Puede cargarla desde el listado para editarla o activarla.
            </x-alert>
        @endif

        <x-card class="{{ $cardClass }}">
            <x-form wire:submit="guardarCliente" no-separator>
                <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-[#1A2B42]">
                            {{ $clienteEditandoId ? 'Actualizar cliente' : 'Registrar cliente' }}
                        </h2>
                        <p class="text-base text-[#5F6B7A]">
                            {{ $clienteEditandoId
                                ? 'Modifique los datos del cliente seleccionado.'
                                : 'Seleccione el tipo de cliente y complete los campos requeridos.' }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <x-select
                        label="Tipo de cliente"
                        wire:model.live="tipoCliente"
                        :options="$tiposCliente"
                        option-value="id"
                        option-label="name"
                        placeholder="Seleccione el tipo"
                        icon="o-user-group"
                        class="{{ $fieldClass }}"
                    />

                    <x-input
                        label="Municipio"
                        wire:model.live.debounce.250ms="municipio"
                        placeholder="Ej: Bonanza"
                        icon="o-map-pin"
                        class="{{ $fieldClass }}"
                    />

                    @if ($isNatural)
                        <x-input
                            label="Nombres"
                            wire:model.live.debounce.250ms="nombre"
                            placeholder="Ej: Daniel Antonio"
                            icon="o-user"
                            class="{{ $personaExiste && ! $clienteEditandoId ? $readonlyClass : $fieldClass }}"
                            :readonly="$personaExiste && ! $clienteEditandoId"
                        />

                        <x-input
                            label="Apellidos"
                            wire:model.live.debounce.250ms="apellido"
                            placeholder="Ej: López Pérez"
                            icon="o-user"
                            class="{{ $personaExiste && ! $clienteEditandoId ? $readonlyClass : $fieldClass }}"
                            :readonly="$personaExiste && ! $clienteEditandoId"
                        />

                        <div>
                            <x-input
                                label="Teléfono"
                                wire:model.live.debounce.250ms="telefono"
                                placeholder="Ej: 88887777"
                                icon="o-phone"
                                maxlength="8"
                                inputmode="numeric"
                                class="{{ $fieldClass }}"
                            />

                            @if ($personaExiste && ! $personaYaEsCliente && ! $clienteEditandoId)
                                <span class="mt-1 block text-sm font-semibold text-blue-700">
                                    Persona encontrada. Se usará su registro existente.
                                </span>
                            @endif

                            @if ($personaYaEsCliente && ! $clienteEditandoId)
                                <span class="mt-1 block text-sm font-semibold text-red-600">
                                    Esta persona ya está registrada como cliente.
                                </span>
                            @endif
                        </div>

                        <x-input
                            label="Tipo de pago"
                            value="Contado"
                            readonly
                            icon="o-banknotes"
                            class="{{ $readonlyClass }}"
                        />

                        <div class="md:col-span-2 xl:col-span-3">
                            <x-textarea
                                label="Dirección"
                                wire:model.live.debounce.250ms="direccion"
                                placeholder="Ingrese la dirección del cliente natural"
                                rows="3"
                                class="{{ $personaExiste && ! $clienteEditandoId ? $readonlyClass : $fieldClass }}"
                                :readonly="$personaExiste && ! $clienteEditandoId"
                            />
                        </div>
                    @endif

                    @if ($isInstitucion)
                        <x-input
                            label="Institución"
                            wire:model.live.debounce.250ms="institucion"
                            placeholder="Ej: SILAIS, Alcaldía Municipal, MINED"
                            icon="o-building-office-2"
                            class="{{ $fieldClass }}"
                        />

                        <x-input
                            label="Teléfono institucional"
                            wire:model.live.debounce.250ms="telefonoInstitucion"
                            placeholder="Ej: 27720000"
                            icon="o-phone"
                            maxlength="8"
                            inputmode="numeric"
                            class="{{ $fieldClass }}"
                        />

                        <x-input
                            label="Correo institucional"
                            wire:model.live.debounce.250ms="correoInstitucion"
                            placeholder="contacto@institucion.gob.ni"
                            icon="o-envelope"
                            class="{{ $fieldClass }}"
                        />

                        <x-select
                            label="Tipo de pago"
                            wire:model.live="tipoPago"
                            :options="$tiposPago"
                            option-value="id"
                            option-label="name"
                            placeholder="Seleccione el tipo de pago"
                            icon="o-banknotes"
                            class="{{ $fieldClass }}"
                        />

                        <div class="md:col-span-2 xl:col-span-3">
                            <x-textarea
                                label="Dirección institucional"
                                wire:model.live.debounce.250ms="direccionInstitucion"
                                placeholder="Ingrese la dirección de la institución"
                                rows="3"
                                class="{{ $fieldClass }}"
                            />
                        </div>
                    @endif
                </div>

                <x-slot:actions>
                    <div class="flex w-full flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        @if ($clienteEditandoId)
                            <x-button
                                type="button"
                                label="Cancelar edición"
                                icon="o-x-mark"
                                wire:click="cancelarEdicion"
                                class="{{ $secondaryButtonClass }}"
                            />
                        @else
                            <x-button
                                type="button"
                                label="Limpiar"
                                icon="o-arrow-path"
                                wire:click="limpiarFormulario"
                                class="{{ $secondaryButtonClass }}"
                            />
                        @endif

                        <x-button
                            type="submit"
                            label="{{ $clienteEditandoId ? 'Actualizar datos' : 'Guardar cliente' }}"
                            icon="o-check-circle"
                            spinner="guardarCliente"
                            :disabled="$personaYaEsCliente && ! $clienteEditandoId && $isNatural"
                            class="{{ $primaryButtonClass }}"
                        />
                    </div>
                </x-slot:actions>
            </x-form>
        </x-card>

        <x-card class="{{ $cardClass }}">
            <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de clientes</h2>
                    <p class="text-sm text-[#5F6B7A]">
                        Consulte, cargue, active o inactive clientes registrados.
                    </p>
                </div>

                <div class="grid w-full grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_auto] lg:max-w-xl">
                    <x-input
                        label="Buscar cliente"
                        wire:model.live.debounce.350ms="buscar"
                        placeholder="Nombre, institución, teléfono, correo o municipio"
                        icon="o-magnifying-glass"
                        class="{{ $fieldClass }}"
                    />

                    <div class="flex items-end">
                        <x-button
                            type="button"
                            icon="o-x-mark"
                            wire:click="limpiarBusqueda"
                            class="{{ $secondaryButtonClass }} h-10 w-full sm:w-11"
                        />
                    </div>
                </div>
            </div>

            @php
                $clientes = $this->clientes();
            @endphp

            @if ($clientes->count() > 0)
                <div class="max-h-[520px] overflow-auto rounded-2xl border border-[#D7E4F3]">
                    <x-table
                        :headers="$headers"
                        :rows="$clientes"
                        with-pagination
                        no-hover
                        class="min-w-[980px] [&_thead_th]:sticky [&_thead_th]:top-0 [&_thead_th]:z-10 [&_thead_th]:border-0 [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:font-semibold [&_thead_th]:text-white [&_tbody_td]:border-[#D7E4F3] [&_tbody_td]:text-[#1A2B42] [&_tbody_tr:hover]:!bg-[#EAF4FD]"
                    >
                        @scope('cell_full_name', $cliente)
                            <span class="block max-w-[240px] truncate font-semibold" title="{{ $cliente['full_name'] }}">
                                {{ $cliente['full_name'] }}
                            </span>
                        @endscope

                        @scope('cell_correo', $cliente)
                            <span class="block max-w-[210px] truncate" title="{{ $cliente['correo'] }}">
                                {{ $cliente['correo'] }}
                            </span>
                        @endscope

                        @scope('cell_tipo_cliente', $cliente)
                            <span
                                class="{{ $cliente['tipo_cliente'] === 'Institucional'
                                    ? 'bg-[#EAF4FD] text-[#0B6FE4]'
                                    : 'bg-slate-100 text-slate-700' }} rounded-full px-2.5 py-1 text-xs font-bold"
                            >
                                {{ $cliente['tipo_cliente'] }}
                            </span>
                        @endscope

                        @scope('cell_tipo_pago', $cliente)
                            <span
                                class="{{ $cliente['tipo_pago'] === 'Crédito'
                                    ? 'bg-amber-100 text-amber-700'
                                    : 'bg-green-100 text-green-700' }} rounded-full px-2.5 py-1 text-xs font-bold"
                            >
                                {{ $cliente['tipo_pago'] }}
                            </span>
                        @endscope

                        @scope('cell_estado', $cliente)
                            <span
                                class="{{ $cliente['activo']
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700' }} rounded-full px-2.5 py-1 text-xs font-bold"
                            >
                                {{ $cliente['estado'] }}
                            </span>
                        @endscope

                        @scope('actions', $cliente)
                            <div class="flex justify-end gap-2">
                                <x-button
                                    icon="o-pencil-square"
                                    wire:click="cargarCliente({{ $cliente['id'] }})"
                                    spinner="cargarCliente({{ $cliente['id'] }})"
                                    title="Editar cliente"
                                    aria-label="Editar cliente"
                                    class="btn-sm rounded-xl border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#EAF2FB] hover:text-[#0B6FE4]"
                                />

                                <x-button
                                    icon="{{ $cliente['activo'] ? 'o-lock-closed' : 'o-lock-open' }}"
                                    wire:click="cambiarEstadoCliente({{ $cliente['id'] }})"
                                    spinner="cambiarEstadoCliente({{ $cliente['id'] }})"
                                    title="{{ $cliente['activo'] ? 'Inactivar cliente' : 'Activar cliente' }}"
                                    aria-label="{{ $cliente['activo'] ? 'Inactivar cliente' : 'Activar cliente' }}"
                                    class="{{ $cliente['activo']
                                        ? 'btn-sm rounded-xl border border-red-200 bg-red-50 text-red-700 hover:bg-red-100'
                                        : 'btn-sm rounded-xl border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]' }}"
                                />
                            </div>
                        @endscope
                    </x-table>
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-[#D7E4F3] bg-[#F0F3F7] px-4 py-8 text-center">
                    <p class="text-base font-semibold text-[#1A2B42]">
                        No hay clientes registrados.
                    </p>
                    <p class="mt-1 text-sm text-[#5F6B7A]">
                        Cuando registres un cliente, aparecerá en este listado.
                    </p>
                </div>
            @endif
        </x-card>

        <x-modal
            wire:model="modalConfirmarPersonaExistente"
            box-class="rounded-2xl border border-[#D7E4F3] bg-white"
        >
            <div class="space-y-4">
                <div>
                    <h2 class="text-2xl font-bold text-[#1A2B42]">Persona existente</h2>
                    <p class="mt-1 text-base text-[#5F6B7A]">
                        Esta persona ya existe en el sistema. ¿Desea registrarla como cliente natural?
                    </p>
                </div>

                <div class="rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-4">
                    <p class="text-sm font-semibold text-[#5F6B7A]">Persona encontrada</p>
                    <p class="mt-1 text-lg font-bold text-[#1A2B42]">
                        {{ $personaExistenteNombre ?? 'Sin nombre registrado' }}
                    </p>
                    <p class="mt-1 text-sm text-[#5F6B7A]">
                        Teléfono: {{ $personaExistenteTelefono ?? $telefono }}
                    </p>
                    <p class="mt-1 text-sm text-[#5F6B7A]">
                        Dirección: {{ $personaExistenteDireccion ?: 'No registrada' }}
                    </p>
                    <p class="mt-1 text-sm text-[#5F6B7A]">
                        Municipio a registrar: {{ $municipio ?: 'Pendiente' }}
                    </p>
                </div>

                <x-alert
                    icon="o-information-circle"
                    class="border border-blue-200 bg-blue-50 text-blue-800"
                >
                    Si confirma, no se creará otra persona. Solo se agregará el registro en cliente relacionado al Id_Persona existente.
                </x-alert>
            </div>

            <x-slot:actions>
                <x-button
                    label="Cancelar"
                    type="button"
                    wire:click="cerrarModalConfirmacion"
                    class="{{ $secondaryButtonClass }}"
                />

                <x-button
                    label="Sí, registrar cliente"
                    type="button"
                    wire:click="confirmarRegistroPersonaExistente"
                    spinner="confirmarRegistroPersonaExistente"
                    class="{{ $primaryButtonClass }}"
                />
            </x-slot:actions>
        </x-modal>
    </div>
</div>