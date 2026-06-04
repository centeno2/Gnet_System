<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteArqueosCaja;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ArqueosCajaReporteService extends BaseReporteService
{
    private string $desde;

    private string $hasta;

    private ?int $usuarioId;

    public function __construct(private readonly Request $request)
    {
        $this->desde = (string) $this->request->query('desde', now()->startOfMonth()->toDateString());
        $this->hasta = (string) $this->request->query('hasta', now()->toDateString());

        $usuario = trim((string) $this->request->query('usuarioId', ''));

        $this->usuarioId = $usuario !== '' && $usuario !== '0' && ctype_digit($usuario)
            ? (int) $usuario
            : null;
    }

    public function titulo(): string
    {
        return $this->usuarioId
            ? 'Reporte de arqueos de caja por cajero'
            : 'Reporte general de arqueos de caja';
    }

    public function nombreArchivo(): string
    {
        $cajero = $this->usuarioId ? 'cajero-' . $this->usuarioId : 'general';

        return 'reporte-arqueos-caja-' . $this->desde . '-' . $this->hasta . '-' . $cajero;
    }

    public function consultar(): Collection
    {
        return VwReporteArqueosCaja::query()
            ->when($this->desde !== '', fn($query) => $query->whereDate('Fecha_Arqueo', '>=', $this->desde))
            ->when($this->hasta !== '', fn($query) => $query->whereDate('Fecha_Arqueo', '<=', $this->hasta))
            ->when($this->usuarioId !== null, fn($query) => $query->where('Id_Usuario', $this->usuarioId))
            ->orderBy('Fecha_Arqueo')
            ->orderBy('Cajero')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        return [
            'Arqueos' => number_format($datos->count()),
            'Apertura' => 'C$ ' . number_format((float) $datos->sum('Monto_Apertura'), 2),
            'Efectivo C$' => 'C$ ' . number_format((float) $datos->sum('Total_Efectivo_Cordobas'), 2),
            'Faltante C$' => 'C$ ' . number_format((float) $datos->sum('Faltante_Cordobas'), 2),
            'Sobrante C$' => 'C$ ' . number_format((float) $datos->sum('Sobrante_Cordobas'), 2),
        ];
    }

    public function columnas(): array
    {
        return [
            ['key' => 'fecha', 'label' => 'Fecha', 'pdf' => 18, 'word' => 900, 'tipo' => 'date'],
            ['key' => 'caja', 'label' => 'Caja', 'pdf' => 13, 'word' => 700, 'tipo' => 'number', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'cajero', 'label' => 'Cajero', 'pdf' => 32, 'word' => 1900, 'tipo' => 'text', 'limit' => 24],
            ['key' => 'apertura', 'label' => 'Apertura', 'pdf' => 24, 'word' => 1200, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'efectivo_cordobas', 'label' => 'Efectivo C$', 'pdf' => 26, 'word' => 1300, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'efectivo_dolares', 'label' => 'Efectivo $', 'pdf' => 24, 'word' => 1200, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'faltante_cordobas', 'label' => 'Falt. C$', 'pdf' => 22, 'word' => 1100, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'faltante_dolares', 'label' => 'Falt. $', 'pdf' => 20, 'word' => 1050, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'sobrante_cordobas', 'label' => 'Sobr. C$', 'pdf' => 22, 'word' => 1100, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'sobrante_dolares', 'label' => 'Sobr. $', 'pdf' => 20, 'word' => 1050, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'estado', 'label' => 'Estado', 'pdf' => 24, 'word' => 1200, 'tipo' => 'badge'],
        ];
    }

    public function mapFila(mixed $fila): array
    {
        return [
            'fecha' => $fila->Fecha_Arqueo
                ? Carbon::parse($fila->Fecha_Arqueo)->format('Y-m-d')
                : '',
            'caja' => (int) $fila->Numero_Caja,
            'cajero' => (string) $fila->Cajero,
            'apertura' => (float) $fila->Monto_Apertura,
            'efectivo_cordobas' => (float) $fila->Total_Efectivo_Cordobas,
            'efectivo_dolares' => (float) $fila->Total_Efectivo_Dolares,
            'faltante_cordobas' => (float) $fila->Faltante_Cordobas,
            'faltante_dolares' => (float) $fila->Faltante_Dolares,
            'sobrante_cordobas' => (float) $fila->Sobrante_Cordobas,
            'sobrante_dolares' => (float) $fila->Sobrante_Dolares,
            'estado' => (string) $fila->Estado_Arqueo,
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
                'fecha' => '',
                'caja' => '',
                'cajero' => 'TOTAL GENERAL',
                'apertura' => (float) $datos->sum('Monto_Apertura'),
                'efectivo_cordobas' => (float) $datos->sum('Total_Efectivo_Cordobas'),
                'efectivo_dolares' => (float) $datos->sum('Total_Efectivo_Dolares'),
                'faltante_cordobas' => (float) $datos->sum('Faltante_Cordobas'),
                'faltante_dolares' => (float) $datos->sum('Faltante_Dolares'),
                'sobrante_cordobas' => (float) $datos->sum('Sobrante_Cordobas'),
                'sobrante_dolares' => (float) $datos->sum('Sobrante_Dolares'),
                'estado' => 'Completado',
            ],
        ]);
    }
}
