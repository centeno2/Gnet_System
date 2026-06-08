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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $periodoMes = '';
    public string $quincena = '';
    public string $fechaInicioCorte = '';
    public string $fechaFinCorte = '';

    public ?int $planillaActualId = null;
    public ?int $previewPlanillaId = null;

    public bool $modalConfirmarPlanilla = false;
    public bool $modalComprobante = false;
    public bool $modalReporteAnual = false;
    public bool $modalIncentivo = false;
    public bool $modalDeduccion = false;
    public bool $modalVacaciones = false;
    public bool $modalLiquidacion = false;

    public string $tipoGeneracionPendiente = Planilla::TIPO_NORMAL;

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

    public string $liquidacionFechaSalida = '';
    public string $liquidacionMotivo = 'RENUNCIA';
    public bool $liquidacionIncluirIndemnizacion = false;
    public ?string $liquidacionObservacion = null;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $hoy = Carbon::now();

        $this->periodoMes = $hoy->format('Y-m');
        $this->quincena = ((int) $hoy->format('d')) <= 15 ? 'PRIMERA' : 'SEGUNDA';

        $this->aplicarPeriodoActual();

        $this->fechaIncentivo = $this->fechaDentroPeriodo($hoy);
        $this->fechaDeduccion = $this->fechaDentroPeriodo($hoy);
        $this->vacacionFechaInicio = $this->fechaDentroPeriodo($hoy);
        $this->vacacionFechaFin = $this->fechaDentroPeriodo($hoy);
        $this->liquidacionFechaSalida = $hoy->format('Y-m-d');

        $this->cargarPlanillaNormalActual();
    }

public function paginationView(): string
{
    return 'vendor.pagination.gnet';
}

    public function updatedPeriodoMes(): void
    {
        $hoy = Carbon::now();
        $base = $hoy->copy()->startOfMonth();

        if (((int) $hoy->format('d')) <= 15) {
            $this->quincena = 'PRIMERA';
            $inicio = $base->copy()->day(1)->startOfDay();
            $fin = $base->copy()->day(15)->endOfDay();
        } else {
            $this->quincena = 'SEGUNDA';
            $inicio = $base->copy()->day(16)->startOfDay();
            $fin = $base->copy()->endOfMonth()->endOfDay();
        }

        $this->periodoMes = $hoy->format('Y-m');
        $this->fechaInicioCorte = $inicio->format('Y-m-d');
        $this->fechaFinCorte = $fin->format('Y-m-d');
    }

    public function cargarPlanillaNormalActual(): void
    {
        $planilla = $this->buscarPlanillaPeriodo(Planilla::TIPO_NORMAL);
        $this->planillaActualId = $planilla?->Id_Planilla;
    }

    public function periodoCerrado(): bool
    {
        return $this->planillaNormalEstaCerrada();
    }

    public function claseAccionPrimaria(): string
    {
        return $this->periodoCerrado()
            ? 'border border-[#D7E4F3] bg-[#E5E7EB] text-[#5F6B7A] hover:bg-[#E5E7EB]'
            : 'border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]';
    }

    public function claseAccionSecundaria(): string
    {
        return $this->periodoCerrado()
            ? 'border border-[#D7E4F3] bg-[#E5E7EB] text-[#5F6B7A] hover:bg-[#E5E7EB]'
            : 'border border-[#D7E4F3] bg-white text-[#111827] hover:bg-[#F0F3F7]';
    }

    public function claseLiquidar(): string
    {
        return $this->periodoCerrado()
            ? 'border border-[#D7E4F3] bg-[#E5E7EB] text-[#5F6B7A] hover:bg-[#E5E7EB]'
            : 'border-0 bg-[#E67E22] text-white hover:opacity-90';
    }

    private function bloquearSiPeriodoCerrado(string $accion): bool
    {
        if (! $this->planillaNormalEstaCerrada()) {
            return false;
        }

        $this->notificar(
            'warning',
            'Opción no disponible',
            "No se puede {$accion} porque la planilla del periodo actual ya fue generada."
        );

        return true;
    }

    public function solicitarGenerarPlanilla(): void
    {
        $existente = $this->buscarPlanillaPeriodo(Planilla::TIPO_NORMAL);

        if ($existente) {
            $this->planillaActualId = $existente->Id_Planilla;
            $this->previewPlanillaId = $existente->Id_Planilla;
            $this->modalComprobante = true;

            $this->notificar('info', 'Planilla existente', 'Ya existe una planilla para el periodo actual. Se cargó el comprobante.');
            return;
        }

        if ($this->trabajadoresParaPlanilla()->isEmpty()) {
            $this->notificar('warning', 'Sin trabajadores', 'No hay trabajadores activos disponibles para generar la planilla.');
            return;
        }

        $this->tipoGeneracionPendiente = Planilla::TIPO_NORMAL;
        $this->modalConfirmarPlanilla = true;
    }

    public function solicitarGenerarAguinaldo(): void
    {
        if (! $this->puedeGenerarAguinaldo()) {
            $this->notificar('warning', 'Aguinaldo no disponible', 'El aguinaldo solo se genera en el cierre de diciembre.');
            return;
        }

        $existente = $this->buscarPlanillaPeriodo(Planilla::TIPO_AGUINALDO);

        if ($existente) {
            $this->previewPlanillaId = $existente->Id_Planilla;
            $this->modalComprobante = true;

            $this->notificar('info', 'Aguinaldo existente', 'Ya existe el pago de aguinaldo para este periodo.');
            return;
        }

        $this->tipoGeneracionPendiente = Planilla::TIPO_AGUINALDO;
        $this->modalConfirmarPlanilla = true;
    }

    public function confirmarGeneracionPlanilla(): void
    {
        $tipo = $this->tipoGeneracionPendiente;

        $existente = $this->buscarPlanillaPeriodo($tipo);

        if ($existente) {
            $this->modalConfirmarPlanilla = false;
            $this->previewPlanillaId = $existente->Id_Planilla;
            $this->modalComprobante = true;

            if ($tipo === Planilla::TIPO_NORMAL) {
                $this->planillaActualId = $existente->Id_Planilla;
            }

            $this->notificar('info', 'Planilla existente', 'No se duplicó el periodo. Se cargó el comprobante.');
            return;
        }

        $ids = $this->trabajadoresParaPlanilla()
            ->pluck('Id_Trabajador')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($ids)) {
            $this->modalConfirmarPlanilla = false;
            $this->notificar('warning', 'Sin trabajadores', 'No hay trabajadores activos para generar la planilla.');
            return;
        }

        $planilla = $this->procesarPlanillaPagada($ids, $tipo);

        if ($tipo === Planilla::TIPO_NORMAL) {
            $this->planillaActualId = $planilla->Id_Planilla;
        }

        $this->previewPlanillaId = $planilla->Id_Planilla;
        $this->modalConfirmarPlanilla = false;
        $this->modalComprobante = true;

        $this->notificar('success', 'Planilla generada', 'La planilla fue generada y marcada como pagada.');
    }

    private function procesarPlanillaPagada(array $ids, string $tipo): Planilla
    {
        $desde = Carbon::parse($this->fechaInicioCorte)->startOfDay();
        $hasta = Carbon::parse($this->fechaFinCorte)->endOfDay();

        return DB::transaction(function () use ($ids, $desde, $hasta, $tipo) {
            $planilla = Planilla::create([
                'Fecha_Inicio_Corte' => $desde,
                'Fecha_Fin_Corte' => $hasta,
                'Fecha_Generacion' => Carbon::now(),
                'Tipo_Planilla' => $tipo,
                'Estado' => Planilla::ESTADO_PAGADA,
                'Total_Bruto' => 0,
                'Total_Incentivos' => 0,
                'Total_Vacaciones' => 0,
                'Total_Aguinaldo' => 0,
                'Total_Indemnizacion' => 0,
                'Total_Deducciones' => 0,
                'Total_Neto' => 0,
                'Observacion' => null,
            ]);

            foreach ($this->trabajadoresPorIds($ids) as $trabajador) {
                $detalle = $this->crearDetallePlanilla($planilla, $trabajador, $desde, $hasta, $tipo);
                $this->crearPagoAutomatico($detalle);
            }

            $this->actualizarTotalesPlanilla($planilla);

            return $planilla->fresh(['detalles.trabajador.persona', 'detalles.trabajador.cargo']);
        });
    }

    public function registrarIncentivo(): void
    {
        if ($this->bloquearSiPeriodoCerrado('registrar incentivos')) {
            $this->modalIncentivo = false;
            return;
        }

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
        $this->notificar('success', 'Incentivo registrado', 'Quedó listo para la planilla del periodo actual.');
    }

    public function registrarDeduccion(): void
    {
        if ($this->bloquearSiPeriodoCerrado('registrar deducciones')) {
            $this->modalDeduccion = false;
            return;
        }

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
        $this->notificar('success', 'Deducción registrada', 'Quedó lista para la planilla del periodo actual.');
    }

    public function registrarVacaciones(): void
    {
        if ($this->bloquearSiPeriodoCerrado('registrar vacaciones')) {
            $this->modalVacaciones = false;
            return;
        }

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

    public function liquidarTrabajador(): void
    {
        if ($this->bloquearSiPeriodoCerrado('liquidar trabajadores en este periodo')) {
            $this->modalLiquidacion = false;
            return;
        }

        $this->validate([
            'formTrabajadorId' => ['required', 'integer', 'exists:trabajador,Id_Trabajador'],
            'liquidacionFechaSalida' => ['required', 'date'],
            'liquidacionMotivo' => ['required', 'string', 'max:80'],
            'liquidacionObservacion' => ['nullable', 'string', 'max:255'],
        ], [
            'formTrabajadorId.required' => 'Seleccione un trabajador.',
            'liquidacionFechaSalida.required' => 'Ingrese la fecha de salida.',
            'liquidacionMotivo.required' => 'Seleccione el motivo de salida.',
        ]);

        $trabajador = Trabajador::query()
            ->with(['persona', 'cargo'])
            ->where('Estado', 1)
            ->find($this->formTrabajadorId);

        if (! $trabajador) {
            $this->notificar('warning', 'Trabajador no válido', 'El trabajador no existe o ya fue liquidado.');
            return;
        }

        $fechaSalida = Carbon::parse($this->liquidacionFechaSalida)->endOfDay();

        if ($trabajador->Fecha_Ingreso && $fechaSalida->lessThan(Carbon::parse($trabajador->Fecha_Ingreso))) {
            $this->notificar('warning', 'Fecha inválida', 'La salida no puede ser menor a la fecha de ingreso.');
            return;
        }

        if ($this->liquidacionMotivo === 'DESPIDO_JUSTIFICADO') {
            $this->liquidacionIncluirIndemnizacion = false;
        }

        $planilla = DB::transaction(function () use ($trabajador, $fechaSalida) {
            $desde = $this->inicioQuincenaPorFecha($fechaSalida);
            $hasta = $fechaSalida->copy();

            $planilla = Planilla::create([
                'Fecha_Inicio_Corte' => $desde,
                'Fecha_Fin_Corte' => $hasta,
                'Fecha_Generacion' => Carbon::now(),
                'Tipo_Planilla' => Planilla::TIPO_LIQUIDACION,
                'Estado' => Planilla::ESTADO_PAGADA,
                'Total_Bruto' => 0,
                'Total_Incentivos' => 0,
                'Total_Vacaciones' => 0,
                'Total_Aguinaldo' => 0,
                'Total_Indemnizacion' => 0,
                'Total_Deducciones' => 0,
                'Total_Neto' => 0,
                'Observacion' => $this->liquidacionMotivo,
            ]);

            $detalle = $this->crearDetallePlanilla($planilla, $trabajador, $desde, $hasta, Planilla::TIPO_LIQUIDACION);
            $this->crearPagoAutomatico($detalle);
            $this->actualizarTotalesPlanilla($planilla);

            $trabajador->update([
                'Estado' => 0,
                'Fecha_Salida' => $fechaSalida->toDateString(),
                'Motivo_Salida' => $this->liquidacionMotivo,
            ]);

            return $planilla->fresh(['detalles.trabajador.persona', 'detalles.trabajador.cargo']);
        });

        $this->modalLiquidacion = false;
        $this->previewPlanillaId = $planilla->Id_Planilla;
        $this->modalComprobante = true;

        $this->notificar('success', 'Liquidación generada', 'La liquidación fue generada de forma individual y el trabajador quedó inactivo.');
    }

    private function crearDetallePlanilla(Planilla $planilla, Trabajador $trabajador, Carbon $desde, Carbon $hasta, string $tipo): DetallePlanilla
    {
        $salarioDia = $this->salarioDiario($trabajador);
        $diasCorte = $this->diasCorte($trabajador, $desde, $hasta, $tipo);

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
            'Estado_Pago' => DetallePlanilla::ESTADO_PAGADO,
            'Fecha_Pago' => Carbon::now(),
            'Observacion' => $tipo === Planilla::TIPO_LIQUIDACION ? $this->liquidacionObservacion : null,
        ]);

        $this->marcarMovimientosAplicados($detalle, $incentivos, $deducciones);
        $this->registrarMovimientoVacacionesPagadas($tipo, $trabajador, $detalle, $hasta, $diasVacaciones);

        return $detalle;
    }

    private function crearPagoAutomatico(DetallePlanilla $detalle): void
    {
        PagoPlanilla::create([
            'Id_Detalle_Planilla' => $detalle->Id_Detalle_Planilla,
            'Fecha_Pago' => Carbon::now(),
            'Monto_Pagado' => $this->numeroSeguro($detalle->Total_Neto),
            'Metodo_Pago' => PagoPlanilla::METODO_EFECTIVO,
            'Observacion' => 'Pago generado automáticamente al crear la planilla.',
        ]);
    }

    private function marcarMovimientosAplicados(DetallePlanilla $detalle, $incentivos, $deducciones): void
    {
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
    }

    private function registrarMovimientoVacacionesPagadas(string $tipo, Trabajador $trabajador, DetallePlanilla $detalle, Carbon $fecha, float $dias): void
    {
        if ($tipo !== Planilla::TIPO_LIQUIDACION || $dias <= 0) {
            return;
        }

        MovimientoVacacion::create([
            'Id_Trabajador' => $trabajador->Id_Trabajador,
            'Id_Vacacion' => null,
            'Id_Detalle_Planilla' => $detalle->Id_Detalle_Planilla,
            'Fecha_Movimiento' => $fecha,
            'Tipo_Movimiento' => MovimientoVacacion::TIPO_PAGADA,
            'Dias' => $dias,
            'Observacion' => 'Vacaciones acumuladas liquidadas.',
        ]);
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

    public function abrirIncentivo(?int $trabajadorId = null): void
    {
        if ($this->bloquearSiPeriodoCerrado('registrar incentivos')) {
            return;
        }

        $this->resetValidation();
        $this->formTrabajadorId = $trabajadorId;
        $this->fechaIncentivo = $this->fechaDentroPeriodo(Carbon::now());
        $this->conceptoIncentivo = '';
        $this->montoIncentivo = 0;
        $this->observacionIncentivo = null;
        $this->modalIncentivo = true;
    }

    public function abrirDeduccion(?int $trabajadorId = null): void
    {
        if ($this->bloquearSiPeriodoCerrado('registrar deducciones')) {
            return;
        }

        $this->resetValidation();
        $this->formTrabajadorId = $trabajadorId;
        $this->fechaDeduccion = $this->fechaDentroPeriodo(Carbon::now());
        $this->conceptoDeduccion = '';
        $this->montoDeduccion = 0;
        $this->observacionDeduccion = null;
        $this->modalDeduccion = true;
    }

    public function abrirVacaciones(?int $trabajadorId = null): void
    {
        if ($this->bloquearSiPeriodoCerrado('registrar vacaciones')) {
            return;
        }

        $this->resetValidation();
        $this->formTrabajadorId = $trabajadorId;
        $this->vacacionFechaInicio = $this->fechaDentroPeriodo(Carbon::now());
        $this->vacacionFechaFin = $this->fechaDentroPeriodo(Carbon::now());
        $this->vacacionDias = 1;
        $this->vacacionEstado = Vacaciones::ESTADO_APROBADA;
        $this->vacacionObservacion = null;
        $this->modalVacaciones = true;
    }

    public function abrirLiquidacion(?int $trabajadorId = null): void
    {
        if ($this->bloquearSiPeriodoCerrado('liquidar trabajadores en este periodo')) {
            return;
        }

        $this->resetValidation();
        $this->formTrabajadorId = $trabajadorId;
        $this->liquidacionFechaSalida = Carbon::now()->format('Y-m-d');
        $this->liquidacionMotivo = 'RENUNCIA';
        $this->liquidacionIncluirIndemnizacion = false;
        $this->liquidacionObservacion = null;
        $this->modalLiquidacion = true;
    }

    public function updatedLiquidacionMotivo(): void
    {
        $this->liquidacionIncluirIndemnizacion = $this->liquidacionMotivo === 'DESPIDO_INJUSTIFICADO';
    }

    public function abrirComprobanteActual(): void
    {
        $planilla = $this->planillaActual();

        if (! $planilla) {
            $this->notificar('warning', 'Sin planilla', 'Todavía no hay una planilla generada para este periodo.');
            return;
        }

        $this->previewPlanillaId = $planilla->Id_Planilla;
        $this->modalComprobante = true;
    }

    public function abrirReporteAnual(): void
    {
        $this->modalReporteAnual = true;
    }

    public function exportarComprobanteCsv()
    {
        $planilla = $this->previewPlanilla();

        if (! $planilla) {
            $this->notificar('warning', 'Sin comprobante', 'No hay comprobante disponible para exportar.');
            return null;
        }

        $filename = 'planilla_' . $planilla->Id_Planilla . '.csv';

        return response()->streamDownload(function () use ($planilla) {
            echo "\xEF\xBB\xBF";

            $output = fopen('php://output', 'w');

            fputcsv($output, ['Planilla', $planilla->Id_Planilla]);
            fputcsv($output, ['Tipo', $planilla->Tipo_Planilla]);
            fputcsv($output, ['Estado', $planilla->Estado]);
            fputcsv($output, ['Periodo', Carbon::parse($planilla->Fecha_Inicio_Corte)->format('d/m/Y') . ' - ' . Carbon::parse($planilla->Fecha_Fin_Corte)->format('d/m/Y')]);
            fputcsv($output, []);

            fputcsv($output, [
                'Empleado',
                'Cargo',
                'Salario',
                'Incentivo',
                'Deducción',
                'Vacaciones',
                'Aguinaldo',
                'Indemnización',
                'Total',
            ]);

            foreach ($planilla->detalles as $detalle) {
                $trabajador = $detalle->trabajador;

                fputcsv($output, [
                    $trabajador ? $this->nombreTrabajador($trabajador) : 'Sin trabajador',
                    $trabajador ? $this->cargoTrabajador($trabajador) : 'Sin cargo',
                    $this->numeroSeguro($detalle->Salario_Base),
                    $this->numeroSeguro($detalle->Monto_Incentivo),
                    $this->numeroSeguro($detalle->Monto_Deduccion),
                    $this->numeroSeguro($detalle->Monto_Vacaciones),
                    $this->numeroSeguro($detalle->Monto_Aguinaldo),
                    $this->numeroSeguro($detalle->Monto_Indemnizacion ?? 0),
                    $this->numeroSeguro($detalle->Total_Neto),
                ]);
            }

            fclose($output);
        }, $filename);
    }

    public function exportarReporteAnualCsv()
    {
        $year = Carbon::now()->year;
        $filename = 'reporte_planillas_' . $year . '.csv';

        return response()->streamDownload(function () use ($year) {
            echo "\xEF\xBB\xBF";

            $output = fopen('php://output', 'w');

            fputcsv($output, ['Reporte anual de planillas', $year]);
            fputcsv($output, []);

            fputcsv($output, [
                'Planilla',
                'Periodo',
                'Tipo',
                'Estado',
                'Bruto',
                'Incentivos',
                'Vacaciones',
                'Aguinaldo',
                'Indemnización',
                'Deducciones',
                'Neto',
            ]);

            Planilla::query()
                ->whereYear('Fecha_Inicio_Corte', $year)
                ->where('Estado', '!=', Planilla::ESTADO_ANULADA)
                ->orderBy('Fecha_Inicio_Corte')
                ->get()
                ->each(function (Planilla $planilla) use ($output) {
                    fputcsv($output, [
                        $planilla->Id_Planilla,
                        Carbon::parse($planilla->Fecha_Inicio_Corte)->format('d/m/Y') . ' - ' . Carbon::parse($planilla->Fecha_Fin_Corte)->format('d/m/Y'),
                        $planilla->Tipo_Planilla,
                        $planilla->Estado,
                        $this->numeroSeguro($planilla->Total_Bruto),
                        $this->numeroSeguro($planilla->Total_Incentivos),
                        $this->numeroSeguro($planilla->Total_Vacaciones),
                        $this->numeroSeguro($planilla->Total_Aguinaldo),
                        $this->numeroSeguro($planilla->Total_Indemnizacion ?? 0),
                        $this->numeroSeguro($planilla->Total_Deducciones),
                        $this->numeroSeguro($planilla->Total_Neto),
                    ]);
                });

            fclose($output);
        }, $filename);
    }

    public function trabajadoresQuery()
    {
        return Trabajador::query()
            ->with(['persona', 'cargo'])
            ->where('Estado', 1)
            ->when($this->fechaFinCorte, function ($query) {
                $query->whereDate('Fecha_Ingreso', '<=', Carbon::parse($this->fechaFinCorte)->toDateString());
            })
            ->orderBy('Id_Trabajador');
    }

    private function trabajadoresParaPlanilla(): Collection
    {
        return $this->trabajadoresQuery()->get();
    }

    private function trabajadoresPorIds(array $ids): Collection
    {
        return Trabajador::query()
            ->with(['persona', 'cargo'])
            ->whereIn('Id_Trabajador', $ids)
            ->where('Estado', 1)
            ->orderBy('Id_Trabajador')
            ->get();
    }

    public function trabajadores()
    {
        return $this->trabajadoresQuery()->paginate(10);
    }

    public function trabajadoresOptions(): array
    {
        return $this->trabajadoresQuery()
            ->limit(250)
            ->get()
            ->map(fn (Trabajador $trabajador) => [
                'id' => $trabajador->Id_Trabajador,
                'name' => $this->nombreTrabajador($trabajador),
            ])
            ->values()
            ->all();
    }

    public function totalTrabajadoresActivos(): int
    {
        return $this->trabajadoresQuery()->count();
    }

    public function buscarPlanillaPeriodo(?string $tipo = null): ?Planilla
    {
        return Planilla::query()
            ->whereDate('Fecha_Inicio_Corte', Carbon::parse($this->fechaInicioCorte)->toDateString())
            ->whereDate('Fecha_Fin_Corte', Carbon::parse($this->fechaFinCorte)->toDateString())
            ->where('Tipo_Planilla', $tipo ?: Planilla::TIPO_NORMAL)
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

        return null;
    }

    public function previewPlanilla(): ?Planilla
    {
        if (! $this->previewPlanillaId) {
            return null;
        }

        return Planilla::query()
            ->with(['detalles.trabajador.persona', 'detalles.trabajador.cargo'])
            ->find($this->previewPlanillaId);
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
                'codigo' => 'Nueva',
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
            'codigo' => 'Planilla #' . $planilla->Id_Planilla,
        ];
    }

    public function detallePlanillaRows(): array
    {
        $planilla = $this->planillaActual();

        if (! $planilla) {
            return [];
        }

        return $this->mapDetalles($planilla);
    }

    public function mapDetalles(Planilla $planilla): array
    {
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
        $planilla = $this->planillaActual();
        $tipo = $planilla?->Tipo_Planilla ?? Planilla::TIPO_NORMAL;

        $base = [
            ['key' => 'empleado', 'label' => 'Empleado'],
            ['key' => 'cargo', 'label' => 'Cargo'],
        ];

        return match ($tipo) {
            Planilla::TIPO_AGUINALDO => [
                ...$base,
                ['key' => 'aguinaldo', 'label' => 'Aguinaldo'],
                ['key' => 'total', 'label' => 'Total pagado'],
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
                ['key' => 'total', 'label' => 'Total pagado'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            default => [
                ...$base,
                ['key' => 'salario', 'label' => 'Salario quincenal'],
                ['key' => 'dias', 'label' => 'Días'],
                ['key' => 'incentivo', 'label' => 'Incentivo'],
                ['key' => 'deduccion', 'label' => 'Deducción'],
                ['key' => 'total', 'label' => 'Total pagado'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
        };
    }

    public function headersTrabajadores(): array
    {
        return [
            ['key' => 'empleado', 'label' => 'Empleado'],
            ['key' => 'cargo', 'label' => 'Cargo'],
            ['key' => 'salario', 'label' => 'Salario mensual'],
            ['key' => 'vacaciones', 'label' => 'Vacaciones'],
        ];
    }

    public function reporteAnualRows(): array
    {
        $year = Carbon::now()->year;

        return Planilla::query()
            ->whereYear('Fecha_Inicio_Corte', $year)
            ->where('Estado', '!=', Planilla::ESTADO_ANULADA)
            ->orderBy('Fecha_Inicio_Corte')
            ->get()
            ->map(fn (Planilla $planilla) => [
                'id' => $planilla->Id_Planilla,
                'periodo' => Carbon::parse($planilla->Fecha_Inicio_Corte)->format('d/m/Y') . ' - ' . Carbon::parse($planilla->Fecha_Fin_Corte)->format('d/m/Y'),
                'tipo' => $planilla->Tipo_Planilla,
                'estado' => $planilla->Estado,
                'bruto' => $this->money($planilla->Total_Bruto),
                'deducciones' => $this->money($planilla->Total_Deducciones),
                'neto' => $this->money($planilla->Total_Neto),
            ])
            ->values()
            ->all();
    }

    public function reporteAnualResumen(): array
    {
        $year = Carbon::now()->year;

        $totales = Planilla::query()
            ->whereYear('Fecha_Inicio_Corte', $year)
            ->where('Estado', '!=', Planilla::ESTADO_ANULADA)
            ->selectRaw('
                COALESCE(SUM(Total_Bruto), 0) AS bruto,
                COALESCE(SUM(Total_Incentivos), 0) AS incentivos,
                COALESCE(SUM(Total_Vacaciones), 0) AS vacaciones,
                COALESCE(SUM(Total_Aguinaldo), 0) AS aguinaldo,
                COALESCE(SUM(Total_Indemnizacion), 0) AS indemnizacion,
                COALESCE(SUM(Total_Deducciones), 0) AS deducciones,
                COALESCE(SUM(Total_Neto), 0) AS neto,
                COUNT(*) AS cantidad
            ')
            ->first();

        return [
            'cantidad' => (int) $totales->cantidad,
            'bruto' => $this->money($totales->bruto),
            'incentivos' => $this->money($totales->incentivos),
            'vacaciones' => $this->money($totales->vacaciones),
            'aguinaldo' => $this->money($totales->aguinaldo),
            'indemnizacion' => $this->money($totales->indemnizacion),
            'deducciones' => $this->money($totales->deducciones),
            'neto' => $this->money($totales->neto),
        ];
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

        $inicioPeriodo = Carbon::create($fechaCorte->year - 1, 12, 1)->startOfDay();

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

    private function diasCorte(Trabajador $trabajador, Carbon $desde, Carbon $hasta, string $tipo): int
    {
        if (! in_array($tipo, [Planilla::TIPO_NORMAL, Planilla::TIPO_LIQUIDACION], true)) {
            return 0;
        }

        $inicioReal = $desde->copy();

        if ($trabajador->Fecha_Ingreso) {
            $ingreso = Carbon::parse($trabajador->Fecha_Ingreso)->startOfDay();

            if ($ingreso->greaterThan($inicioReal)) {
                $inicioReal = $ingreso;
            }
        }

        if ($hasta->lessThan($inicioReal)) {
            return 0;
        }

        return min(15, max(1, $inicioReal->diffInDays($hasta) + 1));
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

    private function planillaNormalEstaCerrada(): bool
    {
        return (bool) $this->buscarPlanillaPeriodo(Planilla::TIPO_NORMAL);
    }

    public function puedeGenerarAguinaldo(): bool
    {
        $hoy = Carbon::now();

        return (int) $hoy->format('m') === 12 && $this->quincena === 'SEGUNDA';
    }

    public function mesActualTexto(): string
    {
        return Carbon::parse($this->periodoMes . '-01')->translatedFormat('F Y');
    }

    public function periodoTexto(): string
    {
        return $this->quincena === 'PRIMERA' ? '1 al 15' : '16 al cierre';
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
        $cargo = $trabajador->cargo;

        if (! $cargo) {
            return 'Sin cargo';
        }

        foreach (['Nombre_Cargo', 'Nombre', 'Cargo', 'Descripcion_Cargo', 'Descripcion'] as $campo) {
            $valor = data_get($cargo, $campo);

            if ($valor) {
                return (string) $valor;
            }
        }

        foreach ($cargo->getAttributes() as $key => $value) {
            if ($value && (stripos($key, 'cargo') !== false || stripos($key, 'nombre') !== false || stripos($key, 'descripcion') !== false)) {
                return (string) $value;
            }
        }

        return 'Sin cargo';
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
                timeout: 3200
            );

            return;
        }

        session()->flash('message', "{$title} - {$description}");
    }
};

?>

<div class="min-h-screen bg-[#F0F3F7] p-4 md:p-6 space-y-5 text-[#111827]">
    @php
        $trabajadores = $this->trabajadores();
        $trabajadorOptions = $this->trabajadoresOptions();
        $resumen = $this->resumen();
        $detalles = $this->detallePlanillaRows();
        $planillaActual = $this->planillaActual();
        $previewPlanilla = $this->previewPlanilla();
        $reporteRows = $this->reporteAnualRows();
        $reporteResumen = $this->reporteAnualResumen();
        $totalActivos = $this->totalTrabajadoresActivos();

        $estadoVacacionOptions = [
            ['id' => 'SOLICITADA', 'name' => 'Solicitada'],
            ['id' => 'APROBADA', 'name' => 'Aprobada'],
            ['id' => 'PAGADA', 'name' => 'Pagada'],
            ['id' => 'ANULADA', 'name' => 'Anulada'],
            ['id' => 'RECHAZADA', 'name' => 'Rechazada'],
        ];

        $motivoLiquidacionOptions = [
            ['id' => 'RENUNCIA', 'name' => 'Renuncia'],
            ['id' => 'MUTUO_ACUERDO', 'name' => 'Mutuo acuerdo'],
            ['id' => 'DESPIDO_INJUSTIFICADO', 'name' => 'Despido injustificado'],
            ['id' => 'DESPIDO_JUSTIFICADO', 'name' => 'Despido justificado'],
            ['id' => 'FIN_CONTRATO', 'name' => 'Fin de contrato'],
        ];
    @endphp

    <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-[#1A2B42]">Gestión de planilla</h1>
            <p class="mt-1 text-sm text-[#111827]">
                Control del periodo actual de pagos, incentivos, deducciones, vacaciones, aguinaldo y liquidaciones.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($planillaActual)
                <x-button label="Ver comprobante" icon="o-document-text" wire:click="abrirComprobanteActual" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
            @else
                <x-button label="Generar planilla" icon="o-calculator" wire:click="solicitarGenerarPlanilla" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
            @endif

            @if($this->puedeGenerarAguinaldo())
                <x-button label="Aguinaldo" icon="o-banknotes" wire:click="solicitarGenerarAguinaldo" class="border-0 bg-[#E67E22] text-white hover:opacity-90" spinner />
            @endif

            <x-button label="Reporte anual" icon="o-document-text" wire:click="abrirReporteAnual" class="border border-[#D7E4F3] bg-white text-[#111827] hover:bg-[#F0F3F7]" spinner />
        </div>
    </div>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Mes actual</p>
                <div class="rounded-xl bg-[#F0F3F7] px-4 py-3 font-semibold text-[#111827]">
                    {{ ucfirst($this->mesActualTexto()) }}
                </div>
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Periodo actual</p>
                <div class="rounded-xl bg-[#F0F3F7] px-4 py-3 font-semibold text-[#111827]">
                    {{ $this->periodoTexto() }}
                </div>
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Inicio</p>
                <div class="rounded-xl bg-[#F0F3F7] px-4 py-3 font-semibold text-[#111827]">
                    {{ Carbon::parse($fechaInicioCorte)->format('d/m/Y') }}
                </div>
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Corte</p>
                <div class="rounded-xl bg-[#F0F3F7] px-4 py-3 font-semibold text-[#111827]">
                    {{ Carbon::parse($fechaFinCorte)->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </x-card>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#5F6B7A]">Trabajadores activos</p>
            <p class="mt-2 text-3xl font-bold text-[#1A2B42]">{{ $totalActivos }}</p>
        </div>

        <div class="rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#5F6B7A]">Total pagado</p>
            <p class="mt-2 text-3xl font-bold text-[#1A2B42]">{{ $resumen['neto'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#5F6B7A]">Incentivos</p>
            <p class="mt-2 text-3xl font-bold text-[#1A2B42]">{{ $resumen['incentivos'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#5F6B7A]">Deducciones</p>
            <p class="mt-2 text-3xl font-bold text-[#1A2B42]">{{ $resumen['deducciones'] }}</p>
        </div>

        <div class="rounded-2xl border border-[#D7E4F3] bg-[#EAF2FB] p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#5F6B7A]">{{ $resumen['codigo'] }}</p>
            <p class="mt-2 text-2xl font-bold text-[#0E48A1]">{{ $resumen['estado'] }}</p>
        </div>
    </div>

    <x-card class="rounded-2xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <h2 class="text-xl font-bold text-[#1A2B42]">
                    {{ $planillaActual ? 'Detalle de planilla pagada' : 'Trabajadores activos del periodo' }}
                </h2>
                <p class="text-sm text-[#5F6B7A]">
                    @if($planillaActual)
                        {{ 'Planilla #' . $planillaActual->Id_Planilla . ' - ' . $planillaActual->Estado }}
                    @else
                        La planilla normal incluirá automáticamente a todos los trabajadores activos.
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button label="Incentivo" icon="o-plus" wire:click="abrirIncentivo" class="{{ $this->claseAccionPrimaria() }}" spinner />
                <x-button label="Deducción" icon="o-minus" wire:click="abrirDeduccion" class="{{ $this->claseAccionPrimaria() }}" spinner />
                <x-button label="Vacaciones" icon="o-sun" wire:click="abrirVacaciones" class="{{ $this->claseAccionSecundaria() }}" spinner />
                <x-button label="Liquidar" icon="o-user-minus" wire:click="abrirLiquidacion" class="{{ $this->claseLiquidar() }}" spinner />
            </div>
        </div>

        <div class="overflow-x-auto">
            @if($planillaActual)
                <x-table
<<<<<<< HEAD
                    :headers="$this->headersDetalle()"
=======
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
>>>>>>> bac15ec2f1d05e22535613b2d3890f3ccee163dd
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
                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                            {{ $row['estado'] }}
                        </span>
                    @endscope
                </x-table>
            @else
                <x-table
                    :headers="$this->headersTrabajadores()"
                    :rows="$trabajadores"
                    with-pagination
                    class="[&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl [&_tbody_tr:hover]:bg-[#F7F9FC]"
                >
                    @scope('cell_empleado', $trabajador)
                        <div>
                            <p class="font-semibold text-[#111827]">{{ $this->nombreTrabajador($trabajador) }}</p>
                            <p class="text-xs text-[#5F6B7A]">Ingreso: {{ optional($trabajador->Fecha_Ingreso)->format('d/m/Y') ?? 'Sin fecha' }}</p>
                        </div>
                    @endscope

                    @scope('cell_cargo', $trabajador)
                        <span class="inline-flex rounded-full bg-[#D7E4F3] px-3 py-1 text-xs font-semibold text-[#111827]">
                            {{ $this->cargoTrabajador($trabajador) }}
                        </span>
                    @endscope

                    @scope('cell_salario', $trabajador)
                        <span class="font-semibold text-[#111827]">{{ $this->money($trabajador->Salario) }}</span>
                    @endscope

                    @scope('cell_vacaciones', $trabajador)
                        <span class="font-semibold text-[#0E48A1]">{{ $this->saldoVacaciones($trabajador->Id_Trabajador) }} días</span>
                    @endscope

                    @scope('actions', $trabajador)
                        <div class="flex flex-wrap gap-1">
                            <x-button icon="o-plus" wire:click="abrirIncentivo({{ $trabajador->Id_Trabajador }})" class="btn-sm {{ $this->claseAccionSecundaria() }}" spinner />
                            <x-button icon="o-minus" wire:click="abrirDeduccion({{ $trabajador->Id_Trabajador }})" class="btn-sm {{ $this->claseAccionSecundaria() }}" spinner />
                            <x-button icon="o-sun" wire:click="abrirVacaciones({{ $trabajador->Id_Trabajador }})" class="btn-sm {{ $this->claseAccionSecundaria() }}" spinner />
                            <x-button icon="o-user-minus" wire:click="abrirLiquidacion({{ $trabajador->Id_Trabajador }})" class="btn-sm {{ $this->claseLiquidar() }}" spinner />
                        </div>
                    @endscope
                </x-table>
            @endif
        </div>
    </x-card>

    <x-modal wire:model="modalConfirmarPlanilla" title="Confirmar generación de planilla" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="space-y-4 text-[#111827]">
            <x-alert icon="o-exclamation-triangle" class="alert-warning">
                <span>
                    Al confirmar, la planilla será generada como <strong>PAGADA</strong>. No se podrán registrar incentivos, deducciones ni cambios sobre este mismo periodo después de generarla.
                </span>
            </x-alert>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-xl bg-[#F0F3F7] p-4">
                    <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Periodo</p>
                    <p class="font-bold text-[#1A2B42]">{{ $this->periodoTexto() }}</p>
                </div>

                <div class="rounded-xl bg-[#F0F3F7] p-4">
                    <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Trabajadores</p>
                    <p class="font-bold text-[#1A2B42]">{{ $totalActivos }}</p>
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalConfirmarPlanilla', false)" />
            <x-button label="Confirmar y generar" icon="o-check" wire:click="confirmarGeneracionPlanilla" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalComprobante" title="Comprobante de planilla" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl max-w-6xl">
        @if($previewPlanilla)
            <div class="space-y-5 text-[#111827]">
                <div class="flex flex-col gap-2 border-b border-[#D7E4F3] pb-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-[#1A2B42]">Comprobante de planilla</h2>
                        <p class="text-sm text-[#5F6B7A]">
                            Planilla #{{ $previewPlanilla->Id_Planilla }} · {{ $previewPlanilla->Tipo_Planilla }} · {{ $previewPlanilla->Estado }}
                        </p>
                    </div>

                    <div class="text-sm text-[#111827]">
                        <p><strong>Generada:</strong> {{ optional($previewPlanilla->Fecha_Generacion)->format('d/m/Y H:i') }}</p>
                        <p><strong>Periodo:</strong> {{ optional($previewPlanilla->Fecha_Inicio_Corte)->format('d/m/Y') }} - {{ optional($previewPlanilla->Fecha_Fin_Corte)->format('d/m/Y') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="rounded-xl bg-[#F0F3F7] p-4">
                        <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Bruto</p>
                        <p class="text-lg font-bold text-[#1A2B42]">{{ $this->money($previewPlanilla->Total_Bruto) }}</p>
                    </div>
                    <div class="rounded-xl bg-[#F0F3F7] p-4">
                        <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Incentivos</p>
                        <p class="text-lg font-bold text-[#1A2B42]">{{ $this->money($previewPlanilla->Total_Incentivos) }}</p>
                    </div>
                    <div class="rounded-xl bg-[#F0F3F7] p-4">
                        <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Deducciones</p>
                        <p class="text-lg font-bold text-[#1A2B42]">{{ $this->money($previewPlanilla->Total_Deducciones) }}</p>
                    </div>
                    <div class="rounded-xl bg-[#EAF2FB] p-4">
                        <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Total pagado</p>
                        <p class="text-lg font-bold text-[#0E48A1]">{{ $this->money($previewPlanilla->Total_Neto) }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <x-table
                        :headers="[
                            ['key' => 'empleado', 'label' => 'Empleado'],
                            ['key' => 'cargo', 'label' => 'Cargo'],
                            ['key' => 'salario', 'label' => 'Salario'],
                            ['key' => 'incentivo', 'label' => 'Incentivo'],
                            ['key' => 'deduccion', 'label' => 'Deducción'],
                            ['key' => 'total', 'label' => 'Total'],
                        ]"
                        :rows="$this->mapDetalles($previewPlanilla)"
                        class="[&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_tbody_tr:hover]:bg-[#F7F9FC]"
                    >
                        @scope('cell_empleado', $row)
                            <span class="font-semibold text-[#111827]">{{ $row['empleado'] }}</span>
                        @endscope

                        @scope('cell_cargo', $row)
                            <span class="text-[#111827]">{{ $row['cargo'] }}</span>
                        @endscope

                        @scope('cell_total', $row)
                            <span class="font-bold text-[#0E48A1]">{{ $row['total'] }}</span>
                        @endscope
                    </x-table>
                </div>
            </div>
        @endif

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalComprobante', false)" />
            <x-button label="Exportar CSV" icon="o-arrow-down-tray" wire:click="exportarComprobanteCsv" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalIncentivo" title="Registrar incentivo" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-[#111827]">
            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Trabajador</p>
                <x-select :options="$trabajadorOptions" option-value="id" option-label="name" wire:model="formTrabajadorId" placeholder="Seleccione un trabajador" class="bg-[#F0F3F7] text-[#111827]" />
                @error('formTrabajadorId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Fecha</p>
                <x-input type="date" wire:model="fechaIncentivo" class="bg-[#F0F3F7] text-[#111827]" />
                @error('fechaIncentivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Monto</p>
                <x-input type="number" step="0.01" min="0" wire:model="montoIncentivo" prefix="C$" class="bg-[#F0F3F7] text-[#111827]" />
                @error('montoIncentivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Concepto</p>
                <x-input wire:model="conceptoIncentivo" placeholder="Bono, comisión u horas extra" class="bg-[#F0F3F7] text-[#111827]" />
                @error('conceptoIncentivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Observación</p>
                <x-textarea wire:model="observacionIncentivo" rows="3" placeholder="Opcional" class="bg-[#F0F3F7] text-[#111827]" />
                @error('observacionIncentivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalIncentivo', false)" />
            <x-button label="Guardar" icon="o-check" wire:click="registrarIncentivo" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalDeduccion" title="Registrar deducción" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-[#111827]">
            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Trabajador</p>
                <x-select :options="$trabajadorOptions" option-value="id" option-label="name" wire:model="formTrabajadorId" placeholder="Seleccione un trabajador" class="bg-[#F0F3F7] text-[#111827]" />
                @error('formTrabajadorId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Fecha</p>
                <x-input type="date" wire:model="fechaDeduccion" class="bg-[#F0F3F7] text-[#111827]" />
                @error('fechaDeduccion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Monto</p>
                <x-input type="number" step="0.01" min="0" wire:model="montoDeduccion" prefix="C$" class="bg-[#F0F3F7] text-[#111827]" />
                @error('montoDeduccion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Concepto</p>
                <x-input wire:model="conceptoDeduccion" placeholder="Préstamo, ausencia o ajuste" class="bg-[#F0F3F7] text-[#111827]" />
                @error('conceptoDeduccion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Observación</p>
                <x-textarea wire:model="observacionDeduccion" rows="3" placeholder="Opcional" class="bg-[#F0F3F7] text-[#111827]" />
                @error('observacionDeduccion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalDeduccion', false)" />
            <x-button label="Guardar" icon="o-check" wire:click="registrarDeduccion" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalVacaciones" title="Registrar vacaciones" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-[#111827]">
            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Trabajador</p>
                <x-select :options="$trabajadorOptions" option-value="id" option-label="name" wire:model="formTrabajadorId" placeholder="Seleccione un trabajador" class="bg-[#F0F3F7] text-[#111827]" />
                @error('formTrabajadorId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Fecha inicio</p>
                <x-input type="date" wire:model.live="vacacionFechaInicio" class="bg-[#F0F3F7] text-[#111827]" />
                @error('vacacionFechaInicio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Fecha fin</p>
                <x-input type="date" wire:model.live="vacacionFechaFin" class="bg-[#F0F3F7] text-[#111827]" />
                @error('vacacionFechaFin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Días</p>
                <x-input type="number" min="1" wire:model="vacacionDias" class="bg-[#F0F3F7] text-[#111827]" />
                @error('vacacionDias') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Estado</p>
                <x-select :options="$estadoVacacionOptions" option-value="id" option-label="name" wire:model="vacacionEstado" class="bg-[#F0F3F7] text-[#111827]" />
                @error('vacacionEstado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Observación</p>
                <x-textarea wire:model="vacacionObservacion" rows="3" placeholder="Opcional" class="bg-[#F0F3F7] text-[#111827]" />
                @error('vacacionObservacion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalVacaciones', false)" />
            <x-button label="Guardar" icon="o-check" wire:click="registrarVacaciones" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalLiquidacion" title="Liquidar trabajador" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-[#111827]">
            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Trabajador</p>
                <x-select :options="$trabajadorOptions" option-value="id" option-label="name" wire:model="formTrabajadorId" placeholder="Seleccione un trabajador" class="bg-[#F0F3F7] text-[#111827]" />
                @error('formTrabajadorId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Fecha de salida</p>
                <x-input type="date" wire:model="liquidacionFechaSalida" class="bg-[#F0F3F7] text-[#111827]" />
                @error('liquidacionFechaSalida') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Motivo</p>
                <x-select :options="$motivoLiquidacionOptions" option-value="id" option-label="name" wire:model.live="liquidacionMotivo" class="bg-[#F0F3F7] text-[#111827]" />
                @error('liquidacionMotivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="md:col-span-2 flex items-center gap-3 rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-4 text-sm font-semibold text-[#111827]">
                <input type="checkbox" wire:model.live="liquidacionIncluirIndemnizacion" class="checkbox checkbox-sm border-[#2E8BC0]" @disabled($liquidacionMotivo === 'DESPIDO_JUSTIFICADO')>
                Incluir indemnización por antigüedad
            </label>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Observación</p>
                <x-textarea wire:model="liquidacionObservacion" rows="3" placeholder="Opcional" class="bg-[#F0F3F7] text-[#111827]" />
                @error('liquidacionObservacion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalLiquidacion', false)" />
            <x-button label="Generar liquidación" icon="o-check" wire:click="liquidarTrabajador" class="border-0 bg-[#E67E22] text-white hover:opacity-90" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalReporteAnual" title="Reporte anual de planillas" separator box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl max-w-6xl">
        <div class="space-y-5 text-[#111827]">
            <div class="flex flex-col gap-1 border-b border-[#D7E4F3] pb-4">
                <h2 class="text-2xl font-bold text-[#1A2B42]">Reporte anual de planillas</h2>
                <p class="text-sm text-[#5F6B7A]">Año {{ Carbon::now()->year }}</p>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="rounded-xl bg-[#F0F3F7] p-4">
                    <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Planillas</p>
                    <p class="text-lg font-bold text-[#1A2B42]">{{ $reporteResumen['cantidad'] }}</p>
                </div>
                <div class="rounded-xl bg-[#F0F3F7] p-4">
                    <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Bruto</p>
                    <p class="text-lg font-bold text-[#1A2B42]">{{ $reporteResumen['bruto'] }}</p>
                </div>
                <div class="rounded-xl bg-[#F0F3F7] p-4">
                    <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Deducciones</p>
                    <p class="text-lg font-bold text-[#1A2B42]">{{ $reporteResumen['deducciones'] }}</p>
                </div>
                <div class="rounded-xl bg-[#EAF2FB] p-4">
                    <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Total anual</p>
                    <p class="text-lg font-bold text-[#0E48A1]">{{ $reporteResumen['neto'] }}</p>
                </div>
            </div>

            <x-table
                :headers="[
                    ['key' => 'id', 'label' => 'Planilla'],
                    ['key' => 'periodo', 'label' => 'Periodo'],
                    ['key' => 'tipo', 'label' => 'Tipo'],
                    ['key' => 'estado', 'label' => 'Estado'],
                    ['key' => 'bruto', 'label' => 'Bruto'],
                    ['key' => 'deducciones', 'label' => 'Deducciones'],
                    ['key' => 'neto', 'label' => 'Neto'],
                ]"
                :rows="$reporteRows"
                class="[&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_tbody_tr:hover]:bg-[#F7F9FC]"
            >
                @scope('cell_id', $row)
                    <span class="font-semibold text-[#111827]">#{{ $row['id'] }}</span>
                @endscope

                @scope('cell_neto', $row)
                    <span class="font-bold text-[#0E48A1]">{{ $row['neto'] }}</span>
                @endscope
            </x-table>
        </div>

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalReporteAnual', false)" />
            <x-button label="Exportar CSV" icon="o-arrow-down-tray" wire:click="exportarReporteAnualCsv" class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>
</div>