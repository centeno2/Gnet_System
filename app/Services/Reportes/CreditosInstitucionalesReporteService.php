<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteCreditosInstitucionales;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CreditosInstitucionalesReporteService extends BaseReporteService
{
    private string $desde;

    private string $hasta;

    private ?int $clienteId;

    public function __construct(private readonly Request $request)
    {
        $this->desde = (string) $this->request->query('desde', now()->startOfMonth()->toDateString());
        $this->hasta = (string) $this->request->query('hasta', now()->toDateString());

        $cliente = trim((string) $this->request->query('clienteId', ''));
        $this->clienteId = $cliente !== '' && ctype_digit($cliente) ? (int) $cliente : null;
    }

    public function titulo(): string
    {
        return 'Recepción de créditos institucionales';
    }

    public function nombreArchivo(): string
    {
        $institucion = $this->clienteId ? 'institucion-' . $this->clienteId : 'sin-institucion';

        return 'recepcion-creditos-institucionales-' . $this->desde . '-' . $this->hasta . '-' . $institucion;
    }

    public function consultar(): Collection
    {
        if (! $this->clienteId) {
            return collect();
        }

        return VwReporteCreditosInstitucionales::query()
            ->where('Id_Cliente', $this->clienteId)
            ->when($this->desde !== '', fn($query) => $query->whereDate('Fecha_Credito', '>=', $this->desde))
            ->when($this->hasta !== '', fn($query) => $query->whereDate('Fecha_Credito', '<=', $this->hasta))
            ->orderBy('Fecha_Credito')
            ->orderBy('Numero_Factura')
            ->orderByRaw("
                CASE Tipo_Detalle
                    WHEN 'COPIA' THEN 1
                    WHEN 'PRODUCTO' THEN 2
                    WHEN 'SERVICIO' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('Formato_Copia')
            ->orderBy('Item')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        return [
            'Institución' => $this->texto($datos->first()?->Institucion ?? 'Institución no seleccionada'),
            'Municipio' => $this->texto($datos->first()?->Municipio ?? '—'),
            'Periodo' => $this->periodoTexto(),
        ];
    }

    public function columnas(): array
    {
        return [
            [
                'key' => 'fecha',
                'label' => 'Fecha',
                'pdf' => 18,
                'word' => 1000,
                'tipo' => 'text',
                'align_pdf' => 'C',
                'align_excel' => 'center',
                'align_word' => 'center',
            ],
            [
                'key' => 'factura',
                'label' => 'Factura',
                'pdf' => 24,
                'word' => 1300,
                'tipo' => 'text',
                'limit' => 18,
            ],
            [
                'key' => 'item',
                'label' => 'Nombre del formato / producto / servicio',
                'pdf' => 62,
                'word' => 3800,
                'tipo' => 'text',
                'limit' => 55,
            ],
            [
                'key' => 'area',
                'label' => 'Área',
                'pdf' => 34,
                'word' => 1900,
                'tipo' => 'text',
                'limit' => 28,
            ],
            [
                'key' => 'tamano',
                'label' => 'Tamaño',
                'pdf' => 20,
                'word' => 1100,
                'tipo' => 'text',
                'limit' => 12,
                'align_pdf' => 'C',
                'align_excel' => 'center',
                'align_word' => 'center',
            ],
            [
                'key' => 'cantidad',
                'label' => 'Cant.',
                'pdf' => 18,
                'word' => 900,
                'tipo' => 'text',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'precio',
                'label' => 'P/Unit',
                'pdf' => 24,
                'word' => 1300,
                'tipo' => 'text',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'monto',
                'label' => 'Monto',
                'pdf' => 28,
                'word' => 1500,
                'tipo' => 'text',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'recibi',
                'label' => 'Recibí conforme',
                'pdf' => 28,
                'word' => 1700,
                'tipo' => 'text',
                'limit' => 24,
            ],
        ];
    }

    public function mapFila(mixed $fila): array
    {
        return [
            'fecha' => $fila->Fecha_Credito
                ? Carbon::parse($fila->Fecha_Credito)->format('d/m/Y')
                : '',

            'factura' => $this->texto($fila->Numero_Factura ?? ''),
            'item' => $this->nombreItem($fila),
            'area' => $this->texto($fila->Area ?? '—'),
            'tamano' => $this->tamanoItem($fila),
            'cantidad' => $this->formatearCantidad((float) ($fila->Cantidad ?? 0)),
            'precio' => $this->formatearDinero((float) ($fila->Precio_Unitario ?? 0)),
            'monto' => $this->formatearDinero((float) ($fila->Total_Linea ?? 0)),
            'recibi' => $this->texto($fila->Recibido_Por ?? ''),
        ];
    }

    public function filas(Collection $datos): Collection
    {
        $filas = parent::filas($datos);

        if ($datos->isEmpty()) {
            return $filas;
        }

        $totales = collect();

        $copias = $datos->where('Tipo_Detalle', 'COPIA');
        $productos = $datos->where('Tipo_Detalle', 'PRODUCTO');
        $servicios = $datos->where('Tipo_Detalle', 'SERVICIO');

        foreach ($this->formatosCopia() as $formatoId => $nombreFormato) {
            $copiasFormato = $copias->where('Formato_Copia', $formatoId);

            if ($copiasFormato->isEmpty()) {
                continue;
            }

            $totales->push($this->filaTotal(
                'TOTAL COPIAS ' . mb_strtoupper($nombreFormato),
                $nombreFormato,
                $this->formatearCantidad((float) $copiasFormato->sum('Cantidad')),
                $this->formatearDinero((float) $copiasFormato->sum('Total_Linea'))
            ));
        }

        if ($productos->isNotEmpty()) {
            $totales->push($this->filaTotal(
                'TOTAL PRODUCTOS',
                '—',
                $this->formatearCantidad((float) $productos->sum('Cantidad')),
                $this->formatearDinero((float) $productos->sum('Total_Linea'))
            ));
        }

        if ($servicios->isNotEmpty()) {
            $totales->push($this->filaTotal(
                'TOTAL SERVICIOS',
                '—',
                $this->formatearCantidad((float) $servicios->sum('Cantidad')),
                $this->formatearDinero((float) $servicios->sum('Total_Linea'))
            ));
        }

        $totales->push($this->filaTotal(
            'TOTAL GENERAL',
            '',
            '',
            $this->formatearDinero((float) $datos->sum('Total_Linea'))
        ));

        return $filas->concat($totales);
    }

    private function filaTotal(string $label, string $tamano, string $cantidad, string $monto): array
    {
        return [
            'fecha' => '',
            'factura' => '',
            'item' => $label,
            'area' => '',
            'tamano' => $tamano,
            'cantidad' => $cantidad,
            'precio' => '',
            'monto' => $monto,
            'recibi' => '',
        ];
    }

    private function nombreItem(mixed $fila): string
    {
        $tipo = (string) ($fila->Tipo_Detalle ?? '');
        $item = $this->texto($fila->Item ?? '—');

        return match ($tipo) {
            'PRODUCTO' => 'Producto: ' . $item,
            'SERVICIO' => 'Servicio: ' . $item,
            'COPIA' => $item,
            default => $item,
        };
    }

    private function tamanoItem(mixed $fila): string
    {
        $tipo = (string) ($fila->Tipo_Detalle ?? '');

        if ($tipo !== 'COPIA') {
            return '—';
        }

        return $this->texto($fila->Formato_Copia_Nombre ?? '—');
    }

    private function formatosCopia(): array
    {
        return [
            1 => 'Carta',
            2 => 'Oficio',
            3 => 'A4',
            4 => 'Legal',
        ];
    }

    private function formatearCantidad(float $cantidad): string
    {
        if ($cantidad === 0.0) {
            return '0';
        }

        return floor($cantidad) == $cantidad
            ? number_format($cantidad, 0, '.', ',')
            : number_format($cantidad, 2, '.', ',');
    }

    private function formatearDinero(float $monto): string
    {
        return 'C$ ' . number_format($monto, 2, '.', ',');
    }

    private function texto(mixed $valor): string
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            return '—';
        }

        return Str::of($texto)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    private function periodoTexto(): string
    {
        $desde = $this->desde !== ''
            ? Carbon::parse($this->desde)->format('d/m/Y')
            : '—';

        $hasta = $this->hasta !== ''
            ? Carbon::parse($this->hasta)->format('d/m/Y')
            : '—';

        return $desde . ' - ' . $hasta;
    }
}
