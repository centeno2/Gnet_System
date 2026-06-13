<?php

namespace App\Services\Ventas;

use App\Models\ServicioTecnico;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use TCPDF;

class ServicioTecnicoVoucherPdfService
{
    public function generar(int $servicioTecnicoId, int $ancho = 80): string
    {
        $ancho = in_array($ancho, [58, 80], true) ? $ancho : 80;

        $servicio = $this->servicio($servicioTecnicoId);
        $this->validarGenerable($servicio);

        $productos = $this->productos($servicioTecnicoId);
        $pagos = $this->pagos($servicio->Id_Venta ? (int) $servicio->Id_Venta : null);

        $items = $this->itemsDetalle($servicio, $productos);
        $observacion = $this->observacionVoucher($servicio);
        $alto = $this->altoVoucher($servicio, $items, $pagos, $observacion, $ancho);

        $pdf = $this->pdf($ancho, $alto);

        $this->titulo($pdf, $ancho, 'VOUCHER SERVICIO TECNICO');
        $this->textoCentro($pdf, 'GNET SYSTEM');
        $this->linea($pdf, $ancho);

        $this->filaTexto($pdf, 'Orden:', $this->texto($servicio->Numero_Orden ?? '—'));
        $this->filaTexto($pdf, 'Factura:', $this->texto($servicio->Numero_Factura ?? '—'));
        $this->filaTexto($pdf, 'Fecha:', $this->fecha($servicio->Fecha_Ingreso ?? null));
        $this->filaTexto($pdf, 'Cliente:', $this->clienteNombre($servicio));
        $this->filaTexto($pdf, 'Telefono:', $this->clienteTelefono($servicio));
        $this->filaTexto($pdf, 'Tecnico:', $this->tecnicoNombre($servicio));
        $this->filaTexto($pdf, 'Tipo:', $this->tipoVentaNombre((string) ($servicio->Tipo_Venta ?? '')));

        $this->linea($pdf, $ancho);

        $this->seccion($pdf, 'EQUIPO');
        $this->filaTexto($pdf, 'Equipo:', $this->equipoTexto($servicio));
        $this->filaTexto($pdf, 'Serie:', $this->texto($servicio->Numero_Serie ?? 'N/A'));
        $this->filaTexto($pdf, 'Estado:', $this->estadoNombre((string) ($servicio->Estado_Servicio ?? '')));

        if ($this->texto($servicio->Problema_Reportado ?? '') !== '—') {
            $this->filaTexto(
                $pdf,
                'Falla:',
                $this->cortar($this->texto($servicio->Problema_Reportado ?? ''), $ancho === 58 ? 90 : 145)
            );
        }

        if ($this->texto($servicio->Observacion_Tecnica ?? '') !== '—') {
            $this->filaTexto(
                $pdf,
                'Obs:',
                $this->cortar($this->texto($servicio->Observacion_Tecnica ?? ''), $ancho === 58 ? 90 : 145)
            );
        }

        $this->linea($pdf, $ancho);
        $this->detalle($pdf, $ancho, $items);

        if ($observacion !== '') {
            $this->linea($pdf, $ancho);
            $this->seccion($pdf, 'OBSERVACION');
            $pdf->SetFont('helvetica', '', 7);
            $pdf->MultiCell(0, 4, $this->cortar($observacion, $ancho === 58 ? 120 : 180), 0, 'L');
        }

        $this->linea($pdf, $ancho);
        $this->total($pdf, 'Mano obra', (float) ($servicio->Costo_Estimado ?? 0));
        $this->total($pdf, 'Repuestos', (float) ($servicio->Total_Repuestos ?? 0));
        $this->total($pdf, 'TOTAL', (float) ($servicio->Total_Servicio ?? 0), true);
        $this->total($pdf, 'Pagado', (float) ($servicio->Monto_Pagado ?? 0));
        $this->total($pdf, 'Saldo', (float) ($servicio->Saldo_Pendiente ?? 0), true);

        if ($pagos->isNotEmpty()) {
            $this->linea($pdf, $ancho);
            $this->seccion($pdf, 'PAGOS');

            foreach ($pagos as $pago) {
                $moneda = (int) ($pago->Moneda ?? 0) === 1 ? 'US$' : 'C$';

                $pdf->SetFont('helvetica', '', 7);
                $pdf->Cell(
                    0,
                    4,
                    $this->texto($pago->Tipo_Pago ?? 'PAGO') . ' ' . $moneda . ' ' . number_format((float) ($pago->Monto ?? 0), 2),
                    0,
                    1,
                    'L'
                );

                if (trim((string) ($pago->Numero_Referencia ?? '')) !== '') {
                    $pdf->MultiCell(
                        0,
                        4,
                        'Ref: ' . $this->cortar((string) $pago->Numero_Referencia, $ancho === 58 ? 28 : 44),
                        0,
                        'L'
                    );
                }
            }
        }

        if ((float) ($servicio->Cambio_Entregado_Cordobas ?? 0) > 0) {
            $this->total($pdf, 'Cambio C$', (float) $servicio->Cambio_Entregado_Cordobas);
        }

        $this->linea($pdf, $ancho);
        $this->textoCentro($pdf, 'Servicio pagado y entregado');
        $this->textoCentro($pdf, 'Gracias por preferirnos');

        return $pdf->Output(
            'voucher-servicio-tecnico-' . $this->texto($servicio->Numero_Orden ?? $servicioTecnicoId) . '.pdf',
            'S'
        );
    }

    private function servicio(int $servicioTecnicoId): object
    {
        $tablaServicio = (new ServicioTecnico())->getTable();

        $servicio = DB::table($tablaServicio . ' as st')
            ->leftJoin('venta as v', 'v.Id_Venta', '=', 'st.Id_Venta')
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', 'st.Id_Cliente')
            ->leftJoin('persona as pc', 'pc.Id_Persona', '=', 'c.Id_Persona')
            ->leftJoin('trabajador as t', 't.Id_Trabajador', '=', 'st.Id_Trabajador')
            ->leftJoin('persona as pt', 'pt.Id_Persona', '=', 't.Id_Persona')
            ->where('st.Id_Servicio_Tecnico', $servicioTecnicoId)
            ->select([
                'st.*',
                'v.Numero_Factura',
                'v.Fecha_venta',
                'c.Institucion',
                'c.Tipo_Cliente',
                'c.Telefono_Institucion',
                'pc.Primer_Nombre as Cliente_Primer_Nombre',
                'pc.Segundo_Nombre as Cliente_Segundo_Nombre',
                'pc.Primer_Apellido as Cliente_Primer_Apellido',
                'pc.Segundo_Apellido as Cliente_Segundo_Apellido',
                'pc.Telefono as Cliente_Telefono',
                'pt.Primer_Nombre as Tecnico_Primer_Nombre',
                'pt.Segundo_Nombre as Tecnico_Segundo_Nombre',
                'pt.Primer_Apellido as Tecnico_Primer_Apellido',
                'pt.Segundo_Apellido as Tecnico_Segundo_Apellido',
            ])
            ->first();

        if (! $servicio) {
            throw new RuntimeException('No se encontró el servicio técnico solicitado.');
        }

        return $servicio;
    }

    private function productos(int $servicioTecnicoId): Collection
    {
        return DB::table('servicio_tecnico_producto as sp')
            ->join('producto as p', 'p.Id_Producto', '=', 'sp.Id_Producto')
            ->leftJoin('marca as m', 'm.Id_Marca', '=', 'p.Id_Marca')
            ->leftJoin('producto_serie as ps', 'ps.id_producto_serie', '=', 'sp.Id_Producto_Serie')
            ->where('sp.Id_Servicio_Tecnico', $servicioTecnicoId)
            ->selectRaw("
                sp.Cantidad,
                sp.Precio_Unitario,
                sp.Subtotal,
                sp.Observacion,
                COALESCE(
                    NULLIF(TRIM(CONCAT_WS(' ', m.Nombre_Marca, p.Nombre_Producto, p.Modelo, CASE WHEN ps.Numero_Serie IS NOT NULL THEN CONCAT('Serie:', ps.Numero_Serie) ELSE NULL END)), ''),
                    'Repuesto'
                ) as Descripcion
            ")
            ->orderBy('sp.Id_Servicio_Tecnico_Producto')
            ->get();
    }

    private function pagos(?int $ventaId): Collection
    {
        if (! $ventaId) {
            return collect();
        }

        return DB::table('pago_venta')
            ->where('Id_Venta', $ventaId)
            ->orderBy('Fecha_Pago')
            ->orderBy('Id_Pago_Venta')
            ->get();
    }

    private function validarGenerable(object $servicio): void
    {
        $saldo = round((float) ($servicio->Saldo_Pendiente ?? 0), 2);
        $estado = strtoupper((string) ($servicio->Estado_Servicio ?? ''));

        if ($estado !== 'ENTREGADO') {
            throw new RuntimeException('El voucher solo puede generarse cuando el servicio está marcado como entregado.');
        }

        if ($saldo > 0) {
            throw new RuntimeException('El voucher solo puede generarse cuando el servicio está pagado completo.');
        }
    }

    private function itemsDetalle(object $servicio, Collection $productos): Collection
    {
        $items = collect();

        if ((float) ($servicio->Costo_Estimado ?? 0) > 0) {
            $items->push([
                'descripcion' => 'Mano de obra servicio técnico',
                'cantidad' => 1,
                'precio_unitario' => (float) $servicio->Costo_Estimado,
                'subtotal_valor' => (float) $servicio->Costo_Estimado,
            ]);
        }

        foreach ($productos as $producto) {
            $items->push([
                'descripcion' => $producto->Descripcion,
                'cantidad' => (float) $producto->Cantidad,
                'precio_unitario' => (float) $producto->Precio_Unitario,
                'subtotal_valor' => (float) $producto->Subtotal,
            ]);
        }

        if ($items->isEmpty()) {
            $items->push([
                'descripcion' => 'Servicio técnico',
                'cantidad' => 1,
                'precio_unitario' => (float) ($servicio->Total_Servicio ?? 0),
                'subtotal_valor' => (float) ($servicio->Total_Servicio ?? 0),
            ]);
        }

        return $items;
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

    private function altoVoucher(object $servicio, Collection $items, Collection $pagos, string $observacion, int $ancho): int
    {
        $caracteresLinea = $ancho === 58 ? 28 : 42;
        $alto = 95;

        $textosVariables = [
            $this->clienteNombre($servicio),
            $this->equipoTexto($servicio),
            (string) ($servicio->Problema_Reportado ?? ''),
            (string) ($servicio->Observacion_Tecnica ?? ''),
        ];

        foreach ($textosVariables as $texto) {
            $lineas = max(1, (int) ceil(mb_strlen($this->texto($texto)) / $caracteresLinea));
            $alto += max(0, $lineas - 1) * 4;
        }

        foreach ($items as $item) {
            $descripcion = (string) ($item['descripcion'] ?? 'Item');
            $lineasDescripcion = max(1, (int) ceil(mb_strlen($descripcion) / $caracteresLinea));
            $alto += 8 + ($lineasDescripcion * 4);
        }

        if ($observacion !== '') {
            $lineasObservacion = max(1, (int) ceil(mb_strlen($observacion) / $caracteresLinea));
            $alto += 8 + ($lineasObservacion * 4);
        }

        $alto += 28;

        if ($pagos->isNotEmpty()) {
            $alto += 8 + ($pagos->count() * 8);
        }

        return max(120, min($alto + 16, 500));
    }

    private function detalle(TCPDF $pdf, int $ancho, Collection $items): void
    {
        $this->seccion($pdf, 'DETALLE');

        foreach ($items as $item) {
            $descripcion = (string) ($item['descripcion'] ?? 'Item');
            $cantidad = (float) ($item['cantidad'] ?? 1);
            $precio = (float) ($item['precio_unitario'] ?? 0);
            $subtotal = (float) ($item['subtotal_valor'] ?? 0);

            $pdf->SetFont('helvetica', '', 7);
            $pdf->MultiCell(0, 4, $this->cortar($descripcion, $ancho === 58 ? 32 : 48), 0, 'L');
            $pdf->Cell(0, 4, $this->cantidad($cantidad) . ' x C$ ' . number_format($precio, 2), 0, 1, 'L');

            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->Cell(0, 4, 'Subtotal: C$ ' . number_format($subtotal, 2), 0, 1, 'R');
        }
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

    private function seccion(TCPDF $pdf, string $texto): void
    {
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(0, 4, $texto, 0, 1, 'L');
    }

    private function filaTexto(TCPDF $pdf, string $label, string $valor): void
    {
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(17, 4, $label, 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell(0, 4, $this->cortar($valor, 55), 0, 'L');
    }

    private function total(TCPDF $pdf, string $label, float $monto, bool $fuerte = false): void
    {
        $pdf->SetFont('helvetica', $fuerte ? 'B' : '', $fuerte ? 8 : 7);
        $pdf->Cell(28, 5, $label, 0, 0, 'L');
        $pdf->Cell(0, 5, 'C$ ' . number_format($monto, 2), 0, 1, 'R');
    }

    private function linea(TCPDF $pdf, int $ancho): void
    {
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 3, str_repeat('-', $ancho === 58 ? 32 : 48), 0, 1, 'C');
    }

    private function cortar(string $texto, int $limite): string
    {
        $texto = trim(preg_replace('/\s+/', ' ', $texto));

        return mb_strlen($texto) <= $limite
            ? $texto
            : mb_substr($texto, 0, $limite - 3) . '...';
    }

    private function texto(mixed $valor): string
    {
        $texto = trim(preg_replace('/\s+/', ' ', (string) $valor));

        return $texto !== '' ? $texto : '—';
    }

    private function fecha(mixed $fecha): string
    {
        if (! $fecha) {
            return now()->format('d/m/Y h:i A');
        }

        return Carbon::parse($fecha)->format('d/m/Y h:i A');
    }

    private function clienteNombre(object $servicio): string
    {
        if ((int) ($servicio->Tipo_Cliente ?? 0) === 2) {
            return $this->texto($servicio->Institucion ?? 'Institución');
        }

        $nombre = trim(implode(' ', array_filter([
            $servicio->Cliente_Primer_Nombre ?? null,
            $servicio->Cliente_Segundo_Nombre ?? null,
            $servicio->Cliente_Primer_Apellido ?? null,
            $servicio->Cliente_Segundo_Apellido ?? null,
        ])));

        return $this->texto($nombre ?: 'Cliente natural');
    }

    private function clienteTelefono(object $servicio): string
    {
        return (int) ($servicio->Tipo_Cliente ?? 0) === 2
            ? $this->texto($servicio->Telefono_Institucion ?? '—')
            : $this->texto($servicio->Cliente_Telefono ?? '—');
    }

    private function tecnicoNombre(object $servicio): string
    {
        $nombre = trim(implode(' ', array_filter([
            $servicio->Tecnico_Primer_Nombre ?? null,
            $servicio->Tecnico_Segundo_Nombre ?? null,
            $servicio->Tecnico_Primer_Apellido ?? null,
            $servicio->Tecnico_Segundo_Apellido ?? null,
        ])));

        return $this->texto($nombre ?: 'Técnico');
    }

    private function equipoTexto(object $servicio): string
    {
        return $this->texto(trim(implode(' ', array_filter([
            $servicio->Tipo_Equipo ?? null,
            $servicio->Marca ?? null,
            $servicio->Modelo ?? null,
        ]))));
    }

    private function estadoNombre(string $estado): string
    {
        return match ($estado) {
            'RECIBIDO' => 'Recibido',
            'EN_REVISION' => 'En revisión',
            'PENDIENTE_REPUESTO' => 'Pendiente repuesto',
            'REPARADO' => 'Reparado',
            'ENTREGADO' => 'Entregado',
            'CANCELADO' => 'Cancelado',
            default => str_replace('_', ' ', $estado),
        };
    }

    private function tipoVentaNombre(string $tipo): string
    {
        return match ($tipo) {
            'CREDITO' => 'Crédito',
            'CONTADO' => 'Contado',
            default => $this->texto($tipo),
        };
    }

    private function observacionVoucher(object $servicio): string
    {
        $observaciones = collect([
            $servicio->Detalle_Descriptivo ?? '',
        ])
            ->map(fn($texto) => trim((string) $texto))
            ->filter()
            ->values();

        return $observaciones->implode(' | ');
    }

    private function cantidad(float $cantidad): string
    {
        return floor($cantidad) == $cantidad
            ? number_format($cantidad, 0, '.', ',')
            : number_format($cantidad, 2, '.', ',');
    }
}
