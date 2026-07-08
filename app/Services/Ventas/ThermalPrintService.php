<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ThermalPrintService
{
    public function imprimirVenta(int $ventaId): void
    {
        $venta = Venta::query()->findOrFail($ventaId);

        $ticket = $this->generarTicket($venta);
        $conexion = $this->conexionImpresora();
        $destino = $this->destinoImpresora($conexion);

        if (in_array($conexion, ['cups', 'linux'], true)) {
            $this->imprimirConCups($destino, $ticket);
            return;
        }

        $resultado = @file_put_contents($destino, $ticket);

        if ($resultado === false) {
            throw new \RuntimeException(
                'No se pudo enviar el voucher a la impresora. Verifica que esté compartida como: ' . $destino
            );
        }
    }

    private function generarTicket(Venta $venta): string
    {
        $cliente = $this->nombreCliente((int) ($venta->Id_Cliente ?? 0));

        $fecha = $venta->Fecha_venta
            ? Carbon::parse($venta->Fecha_venta)->format('d/m/Y h:i A')
            : now()->format('d/m/Y h:i A');

        $detalles = $this->detallesVenta($venta);
        $observacion = $this->observacionDetalleVenta($detalles);

        $ticket = '';

        // Inicializar impresora ESC/POS
        $ticket .= "\x1B\x40";

        $ticket .= $this->centrar('GNET SYSTEM') . "\n";
        $ticket .= $this->centrar('Parque Dario 1C Norte') . "\n";
        $ticket .= $this->centrar('Matagalpa, Nicaragua') . "\n";
        $ticket .= $this->centrar('Tel: 8737-1426') . "\n";
        $ticket .= $this->linea() . "\n";

        $ticket .= $this->texto("Factura: {$venta->Numero_Factura}") . "\n";
        $ticket .= $this->texto("Fecha: {$fecha}") . "\n";
        $ticket .= $this->texto("Cliente: " . $this->cortar($cliente, $this->anchoPapel() - 9)) . "\n";
        $ticket .= $this->texto("Tipo: {$venta->Tipo_Venta}") . "\n";
        $ticket .= $this->linea() . "\n";

        $ticket .= $this->texto('DETALLE') . "\n";
        $ticket .= $this->linea() . "\n";

        foreach ($detalles as $detalle) {
            $descripcion = $this->limpiarTexto((string) $detalle->Descripcion);

            foreach ($this->envolver($descripcion, $this->anchoPapel()) as $lineaDescripcion) {
                $ticket .= $this->texto($lineaDescripcion) . "\n";
            }

            $cantidad = (int) $detalle->Cantidad;
            $precio = (float) $detalle->Precio_Unitario;
            $descuento = (float) $detalle->Descuento;
            $subtotal = (float) $detalle->Subtotal;

            $ticket .= $this->texto("{$cantidad} x C$ " . number_format($precio, 2)) . "\n";

            if ($descuento > 0) {
                $ticket .= $this->texto('Desc: C$ ' . number_format($descuento, 2)) . "\n";
            }

            $ticket .= $this->derecha('C$ ' . number_format($subtotal, 2)) . "\n";
        }

        if ($observacion !== '') {
            $ticket .= $this->linea() . "\n";
            $ticket .= $this->texto('OBSERVACION') . "\n";

            foreach ($this->envolver($observacion, $this->anchoPapel()) as $lineaObservacion) {
                $ticket .= $this->texto($lineaObservacion) . "\n";
            }
        }

        $ticket .= $this->linea() . "\n";

        if ((float) $venta->Descuento > 0) {
            $ticket .= $this->derecha('Descuento: C$ ' . number_format((float) $venta->Descuento, 2)) . "\n";
        }

        $ticket .= $this->derecha('TOTAL: C$ ' . number_format((float) $venta->Total, 2)) . "\n";

        if ((float) ($venta->Cambio_Entregado_Cordobas ?? 0) > 0) {
            $ticket .= $this->derecha('Cambio: C$ ' . number_format((float) $venta->Cambio_Entregado_Cordobas, 2)) . "\n";
        }

        $ticket .= $this->linea() . "\n";

        $pagos = $this->pagosVenta($venta);

        if ($pagos->isNotEmpty()) {
            $ticket .= $this->texto('PAGOS') . "\n";

            foreach ($pagos as $pago) {
                $moneda = (int) $pago->Moneda === 1 ? 'US$' : 'C$';

                $ticket .= $this->texto(
                    "{$pago->Tipo_Pago}: {$moneda} " . number_format((float) $pago->Monto, 2)
                ) . "\n";

                if (! empty($pago->Numero_Referencia)) {
                    $ticket .= $this->texto("Ref: {$pago->Numero_Referencia}") . "\n";
                }
            }

            $ticket .= $this->linea() . "\n";
        }

        $ticket .= "\n";
        $ticket .= $this->centrar('Gracias por su compra') . "\n";
        $ticket .= "\n\n\n";

        if ($this->debeCortarPapel()) {
            // Corte de papel ESC/POS
            $ticket .= "\x1D\x56\x00";
        }

        return $ticket;
    }

    private function detallesVenta(Venta $venta)
    {
        return DB::table('detalle_venta as dv')
            ->leftJoin('producto as p', 'p.Id_Producto', '=', 'dv.Id_Producto')
            ->leftJoin('producto_serie as ps', 'ps.id_producto_serie', '=', 'dv.Id_Producto_serie')
            ->leftJoin('tarifa_copia as tc', 'tc.Id_Tarifa_Copia', '=', 'dv.Id_Tarifa_Copia')
            ->where('dv.Id_Venta', $venta->Id_Venta)
            ->selectRaw("
                dv.Cantidad,
                dv.Precio_Unitario,
                dv.Descuento,
                dv.Subtotal,
                dv.Observacion,
                COALESCE(
                    NULLIF(TRIM(CONCAT_WS(' ', p.Nombre_Producto, CASE WHEN ps.Numero_Serie IS NOT NULL THEN CONCAT('Serie:', ps.Numero_Serie) ELSE NULL END)), ''),
                    dv.Nombre_Formato,
                    tc.Nombre_Tarifa,
                    'Item'
                ) as Descripcion
            ")
            ->get();
    }

    private function observacionDetalleVenta($detalles): string
    {
        foreach ($detalles as $detalle) {
            $observacion = trim((string) ($detalle->Observacion ?? ''));

            if ($observacion !== '') {
                return $this->limpiarTexto($observacion);
            }
        }

        return '';
    }

    private function pagosVenta(Venta $venta)
    {
        return DB::table('pago_venta')
            ->where('Id_Venta', $venta->Id_Venta)
            ->get();
    }

    private function nombreCliente(int $clienteId): string
    {
        if ($clienteId <= 0) {
            return 'Consumidor final';
        }

        $cliente = DB::table('cliente as c')
            ->leftJoin('persona as p', 'p.Id_Persona', '=', 'c.Id_Persona')
            ->where('c.Id_Cliente', $clienteId)
            ->selectRaw("
                CASE
                    WHEN c.Tipo_Cliente = 2 THEN COALESCE(c.Institucion, 'Institucion')
                    ELSE COALESCE(
                        NULLIF(TRIM(CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido)), ''),
                        'Cliente natural'
                    )
                END as Nombre
            ")
            ->first();

        return $cliente->Nombre ?? 'Consumidor final';
    }

    private function conexionImpresora(): string
    {
        $conexion = strtolower(trim((string) config('services.thermal.connection', '')));

        if ($conexion !== '') {
            return $conexion;
        }

        return PHP_OS_FAMILY === 'Windows' ? 'windows' : 'cups';
    }

    private function destinoImpresora(string $conexion): string
    {
        $printerName = trim((string) config('services.thermal.printer_name', ''));

        if ($printerName === '') {
            throw new \RuntimeException('Configura THERMAL_PRINTER_NAME en el archivo .env.');
        }

        $printerName = trim($printerName, "\"' ");

        if (in_array($conexion, ['cups', 'linux', 'file'], true)) {
            return $printerName;
        }

        if (str_starts_with($printerName, '\\\\')) {
            return $printerName;
        }

        if ($conexion === 'windows' || PHP_OS_FAMILY === 'Windows') {
            return '\\\\localhost\\' . $printerName;
        }

        return $printerName;
    }

    private function imprimirConCups(string $cola, string $ticket): void
    {
        $proceso = @proc_open(
            ['lp', '-d', $cola, '-o', 'raw'],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (! is_resource($proceso)) {
            throw new \RuntimeException('No se pudo abrir CUPS. Verifica que el comando lp esté instalado.');
        }

        fwrite($pipes[0], $ticket);
        fclose($pipes[0]);

        $salida = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $codigo = proc_close($proceso);

        if ($codigo !== 0) {
            $detalle = trim($error ?: $salida);

            throw new \RuntimeException(
                'No se pudo enviar el voucher a la cola CUPS "' . $cola . '".'
                . ($detalle !== '' ? ' Detalle: ' . $detalle : '')
            );
        }
    }

    private function anchoPapel(): int
    {
        return (int) config('services.thermal.paper_width', 80) === 58 ? 32 : 48;
    }

    private function debeCortarPapel(): bool
    {
        return $this->booleanConfig('services.thermal.cut', true);
    }

    private function booleanConfig(string $key, bool $default = false): bool
    {
        $valor = config($key);

        if ($valor === null || $valor === '') {
            return $default;
        }

        return in_array(strtolower(trim((string) $valor)), ['1', 'true', 'yes', 'on', 'enable', 'enabled'], true);
    }

    private function linea(): string
    {
        return str_repeat('-', $this->anchoPapel());
    }

    private function centrar(string $texto): string
    {
        $texto = $this->limpiarTexto($texto);
        $ancho = $this->anchoPapel();

        if (strlen($texto) >= $ancho) {
            return substr($texto, 0, $ancho);
        }

        $espacios = (int) floor(($ancho - strlen($texto)) / 2);

        return str_repeat(' ', $espacios) . $texto;
    }

    private function derecha(string $texto): string
    {
        $texto = $this->limpiarTexto($texto);
        $ancho = $this->anchoPapel();

        if (strlen($texto) >= $ancho) {
            return substr($texto, 0, $ancho);
        }

        return str_pad($texto, $ancho, ' ', STR_PAD_LEFT);
    }

    private function envolver(string $texto, int $limite): array
    {
        $texto = $this->limpiarTexto($texto);

        if ($texto === '') {
            return [''];
        }

        return explode("\n", wordwrap($texto, $limite, "\n", true));
    }

    private function cortar(string $texto, int $limite): string
    {
        $texto = $this->limpiarTexto($texto);

        return strlen($texto) <= $limite
            ? $texto
            : substr($texto, 0, $limite - 3) . '...';
    }

    private function texto(string $texto): string
    {
        return $this->limpiarTexto($texto);
    }

    private function limpiarTexto(string $texto): string
    {
        $texto = strtr($texto, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'ñ' => 'n',
            'Ñ' => 'N',
            'ü' => 'u',
            'Ü' => 'U',
            '¿' => '',
            '¡' => '',
        ]);

        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $texto) ?? '';
    }
}
