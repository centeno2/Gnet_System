<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Factura {{ $venta->Numero_Factura }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .print-page {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>

<body class="bg-[#F0F3F7] text-[#1A2B42]">
    <div class="no-print mx-auto mt-4 flex w-full max-w-5xl justify-end gap-2 px-4">
        <x-button label="Volver" link="{{ route('ventas.facturacion') }}" no-wire-navigate
            class="rounded-xl border border-[#D7E4F3] bg-white text-[#1A2B42] hover:bg-[#F0F3F7]" />

        <x-button label="Imprimir con Ctrl + P"
            class="rounded-xl border-0 bg-[#0B6FE4] text-white hover:bg-[#0E48A1]" />
    </div>

    <main class="print-page mx-auto my-4 w-full max-w-5xl rounded-2xl border border-[#D7E4F3] bg-white p-6 shadow-sm">
        <div
            class="mb-6 flex flex-col gap-4 border-b border-[#D7E4F3] pb-5 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-[#1A2B42]">Factura</h1>
                <p class="mt-1 text-sm text-[#5F6B7A]">G-net System</p>
                <p class="mt-1 text-sm text-[#5F6B7A]">Servicios informáticos y ventas</p>
            </div>

            <x-card class="rounded-2xl border border-[#D7E4F3] bg-[#F8FBFF] shadow-none">
                <div class="space-y-2 text-sm">
                    <div>
                        <span class="block text-xs font-semibold text-[#5F6B7A]">Número de factura</span>
                        <strong class="text-[#1A2B42]">{{ $venta->Numero_Factura }}</strong>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-[#5F6B7A]">Fecha</span>
                        <strong class="text-[#1A2B42]">
                            {{ \Carbon\Carbon::parse($venta->Fecha_venta)->format('d/m/Y h:i A') }}
                        </strong>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-[#5F6B7A]">Tipo de venta</span>
                        <x-badge value="{{ $venta->Tipo_Venta }}"
                            class="border-0 bg-[#EAF2FB] px-3 py-1 text-[#0B6FE4]" />
                    </div>
                </div>
            </x-card>
        </div>

        <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-none">
                <h2 class="mb-3 text-lg font-bold text-[#1A2B42]">Datos del cliente</h2>

                <div class="space-y-3 text-sm">
                    <div>
                        <span class="block text-xs font-semibold text-[#5F6B7A]">Cliente</span>
                        <strong>{{ $nombreCliente }}</strong>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-[#5F6B7A]">Departamento / municipio</span>
                        <strong>{{ $municipio ?: 'No especificado' }}</strong>
                    </div>
                </div>
            </x-card>

            <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-none">
                <h2 class="mb-3 text-lg font-bold text-[#1A2B42]">Resumen de venta</h2>

                <div class="space-y-3 text-sm">
                    <div>
                        <span class="block text-xs font-semibold text-[#5F6B7A]">Estado</span>
                        <strong>{{ (int) $venta->Estado === 1 ? 'Activa' : 'Inactiva' }}</strong>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-[#5F6B7A]">Total de líneas</span>
                        <strong>{{ $venta->detalles->count() }}</strong>
                    </div>
                </div>
            </x-card>
        </div>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-none">
            <div class="mb-3">
                <h2 class="text-lg font-bold text-[#1A2B42]">Detalle de la factura</h2>
                <p class="text-sm text-[#5F6B7A]">Productos, series, servicios o copias incluidos en la venta.</p>
            </div>

            <div class="overflow-hidden rounded-xl border border-[#D7E4F3]">
                <table class="w-full border-separate border-spacing-0 text-sm text-[#1A2B42]">
                    <thead>
                        <tr>
                            <th class="rounded-tl-xl bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">Código
                            </th>
                            <th class="bg-[#2E8BC0] px-3 py-3 text-left font-semibold text-white">Descripción</th>
                            <th class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">Tipo</th>
                            <th class="bg-[#2E8BC0] px-3 py-3 text-center font-semibold text-white">Cant.</th>
                            <th class="bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white">Precio</th>
                            <th class="rounded-tr-xl bg-[#2E8BC0] px-3 py-3 text-right font-semibold text-white">
                                Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($venta->detalles as $detalle)
                        @php
                        if ($detalle->Tipo_Detalle === 'COPIA') {
                        $descripcion = $detalle->Nombre_Formato ?: ($detalle->tarifaCopia->Nombre_Tarifa ?? 'Copia');
                        $codigo = 'C-' . $detalle->Id_Tarifa_Copia;
                        } else {
                        $descripcion = $detalle->producto->Nombre_Producto ?? 'Producto';

                        if (! empty($detalle->producto?->Modelo)) {
                        $descripcion .= ' - ' . $detalle->producto->Modelo;
                        }

                        if ($detalle->productoSerie) {
                        $descripcion .= ' · Serie: ' . $detalle->productoSerie->Numero_Serie;
                        }

                        $codigo = 'P-' . $detalle->Id_Producto;
                        }
                        @endphp

                        <tr class="odd:bg-white even:bg-[#F8FBFF]">
                            <td class="px-3 py-3 font-semibold whitespace-nowrap">{{ $codigo }}</td>
                            <td class="px-3 py-3">{{ $descripcion }}</td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <span
                                    class="{{ $detalle->Tipo_Detalle === 'COPIA' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }} rounded-full px-2.5 py-1 text-xs font-semibold">
                                    {{ $detalle->Tipo_Detalle }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                {{ number_format((float) $detalle->Cantidad, 0, '.', ',') }}
                            </td>
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                C$ {{ number_format((float) $detalle->Precio_Unitario, 0, '.', ',') }}
                            </td>
                            <td class="px-3 py-3 text-right font-semibold whitespace-nowrap">
                                C$ {{ number_format((float) $detalle->Subtotal, 0, '.', ',') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-[#7B8794]">
                                Esta factura no tiene detalles registrados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-5 flex justify-end">
            <x-card class="w-full max-w-sm rounded-2xl border border-[#D7E4F3] bg-[#F8FBFF] shadow-none">
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[#5F6B7A]">Subtotal</span>
                        <strong>C$ {{ number_format((float) $venta->detalles->sum('Subtotal'), 0, '.', ',') }}</strong>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[#5F6B7A]">Descuento</span>
                        <strong>C$ {{ number_format((float) $venta->Descuento, 0, '.', ',') }}</strong>
                    </div>

                    <div class="rounded-xl bg-[#EAF2FB] px-4 py-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-[#0B6FE4]">Total</span>
                            <strong class="text-2xl text-[#0B6FE4]">
                                C$ {{ number_format((float) $venta->Total, 0, '.', ',') }}
                            </strong>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="mt-8 border-t border-[#D7E4F3] pt-4 text-center text-xs text-[#5F6B7A]">
            Gracias por su compra.
        </div>
    </main>
</body>

</html>
