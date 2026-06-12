<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReportePlanillasPago;
use App\Models\Reportes\VwReportePlanillasPagoDetalle;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlanillaPagoReporteService extends BaseReporteService
{
    private string $modo;

    private string $vista;

    private string $desde;

    private string $hasta;

    public function __construct(private readonly Request $request)
    {
        $modo = trim((string) $this->request->query('modo', 'ultima'));
        $vista = trim((string) $this->request->query('vista', 'general'));

        $this->modo = in_array($modo, ['ultima', 'rango'], true) ? $modo : 'ultima';
        $this->vista = in_array($vista, ['general', 'detalle'], true) ? $vista : 'general';

        $quincena = $this->quincenaActual();
        $this->desde = (string) $this->request->query('desde', $quincena['desde']);
        $this->hasta = (string) $this->request->query('hasta', $quincena['hasta']);
    }

    public function titulo(): string
    {
        $titulo = $this->vista === 'detalle'
            ? 'Detalle de planilla de pago'
            : 'Planilla de pago - resumen general';

        if ($this->modo === 'ultima') {
            return $titulo . ' (última generada)';
        }

        return $titulo . ' (' . $this->desde . ' al ' . $this->hasta . ')';
    }

    public function nombreArchivo(): string
    {
        $periodo = $this->modo === 'ultima'
            ? 'ultima'
            : $this->desde . '-' . $this->hasta;

        return 'reporte-planilla-pago-' . $this->vista . '-' . $periodo;
    }

    public function consultar(): Collection
    {
        $query = $this->vista === 'detalle'
            ? VwReportePlanillasPagoDetalle::query()
            : VwReportePlanillasPago::query();

        if ($this->modo === 'ultima') {
            $ultimaPlanillaId = DB::table('planilla')
                ->orderByRaw('COALESCE(Fecha_Generacion, Fecha_Fin_Corte, Fecha_Inicio_Corte) DESC')
                ->orderByDesc('Id_Planilla')
                ->value('Id_Planilla');

            if (! $ultimaPlanillaId) {
                return collect();
            }

            $query->where('Id_Planilla', (int) $ultimaPlanillaId);
        } else {
            $query
                ->whereDate('Fecha_Inicio_Corte', '=', $this->desde)
                ->whereDate('Fecha_Fin_Corte', '=', $this->hasta);
        }

        if ($this->vista === 'detalle') {
            return $query
                ->orderBy('Fecha_Inicio_Corte')
                ->orderBy('Trabajador')
                ->get();
        }

        return $query
            ->orderByDesc('Fecha_Generacion')
            ->orderByDesc('Id_Planilla')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        if ($this->vista === 'detalle') {
            return [
                'Planillas' => number_format($datos->pluck('Id_Planilla')->unique()->count()),
                'Trabajadores' => number_format($datos->count()),
                'Pagados' => number_format($datos->where('Estado_Pago', 'PAGADO')->count()),
                'Pendientes' => number_format($datos->where('Estado_Pago', 'PENDIENTE')->count()),
                'Total bruto' => 'C$ ' . number_format((float) $datos->sum('Total_Bruto'), 2),
                'Total neto' => 'C$ ' . number_format((float) $datos->sum('Total_Neto'), 2),
            ];
        }

        return [
            'Planillas' => number_format($datos->count()),
            'Trabajadores' => number_format((float) $datos->sum('Total_Trabajadores')),
            'Pagados' => number_format((float) $datos->sum('Trabajadores_Pagados')),
            'Pendientes' => number_format((float) $datos->sum('Trabajadores_Pendientes')),
            'Total bruto' => 'C$ ' . number_format((float) $datos->sum('Total_Bruto'), 2),
            'Total neto' => 'C$ ' . number_format((float) $datos->sum('Total_Neto'), 2),
        ];
    }

    public function columnas(): array
    {
        return $this->vista === 'detalle'
            ? $this->columnasDetalle()
            : $this->columnasGeneral();
    }

    public function mapFila(mixed $fila): array
    {
        return $this->vista === 'detalle'
            ? $this->mapFilaDetalle($fila)
            : $this->mapFilaGeneral($fila);
    }

    private function columnasGeneral(): array
    {
        return [
            ['key' => 'corte', 'label' => 'Corte', 'pdf' => 34, 'word' => 1800, 'tipo' => 'text', 'limit' => 22],
            ['key' => 'generacion', 'label' => 'Generada', 'pdf' => 25, 'word' => 1300, 'tipo' => 'date'],
            ['key' => 'tipo', 'label' => 'Tipo', 'pdf' => 22, 'word' => 1100, 'tipo' => 'badge'],
            ['key' => 'estado', 'label' => 'Estado', 'pdf' => 24, 'word' => 1200, 'tipo' => 'badge'],
            ['key' => 'trabajadores', 'label' => 'Trab.', 'pdf' => 16, 'word' => 850, 'tipo' => 'number', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'pagados', 'label' => 'Pag.', 'pdf' => 16, 'word' => 850, 'tipo' => 'number', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'pendientes', 'label' => 'Pend.', 'pdf' => 18, 'word' => 900, 'tipo' => 'number', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'total_bruto', 'label' => 'Bruto', 'pdf' => 25, 'word' => 1250, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'deducciones', 'label' => 'Deducc.', 'pdf' => 25, 'word' => 1250, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'total_neto', 'label' => 'Neto', 'pdf' => 25, 'word' => 1250, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'observacion', 'label' => 'Observación', 'pdf' => 35, 'word' => 1900, 'tipo' => 'text', 'limit' => 28],
        ];
    }

    private function columnasDetalle(): array
    {
        return [
            ['key' => 'corte', 'label' => 'Corte', 'pdf' => 29, 'word' => 1500, 'tipo' => 'text', 'limit' => 18],
            ['key' => 'trabajador', 'label' => 'Trabajador', 'pdf' => 38, 'word' => 2200, 'tipo' => 'text', 'limit' => 30],
            ['key' => 'cargo', 'label' => 'Cargo', 'pdf' => 28, 'word' => 1500, 'tipo' => 'text', 'limit' => 20],
            ['key' => 'salario_base', 'label' => 'Salario', 'pdf' => 24, 'word' => 1200, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'dias_trabajados', 'label' => 'Días', 'pdf' => 17, 'word' => 850, 'tipo' => 'number', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'vacaciones', 'label' => 'Vacac.', 'pdf' => 22, 'word' => 1100, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'aguinaldo', 'label' => 'Aguin.', 'pdf' => 22, 'word' => 1100, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'deduccion', 'label' => 'Deducc.', 'pdf' => 23, 'word' => 1150, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'bruto', 'label' => 'Bruto', 'pdf' => 23, 'word' => 1150, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'neto', 'label' => 'Neto', 'pdf' => 23, 'word' => 1150, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'estado_pago', 'label' => 'Pago', 'pdf' => 24, 'word' => 1200, 'tipo' => 'badge'],
        ];
    }

    private function mapFilaGeneral(mixed $fila): array
    {
        return [
            'corte' => $this->formatearCorte($fila->Fecha_Inicio_Corte, $fila->Fecha_Fin_Corte),
            'generacion' => $fila->Fecha_Generacion ? Carbon::parse($fila->Fecha_Generacion)->format('Y-m-d') : '',
            'tipo' => (string) $fila->Tipo_Planilla,
            'estado' => (string) $fila->Estado_Planilla,
            'trabajadores' => (int) $fila->Total_Trabajadores,
            'pagados' => (int) $fila->Trabajadores_Pagados,
            'pendientes' => (int) $fila->Trabajadores_Pendientes,
            'total_bruto' => (float) $fila->Total_Bruto,
            'deducciones' => (float) $fila->Total_Deducciones,
            'total_neto' => (float) $fila->Total_Neto,
            'observacion' => (string) $fila->Observacion,
        ];
    }

    private function mapFilaDetalle(mixed $fila): array
    {
        return [
            'corte' => $this->formatearCorte($fila->Fecha_Inicio_Corte, $fila->Fecha_Fin_Corte),
            'trabajador' => (string) $fila->Trabajador,
            'cargo' => (string) $fila->Cargo,
            'salario_base' => (float) $fila->Salario_Base,
            'dias_trabajados' => (float) $fila->Dias_Trabajados,
            'vacaciones' => (float) $fila->Monto_Vacaciones,
            'aguinaldo' => (float) $fila->Monto_Aguinaldo,
            'deduccion' => (float) $fila->Monto_Deduccion,
            'bruto' => (float) $fila->Total_Bruto,
            'neto' => (float) $fila->Total_Neto,
            'estado_pago' => (string) $fila->Estado_Pago,
        ];
    }

    private function formatearCorte(mixed $inicio, mixed $fin): string
    {
        if (! $inicio || ! $fin) {
            return '';
        }

        return Carbon::parse($inicio)->format('d/m/Y') . ' - ' . Carbon::parse($fin)->format('d/m/Y');
    }

    private function quincenaActual(): array
    {
        $hoy = now();

        if ((int) $hoy->day <= 15) {
            return [
                'desde' => $hoy->copy()->startOfMonth()->toDateString(),
                'hasta' => $hoy->copy()->startOfMonth()->day(15)->toDateString(),
            ];
        }

        return [
            'desde' => $hoy->copy()->startOfMonth()->day(16)->toDateString(),
            'hasta' => $hoy->copy()->endOfMonth()->toDateString(),
        ];
    }
}
