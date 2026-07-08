<?php

namespace App\Services\Ventas;

use App\Models\CotizacionVenta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use TCPDF;

class CotizacionVentaPdfService
{
    private const AZUL = [21, 74, 143];
    private const ROJO = [220, 38, 38];
    private const FONDO_SUAVE = [250, 252, 255];

    public function generarDesdeCotizacion(CotizacionVenta $cotizacion): string
    {
        $cotizacion->loadMissing('detalles', 'cliente.persona');

        $cliente = $cotizacion->cliente;

        return $this->generar([
            'numero' => $cotizacion->Numero_Cotizacion,
            'cliente' => $cotizacion->Cliente_Nombre,
            'telefono' => $cliente?->telefono_facturacion ?: '',
            'correo' => $cliente?->correo_facturacion ?: '',
            'direccion' => $cliente?->direccion_facturacion ?: '',
            'ruc' => $cliente?->ruc_facturacion ?: '',
            'municipio' => $cotizacion->Municipio,
            'tipo_venta' => $cotizacion->Tipo_Venta,
            'tipo_cambio' => (float) $cotizacion->Tipo_Cambio,
            'subtotal' => (float) $cotizacion->Subtotal,
            'descuento' => (float) $cotizacion->Descuento,
            'total' => (float) $cotizacion->Total,
            'observacion' => $cotizacion->Observacion,
            'fecha' => $cotizacion->Fecha_Cotizacion?->toDateTimeString(),
            'vence' => $cotizacion->Fecha_Vencimiento?->toDateTimeString(),
            'validez_dias' => (int) $cotizacion->Plazo_Validez_Dias,
            'estado' => $cotizacion->Estado,
            'items' => $cotizacion->detalles->map(fn ($detalle) => [
                'descripcion' => $detalle->Descripcion,
                'cantidad' => (float) $detalle->Cantidad,
                'precio_unitario' => (float) $detalle->Precio_Unitario_Cotizado,
                'descuento_valor' => (float) $detalle->Descuento,
                'subtotal_bruto_valor' => (float) $detalle->Subtotal_Bruto,
                'subtotal_valor' => (float) $detalle->Subtotal,
            ])->values()->toArray(),
        ]);
    }

    public function generar(array $payload): string
    {
        $items = collect($payload['items'] ?? []);
        $nombreArchivo = 'cotizacion-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($payload['numero'] ?? 'proforma')) . '.pdf';

        $pdf = new class('L', 'mm', 'LETTER', true, 'UTF-8', false) extends TCPDF {
            public function Footer(): void
            {
                $this->SetY(-8);
                $this->SetFont('helvetica', '', 6);
                $this->SetTextColor(21, 74, 143);
                $this->Cell(0, 4, 'Imp. Mariana Telf.: 2772-1224 No. RUC: 441050577006H  O.T. 04862-2026 - 22 B  (C) No.6,001-7,100 AIMP/15/0010/2-2026 F. 260226', 0, 0, 'C');
            }
        };

        $pdf->SetCreator('Gnet System');
        $pdf->SetAuthor('Gnet System');
        $pdf->SetTitle('Cotizacion');
        $pdf->SetSubject('Cotizacion');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetCompression(true);
        $pdf->setFontSubsetting(false);
        $pdf->setJPEGQuality(76);
        $pdf->SetMargins(8, 7, 8);
        $pdf->SetFooterMargin(5);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $this->dibujarPagina($pdf, $payload, $items);

        return $pdf->Output($nombreArchivo, 'S');
    }

    private function dibujarPagina(TCPDF $pdf, array $payload, Collection $items): void
    {
        $this->marco($pdf);
        $this->encabezado($pdf, $payload);
        $this->datosCliente($pdf, $payload);
        $this->tabla($pdf, $items, $payload);
        $this->firmas($pdf);
    }

    private function marco(TCPDF $pdf): void
    {
        $pdf->SetDrawColor(...self::AZUL);
        $pdf->SetLineWidth(0.35);
        $pdf->RoundedRect(9, 8, 261, 191, 3, '1111');
    }

    private function encabezado(TCPDF $pdf, array $payload): void
    {
        $fecha = ! empty($payload['fecha']) ? Carbon::parse($payload['fecha']) : now();
        $tipoVenta = strtoupper((string) ($payload['tipo_venta'] ?? 'CONTADO'));

        $logo = $this->logoParaPdf();

        if ($logo) {
            $pdf->Image($logo, 15, 13, 18, 18, 'JPG');
        }

        $pdf->SetTextColor(...self::AZUL);
        $pdf->SetFont('helvetica', 'B', 21);
        $pdf->SetXY(36, 11);
        $pdf->Cell(38, 8, 'G-NET', 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(75, 12);
        $pdf->Cell(86, 4, 'SERVICIOS INFORMATICOS', 0, 1, 'L');
        $pdf->SetFont('helvetica', 'B', 6.5);
        $pdf->SetX(75);
        $pdf->MultiCell(102, 3.4, 'CENTRO DE COPIAS E IMPRESIONES DIGITALES, VENTA DE EQUIPOS, ACCESORIOS Y SUMINISTROS DE COMPUTACION Y OFICINA.', 0, 'L');
        $pdf->SetX(75);
        $pdf->MultiCell(112, 3.4, 'SOPORTE TECNICO, Reparacion y Mantenimiento de Computadoras portatiles y de escritorio. Instalacion y Actualizacion de Programas.', 0, 'L');
        $pdf->SetX(75);
        $pdf->MultiCell(112, 3.4, 'Diseno e Instalacion de Redes e Instalacion de Camaras de Seguridad Bajo de Control de Asistencia Biometrico.', 0, 'L');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY(36, 34);
        $pdf->Cell(110, 4, 'CEL. 8339-6054 / E-MAIL: gnetservicomp@gmail.com / No. RUC 441081284005P', 0, 1, 'L');
        $pdf->SetX(36);
        $pdf->Cell(115, 4, 'PARQUE DARIO 10 MTS AL NORTE, MATAGALPA, NIC', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 6.5);
        $pdf->SetX(36);
        $pdf->Cell(110, 4, 'Somos Proveedores del Estado', 0, 1, 'L');

        $this->fechaBox($pdf, 196, 13, 'DIA', $fecha->format('d'), 17);
        $this->fechaBox($pdf, 214, 13, 'MES', $fecha->format('m'), 20);
        $this->fechaBox($pdf, 235, 13, 'ANO', $fecha->format('Y'), 26);

        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->SetTextColor(...self::AZUL);
        $pdf->SetXY(188, 30);
        $pdf->Cell(42, 8, 'COTIZACION', 0, 0, 'L');
        $pdf->SetTextColor(...self::ROJO);
        $pdf->SetX(232);
        $pdf->Cell(30, 8, 'No. ' . $this->numeroCorto((string) ($payload['numero'] ?? '')), 0, 1, 'R');

        $this->checkOpcion($pdf, 197, 43, 'CONTADO', $tipoVenta === 'CONTADO');
        $this->checkOpcion($pdf, 229, 43, 'CREDITO', $tipoVenta === 'CREDITO');
    }

    private function datosCliente(TCPDF $pdf, array $payload): void
    {
        $cliente = $this->cortar((string) ($payload['cliente'] ?? 'Consumidor final'), 80);
        $telefono = $this->cortar((string) ($payload['telefono'] ?? ''), 26);
        $municipio = $this->cortar((string) ($payload['municipio'] ?? ''), 24);
        $ruc = $this->cortar((string) ($payload['ruc'] ?? ''), 28);

        $pdf->SetDrawColor(...self::AZUL);
        $pdf->SetTextColor(...self::AZUL);
        $pdf->SetLineWidth(0.25);

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY(15, 49);
        $pdf->Cell(19, 5, 'CLIENTE:', 0, 0, 'L');
        $pdf->Line(35, 53, 178, 53);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(36, 48.9);
        $pdf->Cell(142, 5, $cliente, 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY(182, 49);
        $pdf->Cell(11, 5, 'TEL:', 0, 0, 'L');
        $pdf->Line(194, 53, 263, 53);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(195, 48.9);
        $pdf->Cell(68, 5, $telefono !== '' ? $telefono : '-', 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY(15, 57);
        $pdf->Cell(23, 5, 'MUNICIPIO:', 0, 0, 'L');
        $pdf->Line(39, 61, 92, 61);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(40, 56.9);
        $pdf->Cell(52, 5, $municipio !== '' ? $municipio : '-', 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY(97, 57);
        $pdf->Cell(11, 5, 'RUC:', 0, 0, 'L');
        $pdf->Line(110, 61, 170, 61);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(111, 56.9);
        $pdf->Cell(58, 5, $ruc !== '' ? $ruc : '-', 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY(176, 57);
        $pdf->Cell(30, 5, 'VALIDEZ:', 0, 0, 'L');
        $pdf->Line(204, 61, 263, 61);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY(205, 56.9);
        $pdf->Cell(58, 5, ((int) ($payload['validez_dias'] ?? 15)) . ' dias', 0, 1, 'L');
    }

    private function tabla(TCPDF $pdf, Collection $items, array $payload): void
    {
        $x = 15;
        $y = 69;
        $wCant = 23;
        $wDesc = 155;
        $wUnit = 38;
        $wTotal = 39;
        $rowH = 8.4;
        $rowsPerPage = 12;
        $items = $items->values();
        $page = 0;

        do {
            if ($page > 0) {
                $pdf->AddPage();
                $this->marco($pdf);
                $this->encabezado($pdf, $payload);
                $this->datosCliente($pdf, $payload);
            }

            $this->tablaHeader($pdf, $x, $y, $wCant, $wDesc, $wUnit, $wTotal);
            $currentY = $y + 9;

            for ($i = 0; $i < $rowsPerPage; $i++) {
                $item = $items->get(($page * $rowsPerPage) + $i);
                $this->tablaFila($pdf, $x, $currentY, $rowH, $wCant, $wDesc, $wUnit, $wTotal, $item);
                $currentY += $rowH;
            }

            if (($page + 1) * $rowsPerPage >= max($items->count(), 1)) {
                $this->totalTabla($pdf, $x, $currentY, $wCant, $wDesc, $wUnit, $wTotal, (float) ($payload['total'] ?? 0));
                break;
            }

            $page++;
        } while (true);
    }

    private function tablaHeader(TCPDF $pdf, float $x, float $y, float $wCant, float $wDesc, float $wUnit, float $wTotal): void
    {
        $pdf->SetDrawColor(...self::AZUL);
        $pdf->SetTextColor(...self::AZUL);
        $pdf->SetFillColor(...self::FONDO_SUAVE);
        $pdf->SetLineWidth(0.28);
        $pdf->SetFont('helvetica', 'B', 8);

        $pdf->SetXY($x, $y);
        $pdf->Cell($wCant, 9, 'CANT.', 1, 0, 'C', true);
        $pdf->Cell($wDesc, 9, 'DESCRIPCION', 1, 0, 'C', true);
        $pdf->Cell($wUnit, 9, 'P. UNIT.', 1, 0, 'C', true);
        $pdf->Cell($wTotal, 9, 'TOTAL', 1, 1, 'C', true);
    }

    private function tablaFila(
        TCPDF $pdf,
        float $x,
        float $y,
        float $h,
        float $wCant,
        float $wDesc,
        float $wUnit,
        float $wTotal,
        mixed $item
    ): void {
        $pdf->SetDrawColor(...self::AZUL);
        $pdf->SetTextColor(...self::AZUL);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetLineWidth(0.2);
        $pdf->SetFont('helvetica', '', 7.4);

        $descripcion = '';
        $cantidad = '';
        $unitario = '';
        $total = '';

        if ($item) {
            $descripcion = $this->cortar((string) ($item['descripcion'] ?? 'Item'), 86);
            $cantidad = $this->cantidadTexto((float) ($item['cantidad'] ?? 1));
            $unitario = 'C$ ' . number_format((float) ($item['precio_unitario'] ?? 0), 2);
            $total = 'C$ ' . number_format((float) ($item['subtotal_valor'] ?? 0), 2);
        }

        $pdf->SetXY($x, $y);
        $pdf->Cell($wCant, $h, $cantidad, 1, 0, 'C');
        $pdf->Cell($wDesc, $h, $descripcion, 1, 0, 'L');
        $pdf->Cell($wUnit, $h, $unitario, 1, 0, 'R');
        $pdf->Cell($wTotal, $h, $total, 1, 1, 'R');
    }

    private function totalTabla(TCPDF $pdf, float $x, float $y, float $wCant, float $wDesc, float $wUnit, float $wTotal, float $total): void
    {
        $pdf->SetDrawColor(...self::AZUL);
        $pdf->SetTextColor(...self::AZUL);
        $pdf->SetFillColor(...self::FONDO_SUAVE);
        $pdf->SetFont('helvetica', 'B', 9);

        $pdf->SetXY($x, $y);
        $pdf->Cell($wCant + $wDesc, 9, '', 1, 0, 'C');
        $pdf->Cell($wUnit, 9, 'TOTAL C$', 1, 0, 'C', true);
        $pdf->Cell($wTotal, 9, 'C$ ' . number_format($total, 2), 1, 1, 'R', true);
    }

    private function firmas(TCPDF $pdf): void
    {
        $pdf->SetTextColor(...self::AZUL);
        $pdf->SetDrawColor(...self::AZUL);
        $pdf->SetFont('helvetica', '', 7);

        $y = 188;
        $pdf->Line(23, $y, 92, $y);
        $pdf->Line(186, $y, 255, $y);

        $pdf->SetXY(23, $y + 2);
        $pdf->Cell(69, 4, 'Firma Autorizada', 0, 0, 'C');
        $pdf->SetXY(186, $y + 2);
        $pdf->Cell(69, 4, 'Firma Cliente.', 0, 1, 'C');
    }

    private function fechaBox(TCPDF $pdf, float $x, float $y, string $label, string $value, float $w): void
    {
        $pdf->SetDrawColor(...self::AZUL);
        $pdf->SetTextColor(...self::AZUL);
        $pdf->SetLineWidth(0.25);
        $pdf->Rect($x, $y, $w, 15);
        $pdf->SetFont('helvetica', 'B', 5.5);
        $pdf->SetXY($x, $y + 1);
        $pdf->Cell($w, 3.5, $label, 0, 1, 'C');
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetX($x);
        $pdf->Cell($w, 6, $value, 0, 1, 'C');
    }

    private function checkOpcion(TCPDF $pdf, float $x, float $y, string $label, bool $activo): void
    {
        $pdf->SetDrawColor(...self::AZUL);
        $pdf->SetTextColor(...self::AZUL);
        $pdf->SetLineWidth(0.25);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY($x, $y);
        $pdf->Cell(21, 5, $label, 0, 0, 'L');
        $pdf->Rect($x + 22, $y + 0.6, 4.5, 4.5);

        if ($activo) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetXY($x + 22, $y - 0.1);
            $pdf->Cell(4.5, 4.5, 'X', 0, 0, 'C');
        }
    }

    private function numeroCorto(string $numero): string
    {
        $soloDigitos = preg_replace('/\D+/', '', $numero) ?: '';

        if ($soloDigitos !== '') {
            return substr($soloDigitos, -6);
        }

        return $this->cortar($numero, 12);
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

        $optimizado = $directorio . '/logo-cotizacion-formato.jpg';

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
        imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $anchoOriginal, $altoOriginal);
        imagejpeg($lienzo, $optimizado, 78);

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
