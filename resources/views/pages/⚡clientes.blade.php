<?php

use App\Models\Cliente;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $nombre = '';
    public string $apellido = '';
    public string $telefono = '';
    public ?string $correo = '';
    public ?string $direccion = '';
    public string $municipio = '';

    public int|string $tipoCliente = 1;
    public ?string $institucion = '';

    public int|string $tipoPago = 1;
    public bool $estado = true;

    public string $buscar = '';

    public array $clientes = [];

    public ?string $mensaje = null;
    public string $tipoMensaje = 'success';

    public array $headers = [
        ['key' => 'codigo', 'label' => 'Código'],
        ['key' => 'full_name', 'label' => 'Nombre completo'],
        ['key' => 'telefono', 'label' => 'Teléfono'],
        ['key' => 'correo', 'label' => 'Correo'],
        ['key' => 'municipio', 'label' => 'Municipio'],
        ['key' => 'tipo_cliente', 'label' => 'Tipo cliente'],
        ['key' => 'tipo_pago', 'label' => 'Tipo pago'],
        ['key' => 'estado', 'label' => 'Estado'],
    ];

    public array $tiposCliente = [
        ['id' => 1, 'name' => 'Natural'],
        ['id' => 2, 'name' => 'Institucional'],
    ];

    public array $tiposPago = [
        ['id' => 1, 'name' => 'Contado'],
        ['id' => 2, 'name' => 'Crédito'],
    ];

    public function mount(): void
    {
        $this->cargarClientes();
    }

    public function updatedBuscar(): void
    {
        $this->cargarClientes();
    }

    public function updatedTipoCliente($value): void
    {
        if ((int) $value !== 2) {
            $this->institucion = '';
        }
    }

    protected function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:80',
                'regex:/^[\p{L}]+(?:\s+[\p{L}]+)?$/u',
            ],
            'apellido' => [
                'required',
                'string',
                'max:80',
                'regex:/^[\p{L}]+(?:\s+[\p{L}]+)?$/u',
            ],
            'telefono' => [
                'required',
                'string',
                'max:25',
                'regex:/^\+?[0-9\s\-\(\)]{8,25}$/',
                'unique:persona,Telefono',
            ],
            'correo' => [
                'nullable',
                'email',
                'max:120',
                'unique:persona,Correo',
            ],
            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],
            'municipio' => [
                'required',
                'string',
                'max:100',
            ],
            'tipoCliente' => [
                'required',
                'integer',
                'in:1,2',
            ],
            'institucion' => [
                (int) $this->tipoCliente === 2 ? 'required' : 'nullable',
                'string',
                'max:150',
            ],
            'tipoPago' => [
                'required',
                'integer',
                'in:1,2',
            ],
            'estado' => [
                'boolean',
            ],
        ];
    }

    protected array $messages = [
        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.regex' => 'El nombre solo puede llevar letras y máximo 2 palabras.',
        'apellido.required' => 'El apellido es obligatorio.',
        'apellido.regex' => 'El apellido solo puede llevar letras y máximo 2 palabras.',
        'telefono.required' => 'El teléfono es obligatorio.',
        'telefono.regex' => 'Ingrese un teléfono válido. Puede incluir código de país, por ejemplo +505 8888 8888.',
        'telefono.unique' => 'Este teléfono ya está registrado.',
        'correo.email' => 'Ingrese un correo válido.',
        'correo.unique' => 'Este correo ya está registrado.',
        'municipio.required' => 'El municipio es obligatorio.',
        'tipoCliente.required' => 'Seleccione el tipo de cliente.',
        'tipoCliente.in' => 'Seleccione un tipo de cliente válido.',
        'institucion.required' => 'La institución es obligatoria para clientes institucionales.',
        'tipoPago.required' => 'Seleccione el tipo de pago.',
        'tipoPago.in' => 'Seleccione un tipo de pago válido.',
    ];

    public function guardarCliente(): void
    {
        $this->normalizarCampos();

        $this->validate();

        [$primerNombre, $segundoNombre] = $this->separarEnDosPartes($this->nombre);
        [$primerApellido, $segundoApellido] = $this->separarEnDosPartes($this->apellido);

        try {
            DB::transaction(function () use ($primerNombre, $segundoNombre, $primerApellido, $segundoApellido) {
                $persona = Persona::create([
                    'Primer_Nombre' => $primerNombre,
                    'Segundo_Nombre' => $segundoNombre,
                    'Primer_Apellido' => $primerApellido,
                    'Segundo_Apellido' => $segundoApellido,
                    'Correo' => $this->correo,
                    'Direccion' => $this->direccion,
                    'Telefono' => $this->telefono,
                ]);

                Cliente::create([
                    'Id_Persona' => $persona->Id_Persona,
                    'Tipo_Cliente' => (int) $this->tipoCliente,
                    'Institucion' => (int) $this->tipoCliente === 2 ? $this->institucion : null,
                    'Municipio' => $this->municipio,
                    'Estado' => $this->estado,
                    'Tipo_pago' => (int) $this->tipoPago,
                ]);
            });

            $this->limpiarFormulario();
            $this->cargarClientes();

            $this->tipoMensaje = 'success';
            $this->mensaje = 'Cliente registrado correctamente.';
        } catch (Throwable $e) {
            report($e);

            $this->tipoMensaje = 'error';
            $this->mensaje = 'No se pudo registrar el cliente. Revise los datos e intente nuevamente.';
        }
    }

    public function limpiarFormulario(): void
    {
        $this->reset([
            'nombre',
            'apellido',
            'telefono',
            'correo',
            'direccion',
            'municipio',
            'institucion',
        ]);

        $this->tipoCliente = 1;
        $this->tipoPago = 1;
        $this->estado = true;

        $this->resetValidation();
    }

    public function cargarClientes(): void
    {
        $busqueda = trim($this->buscar);

        $this->clientes = Cliente::query()
            ->with('persona')
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($subQuery) use ($busqueda) {
                    $subQuery
                        ->where('Institucion', 'like', "%{$busqueda}%")
                        ->orWhere('Municipio', 'like', "%{$busqueda}%")
                        ->orWhereHas('persona', function ($personaQuery) use ($busqueda) {
                            $personaQuery
                                ->where('Primer_Nombre', 'like', "%{$busqueda}%")
                                ->orWhere('Segundo_Nombre', 'like', "%{$busqueda}%")
                                ->orWhere('Primer_Apellido', 'like', "%{$busqueda}%")
                                ->orWhere('Segundo_Apellido', 'like', "%{$busqueda}%")
                                ->orWhere('Telefono', 'like', "%{$busqueda}%")
                                ->orWhere('Correo', 'like', "%{$busqueda}%");
                        });
                });
            })
            ->orderByDesc('Id_Cliente')
            ->get()
            ->map(function (Cliente $cliente) {
                $persona = $cliente->persona;

                $nombreCompleto = trim(implode(' ', array_filter([
                    $persona?->Primer_Nombre,
                    $persona?->Segundo_Nombre,
                    $persona?->Primer_Apellido,
                    $persona?->Segundo_Apellido,
                ])));

                return [
                    'codigo' => $cliente->Id_Cliente,
                    'full_name' => $nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre',
                    'telefono' => $persona?->Telefono ?: 'Sin teléfono',
                    'correo' => $persona?->Correo ?: 'No registrado',
                    'municipio' => $cliente->Municipio ?: 'No registrado',
                    'tipo_cliente' => $this->obtenerNombreTipoCliente($cliente->Tipo_Cliente),
                    'tipo_pago' => $this->obtenerNombreTipoPago($cliente->Tipo_pago),
                    'estado' => $cliente->Estado ? 'Activo' : 'Inactivo',
                ];
            })
            ->toArray();
    }

    private function normalizarCampos(): void
    {
        $this->nombre = $this->limpiarTextoObligatorio($this->nombre);
        $this->apellido = $this->limpiarTextoObligatorio($this->apellido);
        $this->telefono = $this->limpiarTextoObligatorio($this->telefono);
        $this->municipio = $this->limpiarTextoObligatorio($this->municipio);

        $this->correo = $this->limpiarTextoOpcional($this->correo);
        $this->direccion = $this->limpiarTextoOpcional($this->direccion);
        $this->institucion = $this->limpiarTextoOpcional($this->institucion);

        if ($this->correo !== null) {
            $this->correo = mb_strtolower($this->correo);
        }
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

    private function separarEnDosPartes(string $valor): array
    {
        $partes = preg_split('/\s+/', trim($valor), 2, PREG_SPLIT_NO_EMPTY);

        return [
            $partes[0] ?? null,
            $partes[1] ?? null,
        ];
    }

    private function obtenerNombreTipoCliente(int|string|null $tipo): string
    {
        return (int) $tipo === 2 ? 'Institucional' : 'Natural';
    }

    private function obtenerNombreTipoPago(int|string|null $tipo): string
    {
        return (int) $tipo === 2 ? 'Crédito' : 'Contado';
    }
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-4 md:p-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#1A2B42]">Clientes</h1>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Registro y gestión de clientes del sistema.
        </p>
    </div>

    @if ($mensaje)
        <div
            class="rounded-2xl border px-4 py-3 text-sm font-semibold shadow-sm
            {{ $tipoMensaje === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                : 'border-red-200 bg-red-50 text-red-700' }}"
        >
            {{ $mensaje }}
        </div>
    @endif

    {{-- Formulario --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <x-form wire:submit="guardarCliente" no-separator>
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-[#1A2B42]">Registrar cliente</h2>
                <p class="text-base text-[#5F6B7A]">
                    Ingrese los datos del cliente. El correo y la dirección son opcionales.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Nombre
                    </label>
                    <x-input
                        wire:model.blur="nombre"
                        placeholder="Ej: Daniel Antonio"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('nombre')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Apellido
                    </label>
                    <x-input
                        wire:model.blur="apellido"
                        placeholder="Ej: López Pérez"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('apellido')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Teléfono
                    </label>
                    <x-input
                        wire:model.blur="telefono"
                        placeholder="Ej: +505 8888 8888"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('telefono')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Correo electrónico
                    </label>
                    <x-input
                        wire:model.blur="correo"
                        placeholder="cliente@correo.com (opcional)"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('correo')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Municipio
                    </label>
                    <x-input
                        wire:model.blur="municipio"
                        placeholder="Ingrese el municipio"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('municipio')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Tipo de cliente
                    </label>
                    <x-select
                        wire:model.live="tipoCliente"
                        :options="$tiposCliente"
                        option-value="id"
                        option-label="name"
                        placeholder="Seleccione el tipo"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                    />
                    @error('tipoCliente')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ((int) $tipoCliente === 2)
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                            Institución
                        </label>
                        <x-input
                            wire:model.blur="institucion"
                            placeholder="Nombre de la institución"
                            class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                        />
                        @error('institucion')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Tipo de pago
                    </label>
                    <x-select
                        wire:model="tipoPago"
                        :options="$tiposPago"
                        option-value="id"
                        option-label="name"
                        placeholder="Seleccione el tipo de pago"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                    />
                    @error('tipoPago')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-end">
                    <x-checkbox
                        wire:model="estado"
                        label="Cliente activo"
                        class="text-[#1A2B42]"
                    />
                </div>

                <div class="md:col-span-2 xl:col-span-3">
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Dirección
                    </label>
                    <x-textarea
                        wire:model.blur="direccion"
                        placeholder="Ingrese la dirección del cliente (opcional)"
                        rows="3"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('direccion')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <x-slot:actions>
                <div class="flex w-full flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <x-button
                        type="button"
                        label="Limpiar"
                        wire:click="limpiarFormulario"
                        class="rounded-xl border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#EAF2FB]"
                    />

                    <x-button
                        type="submit"
                        label="Guardar cliente"
                        spinner="guardarCliente"
                        class="rounded-xl border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30"
                    />
                </div>
            </x-slot:actions>
        </x-form>
    </x-card>

    {{-- Tabla --}}
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de clientes</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Consulte los clientes registrados en el sistema.
                </p>
            </div>

            <div class="w-full lg:max-w-sm">
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Buscar cliente
                </label>
                <x-input
                    wire:model.live.debounce.350ms="buscar"
                    placeholder="Buscar por nombre, teléfono, correo o municipio"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>
        </div>

        @if (count($clientes) > 0)
            <div class="overflow-x-auto rounded-2xl">
                <x-table
                    :headers="$headers"
                    :rows="$clientes"
                    class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
                />
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
</div>