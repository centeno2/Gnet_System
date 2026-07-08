<?php

namespace App\Services\Reportes;

use App\Models\PagoVenta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use TCPDF;

class ArqueoCajaCierrePdfService
{
    public function generar(int $arqueoId): string
    {
        $data = $this->datos($arqueoId);

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
        $pdf->SetTitle('Reporte detallado de cierre de caja');
        $pdf->SetSubject('Reporte detallado de cierre de caja');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetCompression(true);
        $pdf->SetMargins(10, 8, 10);
        $pdf->SetFooterMargin(5);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();

        $this->encabezado($pdf, $data);
        $this->resumenBalance($pdf, $data);
        $this->tablaRecaudacion($pdf, $data['recaudacion']);
        $this->tablaMediosPago($pdf, $data['medios_pago']);
        $this->tablaProductos($pdf, $data['productos']);
        $this->tablaMovimientos($pdf, 'Abonos de credito', $data['abonos']);
        $this->tablaMovimientos($pdf, 'Otros ingresos / egresos', $data['egresos']);
        $this->firmas($pdf);

        $archivo = 'cierre-caja-' . str_pad((string) $data['arqueo']->Id_Arqueo, 6, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->Output($archivo, 'S');
    }

    private function datos(int $arqueoId): array
    {
        $arqueo = DB::table('arqueo_caja as a')
            ->join('apertura_caja as ac', 'ac.Id_Apertura_Caja', '=', 'a.Id_Apertura_Caja')
            ->leftJoin('detalle_arqueo as da', 'da.Id_Arqueo', '=', 'a.Id_Arqueo')
            ->leftJoin('usuario as u', 'u.Id_Usuario', '=', 'a.Id_Usuario')
            ->leftJoin('trabajador as t', 't.Id_Trabajador', '=', 'u.Id_Trabajador')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 't.Id_Persona')
            ->where('a.Id_Arqueo', $arqueoId)
            ->selectRaw("
                a.Id_Arqueo,
                a.Id_Usuario,
                a.Id_Apertura_Caja,
                a.Total_Caja_Cordoba,
                a.Total_Caja_Dolar,
                a.Fecha_Arqueo,
                ac.Numero_Caja,
                ac.Monto_Apertura,
                ac.Fecha_Apertura,
                da.Faltante_Cordoba,
                da.Faltante_Dolar,
                da.Sobrante_Cordoba,
                da.Sobrante_Dolar,
                da.Cantidad_Egresada_Cordoba,
                da.Cantidad_Egresada_Dolar,
                da.Estado_Arqueo,
                u.Nombre_Usuario,
                COALESCE(
                    NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                    u.Nombre_Usuario
                ) as Cajero
            ")
            ->first();

        if (! $arqueo) {
            throw new RuntimeException('No se encontro el arqueo de caja solicitado.');
        }

        $inicio = Carbon::parse($arqueo->Fecha_Apertura);
        $fin = Carbon::parse($arqueo->Fecha_Arqueo);
        $usuarioId = (int) $arqueo->Id_Usuario;

        $ventasPeriodoIds = DB::table('venta')
            ->where('Id_Usuario', $usuarioId)
            ->where('Estado', 1)
            ->whereBetween('Fecha_venta', [$inicio, $fin])
            ->pluck('Id_Venta');

        $lineasVenta = $this->lineasVenta($ventasPeriodoIds);
        $recaudacion = $this->resumenRecaudacion($lineasVenta);
        $productos = $this->resumenProductos($lineasVenta);
        $pagos = $this->pagos($usuarioId, $inicio, $fin);
        $mediosPago = $this->resumenMediosPago($pagos);
        $abonos = $this->abonos($usuarioId, $inicio, $fin);
        $egresos = $this->egresos((int) $arqueo->Id_Apertura_Caja, $usuarioId);

        return [
            'arqueo' => $arqueo,
            'recaudacion' => $recaudacion,
            'productos' => $productos,
            'medios_pago' => $mediosPago,
            'abonos' => $abonos,
            'egresos' => $egresos,
            'totales' => [
                'recaudacion' => round((float) $mediosPago->sum('ventas'), 2),
                'otros_ingresos' => round((float) $abonos->sum('monto_cordobas'), 2),
                'egresos' => round((float) $egresos->sum('monto_cordobas'), 2),
                'descuadre' => round((float) ($arqueo->Sobrante_Cordoba ?? 0) - (float) ($arqueo->Faltante_Cordoba ?? 0), 2),
            ],
        ];
    }

    private function lineasVenta(Collection $ventasIds): Collection
    {
        if ($ventasIds->isEmpty()) {
            return collect();
        }

        return DB::table('detalle_venta as dv')
            ->join('venta as v', 'v.Id_Venta', '=', 'dv.Id_Venta')
            ->leftJoin('producto as p', 'p.Id_Producto', '=', 'dv.Id_Producto')
            ->leftJoin('categoria_producto as cp', 'cp.Id_Categoria', '=', 'p.Id_Categoria')
            ->leftJoin('servicio as s', 's.Id_Servicio', '=', 'dv.Id_Servicio')
            ->leftJoin('tarifa_copia as tc', 'tc.Id_Tarifa_Copia', '=', 'dv.Id_Tarifa_Copia')
            ->whereIn('dv.Id_Venta', $ventasIds->all())
            ->select([
                'dv.Tipo_Detalle',
                'dv.Nombre_Formato',
                'dv.Cantidad',
                'dv.Precio_Unitario',
                'dv.Subtotal',
                'dv.Descuento',
                'p.Nombre_Producto',
                'p.Modelo',
                'cp.Nombre_Categoria',
                's.Nombre_Servicio',
                'tc.Nombre_Tarifa',
                'tc.Tipo_Color',
            ])
            ->get();
    }

    private function resumenRecaudacion(Collection $lineas): Collection
    {
        return $lineas
            ->groupBy(fn ($linea) => $this->categoriaLinea($linea))
            ->map(fn (Collection $items, string $categoria) => [
                'categoria' => $categoria,
                'cantidad' => round((float) $items->sum(fn ($item) => (float) $item->Cantidad), 2),
                'importe' => round((float) $items->sum(fn ($item) => (float) $item->Subtotal), 2),
            ])
            ->sortBy('categoria')
            ->values();
    }

    private function resumenProductos(Collection $lineas): Collection
    {
        return $lineas
            ->groupBy(fn ($linea) => $this->categoriaLinea($linea) . '|' . $this->descripcionLinea($linea))
            ->map(function (Collection $items) {
                $primero = $items->first();

                return [
                    'categoria' => $this->categoriaLinea($primero),
                    'producto' => $this->descripcionLinea($primero),
                    'cantidad' => round((float) $items->sum(fn ($item) => (float) $item->Cantidad), 2),
                    'efectivo' => round((float) $items->sum(fn ($item) => (float) $item->Subtotal), 2),
                ];
            })
            ->sortBy(fn (array $fila) => $fila['categoria'] . '|' . $fila['producto'])
            ->values();
    }

    private function pagos(int $usuarioId, Carbon $inicio, Carbon $fin): Collection
    {
        return DB::table('pago_venta as pv')
            ->join('venta as v', 'v.Id_Venta', '=', 'pv.Id_Venta')
            ->where('v.Id_Usuario', $usuarioId)
            ->where('v.Estado', 1)
            ->whereBetween('pv.Fecha_Pago', [$inicio, $fin])
            ->select([
                'pv.Id_Venta',
                'pv.Tipo_Pago',
                'pv.Moneda',
                'pv.Monto',
                'pv.Tipo_Cambio',
                'pv.Monto_Equivalente_Cordobas',
                'v.Cambio_Entregado_Cordobas',
            ])
            ->get();
    }

    private function resumenMediosPago(Collection $pagos): Collection
    {
        $cambioEfectivo = $pagos
            ->where('Tipo_Pago', PagoVenta::TIPO_EFECTIVO)
            ->unique('Id_Venta')
            ->sum(fn ($pago) => (float) ($pago->Cambio_Entregado_Cordobas ?? 0));

        return $pagos
            ->groupBy('Tipo_Pago')
            ->map(function (Collection $items, string $tipo) use ($cambioEfectivo) {
                $total = round((float) $items->sum(fn ($pago) => $this->montoPagoCordobas($pago)), 2);

                if ($tipo === PagoVenta::TIPO_EFECTIVO) {
                    $total = round(max(0, $total - (float) $cambioEfectivo), 2);
                }

                return [
                    'medio' => $this->nombreMedioPago($tipo),
                    'ventas' => $total,
                    'notas_credito' => 0.0,
                    'saldo' => $total,
                ];
            })
            ->values();
    }

    private function abonos(int $usuarioId, Carbon $inicio, Carbon $fin): Collection
    {
        return DB::table('abono_credito')
            ->where('Id_Usuario', $usuarioId)
            ->whereBetween('Fecha_Abono', [$inicio, $fin])
            ->orderBy('Fecha_Abono')
            ->get()
            ->map(fn ($abono) => [
                'categoria' => 'Abono credito',
                'hora' => Carbon::parse($abono->Fecha_Abono)->format('h:i A'),
                'monto_cordobas' => $this->montoAbonoCordobas($abono),
            ]);
    }

    private function egresos(int $aperturaId, int $usuarioId): Collection
    {
        return DB::table('egreso')
            ->where('Id_Apertura_Caja', $aperturaId)
            ->where('Id_Usuario', $usuarioId)
            ->orderBy('Fecha_Egreso')
            ->get()
            ->map(fn ($egreso) => [
                'categoria' => (string) ($egreso->Motivo_Egreso ?: 'Egreso'),
                'hora' => Carbon::parse($egreso->Fecha_Egreso)->format('h:i A'),
                'monto_cordobas' => round((float) ($egreso->Monto_Egresado_Cordoba ?? 0), 2),
            ]);
    }

    private function encabezado(TCPDF $pdf, array $data): void
    {
        $arqueo = $data['arqueo'];

        $pdf->SetFillColor(240, 243, 247);
        $pdf->SetDrawColor(215, 228, 243);
        $pdf->Rect(10, 8, 196, 29, 'DF');

        $logo = $this->logoParaPdf();

        if ($logo) {
            $pdf->Image($logo, 14, 12, 18, 18, 'JPG');
        }

        $pdf->SetXY($logo ? 37 : 14, 12);
        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(90, 6, 'Reporte detallado de cierre de caja', 0, 1, 'L');
        $pdf->SetX($logo ? 37 : 14);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(95, 107, 122);
        $pdf->Cell(95, 5, 'Operador responsable: ' . (string) $arqueo->Cajero, 0, 1, 'L');

        $pdf->SetXY(138, 12);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(46, 139, 192);
        $pdf->Cell(60, 5, 'No. Caja: ' . (string) $arqueo->Numero_Caja, 0, 1, 'R');
        $pdf->SetX(138);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(95, 107, 122);
        $pdf->Cell(60, 4, 'Apertura: ' . $this->fechaHora($arqueo->Fecha_Apertura), 0, 1, 'R');
        $pdf->SetX(138);
        $pdf->Cell(60, 4, 'Cierre: ' . $this->fechaHora($arqueo->Fecha_Arqueo), 0, 1, 'R');
        $pdf->SetX(138);
        $pdf->Cell(60, 4, 'Printed: ' . now()->format('d/m/Y h:i:s A'), 0, 1, 'R');

        $pdf->SetY(43);
    }

    private function resumenBalance(TCPDF $pdf, array $data): void
    {
        $arqueo = $data['arqueo'];
        $totales = $data['totales'];

        $filas = [
            ['Recaudacion', $totales['recaudacion']],
            ['Otros ingresos', $totales['otros_ingresos']],
            ['Descuadre', $totales['descuadre']],
            ['Caja inicial (efvo)', (float) $arqueo->Monto_Apertura],
            ['Egresos', $totales['egresos']],
            ['Caja entregada C$', (float) $arqueo->Total_Caja_Cordoba],
            ['Caja entregada $', (float) $arqueo->Total_Caja_Dolar],
            ['Estado', (string) ($arqueo->Estado_Arqueo ?? 'CUADRADO')],
        ];

        $this->tablaBasica($pdf, 'Balance de cierre', ['Concepto', 'Monto'], $filas, [118, 72], ['L', 'R'], true);
    }

    private function tablaRecaudacion(TCPDF $pdf, Collection $filas): void
    {
        $rows = $filas->map(fn ($fila) => [
            $fila['categoria'],
            $this->cantidad($fila['cantidad']),
            $this->money($fila['importe']),
        ])->all();

        $this->tablaBasica($pdf, 'Recaudacion', ['Categoria', 'Cantidad', 'Importe'], $rows, [94, 36, 60], ['L', 'R', 'R']);
    }

    private function tablaMediosPago(TCPDF $pdf, Collection $filas): void
    {
        $rows = $filas->map(fn ($fila) => [
            $fila['medio'],
            $this->money($fila['ventas']),
            $this->money($fila['notas_credito']),
            $this->money($fila['saldo']),
        ])->all();

        $this->tablaBasica($pdf, 'Ventas por medio de pago', ['Medio de pago', 'Ventas', 'Notas credito', 'Saldo'], $rows, [58, 44, 44, 44], ['L', 'R', 'R', 'R']);
    }

    private function tablaProductos(TCPDF $pdf, Collection $filas): void
    {
        $rows = $filas->map(fn ($fila) => [
            $this->cortar($fila['categoria'], 24),
            $this->cantidad($fila['cantidad']),
            $this->cortar($fila['producto'], 44),
            $this->money($fila['efectivo']),
        ])->all();

        $this->tablaBasica($pdf, 'Resumen de productos y servicios vendidos', ['Categoria', 'Cant', 'Producto', 'Importe'], $rows, [42, 22, 82, 44], ['L', 'R', 'L', 'R']);
    }

    private function tablaMovimientos(TCPDF $pdf, string $titulo, Collection $filas): void
    {
        $rows = $filas->map(fn ($fila) => [
            $this->cortar($fila['categoria'], 55),
            $fila['hora'],
            $this->money($fila['monto_cordobas']),
        ])->all();

        $this->tablaBasica($pdf, $titulo, ['Categoria', 'Hora', 'Monto C$'], $rows, [102, 34, 54], ['L', 'C', 'R']);
    }

    private function tablaBasica(TCPDF $pdf, string $titulo, array $headers, array $rows, array $widths, array $aligns, bool $compacta = false): void
    {
        $altoHeader = $compacta ? 6 : 6.5;
        $altoFila = $compacta ? 6 : 5.8;

        if ($pdf->GetY() + $altoHeader + (max(1, count($rows)) * $altoFila) + 13 > 260) {
            $pdf->AddPage();
        }

        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(26, 43, 66);
        $pdf->Cell(190, 5, $titulo, 0, 1, 'L');

        $pdf->SetFillColor(46, 139, 192);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(215, 228, 243);
        $pdf->SetFont('helvetica', 'B', 7);

        foreach ($headers as $index => $header) {
            $pdf->Cell($widths[$index], $altoHeader, $header, 1, 0, 'C', true);
        }

        $pdf->Ln();

        if ($rows === []) {
            $pdf->SetFillColor(247, 249, 252);
            $pdf->SetTextColor(95, 107, 122);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell(array_sum($widths), $altoFila, 'Sin movimientos registrados.', 1, 1, 'C', true);

            return;
        }

        $pdf->SetFont('helvetica', '', 7);
        $numero = 0;

        foreach ($rows as $row) {
            if ($pdf->GetY() + $altoFila > 260) {
                $pdf->AddPage();
                $pdf->SetFillColor(46, 139, 192);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetDrawColor(215, 228, 243);
                $pdf->SetFont('helvetica', 'B', 7);

                foreach ($headers as $index => $header) {
                    $pdf->Cell($widths[$index], $altoHeader, $header, 1, 0, 'C', true);
                }

                $pdf->Ln();
                $pdf->SetFont('helvetica', '', 7);
            }

            $numero++;
            $fill = $numero % 2 === 0;
            $pdf->SetFillColor($fill ? 247 : 255, $fill ? 249 : 255, $fill ? 252 : 255);
            $pdf->SetTextColor(26, 43, 66);

            foreach ($row as $index => $valor) {
                $pdf->Cell($widths[$index], $altoFila, (string) $valor, 1, 0, $aligns[$index] ?? 'L', true);
            }

            $pdf->Ln();
        }
    }

    private function firmas(TCPDF $pdf): void
    {
        if ($pdf->GetY() > 235) {
            $pdf->AddPage();
        }

        $pdf->Ln(14);
        $y = $pdf->GetY();
        $pdf->SetDrawColor(26, 43, 66);
        $pdf->Line(22, $y, 88, $y);
        $pdf->Line(128, $y, 194, $y);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(26, 43, 66);
        $pdf->SetXY(22, $y + 2);
        $pdf->Cell(66, 4, 'Firma autorizada', 0, 0, 'C');
        $pdf->SetXY(128, $y + 2);
        $pdf->Cell(66, 4, 'Firma cliente / cajero', 0, 1, 'C');
    }

    private function categoriaLinea(object $linea): string
    {
        if ((string) $linea->Tipo_Detalle === 'COPIA') {
            $tipoColor = strtoupper(trim((string) ($linea->Tipo_Color ?? '')));

            return match ($tipoColor) {
                'BN', 'B/N', 'BLANCO_NEGRO' => 'Impresiones BN',
                'COLOR' => 'Impresiones color',
                default => 'Copias',
            };
        }

        if ((string) $linea->Tipo_Detalle === 'PRODUCTO') {
            return (string) ($linea->Nombre_Categoria ?: 'Productos');
        }

        return 'Servicios';
    }

    private function descripcionLinea(object $linea): string
    {
        if ((string) $linea->Tipo_Detalle === 'COPIA') {
            return (string) ($linea->Nombre_Formato ?: $linea->Nombre_Tarifa ?: 'Copia');
        }

        if ((string) $linea->Tipo_Detalle === 'PRODUCTO') {
            return trim((string) ($linea->Nombre_Producto ?: 'Producto') . ' ' . (string) ($linea->Modelo ?: ''));
        }

        return (string) ($linea->Nombre_Servicio ?: 'Servicio');
    }

    private function montoPagoCordobas(object $pago): float
    {
        if ((int) $pago->Moneda === PagoVenta::MONEDA_DOLAR) {
            $equivalente = (float) ($pago->Monto_Equivalente_Cordobas ?? 0);

            return round($equivalente > 0 ? $equivalente : ((float) $pago->Monto * (float) $pago->Tipo_Cambio), 2);
        }

        return round((float) $pago->Monto, 2);
    }

    private function montoAbonoCordobas(object $abono): float
    {
        if (strtoupper((string) $abono->Moneda) === 'USD') {
            $equivalente = (float) ($abono->Monto_Equivalente_Cordobas ?? 0);

            return round($equivalente > 0 ? $equivalente : ((float) $abono->Monto * (float) $abono->Tipo_Cambio), 2);
        }

        return round((float) $abono->Monto, 2);
    }

    private function nombreMedioPago(string $tipo): string
    {
        return match ($tipo) {
            PagoVenta::TIPO_TRANSFERENCIA => 'Transferencia',
            PagoVenta::TIPO_TARJETA => 'Tarjeta',
            default => 'Efectivo',
        };
    }

    private function fechaHora(mixed $fecha): string
    {
        return $fecha ? Carbon::parse($fecha)->format('d/m/Y h:i A') : '-';
    }

    private function money(float|int|string $monto): string
    {
        return 'C$' . number_format((float) $monto, 2);
    }

    private function cantidad(float|int|string $cantidad): string
    {
        $numero = (float) $cantidad;

        return floor($numero) == $numero
            ? number_format($numero, 0)
            : number_format($numero, 2);
    }

    private function cortar(string $texto, int $limite): string
    {
        $texto = trim($texto);

        if (mb_strlen($texto) <= $limite) {
            return $texto;
        }

        return mb_substr($texto, 0, $limite - 3) . '...';
    }

    private function logoParaPdf(): ?string
    {
        $original = public_path('img/gnetlogo.png');

        if (! is_file($original)) {
            return null;
        }

        $directorio = storage_path('app/reportes');

        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $optimizado = $directorio . '/logo-cierre-caja.jpg';

        if (is_file($optimizado) && filemtime($optimizado) >= filemtime($original)) {
            return $optimizado;
        }

        if (! extension_loaded('gd')) {
            return null;
        }

        $imagen = @imagecreatefrompng($original);

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
}
