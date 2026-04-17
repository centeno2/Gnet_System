<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="flex h-[calc(100vh-3rem)] min-h-0 w-full flex-col gap-4 overflow-y-auto bg-[#F0F3F7] px-4 py-4 md:px-6 md:py-5">
    <div class="flex shrink-0 flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1A2B42]">Servicio técnico</h1>
            <p class="mt-1 text-sm text-[#5F6B7A]">
                Registro de ingreso, revisión y control del equipo.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-button
                label="Buscar Pendientes"
                class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]"
            />

            <x-button
                label="Guardar ingreso"
                class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]"
            />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
        <x-card class="xl:col-span-3 rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Ingreso del equipo</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Información del cliente y del equipo recibido.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">No. orden</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Fecha de ingreso</label>
                    <x-input type="datetime-local" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cliente</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Teléfono</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Documento</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Tipo de equipo</label>
                    <x-select
                        :options="[
                            ['id' => 'PC', 'name' => 'Computadora'],
                            ['id' => 'LAPTOP', 'name' => 'Laptop'],
                            ['id' => 'IMPRESORA', 'name' => 'Impresora'],
                            ['id' => 'OTRO', 'name' => 'Otro']
                        ]"
                        option-value="id"
                        option-label="name"
                        placeholder="Seleccione"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Técnico receptor</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Marca</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Modelo</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Número de serie</label>
                    <x-input type="text" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div class="xl:col-span-4">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Problema reportado</label>
                    <x-textarea rows="3" class="w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div class="xl:col-span-4">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Detalle descriptivo del equipo</label>
                    <x-textarea rows="3" class="w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>
            </div>
        </x-card>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Seguimiento</h2>
                <p class="text-sm text-[#5F6B7A]">
                    Control del servicio.
                </p>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Estado</label>
                    <x-select
                        :options="[
                            ['id' => 'PENDIENTE', 'name' => 'Pendiente'],
                            ['id' => 'REPARADO', 'name' => 'Reparado'],
                            ['id' => 'ENTREGADO', 'name' => 'Entregado']
                        ]"
                        option-value="id"
                        option-label="name"
                        placeholder="Seleccione"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Costo estimado</label>
                    <x-input type="number" step="0.01" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Fecha estimada</label>
                    <x-input type="date" class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Observación técnica</label>
                    <x-textarea rows="6" class="w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]" />
                </div>
            </div>
        </x-card>
    </div>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-[#1A2B42]">Estado del equipo al ingresar</h2>
            <p class="text-sm text-[#5F6B7A]">
                Marque las condiciones visibles del equipo.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Enciende</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Lleva cargador</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Lleva batería</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Pantalla sana</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Teclado completo</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Touchpad funcional</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Tiene golpes visibles</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Tiene humedad</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Tiene sello roto</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Lleva cable de poder</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Lleva cartucho / tóner</label>
            <label class="flex items-center gap-3 rounded-xl bg-[#F0F3F7] px-4 py-3 text-sm font-medium text-[#1A2B42]"><x-checkbox /> Lleva mouse / accesorios</label>
        </div>
    </x-card>

    @php
        $headers = [
            ['key' => 'codigo', 'label' => 'Código'],
            ['key' => 'descripcion', 'label' => 'Descripción'],
            ['key' => 'cantidad', 'label' => 'Cantidad'],
            ['key' => 'precio', 'label' => 'Precio'],
            ['key' => 'subtotal', 'label' => 'Subtotal'],
        ];

        $productos = [];
    @endphp

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-3 flex shrink-0 flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-bold text-[#1A2B42]">Repuestos / insumos</h2>

            <div class="flex flex-wrap gap-2">
                <x-button
                    label="Agregar producto"
                    class="h-10 min-h-10 border-0 bg-[#2E8BC0] px-4 text-sm text-white hover:bg-[#0B6FE4]"
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
</div>