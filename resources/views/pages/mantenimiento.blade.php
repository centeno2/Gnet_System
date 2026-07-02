<?php

use App\Services\Mantenimiento\DatabaseBackupService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

new class extends Component
{
    use Toast;
    use WithFileUploads;

    public $archivoRestauracion = null;

    public array $respaldos = [];

    public bool $modalRestaurar = false;

    public ?string $tipoRestauracion = null;

    public ?string $archivoSeleccionado = null;

    public ?string $nombreArchivoConfirmacion = null;

    public function mount(): void
    {
        $this->cargarRespaldos();
    }

    public function crearRespaldo(): void
    {
        try {
            app(DatabaseBackupService::class)->crearRespaldo();

            $this->cargarRespaldos();

            $this->success('Respaldo creado correctamente.');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function confirmarRestauracionSubida(): void
    {
        $this->validarArchivoSql();

        $this->tipoRestauracion = 'subido';
        $this->archivoSeleccionado = null;
        $this->nombreArchivoConfirmacion = $this->archivoRestauracion->getClientOriginalName();
        $this->modalRestaurar = true;
    }

    public function confirmarRestauracionExistente(string $archivo): void
    {
        if (! Storage::disk('local')->exists($archivo)) {
            $this->error('El respaldo seleccionado no existe.');
            $this->cargarRespaldos();

            return;
        }

        $this->tipoRestauracion = 'existente';
        $this->archivoSeleccionado = $archivo;
        $this->nombreArchivoConfirmacion = basename($archivo);
        $this->modalRestaurar = true;
    }

    public function restaurarBaseDatos(): void
    {
        try {
            $service = app(DatabaseBackupService::class);

            if ($this->tipoRestauracion === 'subido') {
                $archivo = $this->guardarArchivoSubido();
                $service->restaurarDesdeStorage($archivo);
            } elseif ($this->tipoRestauracion === 'existente' && filled($this->archivoSeleccionado)) {
                $service->restaurarDesdeStorage($this->archivoSeleccionado);
            } else {
                $this->warning('Selecciona un respaldo para restaurar.');

                return;
            }

            $this->reset([
                'archivoRestauracion',
                'tipoRestauracion',
                'archivoSeleccionado',
                'nombreArchivoConfirmacion',
                'modalRestaurar',
            ]);

            $this->cargarRespaldos();

            $this->success('Base de datos restaurada correctamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    public function descargarRespaldo(string $archivo): BinaryFileResponse
    {
        if (! Storage::disk('local')->exists($archivo)) {
            $this->error('El respaldo seleccionado no existe.');
            $this->cargarRespaldos();

            abort(404);
        }

        return response()->download(
            Storage::disk('local')->path($archivo),
            basename($archivo)
        );
    }

    public function eliminarRespaldo(string $archivo): void
    {
        try {
            app(DatabaseBackupService::class)->eliminarRespaldo($archivo);

            $this->cargarRespaldos();

            $this->success('Respaldo eliminado correctamente.');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }
    }

    private function cargarRespaldos(): void
    {
        $this->respaldos = app(DatabaseBackupService::class)->listarRespaldos();
    }

    private function validarArchivoSql(): void
    {
        $this->validate([
            'archivoRestauracion' => ['required', 'file', 'max:102400'],
        ], [
            'archivoRestauracion.required' => 'Selecciona un archivo SQL.',
            'archivoRestauracion.file' => 'El respaldo debe ser un archivo válido.',
            'archivoRestauracion.max' => 'El respaldo no debe superar los 100 MB.',
        ]);

        $extension = Str::lower($this->archivoRestauracion->getClientOriginalExtension());

        if ($extension !== 'sql') {
            throw ValidationException::withMessages([
                'archivoRestauracion' => 'Solo se permiten archivos con extensión .sql.',
            ]);
        }
    }

    private function guardarArchivoSubido(): string
    {
        $this->validarArchivoSql();

        $nombreOriginal = pathinfo($this->archivoRestauracion->getClientOriginalName(), PATHINFO_FILENAME);

        $nombre = 'gnet_cargado_' .
            now()->format('Ymd_His') .
            '_' .
            Str::slug($nombreOriginal) .
            '.sql';

        return $this->archivoRestauracion->storeAs(
            'respaldos/cargados',
            $nombre,
            'local'
        );
    }
};
?>

@php
    $pageClass = 'min-h-screen w-full bg-[#F0F3F7] px-4 py-6 md:px-6 md:py-8';
    $cardClass = 'border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-sm [&_.text-base-content\\/70]:text-[#5F6B7A] [&_.text-sm]:text-[#5F6B7A] [&_.text-base-content]:text-[#1A2B42] [&_.card-title]:text-[#1A2B42] [&_label]:text-[#1A2B42]';
    $primaryButtonClass = 'btn-sm h-[46px] border-0 bg-[#2E8BC0] text-white hover:!bg-[#0B6FE4] hover:!text-white active:!bg-[#256F99] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#B7D6F2] disabled:border-0 disabled:bg-[#B8CADB] disabled:text-white';
    $dangerButtonClass = 'btn-sm h-[42px] border-0 bg-red-600 text-white hover:!bg-red-700 hover:!text-white active:!bg-red-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-200 disabled:border-0 disabled:bg-red-300 disabled:text-white';
    $softButtonClass = 'btn-sm h-[42px] border border-[#D7E4F3] bg-white text-[#1A2B42] hover:!border-[#B7D6F2] hover:!bg-[#EAF4FD] hover:!text-[#0B6FE4] active:!bg-[#DCEFFF] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#B7D6F2] disabled:bg-[#EEF3F8] disabled:text-[#7B8794]';
    $fileInputClass = 'file-input-bordered w-full border-[#D7E4F3] bg-[#F0F3F7] text-[#1A2B42] file:border-0 file:bg-[#2E8BC0] file:text-white hover:!border-[#B7D6F2] hover:!bg-[#EAF4FD] hover:!text-[#1A2B42] hover:file:bg-[#0B6FE4] focus:!border-[#2E8BC0] focus:!outline-none focus:!ring-2 focus:!ring-[#B7D6F2]';
    $softInfoClass = 'rounded-2xl border border-[#D7E4F3] bg-[#F7FAFD] px-4 py-3 text-[#1A2B42] hover:bg-[#F7FAFD]';
@endphp

<div class="{{ $pageClass }}">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        <div class="flex flex-col items-center justify-center text-center">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#2E8BC0]/10">
                    <x-icon name="o-wrench-screwdriver" class="h-7 w-7 text-[#2E8BC0]" />
                </span>

                <div>
                    <h1 class="text-left text-2xl font-bold text-[#1A2B42] md:text-3xl">
                        Mantenimiento
                    </h1>
                    <p class="text-left text-sm text-[#1A2B42] md:text-base">
                        Gestión de la base de datos y copias de seguridad
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <x-card class="{{ $cardClass }} rounded-3xl">
                <div class="flex h-full flex-col items-center text-center">
                    <div class="mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-[#2E8BC0]/10">
                        <x-icon name="o-circle-stack" class="h-10 w-10 text-[#2E8BC0]" />
                    </div>

                    <h2 class="text-xl font-bold text-[#1A2B42]">
                        Crear copia de seguridad
                    </h2>

                    <p class="mt-3 max-w-md text-sm leading-6 text-[#1A2B42]">
                        Genera un respaldo completo de la base de datos actual y lo guarda en el almacenamiento privado del sistema.
                    </p>

                    <div class="mt-5 grid w-full grid-cols-1 gap-3">
                        <div class="{{ $softInfoClass }}">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#1A2B42]">
                                Acción recomendada
                            </p>
                            <p class="mt-1 text-sm text-[#1A2B42]">
                                El sistema también crea un respaldo automático antes de restaurar.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 w-full">
                        <x-button
                            label="Crear respaldo"
                            wire:click="crearRespaldo"
                            spinner="crearRespaldo"
                            icon="o-plus"
                            class="w-full {{ $primaryButtonClass }}"
                        />
                    </div>
                </div>
            </x-card>

            <x-card class="{{ $cardClass }} rounded-3xl">
                <div class="flex h-full flex-col items-center text-center">
                    <div class="mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-[#2E8BC0]/10">
                        <x-icon name="o-arrow-path-rounded-square" class="h-10 w-10 text-[#2E8BC0]" />
                    </div>

                    <h2 class="text-xl font-bold text-[#1A2B42]">
                        Restaurar base de datos
                    </h2>

                    <p class="mt-3 max-w-md text-sm leading-6 text-[#1A2B42]">
                        Carga un archivo SQL externo o restaura uno de los respaldos generados anteriormente.
                    </p>

                    <div class="mt-5 grid w-full grid-cols-1 gap-3">
                        <div class="{{ $softInfoClass }} text-left">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#1A2B42]">
                                Archivo SQL
                            </p>

                            <div class="mt-3">
                                <x-file
                                    wire:model="archivoRestauracion"
                                    accept=".sql"
                                    hint="Solo archivos .sql"
                                    class="{{ $fileInputClass }}"
                                />

                                @error('archivoRestauracion')
                                    <p class="mt-2 text-sm font-semibold text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror

                                <div wire:loading wire:target="archivoRestauracion" class="mt-2 text-sm text-[#1A2B42]">
                                    Cargando archivo...
                                </div>

                                @if ($archivoRestauracion)
                                    <p class="mt-2 rounded-xl border border-[#D7E4F3] bg-white px-3 py-2 text-sm text-[#1A2B42] hover:bg-white">
                                        Archivo seleccionado:
                                        <span class="font-semibold">
                                            {{ $archivoRestauracion->getClientOriginalName() }}
                                        </span>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 w-full">
                        <x-button
                            label="Cargar y restaurar"
                            wire:click="confirmarRestauracionSubida"
                            spinner="confirmarRestauracionSubida"
                            icon="o-arrow-up-tray"
                            class="w-full {{ $primaryButtonClass }}"
                        />
                    </div>
                </div>
            </x-card>
        </div>

        <x-card class="{{ $cardClass }} rounded-3xl">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-[#1A2B42]">
                            Respaldos guardados
                        </h2>
                        <p class="text-sm text-[#1A2B42]">
                            Puedes descargar o restaurar una copia creada anteriormente.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 rounded-2xl border border-[#D7E4F3] bg-[#F7FAFD] px-4 py-2">
                        <x-icon name="o-folder" class="h-5 w-5 text-[#2E8BC0]" />
                        <span class="text-sm font-semibold text-[#1A2B42]">
                            {{ count($respaldos) }} respaldo(s)
                        </span>
                    </div>
                </div>

                @if (count($respaldos) > 0)
                    <div class="grid grid-cols-1 gap-3">
                        @foreach ($respaldos as $respaldo)
                            <div class="rounded-2xl border border-[#D7E4F3] bg-[#F7FAFD] p-4 hover:bg-[#F7FAFD]">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#2E8BC0]/10">
                                            <x-icon name="o-document-arrow-down" class="h-6 w-6 text-[#2E8BC0]" />
                                        </span>

                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-[#1A2B42] md:text-base">
                                                {{ $respaldo['nombre'] }}
                                            </p>

                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs font-semibold text-[#1A2B42]">
                                                <span class="rounded-full border border-[#D7E4F3] bg-white px-3 py-1">
                                                    {{ $respaldo['tamano'] }}
                                                </span>

                                                <span class="rounded-full border border-[#D7E4F3] bg-white px-3 py-1">
                                                    {{ $respaldo['fecha'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3 lg:min-w-[360px]">
                                        <x-button
                                            label="Descargar"
                                            wire:click="descargarRespaldo('{{ $respaldo['archivo'] }}')"
                                            spinner="descargarRespaldo('{{ $respaldo['archivo'] }}')"
                                            icon="o-arrow-down-tray"
                                            class="w-full {{ $softButtonClass }}"
                                        />

                                        <x-button
                                            label="Restaurar"
                                            wire:click="confirmarRestauracionExistente('{{ $respaldo['archivo'] }}')"
                                            spinner="confirmarRestauracionExistente('{{ $respaldo['archivo'] }}')"
                                            icon="o-arrow-path"
                                            class="w-full {{ $primaryButtonClass }}"
                                        />

                                        <x-button
                                            label="Eliminar"
                                            wire:click="eliminarRespaldo('{{ $respaldo['archivo'] }}')"
                                            spinner="eliminarRespaldo('{{ $respaldo['archivo'] }}')"
                                            icon="o-trash"
                                            class="w-full {{ $dangerButtonClass }}"
                                        />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-[#D7E4F3] bg-[#F7FAFD] px-4 py-10 text-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[#2E8BC0]/10">
                            <x-icon name="o-archive-box-x-mark" class="h-8 w-8 text-[#2E8BC0]" />
                        </div>

                        <h3 class="mt-4 text-lg font-bold text-[#1A2B42]">
                            No hay respaldos guardados
                        </h3>

                        <p class="mt-2 max-w-md text-sm leading-6 text-[#1A2B42]">
                            Crea una copia de seguridad para que aparezca en este listado.
                        </p>
                    </div>
                @endif
            </div>
        </x-card>
    </div>

    <x-modal wire:model="modalRestaurar" title="Confirmar restauración" separator class="backdrop-blur-sm" box-class="w-full max-w-lg rounded-3xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl [&_.modal-action]:gap-2 [&_.modal-action_.btn:hover]:shadow-sm">
        <div class="space-y-4">
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3">
                <div class="flex items-start gap-3">
                    <x-icon name="o-exclamation-triangle" class="mt-0.5 h-6 w-6 text-red-600" />

                    <div>
                        <p class="font-bold text-red-700">
                            Esta acción reemplazará la información actual.
                        </p>

                        <p class="mt-1 text-sm leading-6 text-red-700">
                            Antes de restaurar, el sistema intentará crear una copia automática del estado actual.
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#D7E4F3] bg-[#F7FAFD] px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#1A2B42]">
                    Archivo a restaurar
                </p>

                <p class="mt-1 break-all text-sm font-bold text-[#1A2B42]">
                    {{ $nombreArchivoConfirmacion ?? 'Sin archivo seleccionado' }}
                </p>
            </div>
        </div>

        <x-slot:actions>
            <x-button
                label="Cancelar"
                type="button"
                wire:click="$set('modalRestaurar', false)"
                class="{{ $softButtonClass }}"
            />

            <x-button
                label="Sí, restaurar"
                type="button"
                wire:click="restaurarBaseDatos"
                spinner="restaurarBaseDatos"
                icon="o-arrow-path"
                class="{{ $dangerButtonClass }}"
            />
        </x-slot:actions>
    </x-modal>
</div>