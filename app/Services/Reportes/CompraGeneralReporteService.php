<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteCompraGeneralDetalle;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CompraGeneralReporteService extends BaseReporteService
{
    private ?int $compraId;

    private string $numeroCompra;

    public function __construct(private readonly Request $request)
    {
        $compraId = trim((string) $this->request->query('compraId', ''));

        $this->compraId = $compraId !== '' && ctype_digit($compraId) ? (int) $compraId : null;
        $this->numeroCompra = trim((string) $this->request->query('numeroCompra', ''));
    }

    public function titulo(): string
    {
        return $this->numeroCompra !== ''
            ? 'Compra #' . $this->numeroCompra
            : 'Compra general';
    }

    public function nombreArchivo(): string
    {
        $compra = $this->numeroCompra !== ''
            ? preg_replace('/[^A-Za-z0-9_-]+/', '-', $this->numeroCompra)
            : 'compra-' . ($this->compraId ?: 'sin-numero');

        return 'reporte-compra-general-' . $compra;
    }

    public function consultar(): Collection
    {
        $query = VwReporteCompraGeneralDetalle::query();

        if ($this->compraId) {
            $query->where('Id_Compra', $this->compraId);
        } elseif ($this->numeroCompra !== '') {
            $query->where('Numero_Compra', $this->numeroCompra);
        } else {
            return collect();
        }

        return $query
            ->orderBy('Id_Detalle_Compra')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        $fila = $datos->first();

        if (! $fila) {
            return [
                'Compra' => $this->numeroCompra !== '' ? $this->numeroCompra : '—',
                'Registros' => '0',
                'Total compra' => 'C$ 0.00',
                'IVA' => 'C$ 0.00',
                'Retención' => 'C$ 0.00',
            ];
        }

        $cuenta = trim((string) ($fila->Nombre_Banco ?? ''));

        if ($cuenta !== '' && trim((string) ($fila->Cuenta_Ultimos_Digitos ?? '')) !== '') {
            $cuenta .= ' · ****' . $fila->Cuenta_Ultimos_Digitos;
        }

        return [
            'Compra' => (string) $fila->Numero_Compra,
            'Proveedor' => (string) $fila->Proveedor,
            'Fecha' => $fila->Fecha_Compra ? Carbon::parse($fila->Fecha_Compra)->format('d/m/Y H:i') : '—',
            'Tipo' => (string) $fila->Tipo_Compra,
            'Medio pago' => (string) $fila->Medio_Pago,
            'Cuenta' => $cuenta !== '' ? $cuenta : '—',
            'Registros' => number_format($datos->whereNotNull('Id_Detalle_Compra')->count()),
            'IVA' => 'C$ ' . number_format((float) $fila->Iva, 2),
            'Retención' => 'C$ ' . number_format((float) $fila->Retencion, 2),
            'Total compra' => 'C$ ' . number_format((float) $fila->Total_Compra, 2),
        ];
    }

    public function columnas(): array
    {
        return [
            [
                'key' => 'producto',
                'label' => 'Producto',
                'pdf' => 50,
                'word' => 2700,
                'tipo' => 'text',
                'limit' => 38,
            ],
            [
                'key' => 'categoria',
                'label' => 'Categoría',
                'pdf' => 28,
                'word' => 1500,
                'tipo' => 'text',
                'limit' => 20,
            ],
            [
                'key' => 'marca',
                'label' => 'Marca',
                'pdf' => 26,
                'word' => 1400,
                'tipo' => 'text',
                'limit' => 18,
            ],
            [
                'key' => 'cantidad',
                'label' => 'Cant.',
                'pdf' => 18,
                'word' => 850,
                'tipo' => 'number',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'precio_compra',
                'label' => 'P. compra',
                'pdf' => 28,
                'word' => 1300,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'precio_venta',
                'label' => 'P. venta',
                'pdf' => 26,
                'word' => 1250,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'garantia',
                'label' => 'Garantía',
                'pdf' => 22,
                'word' => 1050,
                'tipo' => 'text',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'subtotal',
                'label' => 'Subtotal',
                'pdf' => 32,
                'word' => 1450,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
        ];
    }

    public function mapFila(mixed $fila): array
    {
        return [
            'producto' => (string) ($fila->Producto ?: 'Producto no asignado'),
            'categoria' => (string) ($fila->Nombre_Categoria ?: '—'),
            'marca' => (string) ($fila->Nombre_Marca ?: '—'),
            'cantidad' => (float) $fila->Cantidad,
            'precio_compra' => (float) $fila->Precio_Compra,
            'precio_venta' => (float) $fila->Precio_Venta,
            'garantia' => is_null($fila->Meses_Garantia_Proveedor)
                ? '—'
                : number_format((int) $fila->Meses_Garantia_Proveedor) . ' mes(es)',
            'subtotal' => (float) $fila->Subtotal,
        ];
    }
}
