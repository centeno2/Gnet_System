<?php

use App\Models\DeduccionTrabajador;
use App\Models\DetallePlanilla;
use App\Models\IncentivoTrabajador;
use App\Models\MovimientoVacacion;
use App\Models\PagoPlanilla;
use App\Models\Planilla;
use App\Models\Trabajador;
use App\Models\Vacaciones;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $periodoMes = '';
    public string $quincena = 'SEGUNDA';
    public string $fechaInicioCorte = '';
    public string $fechaFinCorte = '';
    public string $tipoPlanilla = Planilla::TIPO_NORMAL;

    public array $selectedTrabajadores = [];
    public bool $seleccionarTodos = false;
    public ?int $planillaActualId = null;

    public bool $modalIncentivo = false;
    public bool $modalDeduccion = false;
    public bool $modalVacaciones = false;
    public bool $modalPago = false;
    public bool $modalReporte = false;
    public bool $modalLiquidacion = false;
    public bool $modalConfirmarRecalculo = false;

    public ?int $planillaConfirmarId = null;
    public array $idsConfirmarRecalculo = [];

    public ?int $formTrabajadorId = null;

    public string $fechaIncentivo = '';
    public string $conceptoIncentivo = '';
    public string|float $montoIncentivo = 0;
    public ?string $observacionIncentivo = null;

    public string $fechaDeduccion = '';
    public string $conceptoDeduccion = '';
    public string|float $montoDeduccion = 0;
    public ?string $observacionDeduccion = null;

    public string $vacacionFechaInicio = '';
    public string $vacacionFechaFin = '';
    public string|int|float $vacacionDias = 1;
    public string $vacacionEstado = Vacaciones::ESTADO_APROBADA;
    public ?string $vacacionObservacion = null;

    public ?int $pagoDetalleId = null;
    public string $pagoFecha = '';
    public string $pagoMetodo = PagoPlanilla::METODO_EFECTIVO;
    public ?string $pagoObservacion = null;

    public string $liquidacionFechaSalida = '';
    public string $liquidacionMotivo = 'Renuncia';
    public bool $liquidacionIncluirIndemnizacion = true;
    public ?string $liquidacionObservacion = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $hoy = Carbon::now();

        $this->periodoMes = $hoy->format('Y-m');
        $this->quincena = ((int) $hoy->format('d')) <= 15 ? 'PRIMERA' : 'SEGUNDA';

        $this->aplicarPeriodo();

        $this->fechaIncentivo = $this->fechaDentroPeriodo($hoy);
        $this->fechaDeduccion = $this->fechaDentroPeriodo($hoy);
        $this->vacacionFechaInicio = $this->fechaDentroPeriodo($hoy);
        $this->vacacionFechaFin = $this->fechaDentroPeriodo($hoy);
        $this->pagoFecha = $hoy->format('Y-m-d');
        $this->liquidacionFechaSalida = $hoy->format('Y-m-d');

        $this->cargarPlanillaDelPeriodo(false);
    }

    public function paginationView(): string
{
    return 'vendor.pagination.gnet';
}

    public function updatedPeriodoMes(): void
    {
        $this->aplicarPeriodo();
        $this->limpiarSeleccion();
        $this->cargarPlanillaDelPeriodo(true);
    }

    public function updatedQuincena(): void
    {
        $this->aplicarPeriodo();
        $this->limpiarSeleccion();
        $this->cargarPlanillaDelPeriodo(true);
    }

    public function updatedTipoPlanilla(): void
    {
        $this->limpiarSeleccion();
        $this->cargarPlanillaDelPeriodo(true);
    }

    public function updatedSeleccionarTodos($value): void
    {
        $this->selectedTrabajadores = $value
            ? $this->trabajadoresQuery()->pluck('Id_Trabajador')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function updatedVacacionFechaInicio(): void
    {
        $this->calcularDiasVacacion();
    }

    public function updatedVacacionFechaFin(): void
    {
        $this->calcularDiasVacacion();
    }

    public function aplicarPeriodo(): void
    {
        [$year, $month] = explode('-', $this->periodoMes ?: Carbon::now()->format('Y-m'));

        $base = Carbon::create((int) $year, (int) $month, 1)->startOfDay();

        if ($this->quincena === 'PRIMERA') {
            $inicio = $base->copy()->day(1)->startOfDay();
            $fin = $base->copy()->day(15)->endOfDay();
        } else {
            $inicio = $base->copy()->day(16)->startOfDay();
            $fin = $base->copy()->endOfMonth()->endOfDay();
        }

        $this->fechaInicioCorte = $inicio->format('Y-m-d');
        $this->fechaFinCorte = $fin->format('Y-m-d');
    }

    public function cargarTrabajadores(): void
    {
        $this->resetPage();

        $this->selectedTrabajadores = $this->trabajadoresQuery()
            ->pluck('Id_Trabajador')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->seleccionarTodos = true;

        $this->notificar('success', 'Trabajadores cargados', 'Se seleccionaron los trabajadores activos del periodo.');
    }

    public function trabajadoresQuery()
    {
        return Trabajador::query()
            ->with(['persona', 'cargo'])
            ->where('Estado', 1)
            ->orderBy('Id_Trabajador');
    }

    public function trabajadores()
    {
        return $this->trabajadoresQuery()->paginate(8);
    }

    public function trabajadoresOptions(): array
    {
        return Trabajador::query()
            ->with('persona')
            ->where('Estado', 1)
            ->orderBy('Id_Trabajador')
            ->limit(250)
            ->get()
            ->map(fn (Trabajador $trabajador) => [
                'id' => $trabajador->Id_Trabajador,
                'name' => $this->nombreTrabajador($trabajador),
            ])
            ->values()
            ->all();
    }

    public function cargarPlanillaDelPeriodo(bool $notificar = false): void
    {
        $planilla = $this->buscarPlanillaPeriodo();

        $this->planillaActualId = $planilla?->Id_Planilla;

        if ($notificar && $planilla) {
            $this->notificar('info', 'Planilla encontrada', 'Se cargó la planilla existente del periodo seleccionado.');
        }
    }

    public function buscarPlanillaPeriodo(?string $tipo = null): ?Planilla
    {
        return Planilla::query()
            ->whereDate('Fecha_Inicio_Corte', Carbon::parse($this->fechaInicioCorte)->toDateString())
            ->whereDate('Fecha_Fin_Corte', Carbon::parse($this->fechaFinCorte)->toDateString())
            ->where('Tipo_Planilla', $tipo ?: $this->tipoPlanilla)
            ->where('Estado', '!=', Planilla::ESTADO_ANULADA)
            ->latest('Id_Planilla')
            ->first();
    }

    public function planillaActual(): ?Planilla
    {
        if ($this->planillaActualId) {
            return Planilla::query()
                ->with(['detalles.trabajador.persona', 'detalles.trabajador.cargo'])
                ->find($this->planillaActualId);
        }

        return $this->buscarPlanillaPeriodo()?->load(['detalles.trabajador.persona', 'detalles.trabajador.cargo']);
    }

    public function resumen(): array
    {
        $planilla = $this->planillaActual();

        if (! $planilla) {
            return [
                'bruto' => $this->money(0),
                'incentivos' => $this->money(0),
                'vacaciones' => $this->money(0),
                'aguinaldo' => $this->money(0),
                'indemnizacion' => $this->money(0),
                'deducciones' => $this->money(0),
                'neto' => $this->money(0),
                'estado' => 'SIN PLANILLA',
            ];
        }

        return [
            'bruto' => $this->money($planilla->Total_Bruto),
            'incentivos' => $this->money($planilla->Total_Incentivos),
            'vacaciones' => $this->money($planilla->Total_Vacaciones),
            'aguinaldo' => $this->money($planilla->Total_Aguinaldo),
            'indemnizacion' => $this->money($planilla->Total_Indemnizacion ?? 0),
            'deducciones' => $this->money($planilla->Total_Deducciones),
            'neto' => $this->money($planilla->Total_Neto),
            'estado' => $planilla->Estado,
        ];
    }

    public function detallePlanillaRows(): array
    {
        $planilla = $this->planillaActual();

        if (! $planilla) {
            return [];
        }

        return $planilla->detalles
            ->map(function (DetallePlanilla $detalle) {
                $trabajador = $detalle->trabajador;

                return [
                    'id' => $detalle->Id_Detalle_Planilla,
                    'empleado' => $trabajador ? $this->nombreTrabajador($trabajador) : 'Sin trabajador',
                    'cargo' => $trabajador ? $this->cargoTrabajador($trabajador) : 'Sin cargo',
                    'salario' => $this->money($detalle->Salario_Base),
                    'dias' => $this->numero($detalle->Dias_Trabajados),
                    'vacaciones' => $this->numero($detalle->Dias_Vacaciones),
                    'monto_vacaciones' => $this->money($detalle->Monto_Vacaciones),
                    'incentivo' => $this->money($detalle->Monto_Incentivo),
                    'aguinaldo' => $this->money($detalle->Monto_Aguinaldo),
                    'indemnizacion' => $this->money($detalle->Monto_Indemnizacion ?? 0),
                    'deduccion' => $this->money($detalle->Monto_Deduccion),
                    'total' => $this->money($detalle->Total_Neto),
                    'estado' => $detalle->Estado_Pago,
                ];
            })
            ->values()
            ->all();
    }

    public function headersDetalle(): array
    {
        $base = [
            ['key' => 'empleado', 'label' => 'Empleado'],
            ['key' => 'cargo', 'label' => 'Cargo'],
        ];

        return match ($this->tipoPlanilla) {
            Planilla::TIPO_AGUINALDO => [
                ...$base,
                ['key' => 'aguinaldo', 'label' => 'Aguinaldo'],
                ['key' => 'total', 'label' => 'Total neto'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            Planilla::TIPO_VACACIONES => [
                ...$base,
                ['key' => 'vacaciones', 'label' => 'Días vac.'],
                ['key' => 'monto_vacaciones', 'label' => 'Monto vac.'],
                ['key' => 'total', 'label' => 'Total neto'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            Planilla::TIPO_LIQUIDACION => [
                ...$base,
                ['key' => 'salario', 'label' => 'Salario prop.'],
                ['key' => 'dias', 'label' => 'Días'],
                ['key' => 'monto_vacaciones', 'label' => 'Vacaciones'],
                ['key' => 'aguinaldo', 'label' => 'Aguinaldo'],
                ['key' => 'indemnizacion', 'label' => 'Indemnización'],
                ['key' => 'deduccion', 'label' => 'Deducción'],
                ['key' => 'total', 'label' => 'Total neto'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            default => [
                ...$base,
                ['key' => 'salario', 'label' => 'Salario quincenal'],
                ['key' => 'dias', 'label' => 'Días'],
                ['key' => 'incentivo', 'label' => 'Incentivo'],
                ['key' => 'deduccion', 'label' => 'Deducción'],
                ['key' => 'total', 'label' => 'Total neto'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
        };
    }

    public function abrirIncentivo(?int $trabajadorId = null): void
    {
        $this->resetValidation();
        $this->formTrabajadorId = $trabajadorId ?: $this->primerTrabajadorSeleccionado();
        $this->fechaIncentivo = $this->fechaDentroPeriodo(Carbon::now());
        $this->conceptoIncentivo = '';
        $this->montoIncentivo = 0;
        $this->observacionIncentivo = null;
        $this->modalIncentivo = true;
    }

    public function registrarIncentivo(): void
    {
        $this->validate([
            'formTrabajadorId' => ['required', 'integer', 'exists:trabajador,Id_Trabajador'],
            'fechaIncentivo' => ['required', 'date'],
            'conceptoIncentivo' => ['required', 'string', 'max:150'],
            'montoIncentivo' => ['required', 'numeric', 'min:0.01'],
            'observacionIncentivo' => ['nullable', 'string', 'max:255'],
        ], [
            'formTrabajadorId.required' => 'Seleccione un trabajador.',
            'conceptoIncentivo.required' => 'Ingrese el concepto del incentivo.',
            'montoIncentivo.min' => 'El incentivo debe ser mayor a cero.',
        ]);

        IncentivoTrabajador::create([
            'Id_Trabajador' => $this->formTrabajadorId,
            'Id_Detalle_Planilla' => null,
            'Fecha_Incentivo' => Carbon::parse($this->fechaIncentivo)->startOfDay(),
            'Concepto' => trim($this->conceptoIncentivo),
            'Monto' => $this->numeroSeguro($this->montoIncentivo),
            'Estado' => IncentivoTrabajador::ESTADO_PENDIENTE,
            'Observacion' => $this->observacionIncentivo,
        ]);

        $this->modalIncentivo = false;

        if ($this->actualizarPlanillaNormalPorMovimiento()) {
            $this->notificar('success', 'Incentivo registrado', 'La planilla calculada fue actualizada automáticamente.');
            return;
        }

        $this->notificar('success', 'Incentivo registrado', 'Quedó pendiente hasta calcular la planilla normal.');
    }

    public function abrirDeduccion(?int $trabajadorId = null): void
    {
        $this->resetValidation();
        $this->formTrabajadorId = $trabajadorId ?: $this->primerTrabajadorSeleccionado();
        $this->fechaDeduccion = $this->fechaDentroPeriodo(Carbon::now());
        $this->conceptoDeduccion = '';
        $this->montoDeduccion = 0;
        $this->observacionDeduccion = null;
        $this->modalDeduccion = true;
    }

    public function registrarDeduccion(): void
    {
        $this->validate([
            'formTrabajadorId' => ['required', 'integer', 'exists:trabajador,Id_Trabajador'],
            'fechaDeduccion' => ['required', 'date'],
            'conceptoDeduccion' => ['required', 'string', 'max:150'],
            'montoDeduccion' => ['required', 'numeric', 'min:0.01'],
            'observacionDeduccion' => ['nullable', 'string', 'max:255'],
        ], [
            'formTrabajadorId.required' => 'Seleccione un trabajador.',
            'conceptoDeduccion.required' => 'Ingrese el concepto de la deducción.',
            'montoDeduccion.min' => 'La deducción debe ser mayor a cero.',
        ]);

        DeduccionTrabajador::create([
            'Id_Trabajador' => $this->formTrabajadorId,
            'Id_Detalle_Planilla' => null,
            'Fecha_Deduccion' => Carbon::parse($this->fechaDeduccion)->startOfDay(),
            'Concepto' => trim($this->conceptoDeduccion),
            'Monto' => $this->numeroSeguro($this->montoDeduccion),
            'Estado' => DeduccionTrabajador::ESTADO_PENDIENTE,
            'Observacion' => $this->observacionDeduccion,
        ]);

        $this->modalDeduccion = false;

        if ($this->actualizarPlanillaNormalPorMovimiento()) {
            $this->notificar('success', 'Deducción registrada', 'La planilla calculada fue actualizada automáticamente.');
            return;
        }

        $this->notificar('success', 'Deducción registrada', 'Quedó pendiente hasta calcular la planilla normal.');
    }

    private function actualizarPlanillaNormalPorMovimiento(): bool
    {
        $tipoAnterior = $this->tipoPlanilla;
        $this->tipoPlanilla = Planilla::TIPO_NORMAL;

        $planilla = $this->buscarPlanillaPeriodo(Planilla::TIPO_NORMAL);

        if (! $planilla) {
            $this->tipoPlanilla = $tipoAnterior;
            return false;
        }

        if ($this->planillaBloqueada($planilla)) {
            $this->tipoPlanilla = $tipoAnterior;
            $this->planillaActualId = $planilla->Id_Planilla;
            return false;
        }

        $this->recalcularPlanilla($planilla);

        $this->tipoPlanilla = Planilla::TIPO_NORMAL;
        $this->planillaActualId = $planilla->Id_Planilla;

        return true;
    }

    public function abrirVacaciones(?int $trabajadorId = null): void
    {
        $this->resetValidation();
        $this->formTrabajadorId = $trabajadorId ?: $this->primerTrabajadorSeleccionado();
        $this->vacacionFechaInicio = $this->fechaDentroPeriodo(Carbon::now());
        $this->vacacionFechaFin = $this->fechaDentroPeriodo(Carbon::now());
        $this->vacacionDias = 1;
        $this->vacacionEstado = Vacaciones::ESTADO_APROBADA;
        $this->vacacionObservacion = null;
        $this->modalVacaciones = true;
    }

    public function registrarVacaciones(): void
    {
        $this->calcularDiasVacacion();

        $this->validate([
            'formTrabajadorId' => ['required', 'integer', 'exists:trabajador,Id_Trabajador'],
            'vacacionFechaInicio' => ['required', 'date'],
            'vacacionFechaFin' => ['required', 'date', 'after_or_equal:vacacionFechaInicio'],
            'vacacionDias' => ['required', 'numeric', 'min:1'],
            'vacacionEstado' => ['required', 'in:SOLICITADA,APROBADA,PAGADA,ANULADA,RECHAZADA'],
            'vacacionObservacion' => ['nullable', 'string', 'max:255'],
        ], [
            'formTrabajadorId.required' => 'Seleccione un trabajador.',
            'vacacionFechaFin.after_or_equal' => 'La fecha final no puede ser menor a la fecha inicial.',
            'vacacionDias.min' => 'Los días deben ser mayores a cero.',
        ]);

        $trabajador = Trabajador::query()->find($this->formTrabajadorId);

        if (! $trabajador) {
            $this->notificar('warning', 'Trabajador no encontrado', 'No se pudo cargar el trabajador seleccionado.');
            return;
        }

        $saldoDisponible = $this->saldoVacacionesNumero($trabajador, Carbon::parse($this->vacacionFechaInicio));

        if (in_array($this->vacacionEstado, [Vacaciones::ESTADO_APROBADA, Vacaciones::ESTADO_PAGADA], true)
            && $this->numeroSeguro($this->vacacionDias) > $saldoDisponible
        ) {
            $this->notificar('warning', 'Saldo insuficiente', 'El trabajador no tiene suficientes días acumulados.');
            return;
        }

        DB::transaction(function () {
            $vacacion = Vacaciones::create([
                'Id_Trabajador' => $this->formTrabajadorId,
                'Id_Detalle_Planilla' => null,
                'Fecha_Inicio' => Carbon::parse($this->vacacionFechaInicio),
                'Fecha_Fin' => Carbon::parse($this->vacacionFechaFin),
                'Dias_Tomados' => (int) $this->vacacionDias,
                'Estado' => $this->vacacionEstado,
                'Observacion' => $this->vacacionObservacion,
            ]);

            if (in_array($this->vacacionEstado, [Vacaciones::ESTADO_APROBADA, Vacaciones::ESTADO_PAGADA], true)) {
                MovimientoVacacion::create([
                    'Id_Trabajador' => $this->formTrabajadorId,
                    'Id_Vacacion' => $vacacion->Id_Vacacion,
                    'Id_Detalle_Planilla' => null,
                    'Fecha_Movimiento' => Carbon::parse($this->vacacionFechaInicio),
                    'Tipo_Movimiento' => MovimientoVacacion::TIPO_TOMADA,
                    'Dias' => (int) $this->vacacionDias,
                    'Observacion' => $this->vacacionObservacion,
                ]);
            }
        });

        $this->modalVacaciones = false;
        $this->notificar('success', 'Vacaciones registradas', 'El saldo del trabajador fue actualizado.');
    }

    public function abrirLiquidacion(?int $trabajadorId = null): void
    {
        $this->resetValidation();
        $this->formTrabajadorId = $trabajadorId ?: $this->primerTrabajadorSeleccionado();
        $this->liquidacionFechaSalida = Carbon::now()->format('Y-m-d');
        $this->liquidacionMotivo = 'Renuncia';
        $this->liquidacionIncluirIndemnizacion = true;
        $this->liquidacionObservacion = null;
        $this->modalLiquidacion = true;
    }

    public function liquidarTrabajador(): void
    {
        $this->validate([
            'formTrabajadorId' => ['required', 'integer', 'exists:trabajador,Id_Trabajador'],
            'liquidacionFechaSalida' => ['required', 'date'],
            'liquidacionMotivo' => ['required', 'string', 'max:255'],
            'liquidacionObservacion' => ['nullable', 'string', 'max:255'],
        ], [
            'formTrabajadorId.required' => 'Seleccione un trabajador.',
            'liquidacionFechaSalida.required' => 'Ingrese la fecha de salida.',
            'liquidacionMotivo.required' => 'Ingrese el motivo de salida.',
        ]);

        $trabajador = Trabajador::query()->find($this->formTrabajadorId);

        if (! $trabajador) {
            $this->notificar('warning', 'Trabajador no encontrado', 'No se pudo cargar el trabajador seleccionado.');
            return;
        }

        $fechaSalida = Carbon::parse($this->liquidacionFechaSalida)->endOfDay();

        if ($trabajador->Fecha_Ingreso && $fechaSalida->lessThan(Carbon::parse($trabajador->Fecha_Ingreso))) {
            $this->notificar('warning', 'Fecha inválida', 'La salida no puede ser menor a la fecha de ingreso.');
            return;
        }

        DB::transaction(function () use ($trabajador, $fechaSalida) {
            $desde = $this->inicioQuincenaPorFecha($fechaSalida);
            $hasta = $fechaSalida->copy();

            $this->tipoPlanilla = Planilla::TIPO_LIQUIDACION;
            $this->fechaInicioCorte = $desde->format('Y-m-d');
            $this->fechaFinCorte = $hasta->format('Y-m-d');

            $planilla = Planilla::create([
                'Fecha_Inicio_Corte' => $desde,
                'Fecha_Fin_Corte' => $hasta,
                'Fecha_Generacion' => Carbon::now(),
                'Tipo_Planilla' => Planilla::TIPO_LIQUIDACION,
                'Estado' => Planilla::ESTADO_CALCULADA,
                'Total_Bruto' => 0,
                'Total_Incentivos' => 0,
                'Total_Vacaciones' => 0,
                'Total_Aguinaldo' => 0,
                'Total_Indemnizacion' => 0,
                'Total_Deducciones' => 0,
                'Total_Neto' => 0,
                'Observacion' => $this->liquidacionMotivo,
            ]);

            $this->crearDetallePlanilla($planilla, $trabajador, $desde, $hasta, true);
            $this->actualizarTotalesPlanilla($planilla);

            $trabajador->update([
                'Estado' => 0,
                'Fecha_Salida' => $fechaSalida->toDateString(),
                'Motivo_Salida' => $this->liquidacionMotivo,
            ]);

            $this->planillaActualId = $planilla->Id_Planilla;
        });

        $this->modalLiquidacion = false;
        $this->limpiarSeleccion();

        $this->notificar('success', 'Liquidación generada', 'El trabajador fue liquidado y marcado como inactivo.');
    }

    public function generarPlanilla(): void
    {
        $this->validate([
            'periodoMes' => ['required'],
            'quincena' => ['required', 'in:PRIMERA,SEGUNDA'],
            'tipoPlanilla' => ['required', 'in:NORMAL,AGUINALDO,VACACIONES,LIQUIDACION'],
        ]);

        $ids = $this->idsParaCalculo();

        if (empty($ids)) {
            $this->notificar('warning', 'Sin trabajadores', 'Presione "Cargar trabajadores" o seleccione al menos un trabajador.');
            return;
        }

        if ($this->tipoPlanilla === Planilla::TIPO_LIQUIDACION && count($ids) !== 1) {
            $this->notificar('warning', 'Seleccione uno', 'La liquidación se genera trabajador por trabajador.');
            return;
        }

        $planilla = $this->buscarPlanillaPeriodo();

        if ($planilla && $this->planillaBloqueada($planilla)) {
            $this->planillaActualId = $planilla->Id_Planilla;
            $this->notificar('warning', 'Planilla bloqueada', 'Este periodo ya tiene pagos aplicados y no puede recalcularse.');
            return;
        }

        if ($planilla) {
            $this->planillaConfirmarId = $planilla->Id_Planilla;
            $this->idsConfirmarRecalculo = $ids;
            $this->modalConfirmarRecalculo = true;
            return;
        }

        $this->procesarPlanilla($ids, null);
    }

    public function confirmarRecalculo(): void
    {
        $planilla = Planilla::query()->find($this->planillaConfirmarId);

        if (! $planilla) {
            $this->modalConfirmarRecalculo = false;
            $this->notificar('warning', 'Planilla no encontrada', 'No se pudo cargar la planilla para actualizar.');
            return;
        }

        if ($this->planillaBloqueada($planilla)) {
            $this->modalConfirmarRecalculo = false;
            $this->planillaActualId = $planilla->Id_Planilla;
            $this->notificar('warning', 'Planilla bloqueada', 'Ya tiene pagos aplicados y no puede recalcularse.');
            return;
        }

        $this->procesarPlanilla($this->idsConfirmarRecalculo, $planilla);

        $this->modalConfirmarRecalculo = false;
        $this->planillaConfirmarId = null;
        $this->idsConfirmarRecalculo = [];
    }

    private function procesarPlanilla(array $ids, ?Planilla $planilla): void
    {
        $desde = Carbon::parse($this->fechaInicioCorte)->startOfDay();
        $hasta = Carbon::parse($this->fechaFinCorte)->endOfDay();
        $actualizando = (bool) $planilla;

        DB::transaction(function () use ($ids, $desde, $hasta, &$planilla) {
            if (! $planilla) {
                $planilla = Planilla::create([
                    'Fecha_Inicio_Corte' => $desde,
                    'Fecha_Fin_Corte' => $hasta,
                    'Fecha_Generacion' => Carbon::now(),
                    'Tipo_Planilla' => $this->tipoPlanilla,
                    'Estado' => Planilla::ESTADO_CALCULADA,
                    'Total_Bruto' => 0,
                    'Total_Incentivos' => 0,
                    'Total_Vacaciones' => 0,
                    'Total_Aguinaldo' => 0,
                    'Total_Indemnizacion' => 0,
                    'Total_Deducciones' => 0,
                    'Total_Neto' => 0,
                    'Observacion' => null,
                ]);
            } else {
                $this->limpiarDetallesPlanilla($planilla);

                $planilla->update([
                    'Fecha_Inicio_Corte' => $desde,
                    'Fecha_Fin_Corte' => $hasta,
                    'Fecha_Generacion' => Carbon::now(),
                    'Tipo_Planilla' => $this->tipoPlanilla,
                    'Estado' => Planilla::ESTADO_CALCULADA,
                    'Observacion' => null,
                ]);
            }

            $trabajadores = Trabajador::query()
                ->with(['persona', 'cargo'])
                ->whereIn('Id_Trabajador', $ids)
                ->where('Estado', 1)
                ->get();

            foreach ($trabajadores as $trabajador) {
                $this->crearDetallePlanilla($planilla, $trabajador, $desde, $hasta, false);
            }

            $this->actualizarTotalesPlanilla($planilla);
            $this->planillaActualId = $planilla->Id_Planilla;
        });

        $this->limpiarSeleccion();

        $this->notificar(
            'success',
            $actualizando ? 'Planilla actualizada' : 'Planilla generada',
            'El periodo seleccionado quedó calculado correctamente.'
        );
    }

    private function recalcularPlanilla(Planilla $planilla): void
    {
        $desde = Carbon::parse($planilla->Fecha_Inicio_Corte)->startOfDay();
        $hasta = Carbon::parse($planilla->Fecha_Fin_Corte)->endOfDay();

        DB::transaction(function () use ($planilla, $desde, $hasta) {
            $ids = $planilla->detalles()->pluck('Id_Trabajador')->filter()->values()->all();

            if (empty($ids)) {
                return;
            }

            $this->limpiarDetallesPlanilla($planilla);

            $trabajadores = Trabajador::query()
                ->with(['persona', 'cargo'])
                ->whereIn('Id_Trabajador', $ids)
                ->where('Estado', 1)
                ->get();

            foreach ($trabajadores as $trabajador) {
                $this->crearDetallePlanilla($planilla, $trabajador, $desde, $hasta, false);
            }

            $this->actualizarTotalesPlanilla($planilla);
        });
    }

    private function limpiarDetallesPlanilla(Planilla $planilla): void
    {
        $detalleIds = $planilla->detalles()->pluck('Id_Detalle_Planilla');

        if ($detalleIds->isEmpty()) {
            return;
        }

        IncentivoTrabajador::query()
            ->whereIn('Id_Detalle_Planilla', $detalleIds)
            ->update([
                'Id_Detalle_Planilla' => null,
                'Estado' => IncentivoTrabajador::ESTADO_PENDIENTE,
            ]);

        DeduccionTrabajador::query()
            ->whereIn('Id_Detalle_Planilla', $detalleIds)
            ->update([
                'Id_Detalle_Planilla' => null,
                'Estado' => DeduccionTrabajador::ESTADO_PENDIENTE,
            ]);

        Vacaciones::query()
            ->whereIn('Id_Detalle_Planilla', $detalleIds)
            ->update([
                'Id_Detalle_Planilla' => null,
                'Estado' => Vacaciones::ESTADO_APROBADA,
            ]);

        MovimientoVacacion::query()
            ->whereIn('Id_Detalle_Planilla', $detalleIds)
            ->where('Tipo_Movimiento', MovimientoVacacion::TIPO_PAGADA)
            ->delete();

        MovimientoVacacion::query()
            ->whereIn('Id_Detalle_Planilla', $detalleIds)
            ->update(['Id_Detalle_Planilla' => null]);

        DetallePlanilla::query()
            ->whereIn('Id_Detalle_Planilla', $detalleIds)
            ->delete();
    }

    private function crearDetallePlanilla(Planilla $planilla, Trabajador $trabajador, Carbon $desde, Carbon $hasta, bool $forzarLiquidacion): void
    {
        $tipo = $forzarLiquidacion ? Planilla::TIPO_LIQUIDACION : $this->tipoPlanilla;

        $salarioDia = $this->salarioDiario($trabajador);
        $diasCorte = $this->diasCorte($desde, $hasta, $tipo, $forzarLiquidacion);

        $incluyeSalario = in_array($tipo, [Planilla::TIPO_NORMAL, Planilla::TIPO_LIQUIDACION], true);
        $incluyeExtras = in_array($tipo, [Planilla::TIPO_NORMAL, Planilla::TIPO_LIQUIDACION], true);

        $diasTrabajados = $incluyeSalario ? $diasCorte : 0;
        $salarioBase = $incluyeSalario ? round($salarioDia * $diasTrabajados, 2) : 0;

        $incentivos = $incluyeExtras
            ? $this->incentivosPendientes($trabajador->Id_Trabajador, $desde, $hasta)
            : collect();

        $deducciones = $incluyeExtras
            ? $this->deduccionesPendientes($trabajador->Id_Trabajador, $desde, $hasta)
            : collect();

        $montoIncentivos = round((float) $incentivos->sum('Monto'), 2);
        $montoDeducciones = round((float) $deducciones->sum('Monto'), 2);

        $diasVacaciones = 0;
        $montoVacaciones = 0;
        $montoAguinaldo = 0;
        $montoIndemnizacion = 0;

        if ($tipo === Planilla::TIPO_VACACIONES) {
            $diasVacaciones = $this->saldoVacacionesNumero($trabajador, $hasta);
            $montoVacaciones = round($diasVacaciones * $salarioDia, 2);
        }

        if ($tipo === Planilla::TIPO_AGUINALDO) {
            $montoAguinaldo = $this->aguinaldoProporcional($trabajador, $hasta);
        }

        if ($tipo === Planilla::TIPO_LIQUIDACION) {
            $diasVacaciones = $this->saldoVacacionesNumero($trabajador, $hasta);
            $montoVacaciones = round($diasVacaciones * $salarioDia, 2);
            $montoAguinaldo = $this->aguinaldoProporcional($trabajador, $hasta);
            $montoIndemnizacion = $this->liquidacionIncluirIndemnizacion
                ? $this->indemnizacionAntiguedad($trabajador, $hasta)
                : 0;
        }

        $totalBruto = round(
            $salarioBase +
            $montoVacaciones +
            $montoIncentivos +
            $montoAguinaldo +
            $montoIndemnizacion,
            2
        );

        $totalNeto = round(max(0, $totalBruto - $montoDeducciones), 2);

        $detalle = DetallePlanilla::create([
            'Id_Planilla' => $planilla->Id_Planilla,
            'Id_Trabajador' => $trabajador->Id_Trabajador,
            'Salario_Base' => $salarioBase,
            'Dias_Trabajados' => $diasTrabajados,
            'Dias_Vacaciones' => $diasVacaciones,
            'Monto_Vacaciones' => $montoVacaciones,
            'Monto_Incentivo' => $montoIncentivos,
            'Monto_Aguinaldo' => $montoAguinaldo,
            'Monto_Indemnizacion' => $montoIndemnizacion,
            'Monto_Deduccion' => $montoDeducciones,
            'Total_Bruto' => $totalBruto,
            'Total_Neto' => $totalNeto,
            'Estado_Pago' => DetallePlanilla::ESTADO_PENDIENTE,
            'Fecha_Pago' => null,
            'Observacion' => $tipo === Planilla::TIPO_LIQUIDACION ? $this->liquidacionObservacion : null,
        ]);

        if ($incentivos->isNotEmpty()) {
            IncentivoTrabajador::query()
                ->whereIn('Id_Incentivo', $incentivos->pluck('Id_Incentivo'))
                ->update([
                    'Id_Detalle_Planilla' => $detalle->Id_Detalle_Planilla,
                    'Estado' => IncentivoTrabajador::ESTADO_APLICADO,
                ]);
        }

        if ($deducciones->isNotEmpty()) {
            DeduccionTrabajador::query()
                ->whereIn('Id_Deduccion', $deducciones->pluck('Id_Deduccion'))
                ->update([
                    'Id_Detalle_Planilla' => $detalle->Id_Detalle_Planilla,
                    'Estado' => DeduccionTrabajador::ESTADO_APLICADA,
                ]);
        }

        if (in_array($tipo, [Planilla::TIPO_VACACIONES, Planilla::TIPO_LIQUIDACION], true) && $diasVacaciones > 0) {
            MovimientoVacacion::create([
                'Id_Trabajador' => $trabajador->Id_Trabajador,
                'Id_Vacacion' => null,
                'Id_Detalle_Planilla' => $detalle->Id_Detalle_Planilla,
                'Fecha_Movimiento' => $hasta,
                'Tipo_Movimiento' => MovimientoVacacion::TIPO_PAGADA,
                'Dias' => $diasVacaciones,
                'Observacion' => $tipo === Planilla::TIPO_LIQUIDACION
                    ? 'Vacaciones acumuladas liquidadas.'
                    : 'Vacaciones acumuladas pagadas.',
            ]);
        }
    }

    public function abrirPago(?int $detalleId = null): void
    {
        $planilla = $this->planillaActual();

        if (! $planilla) {
            $this->notificar('warning', 'Sin planilla', 'Primero genere o cargue una planilla.');
            return;
        }

        $this->resetValidation();
        $this->planillaActualId = $planilla->Id_Planilla;
        $this->pagoDetalleId = $detalleId;
        $this->pagoFecha = Carbon::now()->format('Y-m-d');
        $this->pagoMetodo = PagoPlanilla::METODO_EFECTIVO;
        $this->pagoObservacion = null;
        $this->modalPago = true;
    }

    public function registrarPago(): void
    {
        $this->validate([
            'pagoFecha' => ['required', 'date'],
            'pagoMetodo' => ['required', 'in:EFECTIVO,TRANSFERENCIA,CHEQUE,OTRO'],
            'pagoObservacion' => ['nullable', 'string', 'max:255'],
        ]);

        $planilla = $this->planillaActual();

        if (! $planilla) {
            $this->notificar('warning', 'Sin planilla', 'No hay planilla seleccionada.');
            return;
        }

        DB::transaction(function () use ($planilla) {
            $detalles = DetallePlanilla::query()
                ->where('Id_Planilla', $planilla->Id_Planilla)
                ->when($this->pagoDetalleId, fn ($query) => $query->where('Id_Detalle_Planilla', $this->pagoDetalleId))
                ->where('Estado_Pago', '!=', DetallePlanilla::ESTADO_PAGADO)
                ->get();

            foreach ($detalles as $detalle) {
                PagoPlanilla::create([
                    'Id_Detalle_Planilla' => $detalle->Id_Detalle_Planilla,
                    'Fecha_Pago' => Carbon::parse($this->pagoFecha)->startOfDay(),
                    'Monto_Pagado' => $this->numeroSeguro($detalle->Total_Neto),
                    'Metodo_Pago' => $this->pagoMetodo,
                    'Observacion' => $this->pagoObservacion,
                ]);

                $detalle->update([
                    'Estado_Pago' => DetallePlanilla::ESTADO_PAGADO,
                    'Fecha_Pago' => Carbon::parse($this->pagoFecha)->startOfDay(),
                ]);
            }

            $pendientes = DetallePlanilla::query()
                ->where('Id_Planilla', $planilla->Id_Planilla)
                ->where('Estado_Pago', '!=', DetallePlanilla::ESTADO_PAGADO)
                ->count();

            $planilla->update([
                'Estado' => $pendientes === 0 ? Planilla::ESTADO_PAGADA : Planilla::ESTADO_CALCULADA,
            ]);
        });

        $this->modalPago = false;
        $this->notificar('success', 'Pago registrado', 'El pago fue aplicado correctamente.');
    }

    public function abrirReporte(): void
    {
        $planilla = $this->planillaActual();

        if (! $planilla) {
            $this->notificar('warning', 'Sin planilla', 'Primero genere o cargue una planilla.');
            return;
        }

        $this->planillaActualId = $planilla->Id_Planilla;
        $this->modalReporte = true;
    }

    private function actualizarTotalesPlanilla(Planilla $planilla): void
    {
        $totales = DetallePlanilla::query()
            ->where('Id_Planilla', $planilla->Id_Planilla)
            ->selectRaw('
                COALESCE(SUM(Total_Bruto), 0) AS bruto,
                COALESCE(SUM(Monto_Incentivo), 0) AS incentivos,
                COALESCE(SUM(Monto_Vacaciones), 0) AS vacaciones,
                COALESCE(SUM(Monto_Aguinaldo), 0) AS aguinaldo,
                COALESCE(SUM(Monto_Indemnizacion), 0) AS indemnizacion,
                COALESCE(SUM(Monto_Deduccion), 0) AS deducciones,
                COALESCE(SUM(Total_Neto), 0) AS neto
            ')
            ->first();

        $planilla->update([
            'Total_Bruto' => $totales->bruto,
            'Total_Incentivos' => $totales->incentivos,
            'Total_Vacaciones' => $totales->vacaciones,
            'Total_Aguinaldo' => $totales->aguinaldo,
            'Total_Indemnizacion' => $totales->indemnizacion,
            'Total_Deducciones' => $totales->deducciones,
            'Total_Neto' => $totales->neto,
        ]);
    }

    private function incentivosPendientes(int $trabajadorId, Carbon $desde, Carbon $hasta)
    {
        return IncentivoTrabajador::query()
            ->where('Id_Trabajador', $trabajadorId)
            ->whereNull('Id_Detalle_Planilla')
            ->where('Estado', IncentivoTrabajador::ESTADO_PENDIENTE)
            ->whereBetween('Fecha_Incentivo', [$desde, $hasta])
            ->get();
    }

    private function deduccionesPendientes(int $trabajadorId, Carbon $desde, Carbon $hasta)
    {
        return DeduccionTrabajador::query()
            ->where('Id_Trabajador', $trabajadorId)
            ->whereNull('Id_Detalle_Planilla')
            ->where('Estado', DeduccionTrabajador::ESTADO_PENDIENTE)
            ->whereBetween('Fecha_Deduccion', [$desde, $hasta])
            ->get();
    }

    public function saldoVacaciones(int $trabajadorId): string
    {
        $trabajador = Trabajador::query()->find($trabajadorId);

        if (! $trabajador) {
            return '0';
        }

        return $this->numero($this->saldoVacacionesNumero($trabajador, $this->fechaFinCorte ?: Carbon::now()));
    }

    private function saldoVacacionesNumero(Trabajador $trabajador, string|Carbon|null $hasta = null): float
    {
        $fechaHasta = $hasta instanceof Carbon
            ? $hasta->copy()->startOfDay()
            : Carbon::parse($hasta ?: Carbon::now())->startOfDay();

        $acumuladas = $this->diasVacacionesAcumuladas($trabajador, $fechaHasta);

        $movimientos = MovimientoVacacion::query()
            ->where('Id_Trabajador', $trabajador->Id_Trabajador)
            ->whereDate('Fecha_Movimiento', '<=', $fechaHasta)
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN Tipo_Movimiento = 'AJUSTE_POSITIVO' THEN Dias
                        WHEN Tipo_Movimiento IN ('TOMADA', 'PAGADA', 'AJUSTE_NEGATIVO') THEN -Dias
                        ELSE 0
                    END
                ), 0) AS total
            ")
            ->value('total');

        return round(max(0, $acumuladas + (float) $movimientos), 2);
    }

    private function diasVacacionesAcumuladas(Trabajador $trabajador, Carbon $hasta): float
    {
        if (! $trabajador->Fecha_Ingreso) {
            return 0;
        }

        $ingreso = Carbon::parse($trabajador->Fecha_Ingreso)->startOfDay();

        if ($hasta->lessThan($ingreso)) {
            return 0;
        }

        $diasLaborados = $ingreso->diffInDays($hasta) + 1;

        return round(($diasLaborados / 30) * 2.5, 2);
    }

    private function salarioDiario(Trabajador $trabajador): float
    {
        return round($this->numeroSeguro($trabajador->Salario) / 30, 2);
    }

    private function aguinaldoProporcional(Trabajador $trabajador, Carbon $fechaCorte): float
    {
        $salario = $this->numeroSeguro($trabajador->Salario);

        if ($salario <= 0) {
            return 0;
        }

        $inicioPeriodo = $fechaCorte->month === 12
            ? Carbon::create($fechaCorte->year, 12, 1)->startOfDay()
            : Carbon::create($fechaCorte->year - 1, 12, 1)->startOfDay();

        if ($trabajador->Fecha_Ingreso) {
            $ingreso = Carbon::parse($trabajador->Fecha_Ingreso)->startOfDay();

            if ($ingreso->greaterThan($inicioPeriodo)) {
                $inicioPeriodo = $ingreso;
            }
        }

        if ($fechaCorte->lessThan($inicioPeriodo)) {
            return 0;
        }

        $dias = max(0, $inicioPeriodo->diffInDays($fechaCorte) + 1);

        return round(($salario / 360) * min(360, $dias), 2);
    }

    private function indemnizacionAntiguedad(Trabajador $trabajador, Carbon $fechaSalida): float
    {
        if (! $trabajador->Fecha_Ingreso) {
            return 0;
        }

        $salario = $this->numeroSeguro($trabajador->Salario);
        $ingreso = Carbon::parse($trabajador->Fecha_Ingreso)->startOfDay();

        if ($salario <= 0 || $fechaSalida->lessThan($ingreso)) {
            return 0;
        }

        $anios = ($ingreso->diffInDays($fechaSalida) + 1) / 360;

        if ($anios <= 3) {
            $diasIndemnizacion = $anios * 30;
        } else {
            $diasIndemnizacion = 90 + (($anios - 3) * 20);
        }

        $diasIndemnizacion = min(150, max(30, $diasIndemnizacion));

        return round($this->salarioDiario($trabajador) * $diasIndemnizacion, 2);
    }

    private function idsParaCalculo(): array
    {
        return collect($this->selectedTrabajadores)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function primerTrabajadorSeleccionado(): ?int
    {
        return collect($this->selectedTrabajadores)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->first();
    }

    private function calcularDiasVacacion(): void
    {
        if (! $this->vacacionFechaInicio || ! $this->vacacionFechaFin) {
            return;
        }

        try {
            $inicio = Carbon::parse($this->vacacionFechaInicio);
            $fin = Carbon::parse($this->vacacionFechaFin);

            $this->vacacionDias = $fin->lessThan($inicio)
                ? 1
                : $inicio->diffInDays($fin) + 1;
        } catch (\Throwable) {
            $this->vacacionDias = 1;
        }
    }

    private function diasCorte(Carbon $desde, Carbon $hasta, string $tipo, bool $liquidacion = false): int
    {
        if ($tipo !== Planilla::TIPO_NORMAL && ! $liquidacion) {
            return 0;
        }

        if ($liquidacion) {
            return min(15, max(1, $desde->diffInDays($hasta) + 1));
        }

        return 15;
    }

    private function planillaBloqueada(Planilla $planilla): bool
    {
        if ($planilla->Estado === Planilla::ESTADO_PAGADA) {
            return true;
        }

        return PagoPlanilla::query()
            ->whereHas('detallePlanilla', fn ($query) => $query->where('Id_Planilla', $planilla->Id_Planilla))
            ->exists();
    }

    private function inicioQuincenaPorFecha(Carbon $fecha): Carbon
    {
        return (int) $fecha->format('d') <= 15
            ? $fecha->copy()->day(1)->startOfDay()
            : $fecha->copy()->day(16)->startOfDay();
    }

    private function fechaDentroPeriodo($fecha): string
    {
        $fecha = Carbon::parse($fecha)->startOfDay();

        $inicio = Carbon::parse($this->fechaInicioCorte)->startOfDay();
        $fin = Carbon::parse($this->fechaFinCorte)->endOfDay();

        if ($fecha->betweenIncluded($inicio, $fin)) {
            return $fecha->format('Y-m-d');
        }

        return $fin->format('Y-m-d');
    }

    private function limpiarSeleccion(): void
    {
        $this->selectedTrabajadores = [];
        $this->seleccionarTodos = false;
    }

    public function nombreTrabajador(Trabajador $trabajador): string
    {
        $persona = $trabajador->persona;

        if (! $persona) {
            return 'Trabajador #' . $trabajador->Id_Trabajador;
        }

        $nombre = collect([
            $persona->Primer_Nombre ?? null,
            $persona->Segundo_Nombre ?? null,
            $persona->Primer_Apellido ?? null,
            $persona->Segundo_Apellido ?? null,
        ])->filter()->implode(' ');

        return trim($nombre) !== '' ? $nombre : 'Trabajador #' . $trabajador->Id_Trabajador;
    }

    public function cargoTrabajador(Trabajador $trabajador): string
    {
        return data_get($trabajador, 'cargo.Nombre_Cargo')
            ?? data_get($trabajador, 'cargo.Cargo')
            ?? data_get($trabajador, 'cargo.Nombre')
            ?? 'Sin cargo';
    }

    public function telefonoTrabajador(Trabajador $trabajador): string
    {
        return data_get($trabajador, 'persona.Telefono') ?: 'No registrado';
    }

    public function money($value): string
    {
        return 'C$ ' . number_format($this->numeroSeguro($value), 2, '.', ',');
    }

    public function numero($value): string
    {
        $number = $this->numeroSeguro($value);

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    private function numeroSeguro($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return round((float) str_replace(',', '', (string) $value), 2);
    }

    private function notificar(string $type, string $title, string $description): void
    {
        if (function_exists('toast')) {
            toast(
                type: $type,
                title: $title,
                description: $description,
                position: 'toast-top toast-end',
                timeout: 2800
            );

            return;
        }

        session()->flash('message', "{$title} - {$description}");
    }
};

?>

<div class="payroll-page min-h-screen bg-[#F0F3F7] p-4 md:p-6 space-y-5 text-[#111827]">
    <style>
        .payroll-page label,
        .payroll-page .label,
        .payroll-page .label-text,
        .payroll-page input,
        .payroll-page select,
        .payroll-page textarea,
        .payroll-page table,
        .payroll-page td {
            color: #111827 !important;
        }

        .payroll-page input::placeholder,
        .payroll-page textarea::placeholder {
            color: #6B7280 !important;
        }
    </style>

    @php
        $trabajadores = $this->trabajadores();
        $trabajadorOptions = $this->trabajadoresOptions();
        $resumen = $this->resumen();
        $detalles = $this->detallePlanillaRows();
        $planillaActual = $this->planillaActual();

        $headersTrabajadores = [
            ['key' => 'select', 'label' => ''],
            ['key' => 'empleado', 'label' => 'Empleado'],
            ['key' => 'cargo', 'label' => 'Cargo'],
            ['key' => 'telefono', 'label' => 'Teléfono'],
            ['key' => 'salario', 'label' => 'Salario mensual'],
            ['key' => 'vacaciones', 'label' => 'Vacaciones'],
        ];

        $headersDetalle = $this->headersDetalle();

        $tipoOptions = [
            ['id' => 'NORMAL', 'name' => 'Normal'],
            ['id' => 'AGUINALDO', 'name' => 'Aguinaldo'],
            ['id' => 'VACACIONES', 'name' => 'Pago vacaciones'],
            ['id' => 'LIQUIDACION', 'name' => 'Liquidación'],
        ];

        $quincenaOptions = [
            ['id' => 'PRIMERA', 'name' => '1 al 15'],
            ['id' => 'SEGUNDA', 'name' => '16 al cierre'],
        ];

        $estadoVacacionOptions = [
            ['id' => 'SOLICITADA', 'name' => 'Solicitada'],
            ['id' => 'APROBADA', 'name' => 'Aprobada'],
            ['id' => 'PAGADA', 'name' => 'Pagada'],
            ['id' => 'ANULADA', 'name' => 'Anulada'],
            ['id' => 'RECHAZADA', 'name' => 'Rechazada'],
        ];

        $metodoPagoOptions = [
            ['id' => 'EFECTIVO', 'name' => 'Efectivo'],
            ['id' => 'TRANSFERENCIA', 'name' => 'Transferencia'],
            ['id' => 'CHEQUE', 'name' => 'Cheque'],
            ['id' => 'OTRO', 'name' => 'Otro'],
        ];
    @endphp

    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-[#1A2B42]">Planilla de trabajadores</h1>
            <p class="mt-1 text-sm text-[#111827]">
                Gestión quincenal de pagos, incentivos, deducciones, vacaciones, aguinaldo y liquidaciones.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full border border-[#D7E4F3] bg-white px-4 py-2 text-sm font-semibold text-[#111827]">
                Seleccionados: {{ count($selectedTrabajadores) }}
            </span>
            <span class="rounded-full border border-[#D7E4F3] bg-white px-4 py-2 text-sm font-semibold text-[#111827]">
                Estado: {{ $resumen['estado'] }}
            </span>
        </div>
    </div>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-input label="Mes" type="month" wire:model.live="periodoMes" class="bg-[#F0F3F7] text-[#111827]" />

            <x-select
                label="Periodo"
                :options="$quincenaOptions"
                option-value="id"
                option-label="name"
                wire:model.live="quincena"
                class="bg-[#F0F3F7] text-[#111827]"
            />

            <x-select
                label="Tipo de planilla"
                :options="$tipoOptions"
                option-value="id"
                option-label="name"
                wire:model.live="tipoPlanilla"
                class="bg-[#F0F3F7] text-[#111827]"
            />

            <x-input label="Inicio" type="date" wire:model="fechaInicioCorte" readonly class="bg-[#F0F3F7] text-[#111827]" />
            <x-input label="Corte" type="date" wire:model="fechaFinCorte" readonly class="bg-[#F0F3F7] text-[#111827]" />
        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-7">
            <div class="rounded-2xl bg-[#F0F3F7] p-4">
                <p class="text-xs font-semibold uppercase text-[#111827]">Bruto</p>
                <p class="mt-1 text-lg font-bold text-[#1A2B42]">{{ $resumen['bruto'] }}</p>
            </div>
            <div class="rounded-2xl bg-[#F0F3F7] p-4">
                <p class="text-xs font-semibold uppercase text-[#111827]">Incentivos</p>
                <p class="mt-1 text-lg font-bold text-[#1A2B42]">{{ $resumen['incentivos'] }}</p>
            </div>
            <div class="rounded-2xl bg-[#F0F3F7] p-4">
                <p class="text-xs font-semibold uppercase text-[#111827]">Vacaciones</p>
                <p class="mt-1 text-lg font-bold text-[#1A2B42]">{{ $resumen['vacaciones'] }}</p>
            </div>
            <div class="rounded-2xl bg-[#F0F3F7] p-4">
                <p class="text-xs font-semibold uppercase text-[#111827]">Aguinaldo</p>
                <p class="mt-1 text-lg font-bold text-[#1A2B42]">{{ $resumen['aguinaldo'] }}</p>
            </div>
            <div class="rounded-2xl bg-[#F0F3F7] p-4">
                <p class="text-xs font-semibold uppercase text-[#111827]">Indemnización</p>
                <p class="mt-1 text-lg font-bold text-[#1A2B42]">{{ $resumen['indemnizacion'] }}</p>
            </div>
            <div class="rounded-2xl bg-[#F0F3F7] p-4">
                <p class="text-xs font-semibold uppercase text-[#111827]">Deducciones</p>
                <p class="mt-1 text-lg font-bold text-[#1A2B42]">{{ $resumen['deducciones'] }}</p>
            </div>
            <div class="rounded-2xl bg-[#EAF2FB] p-4">
                <p class="text-xs font-semibold uppercase text-[#111827]">Neto</p>
                <p class="mt-1 text-lg font-bold text-[#0E48A1]">{{ $resumen['neto'] }}</p>
            </div>
        </div>
    </x-card>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <h2 class="text-xl font-bold text-[#1A2B42]">Acciones rápidas</h2>
                <p class="text-sm text-[#111827]">
                    Cada periodo solo puede tener una planilla por tipo. Si ya existe, se solicita confirmación para actualizarla.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button label="Cargar trabajadores" icon="o-users" wire:click="cargarTrabajadores" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
                <x-button label="Vacaciones" icon="o-sun" wire:click="abrirVacaciones" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
                <x-button label="Incentivo" icon="o-gift" wire:click="abrirIncentivo" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
                <x-button label="Deducción" icon="o-minus-circle" wire:click="abrirDeduccion" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
                <x-button label="Calcular" icon="o-calculator" wire:click="generarPlanilla" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
                <x-button label="Liquidar" icon="o-user-minus" wire:click="abrirLiquidacion" class="border-0 bg-[#E67E22] text-white hover:opacity-90" spinner />
                <x-button label="Pagar" icon="o-banknotes" wire:click="abrirPago" class="border-0 bg-[#E67E22] text-white hover:opacity-90" spinner />
                <x-button label="Reporte" icon="o-document-text" wire:click="abrirReporte" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
            </div>
        </div>
    </x-card>

    <div class="grid grid-cols-1 gap-5 2xl:grid-cols-2">
        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-[#1A2B42]">Trabajadores disponibles</h2>
                    <p class="text-sm text-[#111827]">Use “Cargar trabajadores” para seleccionar el listado activo.</p>
                </div>

                <label class="inline-flex items-center gap-2 rounded-xl border border-[#D7E4F3] bg-[#F0F3F7] px-3 py-2 text-sm font-semibold text-[#111827]">
                    <input type="checkbox" wire:model.live="seleccionarTodos" class="checkbox checkbox-sm border-[#2E8BC0]">
                    Seleccionar listado
                </label>
            </div>

            <div class="overflow-x-auto">
                <x-table
                    :headers="$headersTrabajadores"
                    :rows="$trabajadores"
                    with-pagination
                    no-hover
            class="[&_table]:min-w-[980px] [&_table]:w-full [&_table]:border-separate [&_table]:border-spacing-0 [&_table]:text-[13px] [&_table]:text-[#1A2B42] [&_thead]:sticky [&_thead]:top-0 [&_thead]:z-10 [&_thead_th]:border-0 [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:px-3 [&_thead_th]:py-3 [&_thead_th]:font-semibold [&_thead_th]:text-white [&_thead_th]:whitespace-nowrap [&_thead_th:first-child]:rounded-tl-xl [&_thead_th:last-child]:rounded-tr-xl [&_tbody_tr:nth-child(odd)]:bg-white! [&_tbody_tr:nth-child(even)]:bg-[#F8FBFF]! [&_tbody_tr:hover]:!bg-[#EAF4FD] [&_tbody_td]:border-0 [&_tbody_td]:px-3 [&_tbody_td]:py-3 [&_tbody_td]:align-middle [&_tbody_td]:text-[#1A2B42]"
                >
                    @scope('cell_select', $trabajador)
                        <input type="checkbox" value="{{ $trabajador->Id_Trabajador }}" wire:model.live="selectedTrabajadores" class="checkbox checkbox-sm border-[#2E8BC0]">
                    @endscope

                    @scope('cell_empleado', $trabajador)
                        <div>
                            <p class="font-semibold text-[#111827]">{{ $this->nombreTrabajador($trabajador) }}</p>
                            <p class="text-xs text-[#111827]">Ingreso: {{ optional($trabajador->Fecha_Ingreso)->format('d/m/Y') ?? 'Sin fecha' }}</p>
                        </div>
                    @endscope

                    @scope('cell_cargo', $trabajador)
                        <span class="inline-flex rounded-full bg-[#D7E4F3] px-3 py-1 text-xs font-semibold text-[#111827]">
                            {{ $this->cargoTrabajador($trabajador) }}
                        </span>
                    @endscope

                    @scope('cell_telefono', $trabajador)
                        <span class="text-[#111827]">{{ $this->telefonoTrabajador($trabajador) }}</span>
                    @endscope

                    @scope('cell_salario', $trabajador)
                        <span class="font-semibold text-[#111827]">{{ $this->money($trabajador->Salario) }}</span>
                    @endscope

                    @scope('cell_vacaciones', $trabajador)
                        <span class="font-semibold text-[#0E48A1]">{{ $this->saldoVacaciones($trabajador->Id_Trabajador) }} días</span>
                    @endscope

                    @scope('actions', $trabajador)
                        <div class="flex flex-wrap gap-1">
                            <x-button icon="o-sun" wire:click="abrirVacaciones({{ $trabajador->Id_Trabajador }})" class="btn-sm border-0 bg-[#EAF2FB] text-[#111827] hover:bg-[#D7E4F3]" spinner />
                            <x-button icon="o-gift" wire:click="abrirIncentivo({{ $trabajador->Id_Trabajador }})" class="btn-sm border-0 bg-[#EAF2FB] text-[#111827] hover:bg-[#D7E4F3]" spinner />
                            <x-button icon="o-minus-circle" wire:click="abrirDeduccion({{ $trabajador->Id_Trabajador }})" class="btn-sm border-0 bg-[#EAF2FB] text-[#111827] hover:bg-[#D7E4F3]" spinner />
                            <x-button icon="o-user-minus" wire:click="abrirLiquidacion({{ $trabajador->Id_Trabajador }})" class="btn-sm border-0 bg-[#FCEAD8] text-[#9A4A0A] hover:bg-[#F7D1A6]" spinner />
                        </div>
                    @endscope
                </x-table>
            </div>
        </x-card>

        <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-[#1A2B42]">Detalle de planilla</h2>
                <p class="text-sm text-[#111827]">
                    {{ $planillaActual ? 'Planilla #' . $planillaActual->Id_Planilla . ' - ' . $planillaActual->Tipo_Planilla : 'No hay planilla calculada para este periodo.' }}
                </p>
            </div>

            <div class="overflow-x-auto">
                <x-table
                    :headers="$headersDetalle"
                    :rows="$detalles"
                    no-hover
            class="[&_table]:min-w-[980px] [&_table]:w-full [&_table]:border-separate [&_table]:border-spacing-0 [&_table]:text-[13px] [&_table]:text-[#1A2B42] [&_thead]:sticky [&_thead]:top-0 [&_thead]:z-10 [&_thead_th]:border-0 [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:px-3 [&_thead_th]:py-3 [&_thead_th]:font-semibold [&_thead_th]:text-white [&_thead_th]:whitespace-nowrap [&_thead_th:first-child]:rounded-tl-xl [&_thead_th:last-child]:rounded-tr-xl [&_tbody_tr:nth-child(odd)]:bg-white! [&_tbody_tr:nth-child(even)]:bg-[#F8FBFF]! [&_tbody_tr:hover]:!bg-[#EAF4FD] [&_tbody_td]:border-0 [&_tbody_td]:px-3 [&_tbody_td]:py-3 [&_tbody_td]:align-middle [&_tbody_td]:text-[#1A2B42]"
                >
                    @scope('cell_empleado', $row)
                        <span class="font-semibold text-[#111827]">{{ $row['empleado'] }}</span>
                    @endscope

                    @scope('cell_cargo', $row)
                        <span class="inline-flex rounded-full bg-[#D7E4F3] px-3 py-1 text-xs font-semibold text-[#111827]">
                            {{ $row['cargo'] }}
                        </span>
                    @endscope

                    @scope('cell_total', $row)
                        <span class="font-bold text-[#0E48A1]">{{ $row['total'] }}</span>
                    @endscope

                    @scope('cell_estado', $row)
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $row['estado'] === 'PAGADO' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $row['estado'] }}
                        </span>
                    @endscope

                    @scope('actions', $row)
                        @if($row['estado'] !== 'PAGADO')
                            <x-button icon="o-banknotes" wire:click="abrirPago({{ $row['id'] }})" class="btn-sm border-0 bg-[#EAF2FB] text-[#111827] hover:bg-[#D7E4F3]" spinner />
                        @endif
                    @endscope
                </x-table>
            </div>
        </x-card>
    </div>

    <x-modal wire:model="modalConfirmarRecalculo" title="Actualizar planilla existente" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="rounded-2xl bg-[#F0F3F7] p-4 text-sm text-[#111827]">
            Ya existe una planilla calculada para este periodo y tipo. Si continuás, se recalcularán sus detalles con los trabajadores seleccionados, incentivos y deducciones pendientes.
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalConfirmarRecalculo', false)" />
            <x-button label="Actualizar planilla" icon="o-check" wire:click="confirmarRecalculo" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalIncentivo" title="Registrar incentivo" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-select label="Trabajador" :options="$trabajadorOptions" option-value="id" option-label="name" wire:model="formTrabajadorId" placeholder="Seleccione un trabajador" class="bg-[#F0F3F7] text-[#111827]" />
            </div>
            <x-input label="Fecha" type="date" wire:model="fechaIncentivo" class="bg-[#F0F3F7] text-[#111827]" />
            <x-input label="Monto" type="number" step="0.01" min="0" wire:model="montoIncentivo" prefix="C$" class="bg-[#F0F3F7] text-[#111827]" />
            <div class="md:col-span-2">
                <x-input label="Concepto" wire:model="conceptoIncentivo" placeholder="Ej: Bono por desempeño" class="bg-[#F0F3F7] text-[#111827]" />
            </div>
            <div class="md:col-span-2">
                <x-textarea label="Observación" wire:model="observacionIncentivo" rows="3" placeholder="Opcional" class="bg-[#F0F3F7] text-[#111827]" />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalIncentivo', false)" />
            <x-button label="Guardar" icon="o-check" wire:click="registrarIncentivo" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalDeduccion" title="Registrar deducción" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-select label="Trabajador" :options="$trabajadorOptions" option-value="id" option-label="name" wire:model="formTrabajadorId" placeholder="Seleccione un trabajador" class="bg-[#F0F3F7] text-[#111827]" />
            </div>
            <x-input label="Fecha" type="date" wire:model="fechaDeduccion" class="bg-[#F0F3F7] text-[#111827]" />
            <x-input label="Monto" type="number" step="0.01" min="0" wire:model="montoDeduccion" prefix="C$" class="bg-[#F0F3F7] text-[#111827]" />
            <div class="md:col-span-2">
                <x-input label="Concepto" wire:model="conceptoDeduccion" placeholder="Ej: Adelanto salarial" class="bg-[#F0F3F7] text-[#111827]" />
            </div>
            <div class="md:col-span-2">
                <x-textarea label="Observación" wire:model="observacionDeduccion" rows="3" placeholder="Opcional" class="bg-[#F0F3F7] text-[#111827]" />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalDeduccion', false)" />
            <x-button label="Guardar" icon="o-check" wire:click="registrarDeduccion" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalVacaciones" title="Registrar vacaciones" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-select label="Trabajador" :options="$trabajadorOptions" option-value="id" option-label="name" wire:model="formTrabajadorId" placeholder="Seleccione un trabajador" class="bg-[#F0F3F7] text-[#111827]" />
            </div>
            <x-input label="Fecha inicio" type="date" wire:model.live="vacacionFechaInicio" class="bg-[#F0F3F7] text-[#111827]" />
            <x-input label="Fecha fin" type="date" wire:model.live="vacacionFechaFin" class="bg-[#F0F3F7] text-[#111827]" />
            <x-input label="Días" type="number" min="1" wire:model="vacacionDias" class="bg-[#F0F3F7] text-[#111827]" />
            <x-select label="Estado" :options="$estadoVacacionOptions" option-value="id" option-label="name" wire:model="vacacionEstado" class="bg-[#F0F3F7] text-[#111827]" />
            <div class="md:col-span-2">
                <x-textarea label="Observación" wire:model="vacacionObservacion" rows="3" placeholder="Opcional" class="bg-[#F0F3F7] text-[#111827]" />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalVacaciones', false)" />
            <x-button label="Guardar" icon="o-check" wire:click="registrarVacaciones" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalLiquidacion" title="Liquidar trabajador" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-select label="Trabajador" :options="$trabajadorOptions" option-value="id" option-label="name" wire:model="formTrabajadorId" placeholder="Seleccione un trabajador" class="bg-[#F0F3F7] text-[#111827]" />
            </div>

            <x-input label="Fecha de salida" type="date" wire:model="liquidacionFechaSalida" class="bg-[#F0F3F7] text-[#111827]" />
            <x-input label="Motivo" wire:model="liquidacionMotivo" placeholder="Renuncia, despido, mutuo acuerdo..." class="bg-[#F0F3F7] text-[#111827]" />

            <label class="md:col-span-2 flex items-center gap-3 rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-4 text-sm font-semibold text-[#111827]">
                <input type="checkbox" wire:model.live="liquidacionIncluirIndemnizacion" class="checkbox checkbox-sm border-[#2E8BC0]">
                Incluir indemnización por antigüedad
            </label>

            <div class="md:col-span-2">
                <x-textarea label="Observación" wire:model="liquidacionObservacion" rows="3" placeholder="Opcional" class="bg-[#F0F3F7] text-[#111827]" />
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalLiquidacion', false)" />
            <x-button label="Generar liquidación" icon="o-check" wire:click="liquidarTrabajador" class="border-0 bg-[#E67E22] text-white hover:opacity-90" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalPago" title="Registrar pago de planilla" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="space-y-4">
            <div class="rounded-2xl bg-[#F0F3F7] p-4 text-sm text-[#111827]">
                @if($pagoDetalleId)
                    Se pagará únicamente el detalle seleccionado.
                @else
                    Se pagarán todos los detalles pendientes de la planilla actual.
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Fecha de pago" type="date" wire:model="pagoFecha" class="bg-[#F0F3F7] text-[#111827]" />
                <x-select label="Método de pago" :options="$metodoPagoOptions" option-value="id" option-label="name" wire:model="pagoMetodo" class="bg-[#F0F3F7] text-[#111827]" />
                <div class="md:col-span-2">
                    <x-textarea label="Observación" wire:model="pagoObservacion" rows="3" placeholder="Opcional" class="bg-[#F0F3F7] text-[#111827]" />
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalPago', false)" />
            <x-button label="Registrar pago" icon="o-check" wire:click="registrarPago" class="border-0 bg-[#E67E22] text-white hover:opacity-90" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalReporte" title="Resumen de planilla" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        @if($planillaActual)
            <div class="space-y-4">
                <div class="rounded-2xl bg-[#F0F3F7] p-4">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <p class="text-sm text-[#111827]"><span class="font-semibold">Planilla:</span> #{{ $planillaActual->Id_Planilla }}</p>
                        <p class="text-sm text-[#111827]"><span class="font-semibold">Tipo:</span> {{ $planillaActual->Tipo_Planilla }}</p>
                        <p class="text-sm text-[#111827]">
                            <span class="font-semibold">Periodo:</span>
                            {{ optional($planillaActual->Fecha_Inicio_Corte)->format('d/m/Y') }}
                            -
                            {{ optional($planillaActual->Fecha_Fin_Corte)->format('d/m/Y') }}
                        </p>
                        <p class="text-sm text-[#111827]"><span class="font-semibold">Estado:</span> {{ $planillaActual->Estado }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-[#EAF2FB] p-4">
                        <p class="text-xs font-semibold uppercase text-[#111827]">Bruto</p>
                        <p class="text-lg font-bold text-[#1A2B42]">{{ $this->money($planillaActual->Total_Bruto) }}</p>
                    </div>
                    <div class="rounded-2xl bg-[#EAF2FB] p-4">
                        <p class="text-xs font-semibold uppercase text-[#111827]">Deducciones</p>
                        <p class="text-lg font-bold text-[#1A2B42]">{{ $this->money($planillaActual->Total_Deducciones) }}</p>
                    </div>
                    <div class="rounded-2xl bg-[#EAF2FB] p-4">
                        <p class="text-xs font-semibold uppercase text-[#111827]">Neto</p>
                        <p class="text-lg font-bold text-[#0E48A1]">{{ $this->money($planillaActual->Total_Neto) }}</p>
                    </div>
                </div>
            </div>
        @endif

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalReporte', false)" />
        </x-slot:actions>
    </x-modal>
</div>