<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteCreditosInstitucionales;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CreditosInstitucionalesReporteService extends BaseReporteService
{
    private string $desde;

    private string $hasta;

    private ?int $clienteId;

    public function __construct(private readonly Request $request)
    {
        $this->desde = (string) $this->request->query('desde', now()->startOfMonth()->toDateString());
        $this->hasta = (string) $this->request->query('hasta', now()->toDateString());

        $cliente = trim((string) $this->request->query('clienteId', ''));
        $this->clienteId = $cliente !== '' && ctype_digit($cliente) ? (int) $cliente : null;
    }

    public function titulo(): string
    {
        return 'Reporte de créditos institucionales';
    }

    public function nombreArchivo(): string
    {
        $institucion = $this->clienteId ? 'institucion-' . $this->clienteId : 'sin-institucion';

        return 'reporte-creditos-institucionales-' . $this->desde . '-' . $this->hasta . '-' . $institucion;
    }

    public function consultar(): Collection
    {
        if (! $this->clienteId) {
            return collect();
        }

        return VwReporteCreditosInstitucionales::query()
            ->where('Id_Cliente', $this->clienteId)
            ->when($this->desde !== '', fn($query) => $query->whereDate('Fecha_Credito', '>=', $this->desde))
            ->when($this->hasta !== '', fn($query) => $query->whereDate('Fecha_Credito', '<=', $this->hasta))
            ->orderBy('Fecha_Credito')
            ->orderBy('Numero_Factura')
            ->orderBy('Tipo_Detalle')
            ->orderBy('Item')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        $productos = $datos->where('Tipo_Detalle', 'PRODUCTO');
        $copias = $datos->where('Tipo_Detalle', 'COPIA');
        $servicios = $datos->where('Tipo_Detalle', 'SERVICIO');

        return [
            'Facturas' => number_format($datos->pluck('Id_Venta')->unique()->count()),
            'Productos' => number_format((float) $productos->sum('Cantidad'), 2) . ' / C$ ' . number_format((float) $productos->sum('Total_Linea'), 2),
            'Copias' => number_format((float) $copias->sum('Cantidad'), 2) . ' / C$ ' . number_format((float) $copias->sum('Total_Linea'), 2),
            'Servicios' => number_format((float) $servicios->sum('Cantidad'), 2) . ' / C$ ' . number_format((float) $servicios->sum('Total_Linea'), 2),
            'Total general' => 'C$ ' . number_format((float) $datos->sum('Total_Linea'), 2),
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
                'pdf' => 25,
                'word' => 1300,
                'tipo' => 'text',
                'limit' => 18,
            ],
            [
                'key' => 'tipo',
                'label' => 'Tipo',
                'pdf' => 18,
                'word' => 900,
                'tipo' => 'text',
                'limit' => 12,
            ],
            [
                'key' => 'item',
                'label' => 'Detalle',
                'pdf' => 58,
                'word' => 3300,
                'tipo' => 'text',
                'limit' => 42,
            ],
            [
                'key' => 'categoria',
                'label' => 'Categoría',
                'pdf' => 28,
                'word' => 1600,
                'tipo' => 'text',
                'limit' => 18,
            ],
            [
                'key' => 'cantidad',
                'label' => 'Cant.',
                'pdf' => 16,
                'word' => 800,
                'tipo' => 'number',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'precio',
                'label' => 'Precio',
                'pdf' => 23,
                'word' => 1200,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
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
                'pdf' => 30,
                'word' => 1400,
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
            'fecha' => $fila->Fecha_Credito
                ? Carbon::parse($fila->Fecha_Credito)->format('Y-m-d')
                : '',
            'factura' => (string) $fila->Numero_Factura,
            'tipo' => (string) $fila->Tipo_Detalle_Nombre,
            'item' => (string) $fila->Item,
            'categoria' => (string) $fila->Categoria,
            'cantidad' => (float) $fila->Cantidad,
            'precio' => (float) $fila->Precio_Unitario,
            'descuento' => (float) $fila->Descuento,
            'total' => (float) $fila->Total_Linea,
            'estado' => (string) $fila->Estado_Credito,
        ];
    }

    public function filas(Collection $datos): Collection
    {
        $filas = parent::filas($datos);

        if ($datos->isEmpty()) {
            return $filas;
        }

        $productos = $datos->where('Tipo_Detalle', 'PRODUCTO');
        $copias = $datos->where('Tipo_Detalle', 'COPIA');
        $servicios = $datos->where('Tipo_Detalle', 'SERVICIO');

        return $filas->concat([
            $this->filaTotal('TOTAL PRODUCTOS', $productos),
            $this->filaTotal('TOTAL COPIAS', $copias),
            $this->filaTotal('TOTAL SERVICIOS', $servicios),
            $this->filaTotal('TOTAL GENERAL', $datos),
        ]);
    }

    private function filaTotal(string $label, Collection $datos): array
    {
        return [
            'fecha' => '',
            'factura' => '',
            'tipo' => 'TOTAL',
            'item' => $label,
            'categoria' => '',
            'cantidad' => (float) $datos->sum('Cantidad'),
            'precio' => 0,
            'descuento' => (float) $datos->sum('Descuento'),
            'total' => (float) $datos->sum('Total_Linea'),
            'estado' => 'Completado',
        ];
    }
}
