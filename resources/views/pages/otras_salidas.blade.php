<?php

use Livewire\Component;

new class extends Component
{
    public string $categoria = '';
    public string $marca = '';
    public string $modelo = '';
    public string $codigo = '';
    public string $stockDisponible = '0';
    public string $cantidadSalida = '1';
    public string $motivo = '';
    public string $fechaSalida = '';
    public string $descripcion = '';

    public string $buscarRegistro = '';
    public string $filtroMotivo = '';

    public array $categorias = [];
    public array $marcas = [];
    public array $modelos = [];
    public array $motivos = [];
    public array $headersSalidas = [];
    public array $registrosSalidas = [];

    public function mount(): void
    {
        $this->fechaSalida = now()->format('Y-m-d');

        $this->categorias = [
            ['id' => 'telefonos', 'name' => 'Teléfonos'],
            ['id' => 'accesorios', 'name' => 'Accesorios'],
            ['id' => 'repuestos', 'name' => 'Repuestos'],
            ['id' => 'impresoras', 'name' => 'Impresoras'],
        ];

        $this->marcas = [
            ['id' => 'samsung', 'name' => 'Samsung'],
            ['id' => 'apple', 'name' => 'Apple'],
            ['id' => 'jbl', 'name' => 'JBL'],
            ['id' => 'hp', 'name' => 'HP'],
        ];

        $this->modelos = [
            ['id' => 'a24', 'name' => 'Galaxy A24'],
            ['id' => 'iphone13pm', 'name' => 'iPhone 13 Pro Max'],
            ['id' => 'tune500', 'name' => 'JBL Tune 500'],
            ['id' => 'deskjet', 'name' => 'HP DeskJet'],
        ];

        $this->motivos = [
            ['id' => 'patrocinio', 'name' => 'Patrocinio'],
            ['id' => 'defecto', 'name' => 'Defecto de fábrica'],
            ['id' => 'uso_personal', 'name' => 'Uso personal'],
            ['id' => 'facturacion', 'name' => 'Facturación incorrecta'],
            ['id' => 'consumo_interno', 'name' => 'Consumo interno'],
        ];

        $this->headersSalidas = [
            ['key' => 'codigo', 'label' => 'Código', 'class' => 'min-w-[120px]'],
            ['key' => 'producto', 'label' => 'Nombre del producto', 'class' => 'min-w-[220px]'],
            ['key' => 'cantidad', 'label' => 'Cantidad', 'class' => 'min-w-[100px]'],
            ['key' => 'motivo', 'label' => 'Motivo', 'class' => 'min-w-[180px]'],
            ['key' => 'descripcion', 'label' => 'Descripción', 'class' => 'min-w-[220px]'],
            ['key' => 'fecha_salida', 'label' => 'Fecha de salida', 'class' => 'min-w-[140px]'],
        ];
    }

    public function registrarSalida(): void
    {
    }

    public function limpiarFormulario(): void
    {
        $this->reset([
            'categoria',
            'marca',
            'modelo',
            'codigo',
            'stockDisponible',
            'cantidadSalida',
            'motivo',
            'descripcion',
        ]);

        $this->stockDisponible = '0';
        $this->cantidadSalida = '1';
        $this->fechaSalida = now()->format('Y-m-d');
    }
};
?>

@php
    $fieldClass = 'rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#000000] placeholder:text-[#000000] [&_.fieldset-legend]:text-[#000000] [&_.label]:text-[#000000] [&_label]:text-[#000000]';

    $readonlyFieldClass = 'rounded-xl border-[#D7E4F3] bg-[#F0F3F7] text-[#000000] [&_.fieldset-legend]:text-[#000000] [&_.label]:text-[#000000] [&_label]:text-[#000000]';

    $cardClass = 'border border-[#D7E4F3] bg-white [&_.text-base-content\\/70]:text-[#000000] [&_.text-sm]:text-[#000000] [&_.text-base-content]:text-[#000000] [&_.card-title]:text-[#000000] [&_label]:text-[#000000] [&_.fieldset-legend]:text-[#000000]';

    $primaryButtonClass = 'btn-sm border-0 bg-[#2E8BC0] text-white hover:bg-[#256f99]';
    $outlineButtonClass = 'btn-sm border border-[#2E8BC0] bg-white text-[#2E8BC0] hover:bg-[#EAF5FB]';
@endphp

<div class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-hidden bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="flex shrink-0 items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-[#000000]">Otras Salidas</h1>
            <p class="text-sm text-[#000000]">Registro de productos que salen por distintos motivos.</p>
        </div>

        <div class="hidden md:flex items-center gap-2">
            <x-button
                label="Historial"
                icon="o-clock"
                class="{{ $primaryButtonClass }}"
            />
        </div>
    </div>

    <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden">
        <x-card
            title="Registrar salida"
            subtitle="Selecciona el producto, indica la cantidad y define el motivo de la salida."
            shadow
            separator
            class="{{ $cardClass }}"
        >
            <x-form wire:submit="registrarSalida" no-separator>
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                    <div class="lg:col-span-3">
                        <x-select
                            label="Categoría"
                            wire:model="categoria"
                            :options="$categorias"
                            option-value="id"
                            option-label="name"
                            placeholder="Seleccionar"
                            class="{{ $fieldClass }}"
                        />
                    </div>

                    <div class="lg:col-span-3">
                        <x-select
                            label="Marca"
                            wire:model="marca"
                            :options="$marcas"
                            option-value="id"
                            option-label="name"
                            placeholder="Seleccionar"
                            class="{{ $fieldClass }}"
                        />
                    </div>

                    <div class="lg:col-span-3">
                        <x-select
                            label="Producto / Modelo"
                            wire:model="modelo"
                            :options="$modelos"
                            option-value="id"
                            option-label="name"
                            placeholder="Seleccionar"
                            class="{{ $fieldClass }}"
                        />
                    </div>

                    <div class="lg:col-span-3">
                        <x-input
                            label="Código"
                            wire:model="codigo"
                            placeholder="Código del producto"
                            class="{{ $fieldClass }}"
                        />
                    </div>

                    <div class="lg:col-span-2">
                        <x-input
                            label="Stock disponible"
                            wire:model="stockDisponible"
                            readonly
                            class="{{ $readonlyFieldClass }}"
                        />
                    </div>

                    <div class="lg:col-span-2">
                        <x-input
                            label="Cantidad a salir"
                            wire:model="cantidadSalida"
                            type="number"
                            min="1"
                            class="{{ $fieldClass }}"
                        />
                    </div>

                    <div class="lg:col-span-3">
                        <x-select
                            label="Motivo"
                            wire:model="motivo"
                            :options="$motivos"
                            option-value="id"
                            option-label="name"
                            placeholder="Seleccione un motivo"
                            class="{{ $fieldClass }}"
                        />
                    </div>

                    <div class="lg:col-span-2">
                        <x-input
                            label="Fecha de salida"
                            type="date"
                            wire:model="fechaSalida"
                            class="{{ $fieldClass }}"
                        />
                    </div>

                    <div class="lg:col-span-3 flex items-end">
                        <div class="grid w-full grid-cols-2 gap-2">
                            <x-button
                                label="Limpiar"
                                icon="o-arrow-path"
                                wire:click="limpiarFormulario"
                                class="{{ $outlineButtonClass }}"
                            />

                            <x-button
                                label="Guardar"
                                type="submit"
                                spinner="registrarSalida"
                                icon="o-check-circle"
                                class="{{ $primaryButtonClass }}"
                            />
                        </div>
                    </div>

                    <div class="lg:col-span-12">
                        <x-textarea
                            label="Descripción"
                            wire:model="descripcion"
                            rows="3"
                            placeholder="Detalle breve de la salida del producto..."
                            class="{{ $fieldClass }}"
                        />
                    </div>
                </div>
            </x-form>
        </x-card>

        <x-card
            title="Registro de salidas"
            subtitle="Consulta rápida de las salidas registradas."
            shadow
            separator
            class="flex min-h-0 flex-1 flex-col {{ $cardClass }}"
        >
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                <div class="md:col-span-7">
                    <x-input
                        label="Buscar registro"
                        wire:model="buscarRegistro"
                        placeholder="Buscar por código o nombre del producto"
                        icon="o-magnifying-glass"
                        class="{{ $fieldClass }}"
                    />
                </div>

                <div class="md:col-span-3">
                    <x-select
                        label="Filtrar por motivo"
                        wire:model="filtroMotivo"
                        :options="$motivos"
                        option-value="id"
                        option-label="name"
                        placeholder="Todos"
                        class="{{ $fieldClass }}"
                    />
                </div>

                <div class="md:col-span-2 flex items-end">
                    <x-button
                        label="Actualizar"
                        icon="o-arrow-path"
                        class="h-[46px] w-full {{ $primaryButtonClass }}"
                    />
                </div>
            </div>

            <div class="mt-4 min-h-0 flex-1 overflow-hidden rounded-2xl border border-[#D7E4F3]">
                <div class="h-full overflow-auto overscroll-contain">
                    <x-table
                        :headers="$headersSalidas"
                        :rows="$registrosSalidas"
                        class="[&_thead_th]:sticky [&_thead_th]:top-0 [&_thead_th]:z-10 [&_thead_th]:border-0 [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_thead_th:first-child]:rounded-tl-xl [&_thead_th:last-child]:rounded-tr-xl [&_tbody_td]:border-[#D7E4F3] [&_tbody_td]:text-[#000000]"
                    />
                </div>
            </div>

            @if (! count($registrosSalidas))
                <div class="mt-3 rounded-2xl border border-dashed border-[#D7E4F3] bg-[#F8FAFC] px-4 py-6 text-center text-sm text-[#000000]">
                    Aún no hay salidas registradas.
                </div>
            @endif
        </x-card>
    </div>
</div>