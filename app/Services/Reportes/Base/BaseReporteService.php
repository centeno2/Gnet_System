<?php

namespace App\Services\Reportes\Base;

use Illuminate\Support\Collection;

abstract class BaseReporteService
{
    abstract public function titulo(): string;

    abstract public function nombreArchivo(): string;

    abstract public function consultar(): Collection;

    abstract public function columnas(): array;

    abstract public function resumen(Collection $datos): array;

    abstract public function mapFila(mixed $fila): array;

    public function datos(): Collection
    {
        return $this->consultar();
    }

    public function filas(Collection $datos): Collection
    {
        return $datos->map(fn($fila) => $this->mapFila($fila));
    }

    public function logoPath(): ?string
    {
        $paths = [
            public_path('img/gnetlogo.png'),
            public_path('images/logo-gnet.png'),
            public_path('images/logo.png'),
            public_path('logo.png'),
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public function colorEstado(string $estado): array
    {
        return match (mb_strtolower($estado)) {
            'stock bajo', 'próximo a agotarse', 'agotado', 'pendiente', 'moroso' => [
                'texto' => 'B91C1C',
                'fondo' => 'FEE2E2',
            ],
            'disponible', 'activo', 'pagado', 'completado' => [
                'texto' => '166534',
                'fondo' => 'DCFCE7',
            ],
            default => [
                'texto' => '1A2B42',
                'fondo' => 'F7F9FC',
            ],
        };
    }

    public function valorFormateado(mixed $valor, string $tipo = 'text'): string
    {
        return match ($tipo) {
            'money' => 'C$ ' . number_format((float) $valor, 2),
            'number' => number_format((float) $valor),
            'date' => filled($valor) ? date('d/m/Y', strtotime((string) $valor)) : '',
            default => (string) $valor,
        };
    }

    public function cacheKey(): string
    {
        return str($this->nombreArchivo())
            ->replace([' ', '/', '\\'], '-')
            ->lower()
            ->toString();
    }
}
