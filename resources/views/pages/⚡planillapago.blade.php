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
use Illuminate\Support\Facades\Schema;
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
    public bool $modalPdfComprobante = false;
    public bool $modalReporteAnual = false;
    public bool $modalPdfReporteAnual = false;
    public bool $modalIncentivo = false;
    public bool $modalDeduccion = false;
    public bool $modalVacaciones = false;
    public bool $modalLiquidacion = false;

    public string $tipoGeneracionPendiente = Planilla::TIPO_NORMAL;

    public ?int $formTrabajadorId = null;


    public string $busquedaTrabajadorModal = '';
    public int $trabajadoresPorPagina = 5;
    public int $detallesPorPagina = 5;
    public int $reportePorPagina = 5;

   
    public array $cargosExcluidosPlanilla = [2, 5];

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

    public function aplicarPeriodoActual(): void
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
        return 'border-0 bg-[#E67E22] text-white hover:opacity-90';
    }


    public function updatedTrabajadoresPorPagina(): void
    {
        $this->trabajadoresPorPagina = 5;
        $this->resetPage('trabajadoresPage');
    }

    public function updatedDetallesPorPagina(): void
    {
        $this->detallesPorPagina = 5;
        $this->resetPage('detallesPage');
        $this->resetPage('comprobantePage');
    }

    public function updatedReportePorPagina(): void
    {
        $this->reportePorPagina = 5;
        $this->resetPage('reportePage');
    }

    public function aguinaldoTrimestralDisponible(): bool
    {
        return $this->esSegundoCorteTrimestral(Carbon::parse($this->fechaFinCorte)->endOfDay());
    }

    public function trimestreTexto(): string
    {
        $mes = Carbon::parse($this->fechaFinCorte)->month;

        return match (true) {
            $mes <= 3 => 'enero a marzo',
            $mes <= 6 => 'abril a junio',
            $mes <= 9 => 'julio a septiembre',
            default => 'octubre a diciembre',
        };
    }

    private function bloquearSiPeriodoCerrado(string $accion): bool
    {
        if (! $this->planillaNormalEstaCerrada()) {
            return false;
        }

        $this->notificar(
            'warning',
            'Opción no disponible',
            "Periodo cerrado para {$accion}."
        );

        return true;
    }

    public function solicitarGenerarPlanilla(): void
    {
        $existente = $this->buscarPlanillaPeriodo(Planilla::TIPO_NORMAL);

        if ($existente) {
            $this->planillaActualId = $existente->Id_Planilla;
            $this->previewPlanillaId = $existente->Id_Planilla;
            $this->resetPage('comprobantePage');
            $this->modalComprobante = true;

            $this->notificar('info', 'Planilla existente', 'Comprobante cargado.');
            return;
        }

        if (! $this->trabajadoresQuery()->exists()) {
            $this->notificar('warning', 'Sin trabajadores', 'No hay trabajadores activos disponibles para generar la planilla.');
            return;
        }

        $this->tipoGeneracionPendiente = Planilla::TIPO_NORMAL;
        $this->modalConfirmarPlanilla = true;
    }

    public function solicitarGenerarAguinaldo(): void
    {
        $this->notificar(
            'info',
            'Aguinaldo trimestral',
            'Se aplica en la planilla normal del segundo corte trimestral.'
        );
    }

    public function confirmarGeneracionPlanilla(): void
    {
        $tipo = $this->tipoGeneracionPendiente;

        $existente = $this->buscarPlanillaPeriodo($tipo);

        if ($existente) {
            $this->modalConfirmarPlanilla = false;
            $this->previewPlanillaId = $existente->Id_Planilla;
            $this->resetPage('comprobantePage');
            $this->modalComprobante = true;

            if ($tipo === Planilla::TIPO_NORMAL) {
                $this->planillaActualId = $existente->Id_Planilla;
                $this->resetPage('detallesPage');
            }

            $this->notificar('info', 'Planilla existente', 'Comprobante cargado.');
            return;
        }

        $ids = $this->trabajadoresQuery()
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
            $this->resetPage('detallesPage');
        }

        $this->previewPlanillaId = $planilla->Id_Planilla;
        $this->resetPage('comprobantePage');
        $this->modalConfirmarPlanilla = false;
        $this->modalComprobante = true;

        $this->notificar('success', 'Planilla generada', 'Marcada como pagada.');
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

            foreach (array_chunk($ids, 250) as $bloqueIds) {
                foreach ($this->trabajadoresPorIds($bloqueIds) as $trabajador) {
                    $detalle = $this->crearDetallePlanilla($planilla, $trabajador, $desde, $hasta, $tipo);
                    $this->crearPagoAutomatico($detalle);
                }
            }

            $this->actualizarTotalesPlanilla($planilla);

            return $planilla->fresh();
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
        $this->notificar('success', 'Incentivo registrado', 'Pendiente de aplicar.');
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
        $this->notificar('success', 'Deducción registrada', 'Pendiente de aplicar.');
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

        $trabajador = $this->trabajadoresQuery()->where('Id_Trabajador', $this->formTrabajadorId)->first();

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
        $this->notificar('success', 'Vacaciones registradas', 'Saldo actualizado.');
    }

    public function liquidarTrabajador(): void
    {
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

        $trabajador = $this->trabajadoresQuery()
            ->where('Id_Trabajador', $this->formTrabajadorId)
            ->first();

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

            return $planilla->fresh();
        });

        $this->modalLiquidacion = false;
        $this->formTrabajadorId = null;
        $this->busquedaTrabajadorModal = '';
        $this->resetPage('trabajadoresPage');
        $this->resetPage('detallesPage');
        $this->cargarPlanillaNormalActual();

        $this->previewPlanillaId = $planilla->Id_Planilla;
        $this->resetPage('comprobantePage');
        $this->modalComprobante = true;

        $this->notificar('success', 'Liquidación generada', 'Comprobante cargado.');
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

        if ($tipo === Planilla::TIPO_NORMAL && $this->esSegundoCorteTrimestral($hasta)) {
            $montoAguinaldo = $this->aguinaldoTrimestral($trabajador, $hasta);
        }

        if ($tipo === Planilla::TIPO_AGUINALDO) {
            $montoAguinaldo = $this->aguinaldoProporcional($trabajador, $hasta);
        }

        if ($tipo === Planilla::TIPO_LIQUIDACION) {
            $diasVacaciones = $this->saldoVacacionesNumero($trabajador, $hasta);
            $montoVacaciones = round($diasVacaciones * $salarioDia, 2);
            $montoAguinaldo = $this->aguinaldoTrimestral($trabajador, $hasta);
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
            'Observacion' => 'Pago de planilla.',
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
            'Observacion' => 'Vacaciones liquidadas.',
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
        $this->busquedaTrabajadorModal = '';
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
        $this->busquedaTrabajadorModal = '';
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
        $this->busquedaTrabajadorModal = '';
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
        $this->resetValidation();
        $this->busquedaTrabajadorModal = '';
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
            $this->notificar('warning', 'Sin planilla', 'No hay planilla generada.');
            return;
        }

        $this->previewPlanillaId = $planilla->Id_Planilla;
        $this->resetPage('comprobantePage');
        $this->modalComprobante = true;
    }

    public function abrirReporteAnual(): void
    {
        $this->modalReporteAnual = true;
    }

    public function abrirPdfComprobante(): void
    {
        if (! $this->previewPlanillaId || ! $this->previewPlanilla()) {
            $this->notificar('warning', 'Sin comprobante', 'No hay comprobante disponible para previsualizar.');
            return;
        }

        $this->modalPdfComprobante = true;
    }

    public function abrirPdfReporteAnual(): void
    {
        $this->modalPdfReporteAnual = true;
    }

    public function urlPdfComprobante(): string
    {
        if (! $this->previewPlanillaId) {
            return 'about:blank';
        }

        return $this->urlExportarComprobante('pdf') . '#toolbar=1&navpanes=0&view=FitH';
    }

    public function urlPdfReporteAnual(): string
    {
        return $this->urlExportarReporteAnual('pdf') . '#toolbar=1&navpanes=0&view=FitH';
    }

    public function urlExportarComprobante(string $formato): string
    {
        $formato = mb_strtolower(trim($formato));

        if (! $this->previewPlanillaId || ! in_array($formato, ['pdf', 'excel', 'word'], true)) {
            return '#';
        }

        return route('planillapago.exportar.comprobante', [
            'planilla' => $this->previewPlanillaId,
            'formato' => $formato,
        ]);
    }

    public function urlExportarReporteAnual(string $formato): string
    {
        $formato = mb_strtolower(trim($formato));

        if (! in_array($formato, ['pdf', 'excel', 'word'], true)) {
            return '#';
        }

        return route('planillapago.exportar.anual', [
            'year' => Carbon::now()->year,
            'formato' => $formato,
        ]);
    }

    public function exportarComprobanteCsv()
    {
        $planilla = $this->previewPlanillaId
            ? Planilla::query()
                ->with(['detalles.trabajador.persona', 'detalles.trabajador.cargo'])
                ->find($this->previewPlanillaId)
            : null;

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


    private function aplicarFiltroCargosExcluidos($query)
    {
        $idsExcluidos = $this->idsCargosExcluidosPlanilla();
        $columnas = $this->columnasTextoCargo();

        if (! empty($idsExcluidos)) {
            $query->whereNotIn('Id_Cargo', $idsExcluidos);
        }

        if (empty($columnas)) {
            return $query;
        }

        return $query->whereDoesntHave('cargo', function ($cargo) use ($columnas) {
            $cargo->where(function ($cargo) use ($columnas) {
                foreach ($columnas as $columna) {
                    $campo = $cargo->getModel()->qualifyColumn($columna);
                    $cargo->orWhereRaw("LOWER({$campo}) LIKE ?", ['%gerente%']);
                }
            });
        });
    }

    private function idsCargosExcluidosPlanilla(): array
    {
        static $ids = null;

        if ($ids !== null) {
            return $ids;
        }

        $ids = array_map('intval', $this->cargosExcluidosPlanilla);

        try {
            $cargo = (new Trabajador())->cargo()->getRelated();
            $key = $cargo->getKeyName();
            $columnas = $this->columnasTextoCargo();

            if (! empty($columnas)) {
                $idsDetectados = $cargo->newQuery()
                    ->where(function ($query) use ($columnas) {
                        foreach ($columnas as $columna) {
                            $campo = $query->getModel()->qualifyColumn($columna);
                            $query->orWhereRaw("LOWER({$campo}) LIKE ?", ['%gerente%']);
                        }
                    })
                    ->pluck($key)
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                $ids = array_values(array_unique(array_merge($ids, $idsDetectados)));
            }
        } catch (\Throwable) {
            $ids = array_values(array_unique($ids));
        }

        return $ids;
    }

    private function columnasTextoCargo(): array
    {
        static $columnas = null;

        if ($columnas !== null) {
            return $columnas;
        }

        try {
            $cargo = (new Trabajador())->cargo()->getRelated();
            $tabla = $cargo->getTable();

            $preferidas = [
                'Nombre_Cargo',
                'nombre_cargo',
                'Nombre',
                'nombre',
                'Cargo',
                'cargo',
                'Descripcion_Cargo',
                'descripcion_cargo',
                'Descripcion',
                'descripcion',
            ];

            $columnasPreferidas = collect($preferidas)
                ->filter(fn (string $columna) => Schema::hasColumn($tabla, $columna));

            $columnasTexto = collect(Schema::getColumnListing($tabla))
                ->filter(function (string $columna) use ($tabla) {
                    if (str_starts_with(strtolower($columna), 'id_')) {
                        return false;
                    }

                    try {
                        $tipo = Schema::getColumnType($tabla, $columna);
                        return in_array($tipo, ['string', 'text'], true);
                    } catch (\Throwable) {
                        return false;
                    }
                });

            $columnas = $columnasPreferidas
                ->merge($columnasTexto)
                ->unique()
                ->values()
                ->all();

            return $columnas;
        } catch (\Throwable) {
            $columnas = [];
            return $columnas;
        }
    }

    public function trabajadoresQuery()
    {
        $query = Trabajador::query()
            ->with(['persona', 'cargo'])
            ->where('Estado', 1)
            ->when($this->fechaFinCorte, function ($query) {
                $query->whereDate('Fecha_Ingreso', '<=', Carbon::parse($this->fechaFinCorte)->toDateString());
            });

        return $this->aplicarFiltroCargosExcluidos($query)->orderBy('Id_Trabajador');
    }

    private function trabajadoresParaPlanilla(): Collection
    {
        return $this->trabajadoresQuery()->get();
    }

    private function trabajadoresPorIds(array $ids): Collection
    {
        $query = Trabajador::query()
            ->with(['persona', 'cargo'])
            ->whereIn('Id_Trabajador', $ids)
            ->where('Estado', 1);

        return $this->aplicarFiltroCargosExcluidos($query)
            ->orderBy('Id_Trabajador')
            ->get();
    }

    public function trabajadores()
    {
        return $this->trabajadoresQuery()
            ->paginate($this->trabajadoresPorPagina, ['*'], 'trabajadoresPage');
    }

    public function trabajadoresOptions(): array
    {
        $busqueda = trim($this->busquedaTrabajadorModal);

        $seleccionado = collect();

        if ($this->formTrabajadorId) {
            $seleccionado = $this->trabajadoresQuery()
                ->where('Id_Trabajador', $this->formTrabajadorId)
                ->get();
        }

        $resultados = $this->trabajadoresQuery()
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($query) use ($busqueda) {
                    $query
                        ->where('Id_Trabajador', 'like', "%{$busqueda}%")
                        ->orWhereHas('persona', function ($persona) use ($busqueda) {
                            $persona
                                ->where('Primer_Nombre', 'like', "%{$busqueda}%")
                                ->orWhere('Segundo_Nombre', 'like', "%{$busqueda}%")
                                ->orWhere('Primer_Apellido', 'like', "%{$busqueda}%")
                                ->orWhere('Segundo_Apellido', 'like', "%{$busqueda}%")
                                ->orWhere('Telefono', 'like', "%{$busqueda}%")
                                ->orWhere('Cedula', 'like', "%{$busqueda}%");
                        });
                });
            })
            ->limit($busqueda === '' ? 25 : 50)
            ->get();

        return $resultados
            ->merge($seleccionado)
            ->unique('Id_Trabajador')
            ->map(fn (Trabajador $trabajador) => $this->trabajadorOption($trabajador))
            ->values()
            ->all();
    }

    private function trabajadorOption(Trabajador $trabajador): array
    {
        $nombre = $this->nombreTrabajador($trabajador);
        $cargo = $this->cargoTrabajador($trabajador);

        return [
            'id' => $trabajador->Id_Trabajador,
            'name' => "#{$trabajador->Id_Trabajador} - {$nombre} - {$cargo}",
            'search' => strtolower("{$trabajador->Id_Trabajador} {$nombre} {$cargo}"),
        ];
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
            return Planilla::query()->find($this->planillaActualId);
        }

        return null;
    }

    public function previewPlanilla(): ?Planilla
    {
        if (! $this->previewPlanillaId) {
            return null;
        }

        return Planilla::query()->find($this->previewPlanillaId);
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

    public function detallePlanillaRows()
    {
        if (! $this->planillaActualId) {
            return collect();
        }

        return DetallePlanilla::query()
            ->with(['trabajador.persona', 'trabajador.cargo'])
            ->where('Id_Planilla', $this->planillaActualId)
            ->orderBy('Id_Detalle_Planilla')
            ->paginate($this->detallesPorPagina, ['*'], 'detallesPage');
    }

    public function comprobanteDetalleRows()
    {
        if (! $this->previewPlanillaId) {
            return collect();
        }

        return DetallePlanilla::query()
            ->with(['trabajador.persona', 'trabajador.cargo'])
            ->where('Id_Planilla', $this->previewPlanillaId)
            ->orderBy('Id_Detalle_Planilla')
            ->paginate($this->detallesPorPagina, ['*'], 'comprobantePage');
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
            default => $this->headersNormal($planilla),
        };
    }

    private function headersNormal(?Planilla $planilla): array
    {
        $headers = [
            ['key' => 'empleado', 'label' => 'Empleado'],
            ['key' => 'cargo', 'label' => 'Cargo'],
            ['key' => 'salario', 'label' => 'Salario quincenal'],
            ['key' => 'dias', 'label' => 'Días'],
            ['key' => 'incentivo', 'label' => 'Incentivo'],
            ['key' => 'deduccion', 'label' => 'Deducción'],
        ];

        if ($this->numeroSeguro($planilla?->Total_Aguinaldo ?? 0) > 0) {
            $headers[] = ['key' => 'aguinaldo', 'label' => 'Aguinaldo trim.'];
        }

        $headers[] = ['key' => 'total', 'label' => 'Total pagado'];
        $headers[] = ['key' => 'estado', 'label' => 'Estado'];

        return $headers;
    }

    public function headersTrabajadores(): array
    {
        return [
            ['key' => 'empleado', 'label' => 'Empleado'],
            ['key' => 'salario', 'label' => 'Salario mensual'],
            ['key' => 'vacaciones', 'label' => 'Vacaciones'],
        ];
    }

    public function headersComprobante(?Planilla $planilla): array
    {
        $headers = [
            ['key' => 'empleado', 'label' => 'Empleado'],
            ['key' => 'cargo', 'label' => 'Cargo'],
            ['key' => 'salario', 'label' => 'Salario'],
            ['key' => 'incentivo', 'label' => 'Incentivo'],
            ['key' => 'deduccion', 'label' => 'Deducción'],
        ];

        if ($this->numeroSeguro($planilla?->Total_Aguinaldo ?? 0) > 0) {
            $headers[] = ['key' => 'aguinaldo', 'label' => 'Aguinaldo'];
        }

        if ($this->numeroSeguro($planilla?->Total_Vacaciones ?? 0) > 0) {
            $headers[] = ['key' => 'monto_vacaciones', 'label' => 'Vacaciones'];
        }

        if ($this->numeroSeguro($planilla?->Total_Indemnizacion ?? 0) > 0) {
            $headers[] = ['key' => 'indemnizacion', 'label' => 'Indemnización'];
        }

        $headers[] = ['key' => 'total', 'label' => 'Total'];

        return $headers;
    }

    public function reporteAnualRows()
    {
        $year = Carbon::now()->year;

        return Planilla::query()
            ->whereYear('Fecha_Inicio_Corte', $year)
            ->where('Estado', '!=', Planilla::ESTADO_ANULADA)
            ->orderBy('Fecha_Inicio_Corte')
            ->paginate($this->reportePorPagina, ['*'], 'reportePage')
            ->through(fn (Planilla $planilla) => [
                'id' => $planilla->Id_Planilla,
                'periodo' => Carbon::parse($planilla->Fecha_Inicio_Corte)->format('d/m/Y') . ' - ' . Carbon::parse($planilla->Fecha_Fin_Corte)->format('d/m/Y'),
                'tipo' => $planilla->Tipo_Planilla,
                'estado' => $planilla->Estado,
                'bruto' => $this->money($planilla->Total_Bruto),
                'deducciones' => $this->money($planilla->Total_Deducciones),
                'aguinaldo' => $this->money($planilla->Total_Aguinaldo),
                'neto' => $this->money($planilla->Total_Neto),
            ]);
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
        $trabajador = $this->trabajadoresQuery()
            ->where('Id_Trabajador', $trabajadorId)
            ->first();

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


    private function esSegundoCorteTrimestral(Carbon $fechaCorte): bool
    {
        $inicio = Carbon::parse($this->fechaInicioCorte)->startOfDay();

        return $inicio->day === 16
            && in_array((int) $fechaCorte->format('n'), [3, 6, 9, 12], true);
    }

    private function aguinaldoTrimestral(Trabajador $trabajador, Carbon $fechaCorte): float
    {
        $salario = $this->numeroSeguro($trabajador->Salario);

        if ($salario <= 0) {
            return 0;
        }

        $inicioMes = ((int) floor(($fechaCorte->month - 1) / 3) * 3) + 1;
        $inicio = $fechaCorte->copy()->month($inicioMes)->startOfMonth()->startOfDay();

        if ($trabajador->Fecha_Ingreso) {
            $ingreso = Carbon::parse($trabajador->Fecha_Ingreso)->startOfDay();

            if ($ingreso->greaterThan($inicio)) {
                $inicio = $ingreso;
            }
        }

        if ($fechaCorte->lessThan($inicio)) {
            return 0;
        }

        $dias = min(90, max(0, $inicio->diffInDays($fechaCorte) + 1));

        return round(($salario / 360) * $dias, 2);
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
        return (bool) $this->planillaActualId;
    }

    public function puedeGenerarAguinaldo(): bool
    {
        return false;
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

            if ($valor && ! is_numeric($valor)) {
                return (string) $valor;
            }
        }

        foreach ($cargo->getAttributes() as $key => $value) {
            if (! $value || str_starts_with($key, 'Id_') || is_numeric($value)) {
                continue;
            }

            if (stripos($key, 'cargo') !== false || stripos($key, 'nombre') !== false || stripos($key, 'descripcion') !== false) {
                return (string) $value;
            }
        }

        return 'Cargo #' . ($trabajador->Id_Cargo ?? $cargo->getKey() ?? 'N/D');
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

    public function fechaFormato($fecha, string $formato = 'd/m/Y'): string
    {
        if (! $fecha) {
            return 'Sin fecha';
        }

        try {
            return Carbon::parse($fecha)->format($formato);
        } catch (\Throwable) {
            return 'Sin fecha';
        }
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
    $modalTrabajadorAbierto = $modalIncentivo || $modalDeduccion || $modalVacaciones || $modalLiquidacion;
    $trabajadorOptions = $modalTrabajadorAbierto ? $this->trabajadoresOptions() : [];
    $resumen = $this->resumen();
    $planillaActual = $this->planillaActual();
    $detalles = $planillaActual ? $this->detallePlanillaRows() : collect();
    $previewPlanilla = ($modalComprobante || $modalPdfComprobante) ? $this->previewPlanilla() : null;
    $comprobanteDetalles = $modalComprobante ? $this->comprobanteDetalleRows() : collect();
    $reporteRows = $modalReporteAnual ? $this->reporteAnualRows() : null;
    $reporteResumen = $modalReporteAnual ? $this->reporteAnualResumen() : [
    'cantidad' => 0,
    'bruto' => $this->money(0),
    'incentivos' => $this->money(0),
    'vacaciones' => $this->money(0),
    'aguinaldo' => $this->money(0),
    'indemnizacion' => $this->money(0),
    'deducciones' => $this->money(0),
    'neto' => $this->money(0),
    ];
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
        </div>

        <div class="flex flex-wrap gap-2">
            @if($planillaActual)
            <x-button label="Ver comprobante" icon="o-document-text" wire:click="abrirComprobanteActual"
                class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
            @else
            <x-button label="Generar planilla" icon="o-calculator" wire:click="solicitarGenerarPlanilla"
                class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
            @endif

            @if($this->puedeGenerarAguinaldo())
            <x-button label="Aguinaldo" icon="o-banknotes" wire:click="solicitarGenerarAguinaldo"
                class="border-0 bg-[#E67E22] text-white hover:opacity-90" spinner />
            @endif

            <x-button label="Reporte anual" icon="o-document-text" wire:click="abrirReporteAnual"
                class="border border-[#D7E4F3] bg-white text-[#111827] hover:bg-[#F0F3F7]" spinner />
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

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="min-w-0 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#5F6B7A]">Trabajadores activos</p>
            <p class="mt-2 break-words text-2xl font-bold leading-tight text-[#1A2B42] md:text-3xl">{{ $totalActivos }}</p>
        </div>

        <div class="min-w-0 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#5F6B7A]">Total pagado</p>
            <p class="mt-2 break-words text-2xl font-bold leading-tight text-[#1A2B42] md:text-3xl">{{ $resumen['neto'] }}</p>
        </div>

        <div class="min-w-0 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#5F6B7A]">Incentivos</p>
            <p class="mt-2 break-words text-2xl font-bold leading-tight text-[#1A2B42] md:text-3xl">{{ $resumen['incentivos'] }}</p>
        </div>

        <div class="min-w-0 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#5F6B7A]">Aguinaldo</p>
            <p class="mt-2 break-words text-2xl font-bold leading-tight text-[#1A2B42] md:text-3xl">{{ $resumen['aguinaldo'] }}</p>
        </div>

        <div class="min-w-0 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#5F6B7A]">Deducciones</p>
            <p class="mt-2 break-words text-2xl font-bold leading-tight text-[#1A2B42] md:text-3xl">{{ $resumen['deducciones'] }}</p>
        </div>

        <div class="min-w-0 overflow-hidden rounded-2xl border border-[#D7E4F3] bg-[#EAF2FB] p-4 shadow-sm">
            <p class="text-sm font-semibold text-[#5F6B7A]">{{ $resumen['codigo'] }}</p>
            <p class="mt-2 break-words text-xl font-bold leading-tight text-[#0E48A1] md:text-2xl">{{ $resumen['estado'] }}</p>
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
                    Trabajadores activos disponibles para el periodo.
                    @endif
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                <div class="flex flex-wrap gap-2">
                    <x-button label="Incentivo" icon="o-plus" wire:click="abrirIncentivo"
                        class="{{ $this->claseAccionPrimaria() }}" spinner />
                    <x-button label="Deducción" icon="o-minus" wire:click="abrirDeduccion"
                        class="{{ $this->claseAccionPrimaria() }}" spinner />
                    <x-button label="Vacaciones" icon="o-sun" wire:click="abrirVacaciones"
                        class="{{ $this->claseAccionSecundaria() }}" spinner />
                    <x-button label="Liquidar" icon="o-user-minus" wire:click="abrirLiquidacion"
                        class="{{ $this->claseLiquidar() }}" spinner />
                </div>
            </div>
        </div>

        @if(! $planillaActual && $this->aguinaldoTrimestralDisponible())
        <div class="mb-4 rounded-2xl border border-[#D7E4F3] bg-[#EAF2FB] p-4 text-sm text-[#1A2B42]">
            Incluye aguinaldo trimestral proporcional: <strong>{{ $this->trimestreTexto() }}</strong>.
        </div>
        @endif

        <div class="overflow-x-auto">
            @if($planillaActual)
            <x-table :headers="$this->headersDetalle()" :rows="$detalles" with-pagination
                class="min-w-[900px] [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl [&_tbody_td]:py-3 [&_tbody_tr:hover]:bg-[#F7F9FC]">
                @scope('cell_empleado', $detalle)
                @php($trabajador = $detalle->trabajador)
                <span class="font-semibold text-[#111827]">
                    {{ $trabajador ? $this->nombreTrabajador($trabajador) : 'Sin trabajador' }}
                </span>
                @endscope

                @scope('cell_cargo', $detalle)
                @php($trabajador = $detalle->trabajador)
                <span class="inline-flex rounded-full bg-[#D7E4F3] px-3 py-1 text-xs font-semibold text-[#111827]">
                    {{ $trabajador ? $this->cargoTrabajador($trabajador) : 'Sin cargo' }}
                </span>
                @endscope

                @scope('cell_salario', $detalle)
                <span class="font-semibold text-[#111827]">{{ $this->money($detalle->Salario_Base) }}</span>
                @endscope

                @scope('cell_dias', $detalle)
                <span class="text-[#111827]">{{ $this->numero($detalle->Dias_Trabajados) }}</span>
                @endscope

                @scope('cell_vacaciones', $detalle)
                <span class="text-[#111827]">{{ $this->numero($detalle->Dias_Vacaciones) }}</span>
                @endscope

                @scope('cell_monto_vacaciones', $detalle)
                <span class="text-[#111827]">{{ $this->money($detalle->Monto_Vacaciones) }}</span>
                @endscope

                @scope('cell_incentivo', $detalle)
                <span class="text-[#111827]">{{ $this->money($detalle->Monto_Incentivo) }}</span>
                @endscope

                @scope('cell_aguinaldo', $detalle)
                <span class="text-[#111827]">{{ $this->money($detalle->Monto_Aguinaldo) }}</span>
                @endscope

                @scope('cell_indemnizacion', $detalle)
                <span class="text-[#111827]">{{ $this->money($detalle->Monto_Indemnizacion ?? 0) }}</span>
                @endscope

                @scope('cell_deduccion', $detalle)
                <span class="text-[#111827]">{{ $this->money($detalle->Monto_Deduccion) }}</span>
                @endscope

                @scope('cell_total', $detalle)
                <span class="font-bold text-[#0E48A1]">{{ $this->money($detalle->Total_Neto) }}</span>
                @endscope

                @scope('cell_estado', $detalle)
                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                    {{ $detalle->Estado_Pago }}
                </span>
                @endscope
            </x-table>
            @else
            <x-table :headers="$this->headersTrabajadores()" :rows="$trabajadores" with-pagination
                class="min-w-[720px] [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_thead_th:first-child]:rounded-l-xl [&_thead_th:last-child]:rounded-r-xl [&_tbody_td]:py-3 [&_tbody_tr:hover]:bg-[#F7F9FC]">
                @scope('cell_empleado', $trabajador)
                <div class="max-w-[360px]">
                    <p class="font-semibold text-[#111827]">{{ $this->nombreTrabajador($trabajador) }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-[#5F6B7A]">
                        <span class="rounded-full bg-[#D7E4F3] px-2 py-0.5 font-semibold text-[#111827]">
                            {{ $this->cargoTrabajador($trabajador) }}
                        </span>
                        <span>Ingreso: {{ $this->fechaFormato($trabajador->Fecha_Ingreso) }}</span>
                    </div>
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
                <span class="font-semibold text-[#0E48A1]">{{ $this->saldoVacaciones($trabajador->Id_Trabajador) }}
                    días</span>
                @endscope

                @scope('actions', $trabajador)
                <div class="flex items-center justify-end gap-1 whitespace-nowrap">
                    <x-button icon="o-plus" title="Incentivo" wire:click="abrirIncentivo({{ $trabajador->Id_Trabajador }})"
                        class="btn-sm h-9 min-h-9 w-9 rounded-xl p-0 {{ $this->claseAccionSecundaria() }}" spinner />
                    <x-button icon="o-minus" title="Deducción" wire:click="abrirDeduccion({{ $trabajador->Id_Trabajador }})"
                        class="btn-sm h-9 min-h-9 w-9 rounded-xl p-0 {{ $this->claseAccionSecundaria() }}" spinner />
                    <x-button icon="o-sun" title="Vacaciones" wire:click="abrirVacaciones({{ $trabajador->Id_Trabajador }})"
                        class="btn-sm h-9 min-h-9 w-9 rounded-xl p-0 {{ $this->claseAccionSecundaria() }}" spinner />
                    <x-button icon="o-user-minus" title="Liquidar" wire:click="abrirLiquidacion({{ $trabajador->Id_Trabajador }})"
                        class="btn-sm h-9 min-h-9 w-9 rounded-xl p-0 {{ $this->claseLiquidar() }}" spinner />
                </div>
                @endscope
            </x-table>
            @endif
        </div>
    </x-card>

    <x-modal wire:model="modalConfirmarPlanilla" title="Confirmar generación de planilla" separator
        box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="space-y-4 text-[#111827]">
            @if($this->aguinaldoTrimestralDisponible())
            <x-alert icon="o-banknotes" class="alert-info">
                <span>Este periodo incluye aguinaldo trimestral proporcional.</span>
            </x-alert>
            @endif

            <x-alert icon="o-exclamation-triangle" class="alert-warning">
                <span>Al confirmar, la planilla será generada como <strong>PAGADA</strong>.</span>
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
            <x-button label="Confirmar y generar" icon="o-check" wire:click="confirmarGeneracionPlanilla"
                class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalComprobante" title="Comprobante de planilla" separator
        box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl max-w-6xl">
        @if($previewPlanilla)
        <div class="space-y-5 text-[#111827]">
            <div class="flex flex-col gap-2 border-b border-[#D7E4F3] pb-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-[#1A2B42]">Comprobante de planilla</h2>
                    <p class="text-sm text-[#5F6B7A]">
                        Planilla #{{ $previewPlanilla->Id_Planilla }} · {{ $previewPlanilla->Tipo_Planilla }} · {{
                        $previewPlanilla->Estado }}
                    </p>
                </div>

                <div class="text-sm text-[#111827]">
                    <p><strong>Generada:</strong> {{ $this->fechaFormato($previewPlanilla->Fecha_Generacion, 'd/m/Y
                        H:i') }}</p>
                    <p><strong>Periodo:</strong> {{ $this->fechaFormato($previewPlanilla->Fecha_Inicio_Corte) }} - {{
                        $this->fechaFormato($previewPlanilla->Fecha_Fin_Corte) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="rounded-xl bg-[#F0F3F7] p-4">
                    <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Bruto</p>
                    <p class="text-lg font-bold text-[#1A2B42]">{{ $this->money($previewPlanilla->Total_Bruto) }}</p>
                </div>
                <div class="rounded-xl bg-[#F0F3F7] p-4">
                    <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Incentivos</p>
                    <p class="text-lg font-bold text-[#1A2B42]">{{ $this->money($previewPlanilla->Total_Incentivos) }}
                    </p>
                </div>
                <div class="rounded-xl bg-[#F0F3F7] p-4">
                    <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Deducciones</p>
                    <p class="text-lg font-bold text-[#1A2B42]">{{ $this->money($previewPlanilla->Total_Deducciones) }}
                    </p>
                </div>
                <div class="rounded-xl bg-[#EAF2FB] p-4">
                    <p class="text-xs font-semibold uppercase text-[#5F6B7A]">Total pagado</p>
                    <p class="text-lg font-bold text-[#0E48A1]">{{ $this->money($previewPlanilla->Total_Neto) }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <x-table :headers="$this->headersComprobante($previewPlanilla)" :rows="$comprobanteDetalles" with-pagination
                    class="min-w-[850px] [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_tbody_td]:py-3 [&_tbody_tr:hover]:bg-[#F7F9FC]">
                    @scope('cell_empleado', $detalle)
                    @php($trabajador = $detalle->trabajador)
                    <span class="font-semibold text-[#111827]">{{ $trabajador ? $this->nombreTrabajador($trabajador) :
                        'Sin trabajador' }}</span>
                    @endscope

                    @scope('cell_cargo', $detalle)
                    @php($trabajador = $detalle->trabajador)
                    <span class="text-[#111827]">{{ $trabajador ? $this->cargoTrabajador($trabajador) : 'Sin cargo'
                        }}</span>
                    @endscope

                    @scope('cell_salario', $detalle)
                    <span class="text-[#111827]">{{ $this->money($detalle->Salario_Base) }}</span>
                    @endscope

                    @scope('cell_incentivo', $detalle)
                    <span class="text-[#111827]">{{ $this->money($detalle->Monto_Incentivo) }}</span>
                    @endscope

                    @scope('cell_deduccion', $detalle)
                    <span class="text-[#111827]">{{ $this->money($detalle->Monto_Deduccion) }}</span>
                    @endscope

                    @scope('cell_aguinaldo', $detalle)
                    <span class="text-[#111827]">{{ $this->money($detalle->Monto_Aguinaldo) }}</span>
                    @endscope

                    @scope('cell_monto_vacaciones', $detalle)
                    <span class="text-[#111827]">{{ $this->money($detalle->Monto_Vacaciones) }}</span>
                    @endscope

                    @scope('cell_indemnizacion', $detalle)
                    <span class="text-[#111827]">{{ $this->money($detalle->Monto_Indemnizacion ?? 0) }}</span>
                    @endscope

                    @scope('cell_total', $detalle)
                    <span class="font-bold text-[#0E48A1]">{{ $this->money($detalle->Total_Neto) }}</span>
                    @endscope
                </x-table>
            </div>
        </div>
        @endif

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalComprobante', false)" />
            @if($previewPlanilla)
            <x-button label="Ver PDF" icon="o-document-text" wire:click="abrirPdfComprobante"
                class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
            <x-button label="Excel" icon="o-table-cells" link="{{ $this->urlExportarComprobante('excel') }}" external
                class="border border-[#D7E4F3] bg-white text-[#111827] hover:bg-[#F0F3F7]" />
            <x-button label="Word" icon="o-document" link="{{ $this->urlExportarComprobante('word') }}" external
                class="border border-[#D7E4F3] bg-white text-[#111827] hover:bg-[#F0F3F7]" />
            @endif
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalPdfComprobante" title="Vista previa del comprobante PDF" separator
        box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl max-w-7xl">
        @if($modalPdfComprobante && $previewPlanilla)
        <div class="space-y-4 text-[#111827]">
            <div
                class="flex flex-col gap-3 rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-[#1A2B42]">
                        Comprobante de planilla #{{ $previewPlanilla->Id_Planilla }}
                    </h3>
                    <p class="text-sm text-[#5F6B7A]">
                        {{ $previewPlanilla->Tipo_Planilla }} · {{ $previewPlanilla->Estado }}
                    </p>
                </div>

                <x-button label="Descargar PDF" icon="o-arrow-down-tray" link="{{ $this->urlExportarComprobante('pdf') }}"
                    external class="border border-[#D7E4F3] bg-white text-[#111827] hover:bg-[#F0F3F7]" />
            </div>

            <div class="overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white">
                <iframe src="{{ $this->urlPdfComprobante() }}" class="h-[75vh] w-full"
                    title="Vista previa del comprobante PDF"></iframe>
            </div>
        </div>
        @endif

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalPdfComprobante', false)" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalIncentivo" title="Registrar incentivo" separator
        box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-[#111827]">
            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Trabajador</p>
                <x-input wire:model.live.debounce.300ms="busquedaTrabajadorModal"
                    placeholder="Filtrar por código, nombre o cargo" class="mb-2 bg-[#F0F3F7] text-[#111827]" />
                <x-select :options="$trabajadorOptions" option-value="id" option-label="name"
                    wire:model="formTrabajadorId" placeholder="Seleccione un trabajador"
                    class="bg-[#F0F3F7] text-[#111827]" />
                @error('formTrabajadorId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Fecha</p>
                <x-input type="date" wire:model="fechaIncentivo" class="bg-[#F0F3F7] text-[#111827]" />
                @error('fechaIncentivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Monto</p>
                <x-input type="number" step="0.01" min="0" wire:model="montoIncentivo" prefix="C$"
                    class="bg-[#F0F3F7] text-[#111827]" />
                @error('montoIncentivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Concepto</p>
                <x-input wire:model="conceptoIncentivo" placeholder="Bono, comisión u horas extra"
                    class="bg-[#F0F3F7] text-[#111827]" />
                @error('conceptoIncentivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Observación</p>
                <x-textarea wire:model="observacionIncentivo" rows="3" placeholder="Opcional"
                    class="bg-[#F0F3F7] text-[#111827]" />
                @error('observacionIncentivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalIncentivo', false)" />
            <x-button label="Guardar" icon="o-check" wire:click="registrarIncentivo"
                class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalDeduccion" title="Registrar deducción" separator
        box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-[#111827]">
            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Trabajador</p>
                <x-input wire:model.live.debounce.300ms="busquedaTrabajadorModal"
                    placeholder="Filtrar por código, nombre o cargo" class="mb-2 bg-[#F0F3F7] text-[#111827]" />
                <x-select :options="$trabajadorOptions" option-value="id" option-label="name"
                    wire:model="formTrabajadorId" placeholder="Seleccione un trabajador"
                    class="bg-[#F0F3F7] text-[#111827]" />
                @error('formTrabajadorId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Fecha</p>
                <x-input type="date" wire:model="fechaDeduccion" class="bg-[#F0F3F7] text-[#111827]" />
                @error('fechaDeduccion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Monto</p>
                <x-input type="number" step="0.01" min="0" wire:model="montoDeduccion" prefix="C$"
                    class="bg-[#F0F3F7] text-[#111827]" />
                @error('montoDeduccion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Concepto</p>
                <x-input wire:model="conceptoDeduccion" placeholder="Préstamo, ausencia o ajuste"
                    class="bg-[#F0F3F7] text-[#111827]" />
                @error('conceptoDeduccion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Observación</p>
                <x-textarea wire:model="observacionDeduccion" rows="3" placeholder="Opcional"
                    class="bg-[#F0F3F7] text-[#111827]" />
                @error('observacionDeduccion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalDeduccion', false)" />
            <x-button label="Guardar" icon="o-check" wire:click="registrarDeduccion"
                class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalVacaciones" title="Registrar vacaciones" separator
        box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-[#111827]">
            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Trabajador</p>
                <x-input wire:model.live.debounce.300ms="busquedaTrabajadorModal"
                    placeholder="Filtrar por código, nombre o cargo" class="mb-2 bg-[#F0F3F7] text-[#111827]" />
                <x-select :options="$trabajadorOptions" option-value="id" option-label="name"
                    wire:model="formTrabajadorId" placeholder="Seleccione un trabajador"
                    class="bg-[#F0F3F7] text-[#111827]" />
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
                <x-select :options="$estadoVacacionOptions" option-value="id" option-label="name"
                    wire:model="vacacionEstado" class="bg-[#F0F3F7] text-[#111827]" />
                @error('vacacionEstado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Observación</p>
                <x-textarea wire:model="vacacionObservacion" rows="3" placeholder="Opcional"
                    class="bg-[#F0F3F7] text-[#111827]" />
                @error('vacacionObservacion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalVacaciones', false)" />
            <x-button label="Guardar" icon="o-check" wire:click="registrarVacaciones"
                class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalLiquidacion" title="Liquidar trabajador" separator
        box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 text-[#111827]">
            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Trabajador</p>
                <x-input wire:model.live.debounce.300ms="busquedaTrabajadorModal"
                    placeholder="Filtrar por código, nombre o cargo" class="mb-2 bg-[#F0F3F7] text-[#111827]" />
                <x-select :options="$trabajadorOptions" option-value="id" option-label="name"
                    wire:model="formTrabajadorId" placeholder="Seleccione un trabajador"
                    class="bg-[#F0F3F7] text-[#111827]" />
                @error('formTrabajadorId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Fecha de salida</p>
                <x-input type="date" wire:model="liquidacionFechaSalida" class="bg-[#F0F3F7] text-[#111827]" />
                @error('liquidacionFechaSalida') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Motivo</p>
                <x-select :options="$motivoLiquidacionOptions" option-value="id" option-label="name"
                    wire:model.live="liquidacionMotivo" class="bg-[#F0F3F7] text-[#111827]" />
                @error('liquidacionMotivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label
                class="md:col-span-2 flex items-center gap-3 rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-4 text-sm font-semibold text-[#111827]">
                <input type="checkbox" wire:model.live="liquidacionIncluirIndemnizacion"
                    class="checkbox checkbox-sm border-[#2E8BC0]" @disabled($liquidacionMotivo==='DESPIDO_JUSTIFICADO'
                    )>
                Incluir indemnización por antigüedad
            </label>

            <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-[#1A2B42]">Observación</p>
                <x-textarea wire:model="liquidacionObservacion" rows="3" placeholder="Opcional"
                    class="bg-[#F0F3F7] text-[#111827]" />
                @error('liquidacionObservacion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancelar" wire:click="$set('modalLiquidacion', false)" />
            <x-button label="Generar liquidación" icon="o-check" wire:click="liquidarTrabajador"
                class="border-0 bg-[#E67E22] text-white hover:opacity-90" spinner />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalReporteAnual" title="Reporte anual de planillas" separator
        box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl max-w-6xl">
        @if($modalReporteAnual && $reporteRows)
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

            <x-table :headers="[
                    ['key' => 'id', 'label' => 'Planilla'],
                    ['key' => 'periodo', 'label' => 'Periodo'],
                    ['key' => 'tipo', 'label' => 'Tipo'],
                    ['key' => 'estado', 'label' => 'Estado'],
                    ['key' => 'bruto', 'label' => 'Bruto'],
                    ['key' => 'deducciones', 'label' => 'Deducciones'],
                    ['key' => 'aguinaldo', 'label' => 'Aguinaldo'],
                    ['key' => 'neto', 'label' => 'Neto'],
                ]" :rows="$reporteRows" with-pagination
                class="min-w-[850px] [&_thead_th]:bg-[#2E8BC0] [&_thead_th]:text-white [&_thead_th]:font-semibold [&_tbody_td]:py-3 [&_tbody_tr:hover]:bg-[#F7F9FC]">
                @scope('cell_id', $row)
                <span class="font-semibold text-[#111827]">#{{ $row['id'] }}</span>
                @endscope

                @scope('cell_neto', $row)
                <span class="font-bold text-[#0E48A1]">{{ $row['neto'] }}</span>
                @endscope
            </x-table>
        </div>
        @endif

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalReporteAnual', false)" />
            <x-button label="Ver PDF" icon="o-document-text" wire:click="abrirPdfReporteAnual"
                class="border-0 bg-[#2E8BC0] text-white hover:bg-[#0B6FE4]" spinner />
            <x-button label="Excel" icon="o-table-cells" link="{{ $this->urlExportarReporteAnual('excel') }}" external
                class="border border-[#D7E4F3] bg-white text-[#111827] hover:bg-[#F0F3F7]" />
            <x-button label="Word" icon="o-document" link="{{ $this->urlExportarReporteAnual('word') }}" external
                class="border border-[#D7E4F3] bg-white text-[#111827] hover:bg-[#F0F3F7]" />
        </x-slot:actions>
    </x-modal>

    <x-modal wire:model="modalPdfReporteAnual" title="Vista previa del reporte anual PDF" separator
        box-class="bg-white text-[#111827] border border-[#D7E4F3] rounded-2xl shadow-xl max-w-7xl">
        @if($modalPdfReporteAnual)
        <div class="space-y-4 text-[#111827]">
            <div
                class="flex flex-col gap-3 rounded-2xl border border-[#D7E4F3] bg-[#F0F3F7] p-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-[#1A2B42]">
                        Reporte anual de planillas
                    </h3>
                    <p class="text-sm text-[#5F6B7A]">
                        Año {{ Carbon::now()->year }}
                    </p>
                </div>

                <x-button label="Descargar PDF" icon="o-arrow-down-tray" link="{{ $this->urlExportarReporteAnual('pdf') }}"
                    external class="border border-[#D7E4F3] bg-white text-[#111827] hover:bg-[#F0F3F7]" />
            </div>

            <div class="overflow-hidden rounded-2xl border border-[#D7E4F3] bg-white">
                <iframe src="{{ $this->urlPdfReporteAnual() }}" class="h-[75vh] w-full"
                    title="Vista previa del reporte anual PDF"></iframe>
            </div>
        </div>
        @endif

        <x-slot:actions>
            <x-button label="Cerrar" wire:click="$set('modalPdfReporteAnual', false)" />
        </x-slot:actions>
    </x-modal>
</div>
