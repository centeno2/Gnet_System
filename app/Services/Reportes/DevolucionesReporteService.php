<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteDevoluciones;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DevolucionesReporteService extends BaseReporteService
{
    private string $desde;

    private string $hasta;

    public function __construct(private readonly Request $request)
    {
        $this->desde = (string) $this->request->query('desde', now()->startOfMonth()->toDateString());
        $this->hasta = (string) $this->request->query('hasta', now()->toDateString());
    }

    public function titulo(): string
    {
        return 'Reporte de devoluciones por periodo';
    }

    public function nombreArchivo(): string
    {
        return 'reporte-devoluciones-' . $this->desde . '-' . $this->hasta;
    }

    public function consultar(): Collection
    {
        return VwReporteDevoluciones::query()
            ->when($this->desde !== '', fn($query) => $query->whereDate('Fecha_Devolucion', '>=', $this->desde))
            ->when($this->hasta !== '', fn($query) => $query->whereDate('Fecha_Devolucion', '<=', $this->hasta))
            ->orderBy('Fecha_Devolucion')
            ->orderBy('Numero_Factura')
            ->orderBy('Item')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        return [
            'Devoluciones' => number_format($datos->pluck('Id_Devolucion')->unique()->count()),
            'Detalles' => number_format($datos->count()),
            'Unidades' => number_format((float) $datos->sum('Cantidad'), 2),
            'Devuelto' => 'C$ ' . number_format((float) $datos->sum('Monto_Devuelto'), 2),
            'Cambio' => 'C$ ' . number_format((float) $datos->sum('Monto_Cambio'), 2),
        ];
    }

    public function columnas(): array
    {
        return [
            [
                'key' => 'fecha',
                'label' => 'Fecha',
                'pdf' => 18,
                'word' => 900,
                'tipo' => 'date',
            ],
            [
                'key' => 'factura',
                'label' => 'Factura',
                'pdf' => 24,
                'word' => 1300,
                'tipo' => 'text',
                'limit' => 18,
            ],
            [
                'key' => 'cliente',
                'label' => 'Cliente',
                'pdf' => 34,
                'word' => 1900,
                'tipo' => 'text',
                'limit' => 24,
            ],
            [
                'key' => 'tipo_detalle',
                'label' => 'Tipo',
                'pdf' => 18,
                'word' => 900,
                'tipo' => 'text',
                'limit' => 12,
            ],
            [
                'key' => 'item',
                'label' => 'Detalle',
                'pdf' => 44,
                'word' => 2700,
                'tipo' => 'text',
                'limit' => 32,
            ],
            [
                'key' => 'tipo_devolucion',
                'label' => 'Devolución',
                'pdf' => 22,
                'word' => 1100,
                'tipo' => 'text',
                'limit' => 14,
            ],
            [
                'key' => 'cantidad',
                'label' => 'Cant.',
                'pdf' => 14,
                'word' => 700,
                'tipo' => 'number',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'monto_devuelto',
                'label' => 'Devuelto',
                'pdf' => 24,
                'word' => 1200,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'monto_cambio',
                'label' => 'Cambio',
                'pdf' => 22,
                'word' => 1100,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'estado',
                'label' => 'Estado',
                'pdf' => 25,
                'word' => 1200,
                'tipo' => 'badge',
            ],
        ];
    }

    public function mapFila(mixed $fila): array
    {
        return [
            'fecha' => $fila->Fecha_Devolucion
                ? Carbon::parse($fila->Fecha_Devolucion)->format('Y-m-d')
                : '',
            'factura' => (string) $fila->Numero_Factura,
            'cliente' => (string) $fila->Cliente,
            'tipo_detalle' => (string) $fila->Tipo_Detalle_Nombre,
            'item' => (string) $fila->Item,
            'tipo_devolucion' => (string) $fila->Tipo_Devolucion_Nombre,
            'cantidad' => (float) $fila->Cantidad,
            'monto_devuelto' => (float) $fila->Monto_Devuelto,
            'monto_cambio' => (float) $fila->Monto_Cambio,
            'estado' => (string) $fila->Estado_Devolucion_Nombre,
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
                'factura' => '',
                'cliente' => '',
                'tipo_detalle' => 'TOTAL',
                'item' => 'TOTAL GENERAL',
                'tipo_devolucion' => '',
                'cantidad' => (float) $datos->sum('Cantidad'),
                'monto_devuelto' => (float) $datos->sum('Monto_Devuelto'),
                'monto_cambio' => (float) $datos->sum('Monto_Cambio'),
                'estado' => 'Completado',
            ],
        ]);
    }
}
