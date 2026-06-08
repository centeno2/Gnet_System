<?php

namespace App\Services\Ventas;

use Illuminate\Support\Collection;
use TCPDF;

class CotizacionVentaPdfService
{
    private const COLOR_PRIMARIO = [46, 139, 192];
    private const COLOR_TITULO = [26, 43, 66];
    private const COLOR_TEXTO = [95, 107, 122];
    private const COLOR_BORDE = [215, 228, 243];
    private const COLOR_FONDO = [240, 243, 247];
    private const COLOR_FILA = [247, 249, 252];

    public function generar(array $payload): string
    {
        $items = collect($payload['items'] ?? []);
        $nombreArchivo = 'cotizacion-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($payload['numero'] ?? 'proforma')) . '.pdf';

        $pdf = new class('P', 'mm', 'LETTER', true, 'UTF-8', false) extends TCPDF {
            public function Footer(): void
            {
                $this->SetY(-9);
                $this->SetFont('helvetica', '', 7);
                $this->SetTextColor(95, 107, 122);
                $this->Cell(0, 5, 'Gnet System | Pagina ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
            }
        };

        $pdf->SetCreator('Gnet System');
        $pdf->SetAuthor('Gnet System');
        $pdf->SetTitle('Cotización');
        $pdf->SetSubject('Cotización');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetCompression(true);
        $pdf->setFontSubsetting(false);
        $pdf->setJPEGQuality(76);
        $pdf->SetMargins(10, 8, 10);
        $pdf->SetFooterMargin(5);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();

        $this->encabezado($pdf, $payload);
        $this->datosCotizacion($pdf, $payload);
        $this->tabla($pdf, $items, $payload);
        $this->observacion($pdf, (string) ($payload['observacion'] ?? ''));
        $this->firma($pdf);

        return $pdf->Output($nombreArchivo, 'S');
    }

    private function encabezado(TCPDF $pdf, array $payload): void
    {
        [$fr, $fg, $fb] = self::COLOR_FONDO;
        [$br, $bg, $bb] = self::COLOR_BORDE;
        [$tr, $tg, $tb] = self::COLOR_TITULO;
        [$pr, $pg, $pb] = self::COLOR_PRIMARIO;

        $pdf->SetFillColor($fr, $fg, $fb);
        $pdf->SetDrawColor($br, $bg, $bb);
        $pdf->Rect(10, 8, 196, 24, 'DF');

        $logo = $this->logoParaPdf();

        if ($logo) {
            $pdf->Image($logo, 14, 11, 18, 18, 'JPG');
        }

        $pdf->SetXY($logo ? 38 : 14, 12);
        $pdf->SetTextColor($tr, $tg, $tb);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(86, 7, 'GNET SYSTEM', 0, 0, 'L');

        $pdf->SetXY(128, 12);
        $pdf->SetTextColor($pr, $pg, $pb);
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(72, 7, 'COTIZACION', 0, 1, 'R');

        $pdf->SetXY(128, 21);
        $pdf->SetTextColor(95, 107, 122);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(72, 5, (string) ($payload['numero'] ?? 'PROFORMA'), 0, 1, 'R');

        $pdf->SetY(38);
    }

    private function datosCotizacion(TCPDF $pdf, array $payload): void
    {
        [$br, $bg, $bb] = self::COLOR_BORDE;
        [$fr, $fg, $fb] = self::COLOR_FILA;
        [$tr, $tg, $tb] = self::COLOR_TITULO;
        [$sr, $sg, $sb] = self::COLOR_TEXTO;

        $pdf->SetDrawColor($br, $bg, $bb);
        $pdf->SetFillColor($fr, $fg, $fb);
        $pdf->Rect(10, 38, 196, 20, 'DF');

        $cliente = $this->cortar((string) ($payload['cliente'] ?? 'Consumidor final'), 65);
        $numero = (string) ($payload['numero'] ?? 'PROFORMA');
        $fecha = now()->format('d/m/Y h:i A');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor($sr, $sg, $sb);

        $pdf->SetXY(14, 41);
        $pdf->Cell(70, 4, 'DIRIGIDO A', 0, 0, 'L');
        $pdf->SetX(93);
        $pdf->Cell(48, 4, 'NO. COTIZACION', 0, 0, 'L');
        $pdf->SetX(153);
        $pdf->Cell(45, 4, 'FECHA', 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor($tr, $tg, $tb);

        $pdf->SetXY(14, 47);
        $pdf->Cell(70, 5, $cliente, 0, 0, 'L');
        $pdf->SetX(93);
        $pdf->Cell(48, 5, $numero, 0, 0, 'L');
        $pdf->SetX(153);
        $pdf->Cell(45, 5, $fecha, 0, 1, 'L');

        if (! empty($payload['municipio'])) {
            $pdf->SetXY(14, 53);
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->SetTextColor($sr, $sg, $sb);
            $pdf->Cell(20, 4, 'MUNICIPIO:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor($tr, $tg, $tb);
            $pdf->Cell(80, 4, $this->cortar((string) $payload['municipio'], 60), 0, 1, 'L');
        }

        $pdf->SetY(65);
    }

    private function tabla(TCPDF $pdf, Collection $items, array $payload): void
    {
        $this->tablaHeader($pdf);

        $numeroFila = 0;

        foreach ($items as $item) {
            $descripcion = $this->cortar((string) ($item['descripcion'] ?? 'Item'), 105);
            $cantidad = (float) ($item['cantidad'] ?? 1);
            $precio = (float) ($item['precio_unitario'] ?? 0);
            $subtotal = (float) ($item['subtotal_valor'] ?? 0);

            $lineas = max(1, (int) ceil(mb_strlen($descripcion) / 63));
            $alto = max(7, $lineas * 4.3);

            if ($pdf->GetY() + $alto + 24 > 260) {
                $pdf->AddPage();
                $this->tablaHeader($pdf);
            }

            $numeroFila++;
            $fill = $numeroFila % 2 === 0;
            $pdf->SetFillColor($fill ? 247 : 255, $fill ? 249 : 255, $fill ? 252 : 255);
            $pdf->SetTextColor(26, 43, 66);
            $pdf->SetDrawColor(215, 228, 243);
            $pdf->SetFont('helvetica', '', 7.5);

            $pdf->MultiCell(18, $alto, $this->cantidadTexto($cantidad), 1, 'C', true, 0);
            $pdf->MultiCell(106, $alto, $descripcion, 1, 'L', true, 0);
            $pdf->MultiCell(30, $alto, 'C$ ' . number_format($precio, 2), 1, 'R', true, 0);
            $pdf->MultiCell(36, $alto, 'C$ ' . number_format($subtotal, 2), 1, 'R', true, 1);
        }

        if ($items->isEmpty()) {
            $pdf->SetTextColor(95, 107, 122);
            $pdf->SetFillColor(247, 249, 252);
            $pdf->Cell(190, 8, 'No hay items agregados.', 1, 1, 'C', true);
        }

        $this->filasTotales($pdf, $payload);
    }

    private function tablaHeader(TCPDF $pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetFillColor(46, 139, 192);
        $pdf->SetDrawColor(215, 228, 243);
        $pdf->SetTextColor(255, 255, 255);

        $pdf->Cell(18, 7, 'Cant.', 1, 0, 'C', true);
        $pdf->Cell(106, 7, 'Descripcion', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'P/Unit', 1, 0, 'R', true);
        $pdf->Cell(36, 7, 'Subtotal', 1, 1, 'R', true);
    }

    private function filasTotales(TCPDF $pdf, array $payload): void
    {
        $subtotal = (float) ($payload['subtotal'] ?? 0);
        $descuento = (float) ($payload['descuento'] ?? 0);
        $total = (float) ($payload['total'] ?? 0);

        if ($pdf->GetY() + 24 > 260) {
            $pdf->AddPage();
        }

        $pdf->SetDrawColor(215, 228, 243);
        $pdf->SetFont('helvetica', 'B', 8);

        $this->filaTotal($pdf, 'Subtotal', $subtotal, false);
        $this->filaTotal($pdf, 'Descuento', $descuento, false);
        $this->filaTotal($pdf, 'TOTAL', $total, true);
    }

    private function filaTotal(TCPDF $pdf, string $label, float $monto, bool $principal): void
    {
        if ($principal) {
            $pdf->SetFillColor(46, 139, 192);
            $pdf->SetTextColor(255, 255, 255);
        } else {
            $pdf->SetFillColor(240, 243, 247);
            $pdf->SetTextColor(26, 43, 66);
        }

        $pdf->Cell(154, 7, $label, 1, 0, 'R', true);
        $pdf->Cell(36, 7, 'C$ ' . number_format($monto, 2), 1, 1, 'R', true);
    }

    private function observacion(TCPDF $pdf, string $observacion): void
    {
        $observacion = trim($observacion);

        if ($observacion === '') {
            return;
        }

        if ($pdf->GetY() + 18 > 260) {
            $pdf->AddPage();
        }

        $pdf->Ln(5);
        $pdf->SetDrawColor(215, 228, 243);
        $pdf->SetFillColor(247, 249, 252);
        $pdf->SetTextColor(95, 107, 122);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(190, 6, 'OBSERVACION', 1, 1, 'L', true);

        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->MultiCell(190, 7, $this->cortar($observacion, 240), 1, 'L', false, 1);
    }

    private function firma(TCPDF $pdf): void
    {
        if ($pdf->GetY() + 22 > 260) {
            $pdf->AddPage();
        }

        $pdf->Ln(10);
        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', '', 8);

        $pdf->Cell(95, 5, 'Cotizacion generada por GNET System', 0, 0, 'L');
        $pdf->Cell(95, 5, 'Firma / Autorizado: __________________________', 0, 1, 'R');
    }

    private function logoOriginalPath(): ?string
    {
        $rutas = [
            public_path('img/gnetlogo.png'),
            public_path('img/gnetlogo.jpg'),
            public_path('img/logo.png'),
            public_path('img/logo.jpg'),
            public_path('images/gnetlogo.png'),
            public_path('images/logo.png'),
            public_path('assets/img/gnetlogo.png'),
            public_path('assets/img/logo.png'),
            public_path('logo.png'),
        ];

        foreach ($rutas as $ruta) {
            if (is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    private function logoParaPdf(): ?string
    {
        $original = $this->logoOriginalPath();

        if (! $original || ! is_file($original)) {
            return null;
        }

        $directorio = storage_path('app/reportes');

        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $optimizado = $directorio . '/logo-cotizacion-pdf.jpg';

        if (is_file($optimizado) && filemtime($optimizado) >= filemtime($original)) {
            return $optimizado;
        }

        if (! extension_loaded('gd')) {
            $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));

            return in_array($extension, ['jpg', 'jpeg'], true) ? $original : null;
        }

        $imagen = $this->crearImagenDesdeArchivo($original);

        if (! $imagen) {
            return null;
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

    private function crearImagenDesdeArchivo(string $ruta)
    {
        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => @imagecreatefrompng($ruta),
            'jpg', 'jpeg' => @imagecreatefromjpeg($ruta),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($ruta) : false,
            default => false,
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
