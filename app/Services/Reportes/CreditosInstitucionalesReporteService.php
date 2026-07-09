<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteCreditosInstitucionales;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreditosInstitucionalesReporteService extends BaseReporteService
{
    protected string $desde;

    protected string $hasta;

    protected ?int $clienteId;

    protected bool $soloPendientes;

    protected bool $usarRangoFechas;

    public function __construct(private readonly Request $request)
    {
        $this->desde = (string) $this->request->query('desde', now()->startOfMonth()->toDateString());
        $this->hasta = (string) $this->request->query('hasta', now()->toDateString());

        $cliente = trim((string) $this->request->query('clienteId', ''));
        $this->clienteId = $cliente !== '' && ctype_digit($cliente) ? (int) $cliente : null;
        $this->soloPendientes = $this->resolverSoloPendientes();
        $this->usarRangoFechas = $this->resolverUsarRangoFechas();
    }

    public function titulo(): string
    {
        if ($this->soloPendientes) {
            return 'Créditos institucionales pendientes';
        }

        return 'Recepción de créditos institucionales';
    }

    public function nombreArchivo(): string
    {
        $institucion = $this->clienteId ? 'institucion-' . $this->clienteId : 'sin-institucion';

        if ($this->soloPendientes) {
            return 'creditos-institucionales-pendientes-' . $institucion;
        }

        return 'recepcion-creditos-institucionales-' . $this->desde . '-' . $this->hasta . '-' . $institucion;
    }

    public function consultar(): Collection
    {
        if (! $this->clienteId) {
            return collect();
        }

        $query = $this->queryReporte()
            ->where('reporte.Id_Cliente', $this->clienteId);

        if ($this->usarRangoFechas) {
            $query
                ->when($this->desde !== '', fn($query) => $query->whereDate('reporte.Fecha_Credito', '>=', $this->desde))
                ->when($this->hasta !== '', fn($query) => $query->whereDate('reporte.Fecha_Credito', '<=', $this->hasta));
        }

        if ($this->soloPendientes) {
            $query
                ->where('reporte.Saldo_Actual', '>', 0)
                ->where(function ($query) {
                    $query
                        ->whereNull('reporte.Estado_Credito')
                        ->orWhereNotIn('reporte.Estado_Credito', ['CANCELADO', 'PAGADO', 'COMPLETADO']);
                });
        }

        return $query
            ->orderBy('reporte.Fecha_Credito')
            ->orderBy('reporte.Numero_Factura')
            ->orderByRaw("
                CASE reporte.Tipo_Detalle
                    WHEN 'COPIA' THEN 1
                    WHEN 'PRODUCTO' THEN 2
                    WHEN 'SERVICIO' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('reporte.Formato_Copia')
            ->orderBy('reporte.Item')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        $resumen = [
            'Institución' => $this->texto($datos->first()?->Institucion ?? 'Institución no seleccionada'),
            'Municipio' => $this->texto($datos->first()?->Municipio ?? '—'),
        ];

        if ($this->usarRangoFechas) {
            $resumen['Periodo'] = $this->periodoTexto();
        } else {
            $resumen['Consulta'] = 'Pendientes sin pagar';
        }

        if ($this->soloPendientes) {
            $resumen['Saldo pendiente'] = $this->formatearDinero($this->totalSaldoPendiente($datos));
        }

        return $resumen;
    }

    public function columnas(): array
    {
        return [
            [
                'key' => 'fecha',
                'label' => 'Fecha',
                'pdf' => 17,
                'word' => 900,
                'tipo' => 'text',
                'align_pdf' => 'C',
                'align_excel' => 'center',
                'align_word' => 'center',
            ],
            [
                'key' => 'factura',
                'label' => 'Factura',
                'pdf' => 22,
                'word' => 1150,
                'tipo' => 'text',
                'limit' => 18,
            ],
            [
                'key' => 'item',
                'label' => 'Producto / servicio',
                'pdf' => 50,
                'word' => 3000,
                'tipo' => 'text',
                'limit' => 45,
            ],
            [
                'key' => 'area',
                'label' => 'Área',
                'pdf' => 28,
                'word' => 1600,
                'tipo' => 'text',
                'limit' => 22,
            ],
            [
                'key' => 'formato',
                'label' => 'Formato',
                'pdf' => 30,
                'word' => 1700,
                'tipo' => 'text',
                'limit' => 24,
            ],
            [
                'key' => 'tamano',
                'label' => 'Tamaño',
                'pdf' => 18,
                'word' => 950,
                'tipo' => 'text',
                'limit' => 10,
                'align_pdf' => 'C',
                'align_excel' => 'center',
                'align_word' => 'center',
            ],
            [
                'key' => 'cantidad',
                'label' => 'Cant.',
                'pdf' => 15,
                'word' => 800,
                'tipo' => 'text',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'precio',
                'label' => 'P/Unit',
                'pdf' => 22,
                'word' => 1150,
                'tipo' => 'text',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'monto',
                'label' => 'Monto',
                'pdf' => 25,
                'word' => 1250,
                'tipo' => 'text',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'recibi',
                'label' => 'Recibí conforme',
                'pdf' => 26,
                'word' => 1500,
                'tipo' => 'text',
                'limit' => 22,
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
            'formato' => $this->texto($fila->Formato_Entrega ?? '—'),
            'tamano' => $this->tamanoItem($fila),
            'cantidad' => $this->formatearCantidad((float) ($fila->Cantidad ?? 0)),
            'precio' => $this->formatearDinero((float) ($fila->Precio_Unitario ?? 0)),
            'monto' => $this->formatearDinero((float) ($fila->Total_Linea ?? 0)),
            'recibi' => $this->texto($fila->Recibido_Por ?? ''),
            '_estado_credito' => (string) ($fila->Estado_Credito ?? ''),
            '_saldo_actual' => (float) ($fila->Saldo_Actual ?? 0),
            '_pagado' => $this->creditoPagado($fila),
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

    public function filaEsTotal(mixed $fila): bool
    {
        return str_starts_with((string) data_get($fila, 'item', ''), 'TOTAL ');
    }

    public function filaEsTotalGeneral(mixed $fila): bool
    {
        return (string) data_get($fila, 'item', '') === 'TOTAL GENERAL';
    }

    public function estiloFila(mixed $fila): ?array
    {
        if ($this->filaEsTotal($fila) || ! (bool) data_get($fila, '_pagado', false)) {
            return null;
        }

        return [
            'fondo' => 'DCFCE7',
            'texto' => '14532D',
        ];
    }

    public function firmaReporte(): ?array
    {
        return [
            'nombre' => 'Luis Alvarado',
        ];
    }

    private function filaTotal(string $label, string $tamano, string $cantidad, string $monto): array
    {
        return [
            'fecha' => '',
            'factura' => '',
            'item' => $label,
            'area' => '',
            'formato' => '',
            'tamano' => $tamano,
            'cantidad' => $cantidad,
            'precio' => '',
            'monto' => $monto,
            'recibi' => '',
        ];
    }

    protected function resolverSoloPendientes(): bool
    {
        return false;
    }

    protected function resolverUsarRangoFechas(): bool
    {
        return true;
    }

    private function queryReporte()
    {
        $query = VwReporteCreditosInstitucionales::query()
            ->from('vw_reporte_creditos_institucionales as reporte')
            ->select('reporte.*');

        if (! Schema::hasColumn('credito', 'Formato')) {
            return $query->selectRaw("'—' as Formato_Entrega");
        }

        return $query
            ->leftJoin('credito as credito_formato', 'credito_formato.Id_Credito', '=', 'reporte.Id_Credito')
            ->selectRaw("COALESCE(NULLIF(TRIM(credito_formato.Formato), ''), '—') as Formato_Entrega");
    }

    private function creditoPagado(mixed $fila): bool
    {
        $estado = mb_strtoupper(trim((string) ($fila->Estado_Credito ?? '')));
        $saldo = round((float) ($fila->Saldo_Actual ?? 0), 2);

        return $saldo <= 0 || in_array($estado, ['CANCELADO', 'PAGADO', 'COMPLETADO'], true);
    }

    private function totalSaldoPendiente(Collection $datos): float
    {
        return round((float) $datos
            ->unique(fn ($fila) => (int) ($fila->Id_Credito ?? 0))
            ->sum(fn ($fila) => max((float) ($fila->Saldo_Actual ?? 0), 0)), 2);
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

        $formatoId = (int) ($fila->Formato_Copia ?? 0);

        if ($formatoId > 0 && isset($this->formatosCopia()[$formatoId])) {
            return $this->formatosCopia()[$formatoId];
        }

        return $this->tamanoCopiaDesdeTexto(
            $fila->Item ?? '',
            $fila->Formato_Copia_Nombre ?? '',
            $fila->Nombre_Formato ?? ''
        );
    }

    private function tamanoCopiaDesdeTexto(mixed ...$valores): string
    {
        $texto = Str::lower(Str::ascii(implode(' ', array_map(fn ($valor) => (string) $valor, $valores))));

        return match (true) {
            str_contains($texto, 'oficio') => 'Oficio',
            str_contains($texto, 'legal') => 'Legal',
            str_contains($texto, 'carta') => 'Carta',
            str_contains($texto, 'a4') => 'A4',
            default => '—',
        };
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
