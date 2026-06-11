<?php

namespace App\Services\PlanillaPago;

use App\Models\Planilla;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanillaAnualReporteService extends BaseReporteService
{
    public function __construct(private readonly int $year)
    {
    }

    public function titulo(): string
    {
        return 'Reporte anual de planillas ' . $this->year;
    }

    public function nombreArchivo(): string
    {
        return 'reporte-anual-planillas-' . $this->year . '-' . now()->format('Ymd-His');
    }

    public function consultar(): Collection
    {
        return Planilla::query()
            ->whereYear('Fecha_Inicio_Corte', $this->year)
            ->where('Estado', '!=', Planilla::ESTADO_ANULADA)
            ->orderBy('Fecha_Inicio_Corte')
            ->orderBy('Id_Planilla')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        return [
            'Planillas' => number_format($datos->count()),
            'Bruto' => 'C$ ' . number_format((float) $datos->sum('Total_Bruto'), 2),
            'Aguinaldo' => 'C$ ' . number_format((float) $datos->sum('Total_Aguinaldo'), 2),
            'Deducciones' => 'C$ ' . number_format((float) $datos->sum('Total_Deducciones'), 2),
            'Neto' => 'C$ ' . number_format((float) $datos->sum('Total_Neto'), 2),
        ];
    }

    public function columnas(): array
    {
        return [
            ['key' => 'planilla', 'label' => 'Planilla', 'pdf' => 18, 'word' => 850, 'tipo' => 'text'],
            ['key' => 'periodo', 'label' => 'Periodo', 'pdf' => 42, 'word' => 1900, 'tipo' => 'text'],
            ['key' => 'tipo', 'label' => 'Tipo', 'pdf' => 24, 'word' => 1200, 'tipo' => 'text'],
            ['key' => 'estado', 'label' => 'Estado', 'pdf' => 22, 'word' => 1050, 'tipo' => 'badge'],
            ['key' => 'bruto', 'label' => 'Bruto', 'pdf' => 23, 'word' => 1100, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'incentivos', 'label' => 'Incent.', 'pdf' => 22, 'word' => 1050, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'vacaciones', 'label' => 'Vacac.', 'pdf' => 22, 'word' => 1050, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'aguinaldo', 'label' => 'Aguin.', 'pdf' => 22, 'word' => 1050, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'indemnizacion', 'label' => 'Indem.', 'pdf' => 22, 'word' => 1050, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'deducciones', 'label' => 'Deducc.', 'pdf' => 22, 'word' => 1050, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'neto', 'label' => 'Neto', 'pdf' => 24, 'word' => 1150, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
        ];
    }

    public function mapFila(mixed $fila): array
    {
        return [
            'planilla' => '#' . $fila->Id_Planilla,
            'periodo' => $this->periodo($fila),
            'tipo' => (string) $fila->Tipo_Planilla,
            'estado' => (string) $fila->Estado,
            'bruto' => (float) $fila->Total_Bruto,
            'incentivos' => (float) $fila->Total_Incentivos,
            'vacaciones' => (float) $fila->Total_Vacaciones,
            'aguinaldo' => (float) $fila->Total_Aguinaldo,
            'indemnizacion' => (float) ($fila->Total_Indemnizacion ?? 0),
            'deducciones' => (float) $fila->Total_Deducciones,
            'neto' => (float) $fila->Total_Neto,
        ];
    }

    public function filas(Collection $datos): Collection
    {
        $filas = parent::filas($datos);

        if ($datos->isEmpty()) {
            return $filas;
        }

        return $filas->concat([
            [
                'planilla' => '',
                'periodo' => 'TOTAL GENERAL',
                'tipo' => '',
                'estado' => 'Completado',
                'bruto' => (float) $datos->sum('Total_Bruto'),
                'incentivos' => (float) $datos->sum('Total_Incentivos'),
                'vacaciones' => (float) $datos->sum('Total_Vacaciones'),
                'aguinaldo' => (float) $datos->sum('Total_Aguinaldo'),
                'indemnizacion' => (float) $datos->sum('Total_Indemnizacion'),
                'deducciones' => (float) $datos->sum('Total_Deducciones'),
                'neto' => (float) $datos->sum('Total_Neto'),
            ],
        ]);
    }

    public function colorEstado(string $estado): array
    {
        return match (mb_strtolower($estado)) {
            'pagada', 'pagado', 'completado' => [
                'texto' => '166534',
                'fondo' => 'DCFCE7',
            ],
            'anulada', 'pendiente' => [
                'texto' => 'B91C1C',
                'fondo' => 'FEE2E2',
            ],
            default => parent::colorEstado($estado),
        };
    }

    private function periodo(Planilla $planilla): string
    {
        $desde = $planilla->Fecha_Inicio_Corte
            ? Carbon::parse($planilla->Fecha_Inicio_Corte)->format('d/m/Y')
            : '';

        $hasta = $planilla->Fecha_Fin_Corte
            ? Carbon::parse($planilla->Fecha_Fin_Corte)->format('d/m/Y')
            : '';

        return trim($desde . ' - ' . $hasta, ' -');
    }
}
