<?php

namespace App\Services\Compras;

use App\Models\Banco;
use App\Models\CategoriaProducto;
use App\Models\Compra;
use App\Models\CuentaBancaria;
use App\Models\DetalleCompra;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use TCPDF;

class CompraComprobantePdfService
{
    private const COLOR_PRIMARIO = [46, 139, 192];
    private const COLOR_TITULO = [26, 43, 66];
    private const COLOR_TEXTO = [95, 107, 122];
    private const COLOR_BORDE = [215, 228, 243];
    private const COLOR_FONDO = [240, 243, 247];
    private const COLOR_FILA = [247, 249, 252];

    public function generar(int $idCompra): array
    {
        $compra = Compra::query()->find($idCompra);

        abort_if(! $compra, 404);

        $detalles = $this->detallesCompra((int) $compra->Id_Compra);
        $payload = $this->payload($compra, $detalles);
        $nombreArchivo = 'compra-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($compra->Numero_Compra ?? $compra->Id_Compra)) . '.pdf';

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
        $pdf->SetTitle('Compra realizada');
        $pdf->SetSubject('Compra realizada');
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
        $this->datosCompra($pdf, $payload);
        $this->tabla($pdf, $detalles);
        $this->totales($pdf, $payload);
        $this->observacion($pdf, (string) ($payload['observacion'] ?? ''));
        $this->firma($pdf);

        return [
            'filename' => $nombreArchivo,
            'content' => $pdf->Output($nombreArchivo, 'S'),
        ];
    }

    private function payload(Compra $compra, Collection $detalles): array
    {
        $subtotal = (float) $detalles->sum(fn ($detalle) => (float) $detalle->Subtotal);
        $iva = (float) ($compra->Iva ?? 0);
        $retencion = (float) ($compra->Retencion ?? 0);
        $total = (float) ($compra->Total ?? ($subtotal + $iva + $retencion));

        return [
            'numero' => (string) ($compra->Numero_Compra ?? 'COMPRA-' . $compra->Id_Compra),
            'fecha' => $compra->Fecha_Compra ? Carbon::parse($compra->Fecha_Compra)->format('d/m/Y') : now()->format('d/m/Y'),
            'fecha_emision' => now()->format('d/m/Y h:i A'),
            'proveedor' => $this->nombreProveedor((int) $compra->Id_Proveedor),
            'usuario' => $this->nombreUsuario((int) $compra->Id_Usuario),
            'tipo_compra' => $this->textoTipoCompra((string) $compra->Tipo_Compra),
            'medio_pago' => $this->textoMedioPago((string) $compra->Medio_Pago),
            'fecha_limite_credito' => $compra->Fecha_Limite_Credito ? Carbon::parse($compra->Fecha_Limite_Credito)->format('d/m/Y') : null,
            'cuenta' => $compra->Id_Cuenta_Bancaria ? $this->datosCuenta((int) $compra->Id_Cuenta_Bancaria) : null,
            'referencia' => (string) ($compra->Numero_Referencia_Transferencia ?? ''),
            'subtotal' => $subtotal,
            'iva' => $iva,
            'retencion' => $retencion,
            'total' => $total,
            'observacion' => (string) ($compra->Observacion ?? ''),
        ];
    }

    private function detallesCompra(int $idCompra): Collection
    {
        $detalleTable = (new DetalleCompra())->getTable();
        $productoTable = (new Producto())->getTable();
        $categoriaTable = (new CategoriaProducto())->getTable();
        $marcaTable = (new Marca())->getTable();

        return DetalleCompra::query()
            ->from("{$detalleTable} as dc")
            ->leftJoin("{$productoTable} as p", 'dc.Id_Producto', '=', 'p.Id_Producto')
            ->leftJoin("{$categoriaTable} as c", 'p.Id_Categoria', '=', 'c.Id_Categoria')
            ->leftJoin("{$marcaTable} as m", 'p.Id_Marca', '=', 'm.Id_Marca')
            ->where('dc.Id_Compra', $idCompra)
            ->orderBy('dc.Id_Producto')
            ->select(
                'dc.Id_Compra',
                'dc.Id_Producto',
                'dc.Cantidad',
                'dc.Precio_Compra',
                'dc.Meses_Garantia_Proveedor',
                'dc.Subtotal',
                'p.Nombre_Producto',
                'p.Modelo',
                'p.Precio_Venta',
                'c.Nombre_Categoria',
                'm.Nombre_Marca'
            )
            ->get();
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

        $pdf->SetXY(120, 12);
        $pdf->SetTextColor($pr, $pg, $pb);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(80, 7, 'COMPRA REALIZADA', 0, 1, 'R');

        $pdf->SetXY(128, 21);
        $pdf->SetTextColor(95, 107, 122);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(72, 5, (string) ($payload['numero'] ?? 'COMPRA'), 0, 1, 'R');

        $pdf->SetY(38);
    }

    private function datosCompra(TCPDF $pdf, array $payload): void
    {
        [$br, $bg, $bb] = self::COLOR_BORDE;
        [$fr, $fg, $fb] = self::COLOR_FILA;
        [$tr, $tg, $tb] = self::COLOR_TITULO;
        [$sr, $sg, $sb] = self::COLOR_TEXTO;

        $pdf->SetDrawColor($br, $bg, $bb);
        $pdf->SetFillColor($fr, $fg, $fb);
        $pdf->Rect(10, 38, 196, 34, 'DF');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor($sr, $sg, $sb);

        $pdf->SetXY(14, 41);
        $pdf->Cell(70, 4, 'PROVEEDOR', 0, 0, 'L');
        $pdf->SetX(93);
        $pdf->Cell(38, 4, 'NO. FACTURA', 0, 0, 'L');
        $pdf->SetX(143);
        $pdf->Cell(28, 4, 'FECHA COMPRA', 0, 0, 'L');
        $pdf->SetX(176);
        $pdf->Cell(24, 4, 'EMISION', 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 8.3);
        $pdf->SetTextColor($tr, $tg, $tb);

        $pdf->SetXY(14, 47);
        $pdf->Cell(70, 5, $this->cortar((string) $payload['proveedor'], 52), 0, 0, 'L');
        $pdf->SetX(93);
        $pdf->Cell(38, 5, $this->cortar((string) $payload['numero'], 22), 0, 0, 'L');
        $pdf->SetX(143);
        $pdf->Cell(28, 5, (string) $payload['fecha'], 0, 0, 'L');
        $pdf->SetX(176);
        $pdf->Cell(24, 5, (string) $payload['fecha_emision'], 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor($sr, $sg, $sb);
        $pdf->SetXY(14, 56);
        $pdf->Cell(34, 4, 'TIPO DE COMPRA', 0, 0, 'L');
        $pdf->SetX(52);
        $pdf->Cell(34, 4, 'MEDIO DE PAGO', 0, 0, 'L');
        $pdf->SetX(93);
        $pdf->Cell(42, 4, 'REFERENCIA', 0, 0, 'L');
        $pdf->SetX(143);
        $pdf->Cell(57, 4, 'REGISTRADO POR', 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 8.3);
        $pdf->SetTextColor($tr, $tg, $tb);
        $pdf->SetXY(14, 62);
        $pdf->Cell(34, 5, (string) $payload['tipo_compra'], 0, 0, 'L');
        $pdf->SetX(52);
        $pdf->Cell(34, 5, (string) $payload['medio_pago'], 0, 0, 'L');
        $pdf->SetX(93);
        $pdf->Cell(42, 5, $this->cortar((string) ($payload['referencia'] ?: 'Sin referencia'), 28), 0, 0, 'L');
        $pdf->SetX(143);
        $pdf->Cell(57, 5, $this->cortar((string) $payload['usuario'], 40), 0, 1, 'L');

        $y = 78;

        if (! empty($payload['fecha_limite_credito']) || ! empty($payload['cuenta'])) {
            $pdf->SetDrawColor($br, $bg, $bb);
            $pdf->SetFillColor(240, 243, 247);
            $pdf->Rect(10, 74, 196, 12, 'DF');

            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->SetTextColor($sr, $sg, $sb);
            $pdf->SetXY(14, 76);
            $pdf->Cell(42, 4, 'FECHA LIMITE CREDITO', 0, 0, 'L');
            $pdf->SetX(70);
            $pdf->Cell(120, 4, 'CUENTA DESTINO', 0, 1, 'L');

            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor($tr, $tg, $tb);
            $pdf->SetXY(14, 81);
            $pdf->Cell(42, 4, (string) ($payload['fecha_limite_credito'] ?? 'No aplica'), 0, 0, 'L');
            $pdf->SetX(70);
            $pdf->Cell(120, 4, $this->cortar((string) ($payload['cuenta'] ?? 'No aplica'), 92), 0, 1, 'L');

            $y = 92;
        }

        $pdf->SetY($y);
    }

    private function tabla(TCPDF $pdf, Collection $detalles): void
    {
        $this->tablaHeader($pdf);

        $numeroFila = 0;

        foreach ($detalles as $detalle) {
            $descripcion = $this->descripcionDetalle($detalle);
            $cantidad = (float) ($detalle->Cantidad ?? 0);
            $precioCompra = (float) ($detalle->Precio_Compra ?? 0);
            $precioVentaActual = (float) ($detalle->Precio_Venta ?? 0);
            $subtotal = (float) ($detalle->Subtotal ?? ($cantidad * $precioCompra));
            $garantia = $detalle->Meses_Garantia_Proveedor !== null
                ? ((int) $detalle->Meses_Garantia_Proveedor) . ' mes(es)'
                : 'Sin';

            $lineas = max(1, (int) ceil(mb_strlen($descripcion) / 47));
            $alto = max(8, $lineas * 4.4);

            if ($pdf->GetY() + $alto + 34 > 260) {
                $pdf->AddPage();
                $this->tablaHeader($pdf);
            }

            $numeroFila++;
            $fill = $numeroFila % 2 === 0;
            $pdf->SetFillColor($fill ? 247 : 255, $fill ? 249 : 255, $fill ? 252 : 255);
            $pdf->SetTextColor(26, 43, 66);
            $pdf->SetDrawColor(215, 228, 243);
            $pdf->SetFont('helvetica', '', 7.2);

            $pdf->MultiCell(15, $alto, $this->cantidadTexto($cantidad), 1, 'C', true, 0);
            $pdf->MultiCell(78, $alto, $descripcion, 1, 'L', true, 0);
            $pdf->MultiCell(27, $alto, 'C$ ' . number_format($precioCompra, 2), 1, 'R', true, 0);
            $pdf->MultiCell(27, $alto, 'C$ ' . number_format($precioVentaActual, 2), 1, 'R', true, 0);
            $pdf->MultiCell(18, $alto, $garantia, 1, 'C', true, 0);
            $pdf->MultiCell(25, $alto, 'C$ ' . number_format($subtotal, 2), 1, 'R', true, 1);
        }

        if ($detalles->isEmpty()) {
            $pdf->SetTextColor(95, 107, 122);
            $pdf->SetFillColor(247, 249, 252);
            $pdf->Cell(190, 8, 'No hay productos registrados en esta compra.', 1, 1, 'C', true);
        }
    }

    private function tablaHeader(TCPDF $pdf): void
    {
        $pdf->SetFont('helvetica', 'B', 7.2);
        $pdf->SetFillColor(46, 139, 192);
        $pdf->SetDrawColor(215, 228, 243);
        $pdf->SetTextColor(255, 255, 255);

        $pdf->Cell(15, 7, 'Cant.', 1, 0, 'C', true);
        $pdf->Cell(78, 7, 'Producto / detalle', 1, 0, 'L', true);
        $pdf->Cell(27, 7, 'P. compra', 1, 0, 'R', true);
        $pdf->Cell(27, 7, 'P. venta actual', 1, 0, 'R', true);
        $pdf->Cell(18, 7, 'Garantia', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Subtotal', 1, 1, 'R', true);
    }

    private function totales(TCPDF $pdf, array $payload): void
    {
        if ($pdf->GetY() + 34 > 260) {
            $pdf->AddPage();
        }

        $pdf->SetDrawColor(215, 228, 243);
        $pdf->SetFont('helvetica', 'B', 8);

        $this->filaTotal($pdf, 'Subtotal', (float) $payload['subtotal'], false);
        $this->filaTotal($pdf, 'IVA', (float) $payload['iva'], false);
        $this->filaTotal($pdf, 'Retencion', (float) $payload['retencion'], false);
        $this->filaTotal($pdf, 'TOTAL', (float) $payload['total'], true);
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

        $pdf->Cell(165, 7, $label, 1, 0, 'R', true);
        $pdf->Cell(25, 7, 'C$ ' . number_format($monto, 2), 1, 1, 'R', true);
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
        $pdf->MultiCell(190, 7, $this->cortar($observacion, 260), 1, 'L', false, 1);
    }

    private function firma(TCPDF $pdf): void
    {
        if ($pdf->GetY() + 22 > 260) {
            $pdf->AddPage();
        }

        $pdf->Ln(10);
        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', '', 8);

        $pdf->Cell(95, 5, 'Comprobante generado por GNET System', 0, 0, 'L');
    }

    private function descripcionDetalle(object $detalle): string
    {
        $marca = trim((string) ($detalle->Nombre_Marca ?? ''));
        $producto = trim((string) ($detalle->Nombre_Producto ?? 'Producto no encontrado'));
        $modelo = trim((string) ($detalle->Modelo ?? ''));
        $categoria = trim((string) ($detalle->Nombre_Categoria ?? ''));

        $principal = trim($marca . ' ' . $producto . ' ' . $modelo);
        $secundario = $categoria !== '' ? 'Categoria: ' . $categoria : 'Sin categoria';

        return $this->cortar(trim($principal . ' | ' . $secundario), 130);
    }

    private function nombreProveedor(int $idProveedor): string
    {
        $proveedorTable = (new Proveedor())->getTable();

        $proveedor = Proveedor::query()
            ->from("{$proveedorTable} as proveedor")
            ->leftJoin('persona as pe', 'proveedor.Id_Persona', '=', 'pe.Id_Persona')
            ->select(
                'proveedor.Codigo_RUC',
                'pe.Primer_Nombre',
                'pe.Segundo_Nombre',
                'pe.Primer_Apellido',
                'pe.Segundo_Apellido'
            )
            ->where('proveedor.Id_Proveedor', $idProveedor)
            ->first();

        if (! $proveedor) {
            return 'Proveedor no encontrado';
        }

        $nombre = trim(
            ($proveedor->Primer_Nombre ?? '') . ' ' .
            ($proveedor->Segundo_Nombre ?? '') . ' ' .
            ($proveedor->Primer_Apellido ?? '') . ' ' .
            ($proveedor->Segundo_Apellido ?? '')
        );

        $ruc = trim((string) ($proveedor->Codigo_RUC ?? ''));

        if ($nombre === '') {
            return $ruc !== '' ? $ruc : 'Proveedor sin nombre';
        }

        return $ruc !== '' ? $nombre . ' - RUC: ' . $ruc : $nombre;
    }

    private function nombreUsuario(int $idUsuario): string
    {
        return (string) (Usuario::query()->where('Id_Usuario', $idUsuario)->value('Nombre_Usuario') ?: 'Usuario no encontrado');
    }

    private function datosCuenta(int $idCuenta): ?string
    {
        $cuentaTable = (new CuentaBancaria())->getTable();
        $bancoTable = (new Banco())->getTable();

        $cuenta = CuentaBancaria::query()
            ->from("{$cuentaTable} as cuenta")
            ->leftJoin("{$bancoTable} as banco", 'cuenta.Id_Banco', '=', 'banco.Id_Banco')
            ->where('cuenta.Id_Cuenta_Bancaria', $idCuenta)
            ->select(
                'cuenta.Nombre_Titular',
                'cuenta.Ultimos_Digitos',
                'cuenta.Tipo_Cuenta',
                'cuenta.Moneda',
                'banco.Nombre_Banco'
            )
            ->first();

        if (! $cuenta) {
            return null;
        }

        $ultimos = preg_replace('/\D+/', '', (string) $cuenta->Ultimos_Digitos);
        $ultimos = $ultimos !== '' ? str_pad(substr($ultimos, -4), 4, '0', STR_PAD_LEFT) : '----';

        return trim(
            ($cuenta->Nombre_Banco ?: 'Sin banco') . ' | ' .
            ($cuenta->Nombre_Titular ?: 'Sin titular') . ' | ' .
            $this->textoTipoCuenta((string) $cuenta->Tipo_Cuenta) . ' | ' .
            $this->textoMoneda((string) $cuenta->Moneda) . ' | ****' . $ultimos
        );
    }

    private function textoTipoCompra(string $tipo): string
    {
        return match ($tipo) {
            'CREDITO' => 'Credito',
            default => 'Contado',
        };
    }

    private function textoMedioPago(string $medio): string
    {
        return match ($medio) {
            'PAGO_FISICO' => 'Pago fisico',
            'TRANSFERENCIA' => 'Transferencia',
            default => $medio ?: 'No definido',
        };
    }

    private function textoTipoCuenta(string $tipo): string
    {
        return match ($tipo) {
            'CUENTA_AHORRO' => 'Ahorro',
            'CUENTA_CORRIENTE' => 'Corriente',
            'TARJETA_DEBITO' => 'Debito',
            'TARJETA_CREDITO' => 'Credito',
            default => $tipo ?: 'No definida',
        };
    }

    private function textoMoneda(string $moneda): string
    {
        return match ($moneda) {
            'CORDOBAS' => 'Cordobas',
            'DOLARES' => 'Dolares',
            default => $moneda ?: 'No definida',
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

        $optimizado = $directorio . '/logo-compra-pdf.jpg';

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
