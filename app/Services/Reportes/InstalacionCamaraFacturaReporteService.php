<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteInstalacionCamaraFactura;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InstalacionCamaraFacturaReporteService extends BaseReporteService
{
    private ?int $contratoId;

    private string $numeroContrato;

    private string $estado;

    private string $desde;

    private string $hasta;

    public function __construct(private readonly Request $request)
    {
        $contratoId = trim((string) $this->request->query('contratoId', ''));
        $estado = trim((string) $this->request->query('estado', ''));

        $this->contratoId = $contratoId !== '' && ctype_digit($contratoId) ? (int) $contratoId : null;
        $this->numeroContrato = trim((string) $this->request->query('numeroContrato', ''));
        $this->estado = in_array($estado, ['PENDIENTE', 'EN_PROCESO', 'FINALIZADO', 'CANCELADO'], true) ? $estado : '';
        $this->desde = trim((string) $this->request->query('desde', ''));
        $this->hasta = trim((string) $this->request->query('hasta', ''));
    }

    public function titulo(): string
    {
        if ($this->contratoId || $this->numeroContrato !== '') {
            return 'Facturación instalación de cámaras' . ($this->numeroContrato !== '' ? ' #' . $this->numeroContrato : '');
        }

        return 'Instalaciones de cámaras por estado y periodo';
    }

    public function nombreArchivo(): string
    {
        if ($this->contratoId || $this->numeroContrato !== '') {
            $contrato = $this->numeroContrato !== ''
                ? preg_replace('/[^A-Za-z0-9_-]+/', '-', $this->numeroContrato)
                : 'contrato-' . $this->contratoId;

            return 'reporte-instalacion-camara-factura-' . $contrato;
        }

        $estado = $this->estado !== '' ? strtolower($this->estado) : 'todos-los-estados';

        return 'reporte-instalaciones-camara-' . $this->desde . '-' . $this->hasta . '-' . $estado;
    }

    public function consultar(): Collection
    {
        $query = VwReporteInstalacionCamaraFactura::query();

        if ($this->contratoId) {
            $query->where('Id_Contrato_Instalacion_Camara', $this->contratoId);
        } elseif ($this->numeroContrato !== '') {
            $query->where(function ($query) {
                $query
                    ->where('Numero_Contrato', $this->numeroContrato)
                    ->orWhere('Numero_Factura', $this->numeroContrato);
            });
        } else {
            if ($this->desde === '' || $this->hasta === '') {
                return collect();
            }

            $query
                ->whereDate('Fecha_Contrato', '>=', $this->desde)
                ->whereDate('Fecha_Contrato', '<=', $this->hasta);

            if ($this->estado !== '') {
                $query->where('Estado_Contrato', $this->estado);
            }
        }

        return $query
            ->orderBy('Fecha_Contrato')
            ->orderBy('Numero_Contrato')
            ->orderByRaw('Id_Detalle_Venta IS NULL')
            ->orderBy('Tipo_Detalle')
            ->orderBy('Id_Fila_Reporte')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        $fila = $datos->first();

        if (! $fila) {
            return [
                'Periodo' => $this->periodoTexto(),
                'Estado' => $this->estado !== '' ? $this->estado : 'Todos',
                'Contratos' => '0',
                'Registros' => '0',
                'Total' => 'C$ 0.00',
                'Pagado' => 'C$ 0.00',
                'Saldo' => 'C$ 0.00',
            ];
        }

        if ($this->contratoId || $this->numeroContrato !== '') {
            return [
                'Contrato' => (string) $fila->Numero_Contrato,
                'Factura' => (string) ($fila->Numero_Factura ?: 'Sin factura'),
                'Cliente' => (string) $fila->Cliente,
                'Estado contrato' => (string) $fila->Estado_Contrato,
                'Cámaras' => number_format((int) $fila->Cantidad_Camaras),
                'Cableado' => number_format((float) $fila->Metros_Cableado, 2) . ' m',
                'Técnico' => (string) $fila->Tecnico,
                'Fecha' => $fila->Fecha_Contrato ? Carbon::parse($fila->Fecha_Contrato)->format('d/m/Y H:i') : '—',
                'Registros' => number_format($datos->count()),
                'Total' => 'C$ ' . number_format((float) $fila->Total_Factura, 2),
                'Pagado' => 'C$ ' . number_format((float) $fila->Total_Pagado_Cordobas, 2),
                'Saldo' => 'C$ ' . number_format((float) $fila->Saldo_Pendiente_Contrato, 2),
            ];
        }

        $contratos = $datos->unique('Id_Contrato_Instalacion_Camara')->values();

        return [
            'Periodo' => $this->periodoTexto(),
            'Estado' => $this->estado !== '' ? $this->estado : 'Todos',
            'Contratos' => number_format($contratos->count()),
            'Registros' => number_format($datos->count()),
            'Total' => 'C$ ' . number_format((float) $contratos->sum('Total_Factura'), 2),
            'Pagado' => 'C$ ' . number_format((float) $contratos->sum('Total_Pagado_Cordobas'), 2),
            'Saldo' => 'C$ ' . number_format((float) $contratos->sum('Saldo_Pendiente_Contrato'), 2),
        ];
    }

    public function columnas(): array
    {
        $columnas = [
            ['key' => 'fecha', 'label' => 'Fecha', 'pdf' => 20, 'word' => 900, 'tipo' => 'date', 'align_pdf' => 'L'],
            ['key' => 'contrato', 'label' => 'Contrato', 'pdf' => 28, 'word' => 1450, 'tipo' => 'text', 'limit' => 17],
            ['key' => 'cliente', 'label' => 'Cliente', 'pdf' => 32, 'word' => 1800, 'tipo' => 'text', 'limit' => 22],
            ['key' => 'factura', 'label' => 'Factura', 'pdf' => 24, 'word' => 1300, 'tipo' => 'text', 'limit' => 16],
            ['key' => 'ubicacion', 'label' => 'Ubicación', 'pdf' => 36, 'word' => 2100, 'tipo' => 'text', 'limit' => 26],
            ['key' => 'item', 'label' => 'Detalle', 'pdf' => 34, 'word' => 1950, 'tipo' => 'text', 'limit' => 24],
            ['key' => 'camaras', 'label' => 'Cámaras', 'pdf' => 17, 'word' => 850, 'tipo' => 'number', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'subtotal', 'label' => 'Subtotal', 'pdf' => 22, 'word' => 1150, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
            ['key' => 'saldo', 'label' => 'Saldo', 'pdf' => 22, 'word' => 1150, 'tipo' => 'money', 'align_pdf' => 'R', 'align_excel' => 'right', 'align_word' => 'right'],
        ];

        if (! $this->ocultarEstadoEnTabla()) {
            array_splice($columnas, 3, 0, [[
                'key' => 'estado',
                'label' => 'Estado',
                'pdf' => 22,
                'word' => 1150,
                'tipo' => 'badge',
            ]]);
        }

        return $columnas;
    }

    public function mapFila(mixed $fila): array
    {
        return [
            'fecha' => $fila->Fecha_Contrato ? Carbon::parse($fila->Fecha_Contrato)->format('Y-m-d') : '',
            'contrato' => (string) $fila->Numero_Contrato,
            'cliente' => (string) $fila->Cliente,
            'estado' => (string) $fila->Estado_Contrato,
            'factura' => (string) ($fila->Numero_Factura ?: 'Sin factura'),
            'ubicacion' => trim((string) $fila->Municipio . ' · ' . (string) $fila->Direccion_Instalacion),
            'item' => (string) $fila->Item,
            'camaras' => (int) $fila->Cantidad_Camaras,
            'subtotal' => (float) $fila->Subtotal,
            'saldo' => (float) $fila->Saldo_Pendiente_Contrato,
        ];
    }

    private function ocultarEstadoEnTabla(): bool
    {
        return ! $this->esReporteEspecifico() && $this->estado !== '';
    }

    private function esReporteEspecifico(): bool
    {
        return $this->contratoId !== null || $this->numeroContrato !== '';
    }

    private function periodoTexto(): string
    {
        if ($this->desde === '' && $this->hasta === '') {
            return '—';
        }

        return $this->desde . ' al ' . $this->hasta;
    }
}
