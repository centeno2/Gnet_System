<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteVentasPeriodo;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VentasPeriodoReporteService extends BaseReporteService
{
    private string $desde;

    private string $hasta;

    private ?int $usuarioId;

    public function __construct(private readonly Request $request)
    {
        $this->desde = (string) $this->request->query('desde', now()->startOfMonth()->toDateString());
        $this->hasta = (string) $this->request->query('hasta', now()->toDateString());

        $usuario = trim((string) $this->request->query('usuarioId', ''));
        $this->usuarioId = $usuario !== '' && ctype_digit($usuario) ? (int) $usuario : null;
    }

    public function titulo(): string
    {
        return $this->usuarioId
            ? 'Reporte de ventas por periodo y usuario'
            : 'Reporte general de ventas por periodo';
    }

    public function nombreArchivo(): string
    {
        $usuario = $this->usuarioId ? 'usuario-' . $this->usuarioId : 'general';

        return 'reporte-ventas-periodo-' . $this->desde . '-' . $this->hasta . '-' . $usuario;
    }

    public function consultar(): Collection
    {
        return VwReporteVentasPeriodo::query()
            ->when($this->desde !== '', fn($query) => $query->whereDate('Fecha_Venta', '>=', $this->desde))
            ->when($this->hasta !== '', fn($query) => $query->whereDate('Fecha_Venta', '<=', $this->hasta))
            ->when($this->usuarioId, fn($query) => $query->where('Id_Usuario', $this->usuarioId))
            ->orderBy('Fecha_Venta')
            ->orderBy('Numero_Factura')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        return [
            'Ventas' => number_format($datos->count()),
            'Contado' => number_format($datos->where('Tipo_Venta', 'CONTADO')->count()),
            'Crédito' => number_format($datos->where('Tipo_Venta', 'CREDITO')->count()),
            'Total vendido' => 'C$ ' . number_format((float) $datos->sum('Total'), 2),
            'Total pagado' => 'C$ ' . number_format((float) $datos->sum('Total_Pagado_Cordobas'), 2),
        ];
    }

    public function columnas(): array
    {
        return [
            [
                'key' => 'fecha',
                'label' => 'Fecha',
                'pdf' => 24,
                'word' => 1100,
                'tipo' => 'date',
                'align_pdf' => 'L',
            ],
            [
                'key' => 'factura',
                'label' => 'Factura',
                'pdf' => 34,
                'word' => 1900,
                'tipo' => 'text',
                'limit' => 24,
            ],
            [
                'key' => 'usuario',
                'label' => 'Usuario',
                'pdf' => 30,
                'word' => 1700,
                'tipo' => 'text',
                'limit' => 22,
            ],
            [
                'key' => 'cliente',
                'label' => 'Cliente',
                'pdf' => 38,
                'word' => 2200,
                'tipo' => 'text',
                'limit' => 28,
            ],
            [
                'key' => 'tipo_venta',
                'label' => 'Tipo',
                'pdf' => 18,
                'word' => 900,
                'tipo' => 'text',
                'limit' => 10,
            ],
            [
                'key' => 'descuento',
                'label' => 'Desc.',
                'pdf' => 22,
                'word' => 1100,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'total',
                'label' => 'Total',
                'pdf' => 25,
                'word' => 1300,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'pagado',
                'label' => 'Pagado',
                'pdf' => 25,
                'word' => 1300,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'cambio',
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
                'word' => 1300,
                'tipo' => 'badge',
            ],
        ];
    }

    public function mapFila(mixed $fila): array
    {
        return [
            'fecha' => $fila->Fecha_Venta
                ? Carbon::parse($fila->Fecha_Venta)->format('Y-m-d')
                : '',
            'factura' => (string) $fila->Numero_Factura,
            'usuario' => (string) $fila->Usuario,
            'cliente' => (string) $fila->Cliente,
            'tipo_venta' => (string) $fila->Tipo_Venta,
            'descuento' => (float) $fila->Descuento,
            'total' => (float) $fila->Total,
            'pagado' => (float) $fila->Total_Pagado_Cordobas,
            'cambio' => (float) $fila->Cambio_Entregado_Cordobas,
            'estado' => (string) $fila->Estado_Venta,
        ];
    }
}
