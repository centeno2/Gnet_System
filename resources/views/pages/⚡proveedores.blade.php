<?php

use Livewire\Component;
use App\Models\Persona;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

new class extends Component
{
    public string $nombres = '';
    public string $apellidos = '';
    public string $telefono = '';
    public string $direccion = '';
    public string $codigoRuc = '';
    public string $nacionalidad = '';
    public string $buscar = '';

    public array $paises = [
        'Afganistán', 'Albania', 'Alemania', 'Andorra', 'Angola', 'Antigua y Barbuda',
        'Arabia Saudita', 'Argelia', 'Argentina', 'Armenia', 'Australia', 'Austria',
        'Azerbaiyán', 'Bahamas', 'Bangladés', 'Barbados', 'Baréin', 'Bélgica',
        'Belice', 'Benín', 'Bielorrusia', 'Bolivia', 'Bosnia y Herzegovina',
        'Botsuana', 'Brasil', 'Brunéi', 'Bulgaria', 'Burkina Faso', 'Burundi',
        'Bután', 'Cabo Verde', 'Camboya', 'Camerún', 'Canadá', 'Catar',
        'Chad', 'Chile', 'China', 'Chipre', 'Colombia', 'Comoras',
        'Corea del Norte', 'Corea del Sur', 'Costa de Marfil', 'Costa Rica',
        'Croacia', 'Cuba', 'Dinamarca', 'Dominica', 'Ecuador', 'Egipto',
        'El Salvador', 'Emiratos Árabes Unidos', 'Eritrea', 'Eslovaquia',
        'Eslovenia', 'España', 'Estados Unidos', 'Estonia', 'Esuatini',
        'Etiopía', 'Filipinas', 'Finlandia', 'Fiyi', 'Francia', 'Gabón',
        'Gambia', 'Georgia', 'Ghana', 'Granada', 'Grecia', 'Guatemala',
        'Guinea', 'Guinea-Bisáu', 'Guinea Ecuatorial', 'Guyana', 'Haití',
        'Honduras', 'Hungría', 'India', 'Indonesia', 'Irak', 'Irán',
        'Irlanda', 'Islandia', 'Islas Marshall', 'Islas Salomón', 'Israel',
        'Italia', 'Jamaica', 'Japón', 'Jordania', 'Kazajistán', 'Kenia',
        'Kirguistán', 'Kiribati', 'Kuwait', 'Laos', 'Lesoto', 'Letonia',
        'Líbano', 'Liberia', 'Libia', 'Liechtenstein', 'Lituania',
        'Luxemburgo', 'Madagascar', 'Malasia', 'Malaui', 'Maldivas',
        'Malí', 'Malta', 'Marruecos', 'Mauricio', 'Mauritania', 'México',
        'Micronesia', 'Moldavia', 'Mónaco', 'Mongolia', 'Montenegro',
        'Mozambique', 'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Nicaragua',
        'Níger', 'Nigeria', 'Noruega', 'Nueva Zelanda', 'Omán',
        'Países Bajos', 'Pakistán', 'Palaos', 'Palestina', 'Panamá',
        'Papúa Nueva Guinea', 'Paraguay', 'Perú', 'Polonia', 'Portugal',
        'Reino Unido', 'República Centroafricana', 'República Checa',
        'República de Macedonia del Norte', 'República del Congo',
        'República Democrática del Congo', 'República Dominicana', 'Ruanda',
        'Rumania', 'Rusia', 'Samoa', 'San Cristóbal y Nieves', 'San Marino',
        'San Vicente y las Granadinas', 'Santa Lucía', 'Santo Tomé y Príncipe',
        'Senegal', 'Serbia', 'Seychelles', 'Sierra Leona', 'Singapur',
        'Siria', 'Somalia', 'Sri Lanka', 'Sudáfrica', 'Sudán',
        'Sudán del Sur', 'Suecia', 'Suiza', 'Surinam', 'Tailandia',
        'Tanzania', 'Tayikistán', 'Timor Oriental', 'Togo', 'Tonga',
        'Trinidad y Tobago', 'Túnez', 'Turkmenistán', 'Turquía', 'Tuvalu',
        'Ucrania', 'Uganda', 'Uruguay', 'Uzbekistán', 'Vanuatu',
        'Vaticano', 'Venezuela', 'Vietnam', 'Yemen', 'Yibuti', 'Zambia',
        'Zimbabue',
    ];

    protected function rules(): array
    {
        return [
            'nombres' => [
                'required',
                'string',
                'max:100',
                'regex:/^[\pLÁÉÍÓÚáéíóúÑñÜü]+(?:\s+[\pLÁÉÍÓÚáéíóúÑñÜü]+)?$/u',
            ],
            'apellidos' => [
                'required',
                'string',
                'max:100',
                'regex:/^[\pLÁÉÍÓÚáéíóúÑñÜü]+(?:\s+[\pLÁÉÍÓÚáéíóúÑñÜü]+)?$/u',
            ],
            'telefono' => [
                'required',
                'string',
                'max:25',
                'regex:/^\+?[0-9\s().-]{7,25}$/',
            ],
            'direccion' => ['required', 'string', 'max:255'],
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
    }

    protected function messages(): array
    {
        return [
            'nombres.required' => 'El nombre es obligatorio.',
            'nombres.regex' => 'Ingrese máximo dos nombres y use solo letras.',

            'apellidos.required' => 'El apellido es obligatorio.',
            'apellidos.regex' => 'Ingrese máximo dos apellidos y use solo letras.',

            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.regex' => 'Ingrese un teléfono válido. Ejemplo: +505 8888 8888.',

            'direccion.required' => 'La dirección es obligatoria.',

            'codigoRuc.required' => 'El código RUC es obligatorio.',
            'codigoRuc.unique' => 'Este código RUC ya está registrado.',

            'nacionalidad.required' => 'Debe seleccionar una nacionalidad.',
            'nacionalidad.in' => 'La nacionalidad seleccionada no es válida.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'telefono' => 'teléfono',
            'direccion' => 'dirección',
            'codigoRuc' => 'código RUC',
            'nacionalidad' => 'nacionalidad',
        ];
    }

    public function guardarProveedor(): void
    {
        $this->validate(
            $this->rules(),
            $this->messages(),
            $this->validationAttributes()
        );

        [$primerNombre, $segundoNombre] = $this->separarEnDosPartes($this->nombres);
        [$primerApellido, $segundoApellido] = $this->separarEnDosPartes($this->apellidos);

        DB::transaction(function () use ($primerNombre, $segundoNombre, $primerApellido, $segundoApellido) {
            $persona = Persona::create([
                'Primer_Nombre' => $primerNombre,
                'Segundo_Nombre' => $segundoNombre,
                'Primer_Apellido' => $primerApellido,
                'Segundo_Apellido' => $segundoApellido,
                'Correo' => null,
                'Direccion' => $this->limpiarTexto($this->direccion),
                'Telefono' => $this->limpiarTexto($this->telefono),
            ]);

            Proveedor::create([
                'Id_Persona' => $persona->Id_Persona,
                'Estado' => true,
                'Nacionalidad' => $this->nacionalidad,
                'Codigo_Ruc' => $this->limpiarTexto($this->codigoRuc),
            ]);
        });

        $this->resetFormulario();

        session()->flash('success', 'Proveedor registrado correctamente.');
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

    private function limpiarTexto(string $valor): string
    {
        return trim(preg_replace('/\s+/', ' ', $valor));
    }

    public function resetFormulario(): void
    {
        $this->reset([
            'nombres',
            'apellidos',
            'telefono',
            'direccion',
            'codigoRuc',
            'nacionalidad',
        ]);
    }

    public function proveedores(): array
    {
        return Proveedor::query()
            ->with('persona')
            ->when(filled($this->buscar), function ($query) {
                $buscar = '%' . trim($this->buscar) . '%';

                $query->where(function ($q) use ($buscar) {
                    $q->where('Codigo_Ruc', 'like', $buscar)
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
            ->map(function ($proveedor) {
                $persona = $proveedor->persona;

                $nombreCompleto = trim(collect([
                    $persona?->Primer_Nombre,
                    $persona?->Segundo_Nombre,
                    $persona?->Primer_Apellido,
                    $persona?->Segundo_Apellido,
                ])->filter()->implode(' '));

                return [
                    'full_name' => $nombreCompleto ?: 'Sin nombre',
                    'phone' => $persona?->Telefono ?? '—',
                    'address' => $persona?->Direccion ?? '—',
                    'ruc' => $proveedor->Codigo_Ruc ?? '—',
                    'nationality' => $proveedor->Nacionalidad ?? '—',
                    'status' => $proveedor->Estado ? 'Activo' : 'Inactivo',
                ];
            })
            ->toArray();
    }
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#1A2B42]">Proveedores</h1>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Registro y gestión de proveedores del sistema.
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <form wire:submit="guardarProveedor" class="space-y-6">
            <div>
                <h2 class="text-2xl font-bold text-[#1A2B42]">Registrar proveedor</h2>
                <p class="text-base text-[#5F6B7A]">
                    Ingrese los datos personales y fiscales del proveedor.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
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

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Teléfono <span class="text-red-500">*</span>
                    </label>
                    <x-input
                        type="tel"
                        wire:model="telefono"
                        placeholder="Ejemplo: +505 8888 8888"
                        class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                    @error('telefono')
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

                <div>
                    <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                        Dirección <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        wire:model="direccion"
                        rows="3"
                        placeholder="Ingrese la dirección"
                        class="w-full rounded-xl border-0 bg-[#F0F3F7] px-4 py-3 text-[#1A2B42] outline-none ring-1 ring-[#D7E4F3] placeholder:text-[#7B8794] focus:ring-2 focus:ring-[#2E8BC0]"
                    ></textarea>
                    @error('direccion')
                        <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
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
            ['key' => 'full_name', 'label' => 'Nombre completo'],
            ['key' => 'phone', 'label' => 'Teléfono'],
            ['key' => 'address', 'label' => 'Dirección'],
            ['key' => 'ruc', 'label' => 'Código RUC'],
            ['key' => 'nationality', 'label' => 'Nacionalidad'],
            ['key' => 'status', 'label' => 'Estado del proveedor'],
        ];

        $proveedores = $this->proveedores();
    @endphp

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4 space-y-3">
            <div>
                <h2 class="text-2xl font-bold text-[#1A2B42]">Listado de proveedores</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Consulta los proveedores registrados en el sistema.
                </p>
            </div>

            <x-input
                wire:model.live.debounce.300ms="buscar"
                placeholder="Buscar por nombre, teléfono, dirección, RUC o nacionalidad"
                class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
            />
        </div>

        <x-table
            :headers="$headers"
            :rows="$proveedores"
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
        >
        </x-table>
    </x-card>
</div>