<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteServicioTecnicoFactura;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ServicioTecnicoFacturaReporteService extends BaseReporteService
{
    private ?int $servicioTecnicoId;

    private string $numeroOrden;

    private string $estado;

    private string $desde;

    private string $hasta;

    public function __construct(private readonly Request $request)
    {
        $servicioTecnicoId = trim((string) $this->request->query('servicioTecnicoId', ''));
        $estado = trim((string) $this->request->query('estado', ''));

        $this->servicioTecnicoId = $servicioTecnicoId !== '' && ctype_digit($servicioTecnicoId) ? (int) $servicioTecnicoId : null;
        $this->numeroOrden = trim((string) $this->request->query('numeroOrden', ''));
        $this->estado = in_array($estado, ['RECIBIDO', 'EN_REVISION', 'PENDIENTE_REPUESTO', 'REPARADO', 'ENTREGADO', 'CANCELADO'], true) ? $estado : '';
        $this->desde = trim((string) $this->request->query('desde', ''));
        $this->hasta = trim((string) $this->request->query('hasta', ''));
    }

    public function titulo(): string
    {
        if ($this->servicioTecnicoId || $this->numeroOrden !== '') {
            return 'Facturación servicio técnico' . ($this->numeroOrden !== '' ? ' #' . $this->numeroOrden : '');
        }

        return 'Servicios técnicos por estado y periodo';
    }

    public function nombreArchivo(): string
    {
        if ($this->servicioTecnicoId || $this->numeroOrden !== '') {
            $orden = $this->numeroOrden !== ''
                ? preg_replace('/[^A-Za-z0-9_-]+/', '-', $this->numeroOrden)
                : 'servicio-' . $this->servicioTecnicoId;

            return 'reporte-servicio-tecnico-factura-' . $orden;
        }

        $estado = $this->estado !== '' ? strtolower($this->estado) : 'todos-los-estados';

        return 'reporte-servicios-tecnicos-' . $this->desde . '-' . $this->hasta . '-' . $estado;
    }

    public function consultar(): Collection
    {
        $query = VwReporteServicioTecnicoFactura::query();

        if ($this->servicioTecnicoId) {
            $query->where('Id_Servicio_Tecnico', $this->servicioTecnicoId);
        } elseif ($this->numeroOrden !== '') {
            $query->where(function ($query) {
                $query
                    ->where('Numero_Orden', $this->numeroOrden)
                    ->orWhere('Numero_Factura', $this->numeroOrden);
            });
        } else {
            if ($this->desde === '' || $this->hasta === '') {
                return collect();
            }

            $query
                ->whereDate('Fecha_Ingreso', '>=', $this->desde)
                ->whereDate('Fecha_Ingreso', '<=', $this->hasta);

            if ($this->estado !== '') {
                $query->where('Estado_Servicio', $this->estado);
            }
        }

        return $query
            ->orderBy('Fecha_Ingreso')
            ->orderBy('Numero_Orden')
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
                'Órdenes' => '0',
                'Registros' => '0',
                'Total' => 'C$ 0.00',
                'Pagado' => 'C$ 0.00',
                'Saldo' => 'C$ 0.00',
            ];
        }

        if ($this->servicioTecnicoId || $this->numeroOrden !== '') {
            return [
                'Orden' => (string) $fila->Numero_Orden,
                'Factura' => (string) ($fila->Numero_Factura ?: 'Sin factura'),
                'Cliente' => (string) $fila->Cliente,
                'Estado servicio' => (string) $fila->Estado_Servicio,
                'Equipo' => trim((string) $fila->Tipo_Equipo . ' ' . (string) $fila->Marca_Equipo . ' ' . (string) $fila->Modelo_Equipo),
                'Técnico' => (string) $fila->Tecnico,
                'Ingreso' => $fila->Fecha_Ingreso ? Carbon::parse($fila->Fecha_Ingreso)->format('d/m/Y H:i') : '—',
                'Registros' => number_format($datos->count()),
                'Total' => 'C$ ' . number_format((float) $fila->Total_Factura, 2),
                'Pagado' => 'C$ ' . number_format((float) $fila->Total_Pagado_Cordobas, 2),
                'Saldo' => 'C$ ' . number_format((float) $fila->Saldo_Pendiente_Servicio, 2),
            ];
        }

        $ordenes = $datos->unique('Id_Servicio_Tecnico')->values();

        return [
            'Periodo' => $this->periodoTexto(),
            'Estado' => $this->estado !== '' ? $this->estado : 'Todos',
            'Órdenes' => number_format($ordenes->count()),
            'Registros' => number_format($datos->count()),
            'Total' => 'C$ ' . number_format((float) $ordenes->sum('Total_Factura'), 2),
            'Pagado' => 'C$ ' . number_format((float) $ordenes->sum('Total_Pagado_Cordobas'), 2),
            'Saldo' => 'C$ ' . number_format((float) $ordenes->sum('Saldo_Pendiente_Servicio'), 2),
        ];
    }

    public function columnas(): array
    {
        $columnas = [
            ['key' => 'fecha', 'label' => 'Fecha', 'pdf' => 20, 'word' => 900, 'tipo' => 'date', 'align_pdf' => 'L'],
            ['key' => 'orden', 'label' => 'Orden', 'pdf' => 28, 'word' => 1450, 'tipo' => 'text', 'limit' => 17],
            ['key' => 'cliente', 'label' => 'Cliente', 'pdf' => 32, 'word' => 1800, 'tipo' => 'text', 'limit' => 22],
            ['key' => 'factura', 'label' => 'Factura', 'pdf' => 24, 'word' => 1300, 'tipo' => 'text', 'limit' => 16],
            ['key' => 'equipo', 'label' => 'Equipo', 'pdf' => 30, 'word' => 1700, 'tipo' => 'text', 'limit' => 21],
            ['key' => 'problema', 'label' => 'Problema', 'pdf' => 32, 'word' => 1900, 'tipo' => 'text', 'limit' => 23],
            ['key' => 'item', 'label' => 'Detalle', 'pdf' => 30, 'word' => 1750, 'tipo' => 'text', 'limit' => 22],
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
            'fecha' => $fila->Fecha_Ingreso ? Carbon::parse($fila->Fecha_Ingreso)->format('Y-m-d') : '',
            'orden' => (string) $fila->Numero_Orden,
            'cliente' => (string) $fila->Cliente,
            'estado' => (string) $fila->Estado_Servicio,
            'factura' => (string) ($fila->Numero_Factura ?: 'Sin factura'),
            'equipo' => trim((string) $fila->Tipo_Equipo . ' ' . (string) $fila->Marca_Equipo . ' ' . (string) $fila->Modelo_Equipo),
            'problema' => (string) $fila->Problema_Reportado,
            'item' => (string) $fila->Item,
            'subtotal' => (float) $fila->Subtotal,
            'saldo' => (float) $fila->Saldo_Pendiente_Servicio,
        ];
    }

    private function ocultarEstadoEnTabla(): bool
    {
        return ! $this->esReporteEspecifico() && $this->estado !== '';
    }

    private function esReporteEspecifico(): bool
    {
        return $this->servicioTecnicoId !== null || $this->numeroOrden !== '';
    }

    private function periodoTexto(): string
    {
        if ($this->desde === '' && $this->hasta === '') {
            return '—';
        }

        return $this->desde . ' al ' . $this->hasta;
    }
}
