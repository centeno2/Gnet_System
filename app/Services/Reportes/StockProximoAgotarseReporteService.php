<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteStockProximoAgotarse;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Support\Collection;

class StockProximoAgotarseReporteService extends BaseReporteService
{
    public function titulo(): string
    {
        return 'Reporte de stock próximo a agotarse';
    }

    public function nombreArchivo(): string
    {
        return 'reporte-stock-proximo-agotarse';
    }

    public function consultar(): Collection
    {
        return VwReporteStockProximoAgotarse::query()
            ->orderBy('Stock_Actual')
            ->orderBy('Unidades_Faltantes', 'desc')
            ->orderBy('Categoria')
            ->orderBy('Producto')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        return [
            'Productos' => number_format($datos->count()),
            'Unidades' => number_format((int) $datos->sum('Stock_Actual')),
            'Stock mínimo' => number_format((int) $datos->sum('Stock_Minimo')),
            'Faltantes' => number_format((int) $datos->sum('Unidades_Faltantes')),
            'Valor estimado' => 'C$ ' . number_format((float) $datos->sum('Valor_Estimado'), 2),
        ];
    }

    public function columnas(): array
    {
        return [
            [
                'key' => 'codigo',
                'label' => 'Código',
                'pdf' => 17,
                'word' => 900,
                'tipo' => 'text',
                'limit' => 14,
            ],
            [
                'key' => 'producto',
                'label' => 'Producto',
                'pdf' => 46,
                'word' => 2300,
                'tipo' => 'text',
                'limit' => 33,
            ],
            [
                'key' => 'categoria',
                'label' => 'Categoría',
                'pdf' => 29,
                'word' => 1700,
                'tipo' => 'text',
                'limit' => 20,
            ],
            [
                'key' => 'marca',
                'label' => 'Marca',
                'pdf' => 22,
                'word' => 1300,
                'tipo' => 'text',
                'limit' => 15,
            ],
            [
                'key' => 'modelo',
                'label' => 'Modelo',
                'pdf' => 25,
                'word' => 1400,
                'tipo' => 'text',
                'limit' => 17,
            ],
            [
                'key' => 'stock_actual',
                'label' => 'Stock',
                'pdf' => 15,
                'word' => 800,
                'tipo' => 'number',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'stock_minimo',
                'label' => 'Mín.',
                'pdf' => 15,
                'word' => 800,
                'tipo' => 'number',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'unidades_faltantes',
                'label' => 'Faltan',
                'pdf' => 17,
                'word' => 900,
                'tipo' => 'number',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'precio_venta',
                'label' => 'Precio',
                'pdf' => 23,
                'word' => 1200,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'valor_estimado',
                'label' => 'Valor',
                'pdf' => 25,
                'word' => 1400,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'estado',
                'label' => 'Estado',
                'pdf' => 29,
                'word' => 1400,
                'tipo' => 'badge',
            ],
        ];
    }

    public function mapFila(mixed $fila): array
    {
        return [
            'codigo' => (string) $fila->Codigo,
            'producto' => (string) $fila->Producto,
            'categoria' => (string) $fila->Categoria,
            'marca' => (string) $fila->Marca,
            'modelo' => (string) $fila->Modelo,
            'stock_actual' => (int) $fila->Stock_Actual,
            'stock_minimo' => (int) $fila->Stock_Minimo,
            'unidades_faltantes' => max(0, (int) $fila->Unidades_Faltantes),
            'precio_venta' => (float) $fila->Precio_Venta,
            'valor_estimado' => (float) $fila->Valor_Estimado,
            'estado' => (string) $fila->Estado_Stock,
        ];
    }
}
