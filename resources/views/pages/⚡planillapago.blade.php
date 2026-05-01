<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-6 space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-[#1A2B42]">Planilla de trabajadores</h1>
        <p class="mt-1 text-sm text-[#5F6B7A]">
            Control visual de pagos, incentivos, vacaciones y aguinaldo del personal.
        </p>
    </div>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
            <div class="xl:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Buscar empleado
                </label>
                <x-input
                    placeholder="Ingrese el nombre del empleado"
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] placeholder:text-[#7B8794]"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Total de empleados
                </label>
                <x-input
                    value="0"
                    readonly
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] font-semibold"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Pago neto
                </label>
                <x-input
                    value="C$ 0.00"
                    readonly
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] font-semibold"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Pago total de incentivos
                </label>
                <x-input
                    value="C$ 0.00"
                    readonly
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] font-semibold"
                />
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-[#1A2B42]">
                    Pago general
                </label>
                <x-input
                    value="C$ 0.00"
                    readonly
                    class="w-full rounded-xl bg-[#F0F3F7] text-[#1A2B42] font-semibold"
                />
            </div>
        </div>
    </x-card>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-5">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Acciones de planilla</h2>
            <p class="text-base text-[#5F6B7A]">
                Gestione vacaciones y aguinaldo de los empleados.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <x-button
                label="Dar vacaciones"
                icon="o-sun"
                class="border-0 bg-[#2E8BC0] text-white hover:opacity-90"
            />
            <x-button
                label="Pagar aguinaldo"
                icon="o-banknotes"
                class="border-0 bg-[#E67E22] text-white hover:opacity-90"
            />

            <x-button
                label="Registrar incentivo"
                icon="o-gift"
                class="border-0 bg-[#2E8BC0] text-white hover:opacity-90"
            />

            <x-button
                label="Calcular planilla"
                icon="o-calculator"
                class="border-0 bg-[#2E8BC0] text-white hover:opacity-90"
            />
            <x-button
                label="Generar reporte"
                icon="o-document-text"
                class="border-0 bg-[#2E8BC0] text-white hover:opacity-90"
            />
        </div>
    </x-card>

    @php
        $headers = [
            ['key' => 'name', 'label' => 'Nombre'],
            ['key' => 'position', 'label' => 'Cargo'],
            ['key' => 'phone', 'label' => 'Teléfono'],
            ['key' => 'salary', 'label' => 'Salario'],
            ['key' => 'worked_days', 'label' => 'Días trabajados'],
            ['key' => 'vacation_days', 'label' => 'Días de vacaciones'],
            ['key' => 'incentive', 'label' => 'Incentivo'],
            ['key' => 'total_pay', 'label' => 'Total a pagar'],
        ];

        $empleados = [
            [
                'id' => 1,
                'name' => 'Juan Carlos Pérez López',
                'position' => 'Administrador',
                'phone' => '8888-1111',
                'salary' => 'C$ 18,000.00',
                'worked_days' => '30',
                'vacation_days' => '2',
                'incentive' => 'C$ 1,500.00',
                'total_pay' => 'C$ 19,500.00',
            ],
            [
                'id' => 2,
                'name' => 'María Fernanda López Ruiz',
                'position' => 'Empleador',
                'phone' => '8888-2222',
                'salary' => 'C$ 12,500.00',
                'worked_days' => '30',
                'vacation_days' => '0',
                'incentive' => 'C$ 800.00',
                'total_pay' => 'C$ 13,300.00',
            ],
            [
                'id' => 3,
                'name' => 'Carlos Alberto Hernández García',
                'position' => 'Administrador',
                'phone' => '8888-3333',
                'salary' => 'C$ 16,000.00',
                'worked_days' => '28',
                'vacation_days' => '1',
                'incentive' => 'C$ 1,000.00',
                'total_pay' => 'C$ 17,000.00',
            ],
        ];
    @endphp

    
    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-[#1A2B42]">Detalle de planilla</h2>
            <p class="text-base text-[#5F6B7A]">
                Aquí se mostrará el resumen de pago de cada trabajador.
            </p>
        </div>

        <x-table
            :headers="$headers"
            :rows="$empleados"
            class="[&_thead_th]:text-[#feffff] [&_thead_th]:font-semibold [&_thead_th]:bg-[#2E8BC0] [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl"
        >
            @scope('cell_name', $empleado)
                <span class="font-semibold text-[#1A2B42]">{{ $empleado['name'] }}</span>
            @endscope

            @scope('cell_position', $empleado)
                <span class="inline-flex rounded-full bg-[#D7E4F3] px-3 py-1 text-xs font-semibold text-[#1A2B42]">
                    {{ $empleado['position'] }}
                </span>
            @endscope

            @scope('cell_phone', $empleado)
                <span class="text-[#1A2B42]">{{ $empleado['phone'] }}</span>
            @endscope

            @scope('cell_salary', $empleado)
                <span class="text-[#1A2B42]">{{ $empleado['salary'] }}</span>
            @endscope

            @scope('cell_worked_days', $empleado)
                <span class="text-[#1A2B42]">{{ $empleado['worked_days'] }}</span>
            @endscope

            @scope('cell_vacation_days', $empleado)
                <span class="text-[#1A2B42]">{{ $empleado['vacation_days'] }}</span>
            @endscope

            @scope('cell_incentive', $empleado)
                <span class="text-[#1A2B42]">{{ $empleado['incentive'] }}</span>
            @endscope

            @scope('cell_total_pay', $empleado)
                <span class="font-semibold text-[#0E48A1]">{{ $empleado['total_pay'] }}</span>
            @endscope
        </x-table>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
            
        </div>
    </x-card>
</div>