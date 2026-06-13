<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteFacturaContadoDetalle;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FacturaContadoReporteService extends BaseReporteService
{
    private ?int $ventaId;

    private string $factura;

    public function __construct(private readonly Request $request)
    {
        $ventaId = trim((string) $this->request->query('ventaId', ''));

        $this->ventaId = $ventaId !== '' && ctype_digit($ventaId) ? (int) $ventaId : null;
        $this->factura = trim((string) $this->request->query('factura', ''));
    }

    public function titulo(): string
    {
        return $this->factura !== ''
            ? 'Factura contado #' . $this->factura
            : 'Factura contado';
    }

    public function nombreArchivo(): string
    {
        $factura = $this->factura !== ''
            ? preg_replace('/[^A-Za-z0-9_-]+/', '-', $this->factura)
            : 'venta-' . ($this->ventaId ?: 'sin-factura');

        return 'reporte-factura-contado-' . $factura;
    }

    public function consultar(): Collection
    {
        $query = VwReporteFacturaContadoDetalle::query();

        if ($this->ventaId) {
            $query->where('Id_Venta', $this->ventaId);
        } elseif ($this->factura !== '') {
            $query->where('Numero_Factura', $this->factura);
        } else {
            return collect();
        }

        return $query
            ->orderBy('Tipo_Detalle')
            ->orderBy('Id_Detalle_Venta')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        $fila = $datos->first();

        if (! $fila) {
            return [
                'Factura' => $this->factura !== '' ? $this->factura : '—',
                'Registros' => '0',
                'Total factura' => 'C$ 0.00',
                'Total pagado' => 'C$ 0.00',
                'Cambio' => 'C$ 0.00',
            ];
        }

        return [
            'Factura' => (string) $fila->Numero_Factura,
            'Cliente' => (string) $fila->Cliente,
            'Fecha venta' => $fila->Fecha_Venta ? Carbon::parse($fila->Fecha_Venta)->format('d/m/Y H:i') : '—',
            'Estado' => (string) $fila->Estado_Venta,
            'Registros' => number_format($datos->count()),
            'Total factura' => 'C$ ' . number_format((float) $fila->Total_Factura, 2),
            'Total pagado' => 'C$ ' . number_format((float) $fila->Total_Pagado_Cordobas, 2),
            'Cambio' => 'C$ ' . number_format((float) $fila->Cambio_Entregado_Cordobas, 2),
        ];
    }

    public function columnas(): array
    {
        return [
            ['key' => 'tipo', 'label' => 'Tipo', 'pdf' => 20, 'word' => 1000, 'tipo' => 'badge'],
            ['key' => 'item', 'label' => 'Producto / servicio', 'pdf' => 52, 'word' => 2800, 'tipo' => 'text', 'limit' => 38],
            ['key' => 'serie_formato', 'label' => 'Serie / formato', 'pdf' => 38, 'word' => 1900, 'tipo' => 'text', 'limit' => 28],
            ['key' => 'cantidad', 'label' => 'Cant.', 'pdf' => 18, 'word' => 850, 'tipo' => 'number', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'precio', 'label' => 'Precio', 'pdf' => 27, 'word' => 1300, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'descuento', 'label' => 'Desc.', 'pdf' => 24, 'word' => 1100, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'subtotal', 'label' => 'Subtotal', 'pdf' => 30, 'word' => 1450, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'recibido', 'label' => 'Recibido por', 'pdf' => 34, 'word' => 1700, 'tipo' => 'text', 'limit' => 24],
            ['key' => 'observacion', 'label' => 'Observación', 'pdf' => 40, 'word' => 2200, 'tipo' => 'text', 'limit' => 32],
        ];
    }

    public function mapFila(mixed $fila): array
    {
        return [
            'tipo' => (string) $fila->Tipo_Detalle,
            'item' => (string) $fila->Item,
            'serie_formato' => (string) $fila->Serie_Formato,
            'cantidad' => (float) $fila->Cantidad,
            'precio' => (float) $fila->Precio_Unitario,
            'descuento' => (float) $fila->Descuento_Detalle,
            'subtotal' => (float) $fila->Subtotal,
            'recibido' => (string) $fila->Recibido_Por,
            'observacion' => (string) $fila->Observacion_Detalle,
        ];
    }
}
