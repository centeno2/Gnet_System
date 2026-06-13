<?php

namespace App\Services\Creditos;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use TCPDF;

class CreditoVoucherPdfService
{
    public function generar(string $reciboNumero, int $ancho = 80): string
    {
        $ancho = in_array($ancho, [58, 80], true) ? $ancho : 80;
        $reciboNumero = trim($reciboNumero);

        if ($reciboNumero === '') {
            throw new RuntimeException('No se recibió el número de recibo del crédito.');
        }

        $recibo = $this->recibo($reciboNumero);
        $abonos = $this->abonos($reciboNumero);

        if ($abonos->isEmpty()) {
            throw new RuntimeException('No se encontraron abonos asociados al recibo solicitado.');
        }

        $alto = $this->altoVoucher($recibo, $abonos, $ancho);
        $pdf = $this->pdf($ancho, $alto);

        $this->titulo($pdf, $ancho, 'VOUCHER DE CREDITO');
        $this->textoCentro($pdf, 'GNET SYSTEM');
        $this->linea($pdf, $ancho);

        $this->filaTexto($pdf, 'Recibo:', $this->texto($recibo->Numero_Recibo ?? $reciboNumero));
        $this->filaTexto($pdf, 'Fecha:', $this->fecha($recibo->Fecha_Recibo ?? null));
        $this->filaTexto($pdf, 'Instit.:', $this->clienteNombre($recibo));
        $this->filaTexto($pdf, 'Telefono:', $this->telefonoCliente($recibo));
        $this->filaTexto($pdf, 'Municipio:', $this->texto($recibo->Municipio ?? '—'));
        $this->filaTexto($pdf, 'Cuenta:', 'Cuenta #' . str_pad((string) ($recibo->Id_Cliente_Credito ?? 0), 5, '0', STR_PAD_LEFT));
        $this->filaTexto($pdf, 'Cajero:', $this->texto($recibo->Nombre_Usuario ?? '—'));

        $this->linea($pdf, $ancho);
        $this->seccion($pdf, 'RESUMEN DEL PAGO');
        $this->filaTexto($pdf, 'Metodo:', $this->metodoNombre((string) ($recibo->Metodo_Pago ?? '')));

        if ((float) ($recibo->Monto_Cordobas ?? 0) > 0) {
            $this->total($pdf, 'Recibido C$', (float) $recibo->Monto_Cordobas);
        }

        if ((float) ($recibo->Monto_Dolares ?? 0) > 0) {
            $this->totalSinPrefijo($pdf, 'Recibido US$', 'US$ ' . number_format((float) $recibo->Monto_Dolares, 2));
            $this->filaTexto($pdf, 'T/C:', 'C$ ' . number_format((float) ($recibo->Tasa_Cambio ?? 0), 4));
        }

        if (trim((string) ($recibo->Referencia ?? '')) !== '') {
            $this->filaTexto($pdf, 'Ref:', $this->cortar((string) $recibo->Referencia, $ancho === 58 ? 34 : 56));
        }

        $this->total($pdf, 'Saldo antes', (float) ($recibo->Total_Pendiente_Antes ?? 0), true);
        $this->total($pdf, 'Total recibido', (float) ($recibo->Total_Recibido_Cordobas ?? 0), true);
        $this->total($pdf, 'Total aplicado', (float) ($recibo->Total_Aplicado_Cordobas ?? 0), true);

        if ((float) ($recibo->Saldo_Favor_Generado ?? 0) > 0) {
            $this->total($pdf, 'Saldo favor', (float) $recibo->Saldo_Favor_Generado, true);
        }

        if ((float) ($recibo->Saldo_Favor_Disponible ?? 0) > 0) {
            $this->total($pdf, 'Favor actual', (float) $recibo->Saldo_Favor_Disponible, true);
        }

        $this->filaTexto($pdf, 'Cancelados:', (string) (int) ($recibo->Creditos_Cancelados ?? 0));

        $this->linea($pdf, $ancho);
        $this->detalleCreditos($pdf, $ancho, $abonos);

        if (trim((string) ($recibo->Observacion ?? '')) !== '') {
            $this->linea($pdf, $ancho);
            $this->seccion($pdf, 'OBSERVACION');
            $pdf->SetFont('helvetica', '', 7);
            $pdf->MultiCell(0, 4, $this->cortar((string) $recibo->Observacion, $ancho === 58 ? 105 : 170), 0, 'L');
        }

        $this->linea($pdf, $ancho);
        $this->textoCentro($pdf, 'Pago de credito institucional');
        $this->textoCentro($pdf, 'Gracias por su pago');

        return $pdf->Output('voucher-credito-' . $reciboNumero . '.pdf', 'S');
    }

    private function recibo(string $reciboNumero): object
    {
        $recibo = DB::table('credito_recibo as r')
            ->leftJoin('cliente_credito as cc', 'cc.Id_Cliente_Credito', '=', 'r.Id_Cliente_Credito')
            ->leftJoin('cliente as c', 'c.Id_Cliente', '=', 'r.Id_Cliente')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->leftJoin('usuario as u', 'u.Id_Usuario', '=', 'r.Id_Usuario')
            ->where('r.Numero_Recibo', $reciboNumero)
            ->select([
                'r.*',
                'c.Institucion',
                'c.Tipo_Cliente',
                'c.Telefono_Institucion',
                'c.Municipio',
                'p.Primer_Nombre',
                'p.Segundo_Nombre',
                'p.Primer_Apellido',
                'p.Segundo_Apellido',
                'p.Telefono',
                'u.Nombre_Usuario',
                'cc.Saldo_Actual as Saldo_Favor_Disponible',
            ])
            ->first();

        if (! $recibo) {
            throw new RuntimeException('No se encontró el recibo de crédito solicitado.');
        }

        return $recibo;
    }

    private function abonos(string $reciboNumero): Collection
    {
        return DB::table('abono_credito as ac')
            ->join('credito as cr', 'cr.Id_Credito', '=', 'ac.Id_Credito')
            ->leftJoin('venta as v', 'v.Id_Venta', '=', 'cr.Id_Venta')
            ->where('ac.Numero_Recibo', $reciboNumero)
            ->select([
                'ac.Id_Abono_Credito',
                'ac.Numero_Recibo',
                'ac.Fecha_Abono',
                'ac.Moneda',
                'ac.Monto',
                'ac.Tipo_Cambio',
                'ac.Monto_Equivalente_Cordobas',
                'ac.Numero_Transferencia',
                'ac.Observacion',
                'cr.Id_Credito',
                'cr.Estado',
                'cr.Saldo_Actual',
                'v.Numero_Factura',
                'v.Total as Total_Venta',
            ])
            ->orderBy('ac.Id_Abono_Credito')
            ->get();
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

    private function altoVoucher(object $recibo, Collection $abonos, int $ancho): int
    {
        $caracteresLinea = $ancho === 58 ? 28 : 42;
        $alto = 112;

        $textos = [
            $this->clienteNombre($recibo),
            (string) ($recibo->Municipio ?? ''),
            (string) ($recibo->Referencia ?? ''),
            (string) ($recibo->Observacion ?? ''),
        ];

        foreach ($textos as $texto) {
            $lineas = max(1, (int) ceil(mb_strlen($this->texto($texto)) / $caracteresLinea));
            $alto += max(0, $lineas - 1) * 4;
        }

        foreach ($abonos as $abono) {
            $descripcion = $this->descripcionAbono($abono);
            $lineas = max(1, (int) ceil(mb_strlen($descripcion) / $caracteresLinea));
            $alto += 12 + ($lineas * 4);
        }

        if ((float) ($recibo->Monto_Dolares ?? 0) > 0) {
            $alto += 8;
        }

        if ((float) ($recibo->Saldo_Favor_Generado ?? 0) > 0) {
            $alto += 5;
        }

        if ((float) ($recibo->Saldo_Favor_Disponible ?? 0) > 0) {
            $alto += 5;
        }

        return max(130, min($alto + 20, 500));
    }

    private function detalleCreditos(TCPDF $pdf, int $ancho, Collection $abonos): void
    {
        $this->seccion($pdf, 'CREDITOS CANCELADOS / ABONADOS');

        foreach ($abonos as $abono) {
            $pdf->SetFont('helvetica', '', 7);
            $pdf->MultiCell(0, 4, $this->cortar($this->descripcionAbono($abono), $ancho === 58 ? 34 : 52), 0, 'L');

            $moneda = strtoupper((string) ($abono->Moneda ?? 'NIO')) === 'USD' ? 'US$' : 'C$';
            $pdf->Cell(0, 4, $moneda . ' ' . number_format((float) ($abono->Monto ?? 0), 2), 0, 1, 'L');

            if (strtoupper((string) ($abono->Moneda ?? 'NIO')) === 'USD') {
                $pdf->Cell(0, 4, 'Equiv: C$ ' . number_format((float) ($abono->Monto_Equivalente_Cordobas ?? 0), 2), 0, 1, 'L');
            }

            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->Cell(0, 4, 'Aplicado: C$ ' . number_format((float) ($abono->Monto_Equivalente_Cordobas ?? 0), 2), 0, 1, 'R');
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell(0, 4, 'Saldo credito: C$ ' . number_format((float) ($abono->Saldo_Actual ?? 0), 2), 0, 1, 'R');
        }
    }

    private function descripcionAbono(object $abono): string
    {
        $credito = 'CR-' . str_pad((string) ($abono->Id_Credito ?? 0), 5, '0', STR_PAD_LEFT);
        $factura = $this->texto($abono->Numero_Factura ?? 'Sin factura');
        $estado = $this->texto($abono->Estado ?? '—');

        return $credito . ' · Factura: ' . $factura . ' · ' . $estado;
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
        $pdf->Cell(18, 4, $label, 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->MultiCell(0, 4, $this->cortar($valor, 56), 0, 'L');
    }

    private function total(TCPDF $pdf, string $label, float $monto, bool $fuerte = false): void
    {
        $pdf->SetFont('helvetica', $fuerte ? 'B' : '', $fuerte ? 8 : 7);
        $pdf->Cell(34, 5, $label, 0, 0, 'L');
        $pdf->Cell(0, 5, 'C$ ' . number_format($monto, 2), 0, 1, 'R');
    }

    private function totalSinPrefijo(TCPDF $pdf, string $label, string $valor, bool $fuerte = false): void
    {
        $pdf->SetFont('helvetica', $fuerte ? 'B' : '', $fuerte ? 8 : 7);
        $pdf->Cell(34, 5, $label, 0, 0, 'L');
        $pdf->Cell(0, 5, $valor, 0, 1, 'R');
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

    private function clienteNombre(object $recibo): string
    {
        if ((int) ($recibo->Tipo_Cliente ?? 0) === 2 || filled($recibo->Institucion ?? null)) {
            return $this->texto($recibo->Institucion ?? 'Institución');
        }

        $nombre = trim(implode(' ', array_filter([
            $recibo->Primer_Nombre ?? null,
            $recibo->Segundo_Nombre ?? null,
            $recibo->Primer_Apellido ?? null,
            $recibo->Segundo_Apellido ?? null,
        ])));

        return $this->texto($nombre ?: 'Cliente');
    }

    private function telefonoCliente(object $recibo): string
    {
        foreach (['Telefono_Institucion', 'Telefono'] as $campo) {
            if (filled($recibo->{$campo} ?? null)) {
                return (string) $recibo->{$campo};
            }
        }

        return 'Sin teléfono';
    }

    private function metodoNombre(string $metodo): string
    {
        return match (strtolower($metodo)) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'tarjeta' => 'Tarjeta',
            'deposito' => 'Depósito',
            default => $this->texto($metodo),
        };
    }
}
