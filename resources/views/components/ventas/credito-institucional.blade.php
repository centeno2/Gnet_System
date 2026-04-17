<div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
    <div class="space-y-4 xl:col-span-3">
        <x-card class="w-full rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h4 class="text-xl font-bold text-[#1A2B42]">Datos de la institución</h4>
                <p class="text-sm text-[#5F6B7A]">
                    Complete la información principal del cliente institucional.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Municipio</label>
                    <x-input
                        type="text"
                        placeholder="Ingrese el municipio"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                </div>
            </div>
        </x-card>

        <x-card class="w-full rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h4 class="text-xl font-bold text-[#1A2B42]">Responsable que recibe</h4>
                <p class="text-sm text-[#5F6B7A]">
                    Datos de la persona encargada del crédito.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Primer nombre</label>
                    <x-input
                        type="text"
                        placeholder="Ingrese el primer nombre"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Primer apellido</label>
                    <x-input
                        type="text"
                        placeholder="Ingrese el primer apellido"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                </div>
                <div class="xl:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Firma / recibido por</label>
                    <x-input
                        type="text"
                        placeholder="Ingrese el nombre de quien recibe"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                    />
                </div>
            </div>
        </x-card>
    </div>

    <div class="xl:col-span-1">
        <x-card class="w-full rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h4 class="text-xl font-bold text-[#1A2B42]">Resumen del crédito</h4>
                <p class="text-sm text-[#5F6B7A]">
                    Totales generales.
                </p>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Saldo actual</label>
                    <x-input
                        type="number"
                        placeholder="0.00"
                        class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                    />
                </div>

                <div class="rounded-xl bg-[#F0F3F7] px-4 py-4 text-[#1A2B42]">
                    <span class="block text-xs font-semibold text-[#5F6B7A]">Subtotal</span>
                    <span class="block text-base font-bold">C$ 0.00</span>
                </div>

                <div class="rounded-xl bg-[#EAF2FB] px-4 py-4 text-[#0B6FE4]">
                    <span class="block text-xs font-semibold">Total</span>
                    <span class="block text-base font-bold">C$ 0.00</span>
                </div>
            </div>
        </x-card>
    </div>

    <div class="xl:col-span-4">
        <x-card class="w-full rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-3 flex w-full flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h4 class="text-xl font-bold text-[#1A2B42]">Productos del crédito</h4>
                    <p class="text-sm text-[#5F6B7A]">
                        Agregue los productos asociados al crédito.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-button
                        label="Buscar productos"
                        class="h-10 min-h-10 border border-[#D7E4F3] bg-white px-4 text-sm text-[#1A2B42] hover:bg-[#F0F3F7]"
                    />

                    <x-button
                        label="Agregar fila"
                        class="h-10 min-h-10 border-0 bg-[#0E48A1] px-4 text-sm text-white hover:bg-[#0B6FE4]"
                    />
                </div>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-[#D7E4F3] bg-[#FBFCFE] p-3 md:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-4">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Producto</label>
                        <x-input
                            type="text"
                            placeholder="Seleccione o escriba el producto"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42] placeholder:text-[#7B8794]"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">T/Carta</label>
                        <x-input
                            type="number"
                            placeholder="0"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">T/LEGAL</label>
                        <x-input
                            type="number"
                            placeholder="0"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Cantidad</label>
                        <x-input
                            type="number"
                            placeholder="0"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Precio</label>
                        <x-input
                            type="number"
                            placeholder="0.00"
                            class="h-10 min-h-10 w-full rounded-lg bg-[#F0F3F7] text-sm text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-sm font-semibold text-[#1A2B42]">Subtotal</label>
                        <x-input
                            type="text"
                            placeholder="0.00"
                            readonly
                            class="h-10 min-h-10 w-full rounded-lg bg-[#EEF2F7] text-sm font-semibold text-[#1A2B42]"
                        />
                    </div>

                    <div class="xl:col-span-2 flex items-end">
                        <x-button
                            label="Quitar"
                            class="h-10 min-h-10 w-full border border-[#D7E4F3] bg-white px-4 text-sm text-[#B42318] hover:bg-[#FFF5F4]"
                        />
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</div>