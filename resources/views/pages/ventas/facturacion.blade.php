<?php

use Livewire\Component;

new class extends Component
{
    public bool $modalCredito = false;

    public string $tipoVenta = '';
    public string $moneda = '';
    public string $cliente = '';
    public string $telefono = '';
    public string $direccion = '';
    public string $correo = '';

    public $categoriaId = null;
    public $marcaId = null;
    public $productoId = null;

    public int $cantidadProducto = 1;
    public float $precioProducto = 0;
    public float $descuento = 0;
    public int $stockDisponible = 0;

    public array $detalleVenta = [];

    public array $categorias = [];
    public array $marcas = [];
    public array $productos = [];

    public function abrirModalCredito(): void
    {
        $this->modalCredito = true;
    }

    public function cerrarModalCredito(): void
    {
        $this->modalCredito = false;
    }

    public function marcasFiltradas(): array
    {
        if (! $this->categoriaId) {
            return [];
        }

        return array_values(array_filter($this->marcas, function ($marca) {
            return ($marca['categoria_id'] ?? null) == $this->categoriaId;
        }));
    }

    public function productosFiltrados(): array
    {
        if (! $this->categoriaId || ! $this->marcaId) {
            return [];
        }

        return array_values(array_filter($this->productos, function ($producto) {
            return ($producto['categoria_id'] ?? null) == $this->categoriaId
                && ($producto['marca_id'] ?? null) == $this->marcaId;
        }));
    }

    public function updatedCategoriaId(): void
    {
        $this->marcaId = null;
        $this->productoId = null;
        $this->cantidadProducto = 1;
        $this->precioProducto = 0;
        $this->stockDisponible = 0;
    }

    public function updatedMarcaId(): void
    {
        $this->productoId = null;
        $this->cantidadProducto = 1;
        $this->precioProducto = 0;
        $this->stockDisponible = 0;
    }

    public function updatedProductoId($value): void
    {
        $producto = collect($this->productos)->firstWhere('id', $value);

        if (! $producto) {
            $this->precioProducto = 0;
            $this->stockDisponible = 0;
            return;
        }

        $this->precioProducto = (float) ($producto['precio'] ?? 0);
        $this->stockDisponible = (int) ($producto['stock'] ?? 0);
    }

    public function agregarProducto(): void
    {
        $producto = collect($this->productos)->firstWhere('id', $this->productoId);

        if (! $producto) {
            return;
        }

        $cantidad = max(1, (int) $this->cantidadProducto);
        $precio = max(0, (float) $this->precioProducto);
        $subtotal = $cantidad * $precio;

        $this->detalleVenta[] = [
            'codigo' => $producto['codigo'] ?? '',
            'descripcion' => $producto['name'] ?? '',
            'tipo' => $producto['tipo'] ?? '',
            'cantidad' => $cantidad,
            'precio' => 'C$ ' . number_format($precio, 2),
            'subtotal' => 'C$ ' . number_format($subtotal, 2),
            'subtotal_valor' => $subtotal,
        ];

        $this->productoId = null;
        $this->cantidadProducto = 1;
        $this->precioProducto = 0;
        $this->stockDisponible = 0;
    }

    public function cancelarVenta(): void
    {
        $this->reset([
            'tipoVenta',
            'moneda',
            'cliente',
            'telefono',
            'direccion',
            'correo',
            'categoriaId',
            'marcaId',
            'productoId',
            'precioProducto',
            'stockDisponible',
            'detalleVenta',
        ]);

        $this->cantidadProducto = 1;
        $this->descuento = 0;
    }

    public function subtotalVenta(): float
    {
        return collect($this->detalleVenta)->sum('subtotal_valor');
    }

    public function descuentoVenta(): float
    {
        return min((float) $this->descuento, $this->subtotalVenta());
    }

    public function totalVenta(): float
    {
        return max($this->subtotalVenta() - $this->descuentoVenta(), 0);
    }
};
?>

<div class="min-h-[calc(100vh-3rem)] w-full bg-[#F0F3F7] px-4 py-3 md:px-5">
    <div class="mx-auto flex w-full max-w-[1450px] flex-col gap-3">

        <div>
            <h1 class="text-2xl font-bold leading-tight text-[#1A2B42]">Facturación</h1>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Registro de ventas, clientes y detalle de facturación.
            </p>
        </div>

        <div class="flex flex-col gap-3 xl:flex-row xl:items-start">
            <x-card class="flex-1 rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
                <div class="mb-3">
                    <h2 class="text-xl font-bold leading-tight text-[#1A2B42]">Datos de la factura</h2>
                    <p class="text-sm text-[#5F6B7A]">
                        Complete la información general de la venta y del cliente.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo de venta</label>
                        <x-select
                            wire:model="tipoVenta"
                            :options="[
                                ['id' => 'CONTADO', 'name' => 'Contado'],
                                ['id' => 'CREDITO', 'name' => 'Crédito']
                            ]"
                            option-value="id"
                            option-label="name"
                            placeholder="Seleccione"
                            class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Moneda</label>
                        <x-select
                            wire:model="moneda"
                            :options="[
                                ['id' => 'NIO', 'name' => 'Córdobas'],
                                ['id' => 'USD', 'name' => 'Dólares']
                            ]"
                            option-value="id"
                            option-label="name"
                            placeholder="Seleccione"
                            class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-4">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cliente / institución</label>
                        <x-input
                            wire:model="cliente"
                            type="text"
                            placeholder="Buscar o ingresar cliente"
                            class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Teléfono</label>
                        <x-input
                            wire:model="telefono"
                            type="text"
                            class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-4">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Dirección</label>
                        <x-input
                            wire:model="direccion"
                            type="text"
                            class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-3">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Correo</label>
                        <x-input
                            wire:model="correo"
                            type="email"
                            class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Descuento</label>
                        <x-input
                            wire:model="descuento"
                            type="number"
                            min="0"
                            step="0.01"
                            class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>
                </div>
            </x-card>

            <x-card class="w-full shrink-0 rounded-2xl border border-[#D7E4F3] bg-white p-3 shadow-sm xl:w-[235px]">
                <div class="mb-2">
                    <h2 class="text-lg font-bold leading-tight text-[#1A2B42]">Resumen</h2>
                    <p class="text-xs text-[#5F6B7A]">
                        Totales de la venta.
                    </p>
                </div>

                <div class="space-y-2">
                    <div class="rounded-xl bg-[#F0F3F7] px-3 py-2 text-[#1A2B42]">
                        <span class="block text-xs">Subtotal</span>
                        <strong class="mt-0.5 block text-base font-bold">
                            C$ {{ number_format($this->subtotalVenta(), 2) }}
                        </strong>
                    </div>

                    <div class="rounded-xl bg-[#F0F3F7] px-3 py-2 text-[#1A2B42]">
                        <span class="block text-xs">Descuento</span>
                        <strong class="mt-0.5 block text-base font-bold">
                            C$ {{ number_format($this->descuentoVenta(), 2) }}
                        </strong>
                    </div>

                    <div class="rounded-xl bg-[#EAF2FB] px-3 py-2 text-[#0B6FE4]">
                        <span class="block text-xs font-semibold">Total</span>
                        <strong class="mt-0.5 block text-xl font-bold">
                            C$ {{ number_format($this->totalVenta(), 2) }}
                        </strong>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <x-button
                            label="Cancelar"
                            wire:click="cancelarVenta"
                            class="h-8 min-h-8 rounded-xl border border-[#D7E4F3] bg-white px-2 text-xs font-semibold text-[#1A2B42] hover:bg-[#F5F9FC]"
                        />

                        <x-button
                            label="Guardar"
                            class="h-8 min-h-8 rounded-xl border-0 bg-[#2E8BC0] px-2 text-xs font-semibold text-white shadow-sm hover:bg-[#0B6FE4]"
                        />
                    </div>
                </div>
            </x-card>
        </div>

        @php
            $headers = [
                ['key' => 'codigo', 'label' => 'Código'],
                ['key' => 'descripcion', 'label' => 'Descripción'],
                ['key' => 'tipo', 'label' => 'Tipo'],
                ['key' => 'cantidad', 'label' => 'Cantidad'],
                ['key' => 'precio', 'label' => 'Precio'],
                ['key' => 'subtotal', 'label' => 'Subtotal'],
            ];
        @endphp

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
            <div class="mb-3">
                <h2 class="text-xl font-bold leading-tight text-[#1A2B42]">Buscar y agregar productos</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Seleccione la categoría, marca y producto para agregarlo al detalle de la venta.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12">
                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Categoría</label>
                    <x-select
                        wire:model.live="categoriaId"
                        :options="$categorias"
                        option-value="id"
                        option-label="name"
                        placeholder="Seleccione"
                        class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Marca</label>
                    <x-select
                        wire:model.live="marcaId"
                        :options="$this->marcasFiltradas()"
                        option-value="id"
                        option-label="name"
                        placeholder="Seleccione"
                        class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                </div>

                <div class="xl:col-span-3">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Producto</label>
                    <x-select
                        wire:model.live="productoId"
                        :options="$this->productosFiltrados()"
                        option-value="id"
                        option-label="name"
                        placeholder="Seleccione producto"
                        class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                </div>

                <div class="xl:col-span-1">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Stock</label>
                    <x-input
                        wire:model="stockDisponible"
                        type="number"
                        readonly
                        class="h-9 min-h-9 w-full rounded-xl bg-[#EAF2FB] text-sm text-[#1A2B42]"
                    />
                </div>

                <div class="xl:col-span-1">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cantidad</label>
                    <x-input
                        wire:model="cantidadProducto"
                        type="number"
                        min="1"
                        class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Precio</label>
                    <x-input
                        wire:model="precioProducto"
                        type="number"
                        step="0.01"
                        min="0"
                        prefix="C$"
                        class="h-9 min-h-9 w-full rounded-xl bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                </div>

                <div class="flex items-end xl:col-span-1">
                    <x-button
                        label="Agregar"
                        wire:click="agregarProducto"
                        class="h-9 min-h-9 w-full rounded-xl border-0 bg-[#2E8BC0] px-3 text-sm font-semibold text-white hover:bg-[#0B6FE4]"
                    />
                </div>
            </div>

            <div class="mt-4">
                <h2 class="mb-2 text-lg font-bold text-[#1A2B42]">Detalle de la venta</h2>

                <div class="overflow-x-auto rounded-xl border border-[#D7E4F3]">
                    <x-table
                        :headers="$headers"
                        :rows="$detalleVenta"
                        class="[&_thead_th]:bg-[#2E8BC0] [&_thead_th]:font-semibold [&_thead_th]:text-white [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
                    >
                    </x-table>
                </div>
            </div>
        </x-card>

        <x-modal
            wire:model="modalCredito"
            class="backdrop-blur-sm"
            box-class="w-full max-w-7xl rounded-2xl border border-[#D7E4F3] bg-white text-[#1A2B42] shadow-xl"
        >
            <div class="mb-5">
                <h3 class="text-2xl font-bold text-[#1A2B42]">Crédito institucional</h3>
                <p class="mt-1 text-sm text-[#5F6B7A]">
                    Complete los datos del crédito.
                </p>
            </div>

            <x-slot:actions>
                <x-button
                    label="Cerrar"
                    wire:click="cerrarModalCredito"
                    class="border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]"
                />
            </x-slot:actions>
        </x-modal>
    </div>
</div>