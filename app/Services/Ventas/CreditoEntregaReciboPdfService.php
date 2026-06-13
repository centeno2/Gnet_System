<?php

namespace App\Services\Ventas;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TCPDF;

class CreditoEntregaReciboPdfService
{
    private const COLOR_PRIMARIO = [46, 139, 192];
    private const COLOR_TITULO = [26, 43, 66];
    private const COLOR_TEXTO = [95, 107, 122];
    private const COLOR_BORDE = [215, 228, 243];
    private const COLOR_FONDO = [240, 243, 247];
    private const COLOR_FILA = [247, 249, 252];

    public function generar(int $entregaCreditoId): string
    {
        $data = $this->obtenerEntrega($entregaCreditoId);
        abort_if(! $data, 404);

        $detalles = $this->obtenerDetalles($entregaCreditoId);
        abort_if($detalles->isEmpty(), 404);

        $nombreArchivo = 'recibo-entrega-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $data->Numero_Recibo) . '.pdf';

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
        $pdf->SetTitle('Recibo de entrega de credito');
        $pdf->SetSubject('Recibo de entrega de credito');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetCompression(true);
        $pdf->setFontSubsetting(false);
        $pdf->setJPEGQuality(76);
        $pdf->SetMargins(10, 8, 10);
        $pdf->SetFooterMargin(5);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();

        $this->encabezado($pdf, $data);
        $this->datosPrincipales($pdf, $data);
        $this->tablaDetalle($pdf, $data, $detalles);
        $this->observacion($pdf, (string) ($data->Observacion ?? ''));
        $this->firmas($pdf, $data);

        return $pdf->Output($nombreArchivo, 'S');
    }

    private function obtenerEntrega(int $entregaCreditoId): ?object
    {
        return DB::table('entrega_credito as ec')
            ->join('venta as v', 'v.Id_Venta', '=', 'ec.Id_Venta')
            ->join('credito as cr', 'cr.Id_Credito', '=', 'ec.Id_Credito')
            ->join('cliente as c', 'c.Id_Cliente', '=', 'v.Id_Cliente')
            ->leftJoin('persona as pc', 'pc.Id_Persona', '=', 'c.Id_Persona')
            ->leftJoin('usuario as u', 'u.Id_Usuario', '=', 'ec.Id_Usuario')
            ->leftJoin('trabajador as tu', 'tu.Id_Trabajador', '=', 'u.Id_Trabajador')
            ->leftJoin('persona as pu', 'pu.Id_Persona', '=', 'tu.Id_Persona')
            ->where('ec.Id_Entrega_Credito', $entregaCreditoId)
            ->where('v.Tipo_Venta', 'CREDITO')
            ->selectRaw("\n                ec.Id_Entrega_Credito,\n                ec.Numero_Recibo,\n                ec.Fecha_Entrega,\n                ec.Recibido_Por,\n                ec.Observacion,\n                ec.Estado as Estado_Recibo,\n                v.Id_Venta,\n                v.Numero_Factura,\n                v.Fecha_venta,\n                v.Total as Total_Venta,\n                cr.Id_Credito,\n                cr.Saldo_Actual,\n                c.Institucion,\n                c.Municipio,\n                c.Tipo_Cliente,\n                CONCAT_WS(' ', pc.Primer_Nombre, pc.Segundo_Nombre, pc.Primer_Apellido, pc.Segundo_Apellido) as Cliente_Natural,\n                COALESCE(NULLIF(TRIM(CONCAT_WS(' ', pu.Primer_Nombre, pu.Segundo_Nombre, pu.Primer_Apellido, pu.Segundo_Apellido)), ''), u.Nombre_Usuario, 'Usuario') as Usuario_Entrega\n            ")
            ->first();
    }

    private function obtenerDetalles(int $entregaCreditoId)
    {
        return DB::table('entrega_credito_detalle as ecd')
            ->join('entrega_credito as ec', 'ec.Id_Entrega_Credito', '=', 'ecd.Id_Entrega_Credito')
            ->join('venta as v', 'v.Id_Venta', '=', 'ec.Id_Venta')
            ->join('detalle_venta as dv', 'dv.Id_Detalle_Venta', '=', 'ecd.Id_Detalle_Venta')
            ->leftJoin('producto as p', 'p.Id_Producto', '=', 'dv.Id_Producto')
            ->leftJoin('producto_serie as ps', 'ps.id_producto_serie', '=', 'dv.Id_Producto_serie')
            ->leftJoin('servicio as s', 's.Id_Servicio', '=', 'dv.Id_Servicio')
            ->leftJoin('tarifa_copia as tc', 'tc.Id_Tarifa_Copia', '=', 'dv.Id_Tarifa_Copia')
            ->where('ecd.Id_Entrega_Credito', $entregaCreditoId)
            ->orderBy('ecd.Id_Entrega_Credito_Detalle')
            ->selectRaw("\n                v.Numero_Factura,\n                dv.Tipo_Detalle,\n                dv.Nombre_Formato,\n                dv.Formato_Copia,\n                dv.Lados_Copia,\n                COALESCE(NULLIF(TRIM(dv.Observacion), ''), '—') as Area_Item,\n                p.Nombre_Producto,\n                p.Modelo,\n                ps.Numero_Serie,\n                s.Nombre_Servicio,\n                tc.Nombre_Tarifa,\n                ecd.Cantidad_Total,\n                ecd.Cantidad_Pendiente_Anterior,\n                ecd.Cantidad_Entregada_Ahora,\n                ecd.Cantidad_Pendiente_Restante\n            ")
            ->get()
            ->map(function (object $fila) {
                $fila->Detalle_Nombre = $this->nombreDetalle($fila);
                return $fila;
            });
    }

    private function encabezado(TCPDF $pdf, object $data): void
    {
        [$fr, $fg, $fb] = self::COLOR_FONDO;
        [$br, $bg, $bb] = self::COLOR_BORDE;
        [$tr, $tg, $tb] = self::COLOR_TITULO;
        [$pr, $pg, $pb] = self::COLOR_PRIMARIO;

        $pdf->SetFillColor($fr, $fg, $fb);
        $pdf->SetDrawColor($br, $bg, $bb);
        $pdf->Rect(10, 8, 196, 28, 'DF');

        $logo = $this->logoParaPdf();

        if ($logo) {
            $pdf->Image($logo, 14, 12, 18, 18, 'JPG');
        }

        $pdf->SetXY($logo ? 38 : 14, 13);
        $pdf->SetTextColor($tr, $tg, $tb);
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(88, 7, 'GNET SYSTEM', 0, 1, 'L');

        $pdf->SetX($logo ? 38 : 14);
        $pdf->SetTextColor(95, 107, 122);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->Cell(88, 5, 'Comprobante de entrega de credito', 0, 1, 'L');

        $pdf->SetXY(122, 13);
        $pdf->SetTextColor($pr, $pg, $pb);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(76, 7, 'RECIBO DE ENTREGA', 0, 1, 'R');

        $pdf->SetXY(122, 23);
        $pdf->SetTextColor($tr, $tg, $tb);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(76, 5, (string) $data->Numero_Recibo, 0, 1, 'R');

        $pdf->SetY(44);
    }

    private function datosPrincipales(TCPDF $pdf, object $data): void
    {
        [$br, $bg, $bb] = self::COLOR_BORDE;
        [$fr, $fg, $fb] = self::COLOR_FILA;
        [$tr, $tg, $tb] = self::COLOR_TITULO;
        [$sr, $sg, $sb] = self::COLOR_TEXTO;

        $cliente = $this->clienteNombre($data);
        $fecha = $data->Fecha_Entrega ? Carbon::parse($data->Fecha_Entrega)->format('d/m/Y h:i A') : now()->format('d/m/Y h:i A');

        $pdf->SetDrawColor($br, $bg, $bb);
        $pdf->SetFillColor($fr, $fg, $fb);
        $pdf->Rect(10, 44, 196, 42, 'DF');

        $pdf->SetXY(14, 49);
        $pdf->SetTextColor($tr, $tg, $tb);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(188, 5, 'Recibimos conforme la entrega detallada en este comprobante.', 0, 1, 'L');

        $this->dato($pdf, 14, 58, 'Cliente', $cliente, 86);
        $this->dato($pdf, 110, 58, 'Fecha', $fecha, 86);
        $this->dato($pdf, 14, 66, 'Factura', (string) $data->Numero_Factura, 86);
        $this->dato($pdf, 110, 66, 'Credito', '#' . (string) $data->Id_Credito, 86);
        $this->dato($pdf, 14, 74, 'Recibido por', (string) $data->Recibido_Por, 86);
        $this->dato($pdf, 110, 74, 'Registrado por', (string) $data->Usuario_Entrega, 86);

        $pdf->SetY(94);
    }

    private function dato(TCPDF $pdf, float $x, float $y, string $label, string $valor, float $w): void
    {
        $pdf->SetXY($x, $y);
        $pdf->SetTextColor(95, 107, 122);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(22, 4, $label . ':', 0, 0, 'L');

        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->Cell($w - 22, 4, $this->cortar($valor, 48), 0, 0, 'L');
    }

    private function tablaDetalle(TCPDF $pdf, object $data, $detalles): void
    {
        $this->tablaHeader($pdf);

        $i = 0;
        foreach ($detalles as $detalle) {
            $nombre = $this->cortar((string) $detalle->Detalle_Nombre, 48);
            $area = trim((string) ($detalle->Area_Item ?? '—'));
            $area = $area !== '' ? $this->cortar($area, 28) : '—';

            $lineasNombre = max(1, (int) ceil(mb_strlen($nombre) / 38));
            $lineasArea = max(1, (int) ceil(mb_strlen($area) / 22));
            $alto = max(8, max($lineasNombre, $lineasArea) * 4.2);

            if ($pdf->GetY() + $alto + 34 > 260) {
                $pdf->AddPage();
                $this->tablaHeader($pdf);
            }

            $i++;
            $fill = $i % 2 === 0;
            $pdf->SetFillColor($fill ? 247 : 255, $fill ? 249 : 255, $fill ? 252 : 255);
            $pdf->SetTextColor(26, 43, 66);
            $pdf->SetDrawColor(215, 228, 243);
            $pdf->SetFont('helvetica', '', 6.8);

            $pdf->MultiCell(28, $alto, (string) $detalle->Numero_Factura, 1, 'L', true, 0);
            $pdf->MultiCell(52, $alto, $nombre, 1, 'L', true, 0);
            $pdf->MultiCell(32, $alto, $area, 1, 'L', true, 0);
            $pdf->MultiCell(18, $alto, $this->cantidadTexto((float) $detalle->Cantidad_Total), 1, 'C', true, 0);
            $pdf->MultiCell(22, $alto, $this->cantidadTexto((float) $detalle->Cantidad_Pendiente_Anterior), 1, 'C', true, 0);
            $pdf->MultiCell(23, $alto, $this->cantidadTexto((float) $detalle->Cantidad_Entregada_Ahora), 1, 'C', true, 0);
            $pdf->MultiCell(15, $alto, $this->cantidadTexto((float) $detalle->Cantidad_Pendiente_Restante), 1, 'C', true, 1);
        }

        $entregado = $detalles->sum(fn($fila) => (float) $fila->Cantidad_Entregada_Ahora);
        $pendienteRestante = $detalles->sum(fn($fila) => (float) $fila->Cantidad_Pendiente_Restante);

        $pdf->Ln(4);
        $pdf->SetDrawColor(215, 228, 243);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(240, 243, 247);
        $pdf->SetTextColor(26, 43, 66);
        $pdf->Cell(152, 7, 'Total entregado en este recibo', 1, 0, 'R', true);
        $pdf->Cell(38, 7, $this->cantidadTexto($entregado), 1, 1, 'C', true);

        $pdf->SetFillColor($pendienteRestante > 0 ? 255 : 240, $pendienteRestante > 0 ? 247 : 243, $pendienteRestante > 0 ? 237 : 247);
        $pdf->Cell(152, 7, 'Pendiente restante para proxima entrega', 1, 0, 'R', true);
        $pdf->Cell(38, 7, $this->cantidadTexto($pendienteRestante), 1, 1, 'C', true);
    }

    private function tablaHeader(TCPDF $pdf): void
    {
        if ($pdf->GetY() < 94) {
            $pdf->SetY(94);
        }

        $pdf->SetFont('helvetica', 'B', 6.8);
        $pdf->SetFillColor(46, 139, 192);
        $pdf->SetDrawColor(215, 228, 243);
        $pdf->SetTextColor(255, 255, 255);

        $pdf->Cell(28, 7, 'Factura', 1, 0, 'L', true);
        $pdf->Cell(52, 7, 'Detalle entregado', 1, 0, 'L', true);
        $pdf->Cell(32, 7, 'Area', 1, 0, 'L', true);
        $pdf->Cell(18, 7, 'Total', 1, 0, 'C', true);
        $pdf->Cell(22, 7, 'Pend. ant.', 1, 0, 'C', true);
        $pdf->Cell(23, 7, 'Entregado', 1, 0, 'C', true);
        $pdf->Cell(15, 7, 'Pend.', 1, 1, 'C', true);
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
        $pdf->Cell(190, 6, 'OBSERVACION GENERAL', 1, 1, 'L', true);

        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->MultiCell(190, 7, $this->cortar($observacion, 240), 1, 'L', false, 1);
    }

    private function firmas(TCPDF $pdf, object $data): void
    {
        if ($pdf->GetY() + 36 > 260) {
            $pdf->AddPage();
        }

        $pdf->Ln(18);
        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', '', 8);

        $y = $pdf->GetY();
        $pdf->Line(18, $y + 16, 82, $y + 16);
        $pdf->Line(124, $y + 16, 188, $y + 16);

        $pdf->SetXY(18, $y + 18);
        $pdf->Cell(64, 5, 'Entregado por', 0, 0, 'C');
        $pdf->SetXY(124, $y + 18);
        $pdf->Cell(64, 5, 'Recibi conforme', 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetXY(18, $y + 24);
        $pdf->Cell(64, 5, $this->cortar((string) $data->Usuario_Entrega, 42), 0, 0, 'C');
        $pdf->SetXY(124, $y + 24);
        $pdf->Cell(64, 5, $this->cortar((string) $data->Recibido_Por, 42), 0, 1, 'C');
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

            return trim($nombre . ' ' . $formato . ' ' . $lados);
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

        $optimizado = $directorio . '/logo-recibo-entrega-credito.jpg';

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
