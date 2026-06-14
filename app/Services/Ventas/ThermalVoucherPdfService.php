<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use TCPDF;

class ThermalVoucherPdfService
{
    public function venta(Venta $venta, int $ancho = 80): string
    {
        $ancho = in_array($ancho, [58, 80], true) ? $ancho : 80;

        $detalles = DB::table('detalle_venta as dv')
            ->leftJoin('producto as p', 'p.Id_Producto', '=', 'dv.Id_Producto')
            ->leftJoin('producto_serie as ps', 'ps.id_producto_serie', '=', 'dv.Id_Producto_serie')
            ->leftJoin('tarifa_copia as tc', 'tc.Id_Tarifa_Copia', '=', 'dv.Id_Tarifa_Copia')
            ->where('dv.Id_Venta', $venta->Id_Venta)
            ->selectRaw("
                dv.Tipo_Detalle,
                dv.Cantidad,
                dv.Precio_Unitario,
                dv.Descuento,
                dv.Subtotal,
                dv.Observacion,
                COALESCE(
                    NULLIF(TRIM(CONCAT_WS(' ', p.Nombre_Producto, CASE WHEN ps.Numero_Serie IS NOT NULL THEN CONCAT('Serie:', ps.Numero_Serie) ELSE NULL END)), ''),
                    dv.Nombre_Formato,
                    tc.Nombre_Tarifa,
                    'Item'
                ) as Descripcion
            ")
            ->get();

        $pagos = DB::table('pago_venta')
            ->where('Id_Venta', $venta->Id_Venta)
            ->get();

        $observacion = $this->observacionDetalle($detalles);
        $alto = $this->altoVenta($detalles, $pagos, $observacion, $ancho);

        $pdf = $this->pdf($ancho, $alto);

        $cliente = $this->nombreCliente((int) ($venta->Id_Cliente ?? 0));

        $this->titulo($pdf, $ancho, 'VOUCHER DE VENTA');
        $this->textoCentro($pdf, 'GNET SYSTEM');
        $this->linea($pdf, $ancho);

        $fecha = $venta->Fecha_venta
            ? Carbon::parse($venta->Fecha_venta)->format('d/m/Y h:i A')
            : now()->format('d/m/Y h:i A');

        $this->filaTexto($pdf, 'Factura:', (string) $venta->Numero_Factura);
        $this->filaTexto($pdf, 'Fecha:', $fecha);
        $this->filaTexto($pdf, 'Cliente:', $cliente);
        $this->filaTexto($pdf, 'Tipo:', (string) $venta->Tipo_Venta);

        $this->linea($pdf, $ancho);

        $this->detalle($pdf, $ancho, $detalles->map(fn($d) => [
            'descripcion' => $d->Descripcion,
            'cantidad' => (int) $d->Cantidad,
            'precio_unitario' => (float) $d->Precio_Unitario,
            'descuento_valor' => (float) $d->Descuento,
            'subtotal_valor' => (float) $d->Subtotal,
        ]));

        if ($observacion !== '') {
            $this->linea($pdf, $ancho);
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->Cell(0, 4, 'OBSERVACION', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 7);
            $pdf->MultiCell(0, 4, $this->cortar($observacion, $ancho === 58 ? 90 : 140), 0, 'L');
        }

        $this->linea($pdf, $ancho);

        $this->total($pdf, 'Descuento', (float) $venta->Descuento);
        $this->total($pdf, 'TOTAL', (float) $venta->Total, true);

        if ($pagos->isNotEmpty()) {
            $this->linea($pdf, $ancho);

            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->Cell(0, 4, 'PAGOS', 0, 1, 'L');

            $pdf->SetFont('helvetica', '', 7);

            foreach ($pagos as $pago) {
                $moneda = (int) $pago->Moneda === 1 ? 'US$' : 'C$';

                $pdf->Cell(
                    0,
                    4,
                    $pago->Tipo_Pago . ' ' . $moneda . ' ' . number_format((float) $pago->Monto, 2),
                    0,
                    1,
                    'L'
                );

                if (! empty($pago->Numero_Referencia)) {
                    $pdf->MultiCell(0, 4, 'Ref: ' . $pago->Numero_Referencia, 0, 'L');
                }
            }
        }

        if ((float) ($venta->Cambio_Entregado_Cordobas ?? 0) > 0) {
            $this->total($pdf, 'Cambio C$', (float) $venta->Cambio_Entregado_Cordobas);
        }

        $this->linea($pdf, $ancho);
        $this->textoCentro($pdf, 'Gracias por su compra');

        return $pdf->Output('voucher-' . $venta->Numero_Factura . '.pdf', 'S');
    }

    private function pdf(int $ancho, int $alto): TCPDF
    {
        $pdf = new TCPDF('P', 'mm', [$ancho, $alto], true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(3, 4, 3);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        $pdf->SetTextColor(0, 0, 0);

        return $pdf;
    }

    private function altoVenta($detalles, $pagos, string $observacion, int $ancho): int
    {
        $caracteresLinea = $ancho === 58 ? 28 : 42;

        $alto = 65;

        foreach ($detalles as $detalle) {
            $lineasDescripcion = max(1, (int) ceil(mb_strlen((string) $detalle->Descripcion) / $caracteresLinea));
            $alto += 8 + ($lineasDescripcion * 4);

            if ((float) $detalle->Descuento > 0) {
                $alto += 4;
            }
        }

        if ($observacion !== '') {
            $lineasObservacion = max(1, (int) ceil(mb_strlen($observacion) / $caracteresLinea));
            $alto += 9 + ($lineasObservacion * 4);
        }

        $alto += 18;

        if ($pagos->isNotEmpty()) {
            $alto += 8 + ($pagos->count() * 7);
        }

        return max(95, min($alto + 14, 500));
    }

    private function observacionDetalle($detalles): string
    {
        foreach ($detalles as $detalle) {
            $observacion = trim((string) ($detalle->Observacion ?? ''));

            if ($observacion !== '') {
                return $observacion;
            }
        }

        return '';
    }

    private function titulo(TCPDF $pdf, int $ancho, string $titulo): void
    {
        $pdf->SetFont('helvetica', 'B', $ancho === 58 ? 9 : 11);
        $pdf->Cell(0, 6, $titulo, 0, 1, 'C');
    }

    private function textoCentro(TCPDF $pdf, string $texto): void
    {
        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell(0, 4, $texto, 0, 'C');
    }

    private function filaTexto(TCPDF $pdf, string $label, string $valor): void
    {
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(16, 4, $label, 0, 0, 'L');

        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell(0, 4, $this->cortar($valor, 45), 0, 'L');
    }

    private function detalle(TCPDF $pdf, int $ancho, $items): void
    {
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(0, 4, 'DETALLE', 0, 1, 'L');

        foreach ($items as $item) {
            $descripcion = is_array($item) ? $item['descripcion'] : $item->descripcion;
            $cantidad = (int) (is_array($item) ? $item['cantidad'] : $item->cantidad);
            $precio = (float) (is_array($item) ? $item['precio_unitario'] : $item->precio_unitario);
            $descuento = (float) (is_array($item) ? ($item['descuento_valor'] ?? 0) : ($item->descuento_valor ?? 0));
            $subtotal = (float) (is_array($item) ? $item['subtotal_valor'] : $item->subtotal_valor);

            $pdf->SetFont('helvetica', '', 7);
            $pdf->MultiCell(0, 4, $this->cortar((string) $descripcion, $ancho === 58 ? 32 : 48), 0, 'L');

            $pdf->Cell(0, 4, $cantidad . ' x C$ ' . number_format($precio, 2), 0, 1, 'L');

            if ($descuento > 0) {
                $pdf->Cell(0, 4, 'Desc: C$ ' . number_format($descuento, 2), 0, 1, 'L');
            }

            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->Cell(0, 4, 'Subtotal: C$ ' . number_format($subtotal, 2), 0, 1, 'R');
        }
    }

    private function total(TCPDF $pdf, string $label, float $monto, bool $fuerte = false): void
    {
        $pdf->SetFont('helvetica', $fuerte ? 'B' : '', $fuerte ? 8 : 7);
        $pdf->Cell(26, 5, $label, 0, 0, 'L');
        $pdf->Cell(0, 5, 'C$ ' . number_format($monto, 2), 0, 1, 'R');
    }

    private function linea(TCPDF $pdf, int $ancho): void
    {
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 3, str_repeat('-', $ancho === 58 ? 32 : 48), 0, 1, 'C');
    }

    private function cortar(string $texto, int $limite): string
    {
        $texto = trim($texto);

        return mb_strlen($texto) <= $limite
            ? $texto
            : mb_substr($texto, 0, $limite - 3) . '...';
    }

    private function nombreCliente(int $clienteId): string
    {
        if ($clienteId <= 0) {
            return 'Consumidor final';
        }

        $cliente = DB::table('cliente as c')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->where('c.Id_Cliente', $clienteId)
            ->selectRaw("
                CASE
                    WHEN c.Tipo_Cliente = 2 THEN COALESCE(c.Institucion, 'Institución')
                    ELSE COALESCE(
                        NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                        'Cliente natural'
                    )
                END as Nombre
            ")
            ->first();

        return $cliente->Nombre ?? 'Consumidor final';
    }
}
