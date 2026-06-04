<?php

namespace App\Services\Ventas;

use Illuminate\Support\Collection;
use TCPDF;

class CotizacionVentaPdfService
{
    public function generar(array $payload): string
    {
        $items = collect($payload['items'] ?? []);

        $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);

        $pdf->SetCreator('Gnet System');
        $pdf->SetAuthor('Gnet System');
        $pdf->SetTitle('Cotización');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(12, 10, 12);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();

        $this->encabezado($pdf, $payload);
        $this->tabla($pdf, $items);
        $this->totales($pdf, $payload);
        $this->firma($pdf);

        return $pdf->Output('cotizacion.pdf', 'S');
    }

    private function encabezado(TCPDF $pdf, array $payload): void
    {
        $logo = $this->logoPath();

        if ($logo) {
            $pdf->Image($logo, 14, 12, 20, 20, '', '', '', false, 150);
        }

        $pdf->SetFillColor(46, 139, 192);
        $pdf->Rect(42, 12, 157, 14, 'F');

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(42, 15);
        $pdf->Cell(154, 8, 'COTIZACIÓN', 0, 1, 'R');

        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->SetXY(42, 31);
        $pdf->Cell(157, 6, 'GNET SYSTEM', 0, 1, 'R');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetX(42);
        $pdf->Cell(157, 5, 'Parque Dario 1C. Norte y 20 vrs. Oeste, Matagalpa, Nicaragua', 0, 1, 'R');

        $pdf->SetX(42);
        $pdf->Cell(157, 5, 'Tel: 8737-1426 / Email: gnetservicomp@gmail.com', 0, 1, 'R');

        $pdf->Ln(7);

        $pdf->SetFillColor(240, 243, 247);
        $pdf->SetDrawColor(215, 228, 243);
        $pdf->SetTextColor(26, 43, 66);

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(25, 8, 'Cliente:', 1, 0, 'L', true);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(95, 8, $this->cortar((string) ($payload['cliente'] ?? 'Consumidor final'), 55), 1, 0, 'L');

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(22, 8, 'Fecha:', 1, 0, 'L', true);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(48, 8, now()->format('d/m/Y h:i A'), 1, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(25, 8, 'No.:', 1, 0, 'L', true);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(165, 8, (string) ($payload['numero'] ?? 'PROFORMA'), 1, 1, 'L');

        if (! empty($payload['municipio'])) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell(25, 8, 'Municipio:', 1, 0, 'L', true);

            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(165, 8, $this->cortar((string) $payload['municipio'], 95), 1, 1, 'L');
        }

        $pdf->Ln(5);
    }

    private function tabla(TCPDF $pdf, Collection $items): void
    {
        $this->tablaHeader($pdf);

        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', '', 8);

        foreach ($items as $item) {
            $descripcion = (string) ($item['descripcion'] ?? 'Item');
            $cantidad = (int) ($item['cantidad'] ?? 1);
            $precio = (float) ($item['precio_unitario'] ?? 0);
            $subtotal = (float) ($item['subtotal_valor'] ?? 0);

            $alto = max(8, (int) ceil(mb_strlen($descripcion) / 58) * 5);

            if ($pdf->GetY() + $alto > 246) {
                $pdf->AddPage();
                $this->tablaHeader($pdf);
            }

            $pdf->SetFillColor(255, 255, 255);

            $pdf->MultiCell(18, $alto, (string) $cantidad, 1, 'C', true, 0);
            $pdf->MultiCell(104, $alto, $this->cortar($descripcion, 120), 1, 'L', true, 0);
            $pdf->MultiCell(32, $alto, 'C$ ' . number_format($precio, 2), 1, 'R', true, 0);
            $pdf->MultiCell(36, $alto, 'C$ ' . number_format($subtotal, 2), 1, 'R', true, 1);
        }

        if ($items->isEmpty()) {
            $pdf->Cell(190, 9, 'No hay items agregados.', 1, 1, 'C');
        }
    }

    private function tablaHeader(TCPDF $pdf): void
    {
        $pdf->SetFillColor(46, 139, 192);
        $pdf->SetDrawColor(215, 228, 243);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);

        $pdf->Cell(18, 8, 'Cant.', 1, 0, 'C', true);
        $pdf->Cell(104, 8, 'Descripción', 1, 0, 'C', true);
        $pdf->Cell(32, 8, 'P/Unit', 1, 0, 'C', true);
        $pdf->Cell(36, 8, 'Subtotal', 1, 1, 'C', true);
    }

    private function totales(TCPDF $pdf, array $payload): void
    {
        $pdf->Ln(6);

        $subtotal = (float) ($payload['subtotal'] ?? 0);
        $descuento = (float) ($payload['descuento'] ?? 0);
        $total = (float) ($payload['total'] ?? 0);

        $pdf->SetDrawColor(215, 228, 243);
        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', 'B', 9);

        $pdf->Cell(118, 8, '', 0, 0);
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

    private function firma(TCPDF $pdf): void
    {
        if ($pdf->GetY() > 220) {
            $pdf->AddPage();
        }

        $pdf->SetY(235);

        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', '', 9);

        $pdf->Cell(95, 6, 'Elaborado por GNET System', 0, 0, 'L');
        $pdf->Cell(95, 6, '____________________________', 0, 1, 'R');

        $pdf->Cell(95, 6, '', 0, 0, 'L');
        $pdf->Cell(95, 6, 'Autorizado', 0, 1, 'R');
    }

    private function logoPath(): ?string
    {
        $rutas = [
            public_path('img/gnetlogo.png'),
            public_path('img/gnetlogo.jpg'),
            public_path('images/logo.png'),
            public_path('img/logo.png'),
            public_path('logo.png'),
        ];

        foreach ($rutas as $ruta) {
            if (is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    private function cortar(string $texto, int $limite): string
    {
        $texto = trim($texto);

        return mb_strlen($texto) <= $limite
            ? $texto
            : mb_substr($texto, 0, $limite - 3) . '...';
    }
}
