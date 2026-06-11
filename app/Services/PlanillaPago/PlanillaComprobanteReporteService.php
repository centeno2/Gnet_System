<?php

namespace App\Services\PlanillaPago;

use App\Models\DetallePlanilla;
use App\Models\Planilla;
use App\Models\Trabajador;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlanillaComprobanteReporteService extends BaseReporteService
{
    private ?Planilla $planillaCache = null;

    public function __construct(private readonly int $planillaId)
    {
    }

    public function titulo(): string
    {
        $planilla = $this->planilla();
        $tipo = $planilla?->Tipo_Planilla ?: 'PLANILLA';

        return 'Comprobante de planilla ' . $tipo;
    }

    public function nombreArchivo(): string
    {
        $planilla = $this->planilla();
        $tipo = str((string) ($planilla?->Tipo_Planilla ?: 'planilla'))
            ->lower()
            ->replace([' ', '/', '\\'], '-')
            ->toString();

        return 'comprobante-planilla-' . $tipo . '-' . $this->planillaId . '-' . now()->format('Ymd-His');
    }

    public function consultar(): Collection
    {
        return DetallePlanilla::query()
            ->with(['trabajador.persona', 'trabajador.cargo'])
            ->where('Id_Planilla', $this->planillaId)
            ->orderBy('Id_Detalle_Planilla')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        $planilla = $this->planilla();

        if (! $planilla) {
            return [
                'Planilla' => '#' . $this->planillaId,
                'Tipo' => 'No encontrada',
                'Trabajadores' => '0',
                'Total neto' => 'C$ 0.00',
                'Estado' => 'Sin datos',
            ];
        }

        return [
            'Planilla' => '#' . $planilla->Id_Planilla,
            'Tipo' => (string) $planilla->Tipo_Planilla,
            'Trabajadores' => number_format($datos->count()),
            'Total neto' => 'C$ ' . number_format((float) $planilla->Total_Neto, 2),
            'Estado' => (string) $planilla->Estado,
        ];
    }

    public function columnas(): array
    {
        return [
            ['key' => 'empleado', 'label' => 'Empleado', 'pdf' => 42, 'word' => 1900, 'tipo' => 'text', 'limit' => 28],
            ['key' => 'cargo', 'label' => 'Cargo', 'pdf' => 26, 'word' => 1300, 'tipo' => 'text', 'limit' => 18],
            ['key' => 'salario', 'label' => 'Salario', 'pdf' => 23, 'word' => 1150, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'dias', 'label' => 'Días', 'pdf' => 12, 'word' => 650, 'tipo' => 'text', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'incentivo', 'label' => 'Incent.', 'pdf' => 22, 'word' => 1050, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'deduccion', 'label' => 'Deducc.', 'pdf' => 22, 'word' => 1050, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'vacaciones', 'label' => 'Vacac.', 'pdf' => 22, 'word' => 1050, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'aguinaldo', 'label' => 'Aguin.', 'pdf' => 22, 'word' => 1050, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'indemnizacion', 'label' => 'Indem.', 'pdf' => 23, 'word' => 1100, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'total', 'label' => 'Total', 'pdf' => 25, 'word' => 1200, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'estado', 'label' => 'Estado', 'pdf' => 22, 'word' => 1050, 'tipo' => 'badge'],
        ];
    }

    public function mapFila(mixed $fila): array
    {
        $trabajador = $fila->trabajador;

        return [
            'empleado' => $trabajador ? $this->nombreTrabajador($trabajador) : 'Sin trabajador',
            'cargo' => $trabajador ? $this->cargoTrabajador($trabajador) : 'Sin cargo',
            'salario' => (float) $fila->Salario_Base,
            'dias' => $this->numero($fila->Dias_Trabajados),
            'incentivo' => (float) $fila->Monto_Incentivo,
            'deduccion' => (float) $fila->Monto_Deduccion,
            'vacaciones' => (float) $fila->Monto_Vacaciones,
            'aguinaldo' => (float) $fila->Monto_Aguinaldo,
            'indemnizacion' => (float) ($fila->Monto_Indemnizacion ?? 0),
            'total' => (float) $fila->Total_Neto,
            'estado' => (string) $fila->Estado_Pago,
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
                'empleado' => 'TOTAL GENERAL',
                'cargo' => '',
                'salario' => (float) $datos->sum('Salario_Base'),
                'dias' => $this->numero($datos->sum('Dias_Trabajados')),
                'incentivo' => (float) $datos->sum('Monto_Incentivo'),
                'deduccion' => (float) $datos->sum('Monto_Deduccion'),
                'vacaciones' => (float) $datos->sum('Monto_Vacaciones'),
                'aguinaldo' => (float) $datos->sum('Monto_Aguinaldo'),
                'indemnizacion' => (float) $datos->sum('Monto_Indemnizacion'),
                'total' => (float) $datos->sum('Total_Neto'),
                'estado' => (string) ($this->planilla()?->Estado ?: 'Completado'),
            ],
        ]);
    }

    public function colorEstado(string $estado): array
    {
        return match (mb_strtolower($estado)) {
            'pagada', 'pagado', 'pagada automáticamente', 'completado', 'aplicado', 'aplicada' => [
                'texto' => '166534',
                'fondo' => 'DCFCE7',
            ],
            'pendiente', 'anulada', 'rechazada' => [
                'texto' => 'B91C1C',
                'fondo' => 'FEE2E2',
            ],
            default => parent::colorEstado($estado),
        };
    }

    private function planilla(): ?Planilla
    {
        if ($this->planillaCache) {
            return $this->planillaCache;
        }

        $this->planillaCache = Planilla::query()->find($this->planillaId);

        return $this->planillaCache;
    }

    private function nombreTrabajador(Trabajador $trabajador): string
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

    private function cargoTrabajador(Trabajador $trabajador): string
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
            if ($value && ! is_numeric($value) && (stripos($key, 'cargo') !== false || stripos($key, 'nombre') !== false || stripos($key, 'descripcion') !== false)) {
                return (string) $value;
            }
        }

        return 'Cargo #' . ($trabajador->Id_Cargo ?? data_get($cargo, 'Id_Cargo', ''));
    }

    private function numero(mixed $value): string
    {
        $number = round((float) str_replace(',', '', (string) ($value ?? 0)), 2);

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }
}
