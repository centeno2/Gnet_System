<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteCreditoFacturaAbono;
use App\Models\Reportes\VwReporteCreditoFacturaDetalle;
use App\Models\Reportes\VwReporteCreditoFacturaMovimiento;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CreditoFacturaReporteService extends BaseReporteService
{
    private ?int $ventaId;

    private string $factura;

    private string $vista;

    public function __construct(private readonly Request $request)
    {
        $ventaId = trim((string) $this->request->query('ventaId', ''));
        $vista = trim((string) $this->request->query('vista', 'detalle'));

        $this->ventaId = $ventaId !== '' && ctype_digit($ventaId) ? (int) $ventaId : null;
        $this->factura = trim((string) $this->request->query('factura', ''));
        $this->vista = in_array($vista, ['detalle', 'abonos', 'movimientos'], true) ? $vista : 'detalle';
    }

    public function titulo(): string
    {
        $titulo = match ($this->vista) {
            'abonos' => 'Crédito por factura - historial de abonos',
            'movimientos' => 'Crédito por factura - movimientos de cuenta',
            default => 'Crédito por factura - detalle de factura',
        };

        return $this->factura !== ''
            ? $titulo . ' #' . $this->factura
            : $titulo;
    }

    public function nombreArchivo(): string
    {
        $factura = $this->factura !== ''
            ? preg_replace('/[^A-Za-z0-9_-]+/', '-', $this->factura)
            : 'venta-' . ($this->ventaId ?: 'sin-factura');

        return 'reporte-credito-factura-' . $this->vista . '-' . $factura;
    }

    public function consultar(): Collection
    {
        $query = $this->queryPorVista();

        if ($this->ventaId) {
            $query->where('Id_Venta', $this->ventaId);
        } elseif ($this->factura !== '') {
            $query->where('Numero_Factura', $this->factura);
        } else {
            return collect();
        }

        return match ($this->vista) {
            'abonos' => $query
                ->orderByRaw('Fecha_Abono IS NULL')
                ->orderBy('Fecha_Abono')
                ->orderBy('Id_Abono_Credito')
                ->get(),
            'movimientos' => $query
                ->orderByRaw('Fecha_Movimiento IS NULL')
                ->orderBy('Fecha_Movimiento')
                ->orderBy('Id_Movimiento')
                ->get(),
            default => $query
                ->orderBy('Tipo_Detalle')
                ->orderBy('Id_Detalle_Venta')
                ->get(),
        };
    }

    public function resumen(Collection $datos): array
    {
        $fila = $datos->first();

        if (! $fila) {
            return [
                'Factura' => $this->factura !== '' ? $this->factura : '—',
                'Registros' => '0',
                'Total factura' => 'C$ 0.00',
                'Abono inicial' => 'C$ 0.00',
                'Total abonos' => 'C$ 0.00',
                'Saldo crédito' => 'C$ 0.00',
            ];
        }

        $registros = match ($this->vista) {
            'abonos' => $datos->whereNotNull('Id_Abono_Credito')->count(),
            'movimientos' => $datos->whereNotNull('Id_Movimiento')->count(),
            default => $datos->count(),
        };

        return [
            'Factura' => (string) $fila->Numero_Factura,
            'Cliente' => (string) $fila->Cliente,
            'Fecha venta' => $fila->Fecha_Venta ? Carbon::parse($fila->Fecha_Venta)->format('d/m/Y') : '—',
            'Estado crédito' => (string) $fila->Estado_Credito,
            'Registros' => number_format($registros),
            'Total factura' => 'C$ ' . number_format((float) $fila->Total_Factura, 2),
            'Abono inicial' => 'C$ ' . number_format((float) $fila->Abono_Inicial, 2),
            'Total abonos' => 'C$ ' . number_format((float) $fila->Total_Abonos_Cordobas, 2),
            'Saldo crédito' => 'C$ ' . number_format((float) $fila->Saldo_Credito, 2),
            'Saldo cliente' => 'C$ ' . number_format((float) $fila->Saldo_Cliente_Credito, 2),
        ];
    }

    public function columnas(): array
    {
        return match ($this->vista) {
            'abonos' => $this->columnasAbonos(),
            'movimientos' => $this->columnasMovimientos(),
            default => $this->columnasDetalle(),
        };
    }

    public function mapFila(mixed $fila): array
    {
        return match ($this->vista) {
            'abonos' => $this->mapFilaAbono($fila),
            'movimientos' => $this->mapFilaMovimiento($fila),
            default => $this->mapFilaDetalle($fila),
        };
    }

    private function queryPorVista(): Builder
    {
        return match ($this->vista) {
            'abonos' => VwReporteCreditoFacturaAbono::query(),
            'movimientos' => VwReporteCreditoFacturaMovimiento::query(),
            default => VwReporteCreditoFacturaDetalle::query(),
        };
    }

    private function columnasDetalle(): array
    {
        return [
            ['key' => 'tipo', 'label' => 'Tipo', 'pdf' => 20, 'word' => 1000, 'tipo' => 'badge'],
            ['key' => 'item', 'label' => 'Producto / servicio', 'pdf' => 46, 'word' => 2700, 'tipo' => 'text', 'limit' => 36],
            ['key' => 'serie_formato', 'label' => 'Serie / formato', 'pdf' => 34, 'word' => 1800, 'tipo' => 'text', 'limit' => 26],
            ['key' => 'cantidad', 'label' => 'Cant.', 'pdf' => 17, 'word' => 850, 'tipo' => 'number', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'precio', 'label' => 'Precio', 'pdf' => 25, 'word' => 1250, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'descuento', 'label' => 'Desc.', 'pdf' => 22, 'word' => 1100, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'subtotal', 'label' => 'Subtotal', 'pdf' => 27, 'word' => 1300, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'recibido', 'label' => 'Recibido por', 'pdf' => 31, 'word' => 1600, 'tipo' => 'text', 'limit' => 22],
            ['key' => 'observacion', 'label' => 'Observación', 'pdf' => 34, 'word' => 1900, 'tipo' => 'text', 'limit' => 28],
        ];
    }

    private function columnasAbonos(): array
    {
        return [
            ['key' => 'fecha', 'label' => 'Fecha', 'pdf' => 30, 'word' => 1500, 'tipo' => 'text', 'limit' => 16],
            ['key' => 'usuario', 'label' => 'Usuario', 'pdf' => 34, 'word' => 1800, 'tipo' => 'text', 'limit' => 24],
            ['key' => 'moneda', 'label' => 'Moneda', 'pdf' => 20, 'word' => 900, 'tipo' => 'badge'],
            ['key' => 'monto', 'label' => 'Monto', 'pdf' => 27, 'word' => 1300, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'tipo_cambio', 'label' => 'T/C', 'pdf' => 19, 'word' => 900, 'tipo' => 'number', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'equivalente', 'label' => 'Equiv. C$', 'pdf' => 30, 'word' => 1450, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'referencia', 'label' => 'Referencia', 'pdf' => 37, 'word' => 1900, 'tipo' => 'text', 'limit' => 26],
            ['key' => 'observacion', 'label' => 'Observación', 'pdf' => 52, 'word' => 2600, 'tipo' => 'text', 'limit' => 42],
        ];
    }

    private function columnasMovimientos(): array
    {
        return [
            ['key' => 'fecha', 'label' => 'Fecha', 'pdf' => 32, 'word' => 1600, 'tipo' => 'text', 'limit' => 16],
            ['key' => 'tipo', 'label' => 'Tipo', 'pdf' => 25, 'word' => 1200, 'tipo' => 'badge'],
            ['key' => 'monto', 'label' => 'Monto', 'pdf' => 30, 'word' => 1400, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'saldo_anterior', 'label' => 'Saldo ant.', 'pdf' => 34, 'word' => 1600, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'saldo_despues', 'label' => 'Saldo desp.', 'pdf' => 34, 'word' => 1600, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'observacion', 'label' => 'Observación', 'pdf' => 90, 'word' => 4400, 'tipo' => 'text', 'limit' => 74],
        ];
    }

    private function mapFilaDetalle(mixed $fila): array
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

    private function mapFilaAbono(mixed $fila): array
    {
        return [
            'fecha' => $fila->Fecha_Abono ? Carbon::parse($fila->Fecha_Abono)->format('Y-m-d H:i') : 'Sin abonos',
            'usuario' => (string) $fila->Usuario_Abono,
            'moneda' => (string) $fila->Moneda,
            'monto' => (float) $fila->Monto,
            'tipo_cambio' => (float) $fila->Tipo_Cambio,
            'equivalente' => (float) $fila->Monto_Equivalente_Cordobas,
            'referencia' => (string) $fila->Numero_Transferencia,
            'observacion' => (string) $fila->Observacion_Abono,
        ];
    }

    private function mapFilaMovimiento(mixed $fila): array
    {
        return [
            'fecha' => $fila->Fecha_Movimiento ? Carbon::parse($fila->Fecha_Movimiento)->format('Y-m-d H:i') : 'Sin movimientos',
            'tipo' => (string) $fila->Tipo_Movimiento,
            'monto' => (float) $fila->Monto_Movimiento,
            'saldo_anterior' => (float) $fila->Saldo_Anterior,
            'saldo_despues' => (float) $fila->Saldo_Despues,
            'observacion' => (string) $fila->Observacion_Movimiento,
        ];
    }
}
