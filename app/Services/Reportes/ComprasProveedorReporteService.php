<?php

namespace App\Services\Reportes;

use App\Models\Proveedor;
use App\Models\Reportes\VwReporteComprasProveedor;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ComprasProveedorReporteService extends BaseReporteService
{
    private string $desde;

    private string $hasta;

    private string $proveedor;

    private string $proveedorId;

    private string $proveedorNombre = '';

    private string $proveedorRuc = '';

    public function __construct(private readonly Request $request)
    {
        $this->desde = (string) $this->request->query('desde', now()->startOfMonth()->toDateString());
        $this->hasta = (string) $this->request->query('hasta', now()->toDateString());
        $this->proveedor = trim((string) $this->request->query('proveedor', ''));
        $this->proveedorId = trim((string) $this->request->query('proveedorId', ''));

        $this->cargarProveedorFiltrado();
    }

    public function titulo(): string
    {
        if ($this->tieneProveedorFiltrado()) {
            return 'Reporte de compras del proveedor';
        }

        return 'Reporte de compras por proveedor';
    }

    public function nombreArchivo(): string
    {
        $nombreProveedor = $this->proveedorNombre !== ''
            ? $this->proveedorNombre
            : $this->proveedor;

        $sufijoProveedor = $nombreProveedor !== ''
            ? '-' . str($nombreProveedor)->slug('-')->limit(40, '')
            : '';

        return 'reporte-compras-proveedor-' . $this->desde . '-' . $this->hasta . $sufijoProveedor;
    }

    public function consultar(): Collection
    {
        return VwReporteComprasProveedor::query()
            ->when($this->desde !== '', fn ($query) => $query->whereDate('Fecha_Compra', '>=', $this->desde))
            ->when($this->hasta !== '', fn ($query) => $query->whereDate('Fecha_Compra', '<=', $this->hasta))
            ->when($this->proveedorId !== '', fn ($query) => $query->where('Id_Proveedor', (int) $this->proveedorId))
            ->orderBy('Fecha_Compra')
            ->orderBy('Numero_Factura')
            ->orderBy('Proveedor')
            ->orderBy('Producto')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        $comprasUnicas = $datos->unique('Id_Compra');

        $resumen = [];

        if ($this->tieneProveedorFiltrado()) {
            $resumen['Proveedor'] = $this->proveedorNombre !== ''
                ? $this->proveedorNombre
                : 'Proveedor seleccionado';

            $resumen['RUC'] = $this->proveedorRuc !== ''
                ? $this->proveedorRuc
                : 'Sin RUC';
        }

        return array_merge($resumen, [
            'Compras' => number_format($comprasUnicas->count()),
            'Detalles' => number_format($datos->count()),
            'Unidades' => number_format((float) $datos->sum('Cantidad'), 2),
            'Subtotal' => 'C$ ' . number_format((float) $datos->sum('Subtotal_Linea'), 2),
            'IVA' => 'C$ ' . number_format((float) $comprasUnicas->sum('Iva_Compra'), 2),
            'Retención' => 'C$ ' . number_format((float) $comprasUnicas->sum('Retencion_Compra'), 2),
            'Total compras' => 'C$ ' . number_format((float) $comprasUnicas->sum('Total_Compra'), 2),
        ]);
    }

    public function columnas(): array
    {
        $columnas = [
            [
                'key' => 'fecha',
                'label' => 'Fecha',
                'pdf' => 18,
                'word' => 900,
                'tipo' => 'date',
            ],
            [
                'key' => 'factura',
                'label' => 'Factura',
                'pdf' => 24,
                'word' => 1200,
                'tipo' => 'text',
                'limit' => 16,
            ],
        ];

        if (! $this->tieneProveedorFiltrado()) {
            $columnas[] = [
                'key' => 'proveedor',
                'label' => 'Proveedor',
                'pdf' => 34,
                'word' => 1900,
                'tipo' => 'text',
                'limit' => 24,
            ];

            $columnas[] = [
                'key' => 'ruc',
                'label' => 'RUC',
                'pdf' => 24,
                'word' => 1200,
                'tipo' => 'text',
                'limit' => 16,
            ];
        }

        return array_merge($columnas, [
            [
                'key' => 'tipo',
                'label' => 'Tipo',
                'pdf' => 18,
                'word' => 900,
                'tipo' => 'text',
                'limit' => 12,
            ],
            [
                'key' => 'producto',
                'label' => 'Producto',
                'pdf' => $this->tieneProveedorFiltrado() ? 50 : 34,
                'word' => 2200,
                'tipo' => 'text',
                'limit' => $this->tieneProveedorFiltrado() ? 38 : 26,
            ],
            [
                'key' => 'garantia',
                'label' => 'Garantía',
                'pdf' => 20,
                'word' => 1000,
                'tipo' => 'text',
                'limit' => 14,
            ],
            [
                'key' => 'cantidad',
                'label' => 'Cant.',
                'pdf' => 14,
                'word' => 700,
                'tipo' => 'number',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'precio',
                'label' => 'Precio',
                'pdf' => 22,
                'word' => 1100,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'subtotal',
                'label' => 'Subtotal',
                'pdf' => 24,
                'word' => 1200,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
        ]);
    }

    public function mapFila(mixed $fila): array
    {
        $filaMapeada = [
            'fecha' => $fila->Fecha_Compra
                ? Carbon::parse($fila->Fecha_Compra)->format('Y-m-d')
                : '',
            'factura' => (string) $fila->Numero_Factura,
        ];

        if (! $this->tieneProveedorFiltrado()) {
            $filaMapeada['proveedor'] = (string) $fila->Proveedor;
            $filaMapeada['ruc'] = (string) $fila->Codigo_Ruc;
        }

        return array_merge($filaMapeada, [
            'tipo' => (string) $fila->Tipo_Compra_Nombre,
            'producto' => (string) $fila->Producto,
            'garantia' => (string) $fila->Garantia_Proveedor,
            'cantidad' => (float) $fila->Cantidad,
            'precio' => (float) $fila->Precio_Compra,
            'subtotal' => (float) $fila->Subtotal_Linea,
        ]);
    }

    public function filas(Collection $datos): Collection
    {
        $filas = parent::filas($datos);

        if ($datos->isEmpty()) {
            return $filas;
        }

        $total = [
            'fecha' => '',
            'factura' => '',
        ];

        if (! $this->tieneProveedorFiltrado()) {
            $total['proveedor'] = '';
            $total['ruc'] = '';
        }

        return $filas->concat([
            array_merge($total, [
                'tipo' => 'TOTAL',
                'producto' => 'TOTAL GENERAL',
                'garantia' => '',
                'cantidad' => (float) $datos->sum('Cantidad'),
                'precio' => 0,
                'subtotal' => (float) $datos->sum('Subtotal_Linea'),
            ]),
        ]);
    }

    private function tieneProveedorFiltrado(): bool
    {
        return $this->proveedorId !== '' && ctype_digit($this->proveedorId);
    }

    private function cargarProveedorFiltrado(): void
    {
        if (! $this->tieneProveedorFiltrado()) {
            return;
        }

        $proveedor = Proveedor::query()
            ->with('persona')
            ->find((int) $this->proveedorId);

        if (! $proveedor) {
            return;
        }

        $this->proveedorNombre = $this->nombreProveedor($proveedor);
        $this->proveedorRuc = (string) ($proveedor->Codigo_Ruc ?: 'Sin RUC');
    }

    private function nombreProveedor(Proveedor $proveedor): string
    {
        if ((int) $proveedor->Tipo_Proveedor === 2) {
            return trim((string) $proveedor->Empresa) !== ''
                ? (string) $proveedor->Empresa
                : 'Proveedor #' . $proveedor->Id_Proveedor;
        }

        $persona = $proveedor->persona;

        $nombre = trim(
            ($persona?->Primer_Nombre ?? '') . ' ' .
            ($persona?->Segundo_Nombre ?? '') . ' ' .
            ($persona?->Primer_Apellido ?? '') . ' ' .
            ($persona?->Segundo_Apellido ?? '')
        );

        return $nombre !== ''
            ? $nombre
            : 'Proveedor #' . $proveedor->Id_Proveedor;
    }
}