<?php

namespace App\Services\Reportes;

use App\Models\Reportes\VwReporteOtrasSalidas;
use App\Services\Reportes\Base\BaseReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class OtrasSalidasReporteService extends BaseReporteService
{
    private string $desde;

    private string $hasta;

    private string $tipoMovimiento;

    public function __construct(private readonly Request $request)
    {
        $this->desde = (string) $this->request->query('desde', now()->startOfMonth()->toDateString());
        $this->hasta = (string) $this->request->query('hasta', now()->toDateString());
        $this->tipoMovimiento = trim((string) $this->request->query('tipoSalida', ''));
    }

    public function titulo(): string
    {
        return $this->tipoMovimiento !== ''
            ? 'Reporte de otras salidas por tipo'
            : 'Reporte general de otras salidas';
    }

    public function nombreArchivo(): string
    {
        $tipo = $this->tipoMovimiento !== ''
            ? str($this->tipoMovimiento)->lower()->replace([' ', '/', '\\'], '-')->toString()
            : 'general';

        return 'reporte-otras-salidas-' . $this->desde . '-' . $this->hasta . '-' . $tipo;
    }

    public function consultar(): Collection
    {
        return VwReporteOtrasSalidas::query()
            ->when($this->desde !== '', fn($query) => $query->whereDate('Fecha_Movimiento', '>=', $this->desde))
            ->when($this->hasta !== '', fn($query) => $query->whereDate('Fecha_Movimiento', '<=', $this->hasta))
            ->when($this->tipoMovimiento !== '', fn($query) => $query->where('Tipo_Movimiento', $this->tipoMovimiento))
            ->orderBy('Fecha_Movimiento')
            ->orderBy('Tipo_Movimiento_Nombre')
            ->orderBy('Producto')
            ->get();
    }

    public function resumen(Collection $datos): array
    {
        return [
            'Movimientos' => number_format($datos->count()),
            'Productos' => number_format($datos->pluck('Id_Producto')->unique()->count()),
            'Unidades' => number_format((int) $datos->sum('Cantidad')),
            'Tipos' => number_format($datos->pluck('Tipo_Movimiento')->unique()->count()),
            'Valor estimado' => 'C$ ' . number_format((float) $datos->sum('Valor_Estimado'), 2),
        ];
    }

    public function columnas(): array
    {
        return [
            [
                'key' => 'fecha',
                'label' => 'Fecha',
                'pdf' => 20,
                'word' => 1000,
                'tipo' => 'date',
            ],
            [
                'key' => 'codigo',
                'label' => 'Código',
                'pdf' => 15,
                'word' => 800,
                'tipo' => 'text',
                'limit' => 12,
            ],
            [
                'key' => 'producto',
                'label' => 'Producto',
                'pdf' => 38,
                'word' => 2200,
                'tipo' => 'text',
                'limit' => 28,
            ],
            [
                'key' => 'categoria',
                'label' => 'Categoría',
                'pdf' => 24,
                'word' => 1500,
                'tipo' => 'text',
                'limit' => 18,
            ],
            [
                'key' => 'marca',
                'label' => 'Marca',
                'pdf' => 18,
                'word' => 1200,
                'tipo' => 'text',
                'limit' => 14,
            ],
            [
                'key' => 'modelo',
                'label' => 'Modelo',
                'pdf' => 20,
                'word' => 1300,
                'tipo' => 'text',
                'limit' => 15,
            ],
            [
                'key' => 'serie',
                'label' => 'Serie',
                'pdf' => 24,
                'word' => 1450,
                'tipo' => 'text',
                'limit' => 18,
            ],
            [
                'key' => 'tipo_movimiento',
                'label' => 'Tipo salida',
                'pdf' => 31,
                'word' => 1900,
                'tipo' => 'text',
                'limit' => 24,
            ],
            [
                'key' => 'cantidad',
                'label' => 'Cant.',
                'pdf' => 14,
                'word' => 800,
                'tipo' => 'number',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'precio_venta',
                'label' => 'Precio',
                'pdf' => 22,
                'word' => 1200,
                'tipo' => 'money',
                'align_pdf' => 'R',
                'align_excel' => 'right',
                'align_word' => 'right',
            ],
            [
                'key' => 'valor_estimado',
                'label' => 'Valor',
                'pdf' => 22,
                'word' => 1300,
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
            'fecha' => $fila->Fecha_Movimiento
                ? Carbon::parse($fila->Fecha_Movimiento)->format('Y-m-d')
                : '',
            'codigo' => (string) $fila->Codigo,
            'producto' => (string) $fila->Producto,
            'categoria' => (string) $fila->Categoria,
            'marca' => (string) $fila->Marca,
            'modelo' => (string) $fila->Modelo,
            'serie' => (string) $fila->Numero_Serie,
            'tipo_movimiento' => (string) $fila->Tipo_Movimiento_Nombre,
            'cantidad' => (int) $fila->Cantidad,
            'precio_venta' => (float) $fila->Precio_Venta,
            'valor_estimado' => (float) $fila->Valor_Estimado,
        ];
    }
}
