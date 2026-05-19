<?php

use Livewire\Component;
use App\Models\Persona;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\Intl\Countries;

new class extends Component
{
    public bool $esInstitucional = false;

    public string $nombres = '';
    public string $apellidos = '';
    public string $nombreInstitucion = '';

    public string $telefono = '';
    public string $correo = '';
    public string $direccion = '';

    public string $codigoRuc = '';
    public string $nacionalidad = '';

    public string $buscar = '';
    public array $paises = [];

    public ?array $mensaje = null;

    public bool $modalEditarProveedor = false;
    public ?int $editProveedorId = null;
    public bool $editEsInstitucional = false;

    public string $editTipoProveedor = '';
    public string $editNombres = '';
    public string $editApellidos = '';
    public string $editNombreInstitucion = '';
    public string $editTelefono = '';
    public string $editCorreo = '';
    public string $editDireccion = '';
    public string $editCodigoRuc = '';
    public string $editNacionalidad = '';
    public string $editEstado = '1';

    public function mount(): void
    {
        $this->paises = collect(Countries::getNames('es'))
            ->sort()
            ->values()
            ->all();
    }

    public function updatedEsInstitucional(): void
    {
        $this->resetValidation();

        $this->reset([
            'nombres',
            'apellidos',
            'nombreInstitucion',
            'telefono',
            'correo',
            'direccion',
            'codigoRuc',
            'nacionalidad',
        ]);
    }

    protected function rules(): array
    {
        $rules = [
            'telefono' => [
                'required',
                'string',
                'max:25',
                'regex:/^\+?[0-9\s().-]{7,25}$/',
            ],
            'correo' => [
                'required',
                'email',
                'max:150',
            ],
            'direccion' => [
                'required',
                'string',
                'max:255',
            ],
            'codigoRuc' => [
                'required',
                'string',
                'max:30',
                Rule::unique('proveedor', 'Codigo_Ruc'),
            ],
            'nacionalidad' => [
                'required',
                'string',
                Rule::in($this->paises),
            ],
        ];

        if ($this->esInstitucional) {
            $rules['nombreInstitucion'] = [
                'required',
                'string',
                'max:150',
            ];

            return $rules;
        }

        $rules['nombres'] = [
            'required',
            'string',
            'max:100',
            'regex:/^[\pLÁÉÍÓÚáéíóúÑñÜü]+(?:\s+[\pLÁÉÍÓÚáéíóúÑñÜü]+)?$/u',
        ];

        $rules['apellidos'] = [
            'required',
            'string',
            'max:100',
            'regex:/^[\pLÁÉÍÓÚáéíóúÑñÜü]+(?:\s+[\pLÁÉÍÓÚáéíóúÑñÜü]+)?$/u',
        ];

        return $rules;
    }

    protected function editRules(): array
    {
        $rucRule = Rule::unique('proveedor', 'Codigo_Ruc');

        if ($this->editProveedorId) {
            $rucRule->ignore($this->editProveedorId, 'Id_Proveedor');
        }

        $rules = [
            'editTelefono' => [
                'required',
                'string',
                'max:25',
                'regex:/^\+?[0-9\s().-]{7,25}$/',
            ],
            'editCorreo' => [
                'required',
                'email',
                'max:150',
            ],
            'editDireccion' => [
                'required',
                'string',
                'max:255',
            ],
            'editCodigoRuc' => [
                'required',
                'string',
                'max:30',
                $rucRule,
            ],
            'editNacionalidad' => [
                'required',
                'string',
                Rule::in($this->paises),
            ],
            'editEstado' => [
                'required',
                'in:0,1',
            ],
        ];

        if ($this->editEsInstitucional) {
            $rules['editNombreInstitucion'] = [
                'required',
                'string',
                'max:150',
            ];

            return $rules;
        }

        $rules['editNombres'] = [
            'required',
            'string',
            'max:100',
            'regex:/^[\pLÁÉÍÓÚáéíóúÑñÜü]+(?:\s+[\pLÁÉÍÓÚáéíóúÑñÜü]+)?$/u',
        ];

        $rules['editApellidos'] = [
            'required',
            'string',
            'max:100',
            'regex:/^[\pLÁÉÍÓÚáéíóúÑñÜü]+(?:\s+[\pLÁÉÍÓÚáéíóúÑñÜü]+)?$/u',
        ];

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'nombres.required' => 'El nombre es obligatorio.',
            'nombres.regex' => 'Ingrese máximo dos nombres y use solo letras.',

            'apellidos.required' => 'El apellido es obligatorio.',
            'apellidos.regex' => 'Ingrese máximo dos apellidos y use solo letras.',

            'nombreInstitucion.required' => 'El nombre de la institución es obligatorio.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.regex' => 'Ingrese un teléfono válido. Ejemplo: 58631620.',

            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Ingrese un correo electrónico válido. Ejemplo: example@gmail.com.',

            'direccion.required' => 'La dirección es obligatoria.',

            'codigoRuc.required' => 'El código RUC es obligatorio.',
            'codigoRuc.unique' => 'Este código RUC ya está registrado.',

            'nacionalidad.required' => 'Debe seleccionar una nacionalidad.',
            'nacionalidad.in' => 'La nacionalidad seleccionada no es válida.',

            'editNombres.required' => 'El nombre es obligatorio.',
            'editNombres.regex' => 'Ingrese máximo dos nombres y use solo letras.',

            'editApellidos.required' => 'El apellido es obligatorio.',
            'editApellidos.regex' => 'Ingrese máximo dos apellidos y use solo letras.',

            'editNombreInstitucion.required' => 'El nombre de la institución es obligatorio.',

            'editTelefono.required' => 'El teléfono es obligatorio.',
            'editTelefono.regex' => 'Ingrese un teléfono válido. Ejemplo: 58631620.',

            'editCorreo.required' => 'El correo electrónico es obligatorio.',
            'editCorreo.email' => 'Ingrese un correo electrónico válido. Ejemplo: example@gmail.com.',

            'editDireccion.required' => 'La dirección es obligatoria.',

            'editCodigoRuc.required' => 'El código RUC es obligatorio.',
            'editCodigoRuc.unique' => 'Este código RUC ya está registrado.',

            'editNacionalidad.required' => 'Debe seleccionar una nacionalidad.',
            'editNacionalidad.in' => 'La nacionalidad seleccionada no es válida.',

            'editEstado.required' => 'Debe seleccionar el estado.',
            'editEstado.in' => 'El estado seleccionado no es válido.',
        ];
    }

    public function guardarProveedor(): void
    {
        $this->validate($this->rules(), $this->messages());

        DB::transaction(function () {
            if ($this->esInstitucional) {
                $this->guardarProveedorInstitucional();
                return;
            }

            $this->guardarProveedorPersonal();
        });

        $this->resetFormulario();

        $this->mostrarMensaje('success', 'Proveedor guardado correctamente.');
    }

    public function cargarProveedorEditar(int $id): void
    {
        $proveedor = Proveedor::query()
            ->with('persona')
            ->where('Id_Proveedor', $id)
            ->first();

        if (! $proveedor) {
            $this->mostrarMensaje('error', 'El proveedor seleccionado no existe.');
            return;
        }

        $this->resetValidation();

        $this->editProveedorId = (int) $proveedor->Id_Proveedor;
        $this->editEsInstitucional = $proveedor->esEmpresa();
        $this->editTipoProveedor = $this->editEsInstitucional ? 'Institucional' : 'Personal';
        $this->editEstado = (int) $proveedor->Estado === 1 ? '1' : '0';

        $this->editCorreo = (string) ($proveedor->Correo_Empresa ?? '');
        $this->editCodigoRuc = (string) ($proveedor->Codigo_Ruc ?? '');
        $this->editNacionalidad = (string) ($proveedor->Nacionalidad ?? '');

        if ($this->editEsInstitucional) {
            $this->editNombreInstitucion = (string) ($proveedor->Empresa ?? '');
            $this->editTelefono = (string) ($proveedor->Telefono_Empresa ?? '');
            $this->editDireccion = (string) ($proveedor->Direccion_Empresa ?? '');

            $this->editNombres = '';
            $this->editApellidos = '';
        } else {
            $persona = $proveedor->persona;

            $this->editNombres = $this->limpiarTexto(
                trim(
                    ($persona?->Primer_Nombre ?? '') . ' ' .
                    ($persona?->Segundo_Nombre ?? '')
                )
            );

            $this->editApellidos = $this->limpiarTexto(
                trim(
                    ($persona?->Primer_Apellido ?? '') . ' ' .
                    ($persona?->Segundo_Apellido ?? '')
                )
            );

            $this->editTelefono = (string) ($persona?->Telefono ?? '');
            $this->editDireccion = (string) ($persona?->Direccion ?? '');
            $this->editNombreInstitucion = '';
        }

        $this->modalEditarProveedor = true;
    }

    public function actualizarProveedor(): void
    {
        $this->validate($this->editRules(), $this->messages());

        DB::transaction(function () {
            $proveedor = Proveedor::query()
                ->with('persona')
                ->where('Id_Proveedor', $this->editProveedorId)
                ->lockForUpdate()
                ->first();

            if (! $proveedor) {
                throw new RuntimeException('El proveedor seleccionado ya no existe.');
            }

            $estado = $this->editEstado === '1' ? 1 : 0;

            if ($this->editEsInstitucional) {
                $proveedor->forceFill([
                    'Empresa' => $this->limpiarTexto($this->editNombreInstitucion),
                    'Telefono_Empresa' => $this->telefonoSinCodigoPais($this->editTelefono),
                    'Direccion_Empresa' => $this->limpiarTexto($this->editDireccion),
                    'Correo_Empresa' => $this->limpiarTexto($this->editCorreo),
                    'Estado' => $estado,
                    'Nacionalidad' => $this->editNacionalidad,
                    'Codigo_Ruc' => $this->limpiarTexto($this->editCodigoRuc),
                ])->save();

                return;
            }

            [$primerNombre, $segundoNombre] = $this->separarEnDosPartes($this->editNombres);
            [$primerApellido, $segundoApellido] = $this->separarEnDosPartes($this->editApellidos);

            $persona = $proveedor->persona;

            if (! $persona) {
                $persona = Persona::create([
                    'Primer_Nombre' => $primerNombre,
                    'Segundo_Nombre' => $segundoNombre,
                    'Primer_Apellido' => $primerApellido,
                    'Segundo_Apellido' => $segundoApellido,
                    'Direccion' => $this->limpiarTexto($this->editDireccion),
                    'Telefono' => $this->telefonoSinCodigoPais($this->editTelefono),
                ]);

                $proveedor->Id_Persona = $persona->Id_Persona;
            } else {
                $persona->forceFill([
                    'Primer_Nombre' => $primerNombre,
                    'Segundo_Nombre' => $segundoNombre,
                    'Primer_Apellido' => $primerApellido,
                    'Segundo_Apellido' => $segundoApellido,
                    'Direccion' => $this->limpiarTexto($this->editDireccion),
                    'Telefono' => $this->telefonoSinCodigoPais($this->editTelefono),
                ])->save();
            }

            $proveedor->forceFill([
                'Empresa' => null,
                'Telefono_Empresa' => null,
                'Direccion_Empresa' => null,
                'Correo_Empresa' => $this->limpiarTexto($this->editCorreo),
                'Estado' => $estado,
                'Nacionalidad' => $this->editNacionalidad,
                'Codigo_Ruc' => $this->limpiarTexto($this->editCodigoRuc),
            ])->save();
        });

        $this->cerrarModalEditar();

        $this->mostrarMensaje('success', 'Proveedor actualizado correctamente.');
    }

    public function cerrarModalEditar(): void
    {
        $this->modalEditarProveedor = false;

        $this->reset([
            'editProveedorId',
            'editEsInstitucional',
            'editTipoProveedor',
            'editNombres',
            'editApellidos',
            'editNombreInstitucion',
            'editTelefono',
            'editCorreo',
            'editDireccion',
            'editCodigoRuc',
            'editNacionalidad',
            'editEstado',
        ]);

        $this->editEstado = '1';

        $this->resetValidation();
    }

    private function guardarProveedorPersonal(): void
    {
        [$primerNombre, $segundoNombre] = $this->separarEnDosPartes($this->nombres);
        [$primerApellido, $segundoApellido] = $this->separarEnDosPartes($this->apellidos);

        $persona = Persona::create([
            'Primer_Nombre' => $primerNombre,
            'Segundo_Nombre' => $segundoNombre,
            'Primer_Apellido' => $primerApellido,
            'Segundo_Apellido' => $segundoApellido,
            'Direccion' => $this->limpiarTexto($this->direccion),
            'Telefono' => $this->telefonoSinCodigoPais($this->telefono),
        ]);

        Proveedor::create([
            'Id_Persona' => $persona->Id_Persona,
            'Tipo_Proveedor' => Proveedor::TIPO_NATURAL,
            'Empresa' => null,
            'Telefono_Empresa' => null,
            'Direccion_Empresa' => null,
            'Correo_Empresa' => $this->limpiarTexto($this->correo),
            'Estado' => true,
            'Nacionalidad' => $this->nacionalidad,
            'Codigo_Ruc' => $this->limpiarTexto($this->codigoRuc),
        ]);
    }

    private function guardarProveedorInstitucional(): void
    {
        Proveedor::create([
            'Id_Persona' => null,
            'Tipo_Proveedor' => Proveedor::TIPO_EMPRESA,
            'Empresa' => $this->limpiarTexto($this->nombreInstitucion),
            'Telefono_Empresa' => $this->telefonoSinCodigoPais($this->telefono),
            'Direccion_Empresa' => $this->limpiarTexto($this->direccion),
            'Correo_Empresa' => $this->limpiarTexto($this->correo),
            'Estado' => true,
            'Nacionalidad' => $this->nacionalidad,
            'Codigo_Ruc' => $this->limpiarTexto($this->codigoRuc),
        ]);
    }

    private function separarEnDosPartes(string $valor): array
    {
        $valor = $this->limpiarTexto($valor);

        $partes = preg_split('/\s+/', $valor, 2);

        return [
            $partes[0] ?? '',
            $partes[1] ?? null,
        ];
    }

    private function limpiarTexto(?string $valor): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $valor));
    }

    private function telefonoSinCodigoPais(string $telefono): string
    {
        $telefono = preg_replace('/\D+/', '', $telefono);

        if (str_starts_with($telefono, '505') && strlen($telefono) > 8) {
            return substr($telefono, 3);
        }

        return $telefono;
    }

    public function resetFormulario(): void
    {
        $this->reset([
            'esInstitucional',
            'nombres',
            'apellidos',
            'nombreInstitucion',
            'telefono',
            'correo',
            'direccion',
            'codigoRuc',
            'nacionalidad',
        ]);

        $this->resetValidation();
    }

    private function mostrarMensaje(string $tipo, string $texto): void
    {
        $this->mensaje = [
            'tipo' => $tipo,
            'texto' => $texto,
        ];
    }

    public function proveedores(): array
    {
        return Proveedor::query()
            ->with('persona')
            ->when(filled($this->buscar), function ($query) {
                $buscar = '%' . trim($this->buscar) . '%';

                $query->where(function ($q) use ($buscar) {
                    $q->where('Empresa', 'like', $buscar)
                        ->orWhere('Telefono_Empresa', 'like', $buscar)
                        ->orWhere('Direccion_Empresa', 'like', $buscar)
                        ->orWhere('Correo_Empresa', 'like', $buscar)
                        ->orWhere('Codigo_Ruc', 'like', $buscar)
                        ->orWhere('Nacionalidad', 'like', $buscar)
                        ->orWhereHas('persona', function ($personaQuery) use ($buscar) {
                            $personaQuery
                                ->where('Primer_Nombre', 'like', $buscar)
                                ->orWhere('Segundo_Nombre', 'like', $buscar)
                                ->orWhere('Primer_Apellido', 'like', $buscar)
                                ->orWhere('Segundo_Apellido', 'like', $buscar)
                                ->orWhere('Telefono', 'like', $buscar)
                                ->orWhere('Direccion', 'like', $buscar);
                        });
                });
            })
            ->orderByDesc('Id_Proveedor')
            ->get()
            ->map(function (Proveedor $proveedor) {
                $esInstitucional = $proveedor->esEmpresa();
                $persona = $proveedor->persona;

                $nombreCompleto = $this->limpiarTexto(
                    trim(
                        ($persona?->Primer_Nombre ?? '') . ' ' .
                        ($persona?->Segundo_Nombre ?? '') . ' ' .
                        ($persona?->Primer_Apellido ?? '') . ' ' .
                        ($persona?->Segundo_Apellido ?? '')
                    )
                );

                return [
                    'id' => (int) $proveedor->Id_Proveedor,
                    'type' => $esInstitucional ? 'Institucional' : 'Personal',
                    'full_name' => $esInstitucional
                        ? ($proveedor->Empresa ?: 'Sin institución')
                        : ($nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre'),
                    'phone' => $esInstitucional
                        ? ($proveedor->Telefono_Empresa ?: '—')
                        : ($persona?->Telefono ?: '—'),
                    'email' => $proveedor->Correo_Empresa ?: '—',
                    'address' => $esInstitucional
                        ? ($proveedor->Direccion_Empresa ?: '—')
                        : ($persona?->Direccion ?: '—'),
                    'ruc' => $proveedor->Codigo_Ruc ?? '—',
                    'nationality' => $proveedor->Nacionalidad ?? '—',
                    'status' => $proveedor->Estado ? 'Activo' : 'Inactivo',
                    'acciones' => '',
                ];
            })
            ->toArray();
    }
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-6 space-y-6">
    @if ($mensaje)
        <div
            class="fixed right-5 top-5 z-50 flex w-[min(480px,calc(100vw-2rem))] items-center justify-between gap-4 rounded-2xl border px-5 py-4 shadow-lg
            {{ $mensaje['tipo'] === 'success'
                ? 'border-[#B7D6F2] bg-[#EAF4FD] text-[#1A2B42]'
                : 'border-red-200 bg-red-50 text-red-700' }}"
        >
            <p class="text-sm font-bold">
                {{ $mensaje['texto'] }}
            </p>

            <button
                type="button"
                wire:click="$set('mensaje', null)"
                class="rounded-lg px-2 text-xl leading-none text-[#5F6B7A] hover:bg-white/70"
            >
                ×
            </button>
        </div>
    @endif

    <div>
        <h1 class="text-3xl font-bold text-[#1A2B42]">Proveedores</h1>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Registro y gestión de proveedores del sistema.
        </p>
    </div>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <form wire:submit="guardarProveedor" class="space-y-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-[#1A2B42]">
                        Registrar proveedor
                    </h2>

                    <p class="text-base text-[#5F6B7A]">
                        Seleccione el tipo de proveedor y complete los datos correspondientes.
                    </p>
                </div>

                <label class="flex cursor-pointer select-none items-center gap-3 rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] px-4 py-3">
                    <span class="text-sm font-semibold {{ $esInstitucional ? 'text-[#5F6B7A]' : 'text-[#1A2B42]' }}">
                        Personal
                    </span>

                    <input
                        type="checkbox"
                        wire:model.live="esInstitucional"
                        class="sr-only"
                    />

                    <span class="relative h-7 w-14 rounded-full transition duration-300 {{ $esInstitucional ? 'bg-[#2E8BC0]' : 'bg-[#D7E4F3]' }}">
                        <span class="absolute left-1 top-1 h-5 w-5 rounded-full bg-white shadow transition-transform duration-300 {{ $esInstitucional ? 'translate-x-7' : 'translate-x-0' }}"></span>
                    </span>

                    <span class="text-sm font-semibold {{ $esInstitucional ? 'text-[#1A2B42]' : 'text-[#5F6B7A]' }}">
                        Institucional
                    </span>
                </label>
            </div>

            <div class="rounded-2xl border border-[#D7E4F3] bg-[#F7F9FC] p-5">
                <h3 class="mb-4 text-lg font-bold text-[#1A2B42]">
                    {{ $esInstitucional ? 'Datos del proveedor institucional' : 'Datos del proveedor personal' }}
                </h3>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @if ($esInstitucional)
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                Nombre de la institución <span class="text-red-500">*</span>
                            </label>

                            <x-input
                                wire:model="nombreInstitucion"
                                placeholder="Ejemplo: Amazon"
                                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                            />

                            @error('nombreInstitucion')
                                <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                Nombres <span class="text-red-500">*</span>
                            </label>

                            <x-input
                                wire:model="nombres"
                                placeholder="Ejemplo: Daniel Antonio"
                                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                            />

                            @error('nombres')
                                <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                                Apellidos <span class="text-red-500">*</span>
                            </label>

                            <x-input
                                wire:model="apellidos"
                                placeholder="Ejemplo: López García"
                                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                            />

                            @error('apellidos')
                                <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                            Teléfono <span class="text-red-500">*</span>
                        </label>

                        <x-input
                            type="tel"
                            wire:model="telefono"
                            placeholder="Ejemplo: 58631620"
                            class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                        />

                        @error('telefono')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                            Correo electrónico <span class="text-red-500">*</span>
                        </label>

                        <x-input
                            type="email"
                            wire:model="correo"
                            placeholder="Ejemplo: example@gmail.com"
                            class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                        />

                        @error('correo')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                            Código RUC <span class="text-red-500">*</span>
                        </label>

                        <x-input
                            wire:model="codigoRuc"
                            placeholder="Ingrese el código RUC"
                            class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                        />

                        @error('codigoRuc')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                            Nacionalidad <span class="text-red-500">*</span>
                        </label>

                        <select
                            wire:model="nacionalidad"
                            class="w-full rounded-xl border-0 bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none ring-1 ring-[#D7E4F3] focus:ring-2 focus:ring-[#2E8BC0]"
                        >
                            <option value="">Seleccione un país</option>

                            @foreach ($paises as $pais)
                                <option value="{{ $pais }}">{{ $pais }}</option>
                            @endforeach
                        </select>

                        @error('nacionalidad')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                            Dirección <span class="text-red-500">*</span>
                        </label>

                        <textarea
                            wire:model="direccion"
                            rows="3"
                            placeholder="{{ $esInstitucional ? 'Ingrese la dirección de la institución' : 'Ingrese la dirección del proveedor' }}"
                            class="w-full rounded-xl border-0 bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none ring-1 ring-[#D7E4F3] placeholder:text-[#7B8794] focus:ring-2 focus:ring-[#2E8BC0]"
                        ></textarea>

                        @error('direccion')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-button
                    type="button"
                    label="Limpiar"
                    wire:click="resetFormulario"
                    class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#EAF2FB]"
                />

                <x-button
                    type="submit"
                    label="Guardar proveedor"
                    spinner="guardarProveedor"
                    class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30"
                />
            </div>
        </form>
    </x-card>

    @php
        $headers = [
            ['key' => 'type', 'label' => 'Tipo'],
            ['key' => 'full_name', 'label' => 'Proveedor'],
            ['key' => 'phone', 'label' => 'Teléfono'],
            ['key' => 'email', 'label' => 'Correo'],
            ['key' => 'address', 'label' => 'Dirección'],
            ['key' => 'ruc', 'label' => 'Código RUC'],
            ['key' => 'nationality', 'label' => 'Nacionalidad'],
            ['key' => 'status', 'label' => 'Estado'],
            ['key' => 'acciones', 'label' => 'Acciones'],
        ];

        $proveedores = $this->proveedores();
    @endphp

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4 space-y-3">
            <div>
                <h2 class="text-2xl font-bold text-[#1A2B42]">
                    Listado de proveedores
                </h2>

                <p class="text-sm text-[#5F6B7A]">
                    Consulta los proveedores personales e institucionales registrados en el sistema.
                </p>
            </div>

            <x-input
                wire:model.live.debounce.300ms="buscar"
                icon="o-magnifying-glass"
                placeholder="Buscar por proveedor, teléfono, correo, dirección, RUC o nacionalidad"
                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
            />
        </div>

        <x-table
            :headers="$headers"
            :rows="$proveedores"
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl [&_tbody_tr:hover]:bg-[#F7F9FC]"
        >
            @scope('cell_full_name', $row)
                <span class="block min-w-[220px] whitespace-nowrap font-medium text-[#1A2B42]">
                    {{ $row['full_name'] }}
                </span>
            @endscope

            @scope('cell_status', $row)
                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $row['status'] === 'Activo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $row['status'] }}
                </span>
            @endscope

            @scope('cell_acciones', $row)
                <x-button
                    icon="o-pencil-square"
                    label="Editar"
                    wire:click="cargarProveedorEditar({{ $row['id'] }})"
                    class="h-10 min-h-10 rounded-md border-0 bg-[#2E8BC0] px-4 text-sm font-bold text-white hover:bg-[#0B6FE4]"
                />
            @endscope
        </x-table>
    </x-card>

    <x-modal
        wire:model="modalEditarProveedor"
        class="backdrop-blur"
        box-class="max-w-3xl rounded-2xl bg-white p-0"
    >
        <div class="relative p-6">
            <button
                type="button"
                wire:click="cerrarModalEditar"
                class="absolute right-5 top-4 rounded-lg px-2 text-2xl leading-none text-[#1A2B42] hover:bg-[#F0F3F7]"
            >
                ×
            </button>

            <div class="mb-6">
                <h2 class="text-2xl font-black text-[#1A2B42]">
                    Editar proveedor
                </h2>

                <p class="mt-1 text-base text-[#5F6B7A]">
                    Modifique los datos generales, contacto o estado del proveedor.
                </p>
            </div>

            <form wire:submit="actualizarProveedor" class="space-y-5">
                <div>
                    <label class="mb-2 block text-sm font-bold text-[#1A2B42]">
                        Tipo de proveedor
                    </label>

                    <x-input
                        wire:model="editTipoProveedor"
                        readonly
                        class="h-12 min-h-12 w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                    />
                </div>

                @if ($editEsInstitucional)
                    <div>
                        <label class="mb-2 block text-sm font-bold text-[#1A2B42]">
                            Institución
                        </label>

                        <x-input
                            wire:model="editNombreInstitucion"
                            class="h-12 min-h-12 w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                        />

                        @error('editNombreInstitucion')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div>
                        <label class="mb-2 block text-sm font-bold text-[#1A2B42]">
                            Nombres
                        </label>

                        <x-input
                            wire:model="editNombres"
                            class="h-12 min-h-12 w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                        />

                        @error('editNombres')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-[#1A2B42]">
                            Apellidos
                        </label>

                        <x-input
                            wire:model="editApellidos"
                            class="h-12 min-h-12 w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                        />

                        @error('editApellidos')
                            <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label class="mb-2 block text-sm font-bold text-[#1A2B42]">
                        Teléfono
                    </label>

                    <x-input
                        wire:model="editTelefono"
                        type="tel"
                        class="h-12 min-h-12 w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                    />

                    @error('editTelefono')
                        <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-[#1A2B42]">
                        Correo electrónico
                    </label>

                    <x-input
                        wire:model="editCorreo"
                        type="email"
                        class="h-12 min-h-12 w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                    />

                    @error('editCorreo')
                        <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-[#1A2B42]">
                        Código RUC
                    </label>

                    <x-input
                        wire:model="editCodigoRuc"
                        class="h-12 min-h-12 w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42]"
                    />

                    @error('editCodigoRuc')
                        <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-[#1A2B42]">
                        Nacionalidad
                    </label>

                    <select
                        wire:model="editNacionalidad"
                        class="h-12 w-full rounded-xl border border-[#D7E4F3] bg-white px-4 text-[#1A2B42] outline-none focus:ring-2 focus:ring-[#2E8BC0]"
                    >
                        <option value="">Seleccione un país</option>

                        @foreach ($paises as $pais)
                            <option value="{{ $pais }}">{{ $pais }}</option>
                        @endforeach
                    </select>

                    @error('editNacionalidad')
                        <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-[#1A2B42]">
                        Dirección
                    </label>

                    <textarea
                        wire:model="editDireccion"
                        rows="3"
                        class="w-full rounded-xl border-0 bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none ring-1 ring-[#D7E4F3] focus:ring-2 focus:ring-[#2E8BC0]"
                    ></textarea>

                    @error('editDireccion')
                        <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-[#1A2B42]">
                        Estado
                    </label>

                    <select
                        wire:model.live="editEstado"
                        class="h-12 w-full rounded-xl border border-[#D7E4F3] bg-white px-4 text-[#1A2B42] outline-none focus:ring-2 focus:ring-[#2E8BC0]"
                    >
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>

                    @error('editEstado')
                        <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-3">
                    <x-button
                        type="button"
                        label="Cancelar"
                        wire:click="cerrarModalEditar"
                        class="h-12 min-h-12 rounded-md border border-[#D7E4F3] bg-white px-6 font-bold text-[#1A2B42] shadow-sm hover:bg-[#F0F3F7]"
                    />

                    <x-button
                        type="submit"
                        icon="o-check"
                        label="Guardar cambios"
                        spinner="actualizarProveedor"
                        class="h-12 min-h-12 rounded-md border-0 bg-[#0E48A1] px-6 font-bold text-white shadow-sm hover:bg-[#0B6FE4]"
                    />
                </div>
            </form>
        </div>
    </x-modal>
</div>