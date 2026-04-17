<?php
use Livewire\Attributes\Layout;
use Livewire\Component;

new class extends Component
{
    public bool $modalCategoria = false;
    public bool $modalMarca = false;

    public string $nombreCategoria = '';
    public string $estadoCategoria = '';

    public string $nombreMarca = '';
    public string $estadoMarca = '';

    public function abrirModalCategoria(): void
    {
        $this->reset(['nombreCategoria', 'estadoCategoria']);
        $this->modalCategoria = true;
    }

    public function cerrarModalCategoria(): void
    {
        $this->modalCategoria = false;
    }

    public function guardarCategoria(): void
    {
        $this->modalCategoria = false;
    }

    public function abrirModalMarca(): void
    {
        $this->reset(['nombreMarca', 'estadoMarca']);
        $this->modalMarca = true;
    }

    public function cerrarModalMarca(): void
    {
        $this->modalMarca = false;
    }

    public function guardarMarca(): void
    {
        $this->modalMarca = false;
    }
};
?>

<div class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-hidden bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="flex shrink-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1A2B42]">Productos</h1>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Registro y gestión de productos del sistema.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-button
                label="Nueva categoría"
                wire:click="abrirModalCategoria"
                class="h-10 min-h-10 border-0 bg-[#0E48A1] px-4 text-sm text-white hover:bg-[#0B6FE4]"
            />

            <x-button
                label="Nueva marca"
                wire:click="abrirModalMarca"
                class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]"
            />

            <a
                href="{{ route('productos.listado') }}"
                class="inline-flex h-10 min-h-10 items-center justify-center rounded-xl border border-[#D7E4F3] bg-white px-4 text-sm font-semibold text-[#1A2B42] transition hover:bg-[#F0F3F7]"
            >
                Ver tabla completa
            </a>
        </div>
    </div>

    {{-- Formulario  --}}
    <x-card class="shrink-0 rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-[#1A2B42]">Registrar producto</h2>
            <p class="text-sm text-[#5F6B7A]">
                Complete la información básica del producto.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="xl:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Nombre del producto
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese el nombre del producto"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Modelo
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese el modelo"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Número de serie
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese el número de serie"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Categoría
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese la categoría"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Marca
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese la marca"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Stock actual
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese el stock actual"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Stock mínimo
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese el stock mínimo"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Precio de compra
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese el precio de compra"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Precio de venta
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese el precio de venta"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Garantía nuevo
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese la garantía nuevo"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Garantía usado
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese la garantía usado"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Estado
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese el estado"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div class="xl:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">
                    Fecha de vencimiento
                </label>
                <x-input
                    type="text"
                    placeholder="Ingrese fecha de vencimiento"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>
        </div>
        <x-slot:actions>
            <x-button
                label="Guardar producto"
                class="h-10 min-h-10 border-0 bg-[#0E48A1] px-4 text-sm text-white hover:bg-[#0B6FE4] focus:ring-2 focus:ring-[#0E48A1]/30"
            />
        </x-slot:actions>
    </x-card>

    @php
        $headers = [
            ['key' => 'producto', 'label' => 'Producto'],
            ['key' => 'categoria', 'label' => 'Categoría'],
            ['key' => 'marca', 'label' => 'Marca'],
            ['key' => 'modelo', 'label' => 'Modelo'],
            ['key' => 'serie', 'label' => 'Número de serie'],
            ['key' => 'stock', 'label' => 'Stock'],
            ['key' => 'stock_minimo', 'label' => 'Stock mínimo'],
            ['key' => 'precio_compra', 'label' => 'Precio compra'],
            ['key' => 'precio_venta', 'label' => 'Precio venta'],
            ['key' => 'garantia_nuevo', 'label' => 'Garantía nuevo'],
            ['key' => 'garantia_usado', 'label' => 'Garantía usado'],
            ['key' => 'estado', 'label' => 'Estado'],
        ];

        $productos = [
            //
        ];
    @endphp

    {{-- Tabla --}}
    <x-card class="flex min-h-0 flex-1 flex-col rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-3 flex shrink-0 flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-bold text-[#1A2B42]">Resumen de productos</h2>

            <div class="w-full md:w-80">
                <x-input
                    type="text"
                    placeholder="Buscar productos"
                    class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>
        </div>

        <div class="min-h-0 flex-1 overflow-hidden rounded-xl border border-[#D7E4F3]">
            <div class="h-full overflow-auto">
                <x-table
                    :headers="$headers"
                    :rows="$productos"
                    class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
                >
                </x-table>
            </div>
        </div>
    </x-card>

{{-- Modal categoría --}}
<x-modal
    wire:model="modalCategoria"
    class="backdrop-blur-sm"
    box-class="w-full max-w-2xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl"
>
    <div class="mb-5">
        <h3 class="text-2xl font-bold text-[#1A2B42]">Registrar categoría</h3>
        <p class="mt-1 text-sm text-[#5F6B7A]">Agregue una nueva categoría</p>
    </div>

    <form wire:submit="guardarCategoria" class="space-y-4">
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                Nombre de la categoría
            </label>
            <x-input
                type="text"
                wire:model="nombreCategoria"
                placeholder="Ingrese el nombre"
                class="w-full rounded-xl  border-[#D7E4F3] bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
            />
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                Estado
            </label>
            <x-input
                type="text"
                wire:model="estadoCategoria"
                placeholder="Ingrese el estado"
                class="w-full rounded-xl  border-[#D7E4F3] bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
            />
        </div>

        <x-slot:actions>
            <x-button
                label="Cancelar"
                wire:click="cerrarModalCategoria"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]"
            />

            <x-button
                label="Guardar categoría"
                type="submit"
                class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]"
            />
        </x-slot:actions>
    </form>
</x-modal>

{{-- Modal marca --}}
<x-modal
    wire:model="modalMarca"
    class="backdrop-blur-sm"
    box-class="w-full max-w-2xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl"
>
    <div class="mb-5">
        <h3 class="text-2xl font-bold text-[#1A2B42]">Registrar marca</h3>
        <p class="mt-1 text-sm text-[#5F6B7A]">Agregue una nueva marca</p>
    </div>

    <form wire:submit="guardarMarca" class="space-y-4">
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                Nombre de la marca
            </label>
            <x-input
                type="text"
                wire:model="nombreMarca"
                placeholder="Ingrese el nombre"
                class="w-full rounded-xl  border-[#D7E4F3] bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
            />
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                Estado
            </label>
            <x-input
                type="text"
                wire:model="estadoMarca"
                placeholder="Ingrese el estado"
                class="w-full rounded-xl  border-[#D7E4F3] bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
            />
        </div>

        <x-slot:actions>
            <x-button
                label="Cancelar"
                wire:click="cerrarModalMarca"
                class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]"
            />

            <x-button
                label="Guardar marca"
                type="submit"
                class="border-0 bg-[#0E48A1] text-white hover:bg-[#0B6FE4]"
            />
        </x-slot:actions>
    </form>
</x-modal>

</div>