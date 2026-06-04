<?php

namespace App\Services\Ventas;

use TCPDF;

class CotizacionVentaPdfService
{
    public function generar(array $payload): string
    {
        $items = collect($payload['items'] ?? []);

        $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);

        $pdf->SetCreator('Gnet System');
        $pdf->SetAuthor('Gnet System');
        $pdf->SetTitle('Proforma');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();

        $this->encabezado($pdf, $payload);
        $this->tabla($pdf, $items);
        $this->totales($pdf, $payload);
        $this->pie($pdf);

        return $pdf->Output('proforma.pdf', 'S');
    }

    private function encabezado(TCPDF $pdf, array $payload): void
    {
        $logo = public_path('img/gnetlogo.png');

        if (file_exists($logo)) {
            $pdf->Image($logo, 12, 10, 28, 28);
        }

        $pdf->SetFillColor(46, 139, 192);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 14, 'PROFORMA', 0, 1, 'R', true);

        $pdf->Ln(4);

        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(0, 7, 'GNET SYSTEM', 0, 1, 'R');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(0, 5, 'Parque Dario 1C. Norte y 20 vrs. Oeste, Matagalpa, Nicaragua', 0, 1, 'R');
        $pdf->Cell(0, 5, 'Tel: 8737-1426 / Email: gnetservicomp@gmail.com', 0, 1, 'R');

        $pdf->Ln(6);

        $pdf->SetFillColor(240, 243, 247);
        $pdf->SetTextColor(26, 43, 66);

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(26, 8, 'Cliente:', 1, 0, 'L', true);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(105, 8, (string) ($payload['cliente'] ?? 'Consumidor final'), 1, 0, 'L');

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(22, 8, 'Fecha:', 1, 0, 'L', true);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(37, 8, now()->format('d/m/Y'), 1, 1, 'L');

        if (! empty($payload['municipio'])) {
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(26, 8, 'Municipio:', 1, 0, 'L', true);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(164, 8, (string) $payload['municipio'], 1, 1, 'L');
        }

        $pdf->Ln(6);
    }

    private function tabla(TCPDF $pdf, $items): void
    {
        $headers = [
            ['Cant.', 18],
            ['Descripcion', 104],
            ['P/Unit', 32],
            ['Subtotal', 36],
        ];

        $pdf->SetFillColor(46, 139, 192);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);

        foreach ($headers as [$texto, $ancho]) {
            $pdf->Cell($ancho, 8, $texto, 1, 0, 'C', true);
        }

        $pdf->Ln();

        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', '', 8);

        foreach ($items as $item) {
            $descripcion = (string) ($item['descripcion'] ?? 'Item');
            $cantidad = (int) ($item['cantidad'] ?? 1);
            $precio = (float) ($item['precio_unitario'] ?? 0);
            $subtotal = (float) ($item['subtotal_valor'] ?? 0);

            $y = $pdf->GetY();
            $alto = max(9, ceil(mb_strlen($descripcion) / 55) * 5);

            if ($y + $alto > 250) {
                $pdf->AddPage();
            }

            $x = $pdf->GetX();

            $pdf->MultiCell(18, $alto, (string) $cantidad, 1, 'C', false, 0);
            $pdf->MultiCell(104, $alto, $descripcion, 1, 'L', false, 0);
            $pdf->MultiCell(32, $alto, 'C$ ' . number_format($precio, 2), 1, 'R', false, 0);
            $pdf->MultiCell(36, $alto, 'C$ ' . number_format($subtotal, 2), 1, 'R', false, 1);

            $pdf->SetX($x);
        }

        if ($items->isEmpty()) {
            $pdf->Cell(190, 10, 'No hay items agregados.', 1, 1, 'C');
        }
    }

    private function totales(TCPDF $pdf, array $payload): void
    {
        $pdf->Ln(6);

        $subtotal = (float) ($payload['subtotal'] ?? 0);
        $descuento = (float) ($payload['descuento'] ?? 0);
        $total = (float) ($payload['total'] ?? 0);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(26, 43, 66);

        $pdf->Cell(118, 8, 'Vigencia de la oferta: 5 dias', 0, 0, 'L');

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(36, 8, 'Subtotal:', 1, 0, 'R');
        $pdf->Cell(36, 8, 'C$ ' . number_format($subtotal, 2), 1, 1, 'R');

        $pdf->Cell(118, 8, '', 0, 0);
        $pdf->Cell(36, 8, 'Descuento:', 1, 0, 'R');
        $pdf->Cell(36, 8, 'C$ ' . number_format($descuento, 2), 1, 1, 'R');

        $pdf->SetFillColor(46, 139, 192);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(118, 9, '', 0, 0);
        $pdf->Cell(36, 9, 'TOTAL:', 1, 0, 'R', true);
        $pdf->Cell(36, 9, 'C$ ' . number_format($total, 2), 1, 1, 'R', true);
    }

    private function pie(TCPDF $pdf): void
    {
        $pdf->SetY(-42);

        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', '', 9);

        $pdf->Cell(95, 6, 'Elaborar CK a nombre de Luis Joel Garcia', 0, 0, 'L');
        $pdf->Cell(95, 6, 'Gerente', 0, 1, 'R');

        $pdf->Ln(10);

        $pdf->Cell(95, 6, 'Ing. Luis J. Garcia B.', 0, 0, 'L');
        $pdf->Cell(95, 6, '____________________________', 0, 1, 'R');
    }
}
