<?php

namespace App\Services\Reportes\Base;

use Illuminate\Support\Collection;

class BasePdfReporteService
{
    public function generar(BaseReporteService $reporte): string
    {
        $datos = $reporte->datos();
        $filas = $reporte->filas($datos);
        $resumen = $reporte->resumen($datos);

        $pdf = new class('L', 'mm', 'LETTER', true, 'UTF-8', false) extends \TCPDF {
            public function Footer(): void
            {
                $this->SetY(-9);
                $this->SetFont('helvetica', '', 7);
                $this->SetTextColor(95, 107, 122);

                $this->Cell(
                    0,
                    5,
                    'Gnet System | Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(),
                    0,
                    0,
                    'R'
                );
            }
        };

        $pdf->SetCreator('Gnet System');
        $pdf->SetAuthor('Gnet System');
        $pdf->SetTitle($reporte->titulo());
        $pdf->SetSubject($reporte->titulo());

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetCompression(true);

        $pdf->SetMargins(8, 7, 8);
        $pdf->SetFooterMargin(5);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();

        $this->encabezado($pdf, $reporte, $resumen);
        $this->tabla($pdf, $reporte, $filas);

        return $pdf->Output($reporte->nombreArchivo() . '.pdf', 'S');
    }

    private function encabezado(\TCPDF $pdf, BaseReporteService $reporte, array $resumen): void
    {
        $pdf->SetFillColor(240, 243, 247);
        $pdf->SetDrawColor(215, 228, 243);
        $pdf->Rect(8, 7, 263, 25, 'DF');

        $logo = $this->logoParaPdf($reporte);

        if ($logo) {
            $pdf->Image($logo, 13, 10, 18, 18, 'JPG');
        } else {
            $pdf->SetTextColor(46, 139, 192);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetXY(13, 15);
            $pdf->Cell(18, 6, 'GNET', 0, 0, 'C');
        }

        $pdf->SetXY(37, 11);
        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(180, 7, $reporte->titulo(), 0, 1, 'L');

        $pdf->SetX(37);
        $pdf->SetTextColor(95, 107, 122);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(180, 5, 'Generado el: ' . now()->format('d/m/Y h:i A'), 0, 1, 'L');

        $x = 8;
        $y = 36;
        $w = 51;
        $h = 13;

        foreach ($resumen as $label => $value) {
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetDrawColor(215, 228, 243);
            $pdf->Rect($x, $y, $w, $h, 'DF');

            $pdf->SetXY($x + 2, $y + 1.5);
            $pdf->SetTextColor(95, 107, 122);
            $pdf->SetFont('helvetica', 'B', 6.3);
            $pdf->Cell($w - 4, 3.5, mb_strtoupper((string) $label), 0, 1, 'C');

            $pdf->SetX($x + 2);
            $pdf->SetTextColor(26, 43, 66);
            $pdf->SetFont('helvetica', 'B', 8.5);
            $pdf->Cell($w - 4, 4.5, (string) $value, 0, 1, 'C');

            $x += 53;

            if ($x > 230) {
                break;
            }
        }

        $pdf->SetY(53);
    }

    private function tabla(\TCPDF $pdf, BaseReporteService $reporte, Collection $filas): void
    {
        $columnas = $reporte->columnas();

        $this->tablaHeader($pdf, $columnas);

        $pdf->SetFont('helvetica', '', 6.5);

        $numeroFila = 0;

        foreach ($filas as $fila) {
            if ($pdf->GetY() > 190) {
                $pdf->AddPage();
                $this->tablaHeader($pdf, $columnas);
                $pdf->SetFont('helvetica', '', 6.5);
            }

            $numeroFila++;
            $fill = $numeroFila % 2 === 0;

            $pdf->SetFillColor($fill ? 247 : 255, $fill ? 249 : 255, $fill ? 252 : 255);
            $pdf->SetTextColor(26, 43, 66);

            foreach ($columnas as $columna) {
                $key = $columna['key'];
                $tipo = $columna['tipo'] ?? 'text';
                $ancho = $columna['pdf'] ?? 20;
                $align = $columna['align_pdf'] ?? 'L';

                $valor = data_get($fila, $key, '');

                if ($tipo === 'badge') {
                    $colores = $reporte->colorEstado((string) $valor);
                    [$r, $g, $b] = $this->hexToRgb($colores['texto']);

                    $pdf->SetTextColor($r, $g, $b);
                    $pdf->SetFont('helvetica', 'B', 6.5);
                    $pdf->Cell($ancho, 6, (string) $valor, 0, 0, 'C', true);
                    $pdf->SetFont('helvetica', '', 6.5);
                    $pdf->SetTextColor(26, 43, 66);

                    continue;
                }

                $texto = $reporte->valorFormateado($valor, $tipo);

                if ($tipo === 'text') {
                    $texto = $this->cortar($texto, $columna['limit'] ?? 28);
                }

                $pdf->Cell($ancho, 6, $texto, 0, 0, $align, true);
            }

            $pdf->Ln();
        }

        if ($filas->isEmpty()) {
            $pdf->SetTextColor(95, 107, 122);
            $pdf->SetFillColor(247, 249, 252);
            $pdf->Cell(263, 8, 'No hay datos disponibles para mostrar.', 0, 1, 'C', true);
        }
    }

    private function tablaHeader(\TCPDF $pdf, array $columnas): void
    {
        $pdf->SetFont('helvetica', 'B', 6.8);
        $pdf->SetFillColor(46, 139, 192);
        $pdf->SetTextColor(255, 255, 255);

        foreach ($columnas as $columna) {
            $pdf->Cell($columna['pdf'] ?? 20, 6.5, $columna['label'], 0, 0, 'C', true);
        }

        $pdf->Ln();
    }

    private function logoParaPdf(BaseReporteService $reporte): ?string
    {
        $original = $reporte->logoPath();

        if (! $original || ! is_file($original)) {
            return null;
        }

        $directorio = storage_path('app/reportes');

        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $optimizado = $directorio . '/logo-pdf.jpg';

        if (is_file($optimizado) && filemtime($optimizado) >= filemtime($original)) {
            return $optimizado;
        }

        if (! extension_loaded('gd')) {
            return $original;
        }

        $imagen = @imagecreatefrompng($original);

        if (! $imagen) {
            return $original;
        }

        $anchoOriginal = imagesx($imagen);
        $altoOriginal = imagesy($imagen);

        $maximo = 160;
        $escala = min($maximo / $anchoOriginal, $maximo / $altoOriginal, 1);

        $nuevoAncho = max(1, (int) round($anchoOriginal * $escala));
        $nuevoAlto = max(1, (int) round($altoOriginal * $escala));

        $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

        $blanco = imagecolorallocate($lienzo, 255, 255, 255);
        imagefilledrectangle($lienzo, 0, 0, $nuevoAncho, $nuevoAlto, $blanco);

        imagecopyresampled(
            $lienzo,
            $imagen,
            0,
            0,
            0,
            0,
            $nuevoAncho,
            $nuevoAlto,
            $anchoOriginal,
            $altoOriginal
        );

        imagejpeg($lienzo, $optimizado, 78);

        imagedestroy($imagen);
        imagedestroy($lienzo);

        return $optimizado;
    }

    private function cortar(string $texto, int $limite): string
    {
        $texto = trim($texto);

        if (mb_strlen($texto) <= $limite) {
            return $texto;
        }

        return mb_substr($texto, 0, $limite - 3) . '...';
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
