<?php

namespace App\Services\Ventas;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use TCPDF;

class CreditoEntregaReciboPdfService
{
    private const ANCHO = 80;

    public function generar(int $entregaCreditoId): string
    {
        $data = $this->obtenerEntrega($entregaCreditoId);
        abort_if(! $data, 404);

        $detalles = $this->obtenerDetalles($entregaCreditoId);
        abort_if($detalles->isEmpty(), 404);

        $nombreArchivo = 'voucher-entrega-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $data->Numero_Recibo) . '.pdf';
        $pdf = $this->pdf($this->altoVoucher($data, $detalles));

        $this->encabezado($pdf, $data);
        $this->datosPrincipales($pdf, $data);
        $this->detalle($pdf, $detalles);
        $this->observacion($pdf, (string) ($data->Observacion ?? ''));
        $this->firmas($pdf, $data);

        return $pdf->Output($nombreArchivo, 'S');
    }

    private function pdf(int $alto): TCPDF
    {
        $pdf = new TCPDF('P', 'mm', [self::ANCHO, $alto], true, 'UTF-8', false);
        $pdf->SetCreator('Gnet System');
        $pdf->SetAuthor('Gnet System');
        $pdf->SetTitle('Voucher general de entrega');
        $pdf->SetSubject('Comprobante general de entrega');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCompression(true);
        $pdf->setFontSubsetting(false);
        $pdf->SetMargins(3, 2, 3);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->AddPage();
        $pdf->SetY(2);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->setCellMargins(0, 0, 0, 0);

        return $pdf;
    }

    private function obtenerEntrega(int $entregaCreditoId): ?object
    {
        return DB::table('entrega_credito as ec')
            ->join('cliente as c', 'c.Id_Cliente', '=', 'ec.Id_Cliente')
            ->leftJoin('persona as pc', 'pc.Id_Persona', '=', 'c.Id_Persona')
            ->leftJoin('usuario as u', 'u.Id_Usuario', '=', 'ec.Id_Usuario')
            ->leftJoin('trabajador as tu', 'tu.Id_Trabajador', '=', 'u.Id_Trabajador')
            ->leftJoin('persona as pu', 'pu.Id_Persona', '=', 'tu.Id_Persona')
            ->where('ec.Id_Entrega_Credito', $entregaCreditoId)
            ->selectRaw("
                ec.Id_Entrega_Credito,
                ec.Numero_Recibo,
                ec.Fecha_Entrega,
                ec.Recibido_Por,
                ec.Observacion,
                c.Institucion,
                c.Municipio,
                c.Tipo_Cliente,
                CONCAT_WS(' ', pc.Primer_Nombre, pc.Segundo_Nombre, pc.Primer_Apellido, pc.Segundo_Apellido) as Cliente_Natural,
                COALESCE(NULLIF(TRIM(CONCAT_WS(' ', pu.Primer_Nombre, pu.Segundo_Nombre, pu.Primer_Apellido, pu.Segundo_Apellido)), ''), u.Nombre_Usuario, 'Usuario') as Usuario_Entrega
            ")
            ->first();
    }

    private function obtenerDetalles(int $entregaCreditoId)
    {
        return DB::table('entrega_credito_detalle as ecd')
            ->join('detalle_venta as dv', 'dv.Id_Detalle_Venta', '=', 'ecd.Id_Detalle_Venta')
            ->join('venta as v', 'v.Id_Venta', '=', 'dv.Id_Venta')
            ->leftJoin('producto as p', 'p.Id_Producto', '=', 'dv.Id_Producto')
            ->leftJoin('producto_serie as ps', 'ps.id_producto_serie', '=', 'dv.Id_Producto_serie')
            ->leftJoin('servicio as s', 's.Id_Servicio', '=', 'dv.Id_Servicio')
            ->leftJoin('tarifa_copia as tc', 'tc.Id_Tarifa_Copia', '=', 'dv.Id_Tarifa_Copia')
            ->where('ecd.Id_Entrega_Credito', $entregaCreditoId)
            ->orderBy('ecd.Id_Entrega_Credito_Detalle')
            ->selectRaw("
                v.Numero_Factura,
                dv.Tipo_Detalle,
                dv.Nombre_Formato,
                dv.Formato_Copia,
                dv.Lados_Copia,
                COALESCE(NULLIF(TRIM(dv.Observacion), ''), '') as Area_Item,
                p.Nombre_Producto,
                p.Modelo,
                ps.Numero_Serie,
                s.Nombre_Servicio,
                tc.Nombre_Tarifa,
                ecd.Cantidad_Total,
                ecd.Cantidad_Pendiente_Anterior,
                ecd.Cantidad_Entregada_Ahora,
                ecd.Cantidad_Pendiente_Restante
            ")
            ->get()
            ->map(function (object $fila) {
                $fila->Detalle_Nombre = $this->nombreDetalle($fila);
                return $fila;
            });
    }

    private function encabezado(TCPDF $pdf, object $data): void
    {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'GNET SERVICOMP', 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(0, 4, 'VOUCHER GENERAL DE ENTREGA', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell(0, 4, 'Comprobante de los artículos y servicios entregados', 0, 'C');
        $this->linea($pdf);

        $this->filaTexto($pdf, 'Recibo:', (string) $data->Numero_Recibo);
    }

    private function datosPrincipales(TCPDF $pdf, object $data): void
    {
        $fecha = $data->Fecha_Entrega
            ? Carbon::parse($data->Fecha_Entrega)->format('d/m/Y h:i A')
            : now()->format('d/m/Y h:i A');

        $this->filaTexto($pdf, 'Fecha:', $fecha);
        $this->filaTexto($pdf, 'Cliente:', $this->clienteNombre($data));

        $municipio = trim((string) ($data->Municipio ?? ''));

        if ($municipio !== '') {
            $this->filaTexto($pdf, 'Municipio:', $municipio);
        }

        $this->filaTexto($pdf, 'Entregó:', (string) $data->Usuario_Entrega);
    }

    private function detalle(TCPDF $pdf, $detalles): void
    {
        $this->linea($pdf);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(0, 5, 'DETALLE DE LA ENTREGA', 0, 1, 'L');

        foreach ($detalles as $indice => $detalle) {
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->MultiCell(0, 4, ($indice + 1) . '. Se entregó ' . $this->cortar((string) $detalle->Detalle_Nombre, 140), 0, 'L');

            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell(0, 4, 'Cantidad entregada: ' . $this->cantidadTexto((float) $detalle->Cantidad_Entregada_Ahora), 0, 1, 'L');

            $area = trim((string) ($detalle->Area_Item ?? ''));

            if ($area !== '') {
                $pdf->SetFont('helvetica', '', 7);
                $pdf->MultiCell(0, 4, 'Área: ' . $this->cortar($area, 80), 0, 'L');
            }

            $pdf->Ln(1);
        }
    }

    private function observacion(TCPDF $pdf, string $observacion): void
    {
        $observacion = trim($observacion);

        if ($observacion === '') {
            return;
        }

        $this->linea($pdf);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(0, 4, 'OBSERVACION', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell(0, 4, $this->cortar($observacion, 180), 0, 'L');
    }

    private function firmas(TCPDF $pdf, object $data): void
    {
        $pdf->Ln(9);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 4, '____________________________', 0, 1, 'C');
        $pdf->Cell(0, 4, 'Firma de recibido', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->MultiCell(0, 4, 'Recibido por: ' . $this->cortar((string) $data->Recibido_Por, 54), 0, 'C');
    }

    private function filaTexto(TCPDF $pdf, string $label, string $valor): void
    {
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(18, 4, $label, 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell(0, 4, $this->cortar($valor, 70), 0, 'L');
    }

    private function linea(TCPDF $pdf): void
    {
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 3, str_repeat('-', 46), 0, 1, 'C');
    }

    private function altoVoucher(object $data, $detalles): int
    {
        $medidor = $this->pdf(5000);

        $this->encabezado($medidor, $data);
        $this->datosPrincipales($medidor, $data);
        $this->detalle($medidor, $detalles);
        $this->observacion($medidor, (string) ($data->Observacion ?? ''));
        $this->firmas($medidor, $data);

        return max(110, (int) ceil($medidor->GetY() + 3));
    }

    private function clienteNombre(object $data): string
    {
        if ((int) $data->Tipo_Cliente === 2) {
            return trim((string) ($data->Institucion ?: 'Institucion'));
        }

        $natural = trim((string) $data->Cliente_Natural);

        return $natural !== '' ? $natural : 'Cliente';
    }

    private function nombreDetalle(object $fila): string
    {
        if ((string) $fila->Tipo_Detalle === 'COPIA') {
            $nombre = (string) ($fila->Nombre_Formato ?: $fila->Nombre_Tarifa ?: 'Copia');
            $formato = $this->formatoCopiaNombre($fila->Formato_Copia);
            $lados = $this->ladosCopiaNombre($fila->Lados_Copia);

            $incluyeFormato = preg_match('/\b(?:carta|oficio|a4|legal)\b/iu', $nombre) === 1;
            $incluyeLados = preg_match('/\b(?:una|doble|1|2)\s+caras?\b/iu', $nombre) === 1;

            if ($formato !== '' && ! $incluyeFormato) {
                $nombre .= ' ' . $formato;
            }

            if ($lados !== '' && ! $incluyeLados) {
                $nombre .= ' ' . $lados;
            }

            return trim($nombre);
        }

        if ((string) $fila->Tipo_Detalle === 'PRODUCTO') {
            return trim(($fila->Nombre_Producto ?: 'Producto') . ($fila->Modelo ? ' - ' . $fila->Modelo : '') . ($fila->Numero_Serie ? ' | Serie: ' . $fila->Numero_Serie : ''));
        }

        return trim((string) ($fila->Nombre_Servicio ?: 'Servicio'));
    }

    private function formatoCopiaNombre($valor): string
    {
        return match ((int) $valor) {
            1 => 'Carta',
            2 => 'Oficio',
            3 => 'A4',
            4 => 'Legal',
            default => '',
        };
    }

    private function ladosCopiaNombre($valor): string
    {
        return match ((int) $valor) {
            1 => 'una cara',
            2 => 'doble cara',
            default => '',
        };
    }

    private function cantidadTexto(float $cantidad): string
    {
        return floor($cantidad) == $cantidad
            ? number_format($cantidad, 0, '.', ',')
            : number_format($cantidad, 2, '.', ',');
    }

    private function cortar(string $texto, int $limite): string
    {
        $texto = trim($texto);

        if (mb_strlen($texto) <= $limite) {
            return $texto;
        }

        return mb_substr($texto, 0, $limite - 3) . '...';
    }
}
