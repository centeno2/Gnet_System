<?php

namespace App\Services\Reportes\Base;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BaseExcelReporteService
{
    public function generar(BaseReporteService $reporte): Xlsx
    {
        $datos = $reporte->datos();
        $filas = $reporte->filas($datos);
        $resumen = $reporte->resumen($datos);
        $columnas = $reporte->columnas();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle($this->tituloHoja($reporte->nombreArchivo()));
        $sheet->setShowGridlines(false);
        $sheet->getDefaultRowDimension()->setRowHeight(22);

        $this->encabezado($sheet, $reporte, $resumen, $columnas);
        $this->tabla($sheet, $reporte, $filas, $columnas);

        $spreadsheet->getProperties()
            ->setCreator('Gnet System')
            ->setTitle($reporte->titulo())
            ->setSubject($reporte->titulo())
            ->setDescription($reporte->titulo() . ' generado desde Gnet System');

        return new Xlsx($spreadsheet);
    }

    private function encabezado(
        Worksheet $sheet,
        BaseReporteService $reporte,
        array $resumen,
        array $columnas
    ): void {
        $totalColumnas = max(count($columnas), 5);
        $ultimaColumna = Coordinate::stringFromColumnIndex($totalColumnas);

        $sheet->mergeCells("A1:{$ultimaColumna}3");

        $sheet->getStyle("A1:{$ultimaColumna}3")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'F0F3F7'],
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D7E4F3'],
                ],
            ],
        ]);

        $logo = $reporte->logoPath();

        if ($logo) {
            $drawing = new Drawing();
            $drawing->setName('Logo Gnet');
            $drawing->setDescription('Logo Gnet System');
            $drawing->setPath($logo);
            $drawing->setHeight(58);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(12);
            $drawing->setOffsetY(8);
            $drawing->setWorksheet($sheet);
        } else {
            $sheet->setCellValue('A1', 'GNET');
        }

        $sheet->setCellValue('C1', $reporte->titulo());
        $sheet->setCellValue('C2', 'Generado el: ' . now()->format('d/m/Y h:i A'));

        $sheet->getStyle('C1')->getFont()
            ->setBold(true)
            ->setSize(18)
            ->getColor()
            ->setRGB('1A2B42');

        $sheet->getStyle('C2')->getFont()
            ->setBold(true)
            ->setSize(10)
            ->getColor()
            ->setRGB('5F6B7A');

        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getRowDimension(3)->setRowHeight(12);

        $this->resumen($sheet, $resumen, $totalColumnas);
    }

    private function resumen(Worksheet $sheet, array $resumen, int $totalColumnas): void
    {
        if (empty($resumen)) {
            return;
        }

        $items = array_slice($resumen, 0, 5, true);
        $cantidadItems = count($items);

        if ($cantidadItems === 0) {
            return;
        }

        $columnaActual = 1;
        $anchoPorCard = max(1, intdiv($totalColumnas, $cantidadItems));

        foreach ($items as $label => $valor) {
            $inicio = $columnaActual;
            $fin = min($totalColumnas, $columnaActual + $anchoPorCard - 1);

            if ($label === array_key_last($items)) {
                $fin = $totalColumnas;
            }

            $colInicio = Coordinate::stringFromColumnIndex($inicio);
            $colFin = Coordinate::stringFromColumnIndex($fin);
            $rango = "{$colInicio}5:{$colFin}6";

            $sheet->mergeCells($rango);
            $sheet->setCellValue($colInicio . '5', mb_strtoupper((string) $label) . "\n" . (string) $valor);

            $sheet->getStyle($rango)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1A2B42'],
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D7E4F3'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);

            $columnaActual = $fin + 1;
        }

        $sheet->getRowDimension(5)->setRowHeight(22);
        $sheet->getRowDimension(6)->setRowHeight(22);
    }

    private function tabla(
        Worksheet $sheet,
        BaseReporteService $reporte,
        $filas,
        array $columnas
    ): void {
        $encabezados = collect($columnas)
            ->pluck('label')
            ->values()
            ->toArray();

        $totalColumnas = count($columnas);
        $ultimaColumna = Coordinate::stringFromColumnIndex($totalColumnas);

        $sheet->fromArray($encabezados, null, 'A8');

        $sheet->getStyle("A8:{$ultimaColumna}8")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => '2E8BC0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '2E8BC0'],
                ],
            ],
        ]);

        $filaExcel = 9;

        foreach ($filas as $fila) {
            $valores = [];

            foreach ($columnas as $columna) {
                $key = $columna['key'];
                $tipo = $columna['tipo'] ?? 'text';
                $valor = data_get($fila, $key, '');

                $valores[] = match ($tipo) {
                    'money', 'number' => is_numeric($valor) ? (float) $valor : 0,
                    default => (string) $valor,
                };
            }

            $sheet->fromArray($valores, null, 'A' . $filaExcel);

            $fillColor = $filaExcel % 2 === 0 ? 'F7F9FC' : 'FFFFFF';

            $sheet->getStyle("A{$filaExcel}:{$ultimaColumna}{$filaExcel}")->applyFromArray([
                'font' => [
                    'color' => ['rgb' => '1A2B42'],
                    'size' => 9,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => $fillColor],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D7E4F3'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);

            $this->aplicarFormatoFila($sheet, $reporte, $fila, $columnas, $filaExcel);

            $filaExcel++;
        }

        if ($filas->isEmpty()) {
            $sheet->mergeCells("A9:{$ultimaColumna}9");
            $sheet->setCellValue('A9', 'No hay datos disponibles para mostrar.');

            $sheet->getStyle("A9:{$ultimaColumna}9")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '5F6B7A'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'F7F9FC'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        }

        $ultimaFila = max($filaExcel - 1, 9);

        foreach ($columnas as $index => $columna) {
            $letra = Coordinate::stringFromColumnIndex($index + 1);
            $tipo = $columna['tipo'] ?? 'text';

            if (($columna['align_excel'] ?? null) === 'right' || in_array($tipo, ['money', 'number'], true)) {
                $sheet->getStyle("{$letra}9:{$letra}{$ultimaFila}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            if ($tipo === 'money') {
                $sheet->getStyle("{$letra}9:{$letra}{$ultimaFila}")
                    ->getNumberFormat()
                    ->setFormatCode('"C$" #,##0.00');
            }

            if ($tipo === 'number') {
                $sheet->getStyle("{$letra}9:{$letra}{$ultimaFila}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
            }

            $sheet->getColumnDimension($letra)->setAutoSize(true);
        }

        $sheet->freezePane('A9');
        $sheet->setAutoFilter("A8:{$ultimaColumna}{$ultimaFila}");
    }

    private function aplicarFormatoFila(
        Worksheet $sheet,
        BaseReporteService $reporte,
        mixed $fila,
        array $columnas,
        int $filaExcel
    ): void {
        foreach ($columnas as $index => $columna) {
            $tipo = $columna['tipo'] ?? 'text';

            if ($tipo !== 'badge') {
                continue;
            }

            $key = $columna['key'];
            $valor = (string) data_get($fila, $key, '');

            $colores = $reporte->colorEstado($valor);
            $letra = Coordinate::stringFromColumnIndex($index + 1);

            $sheet->getStyle("{$letra}{$filaExcel}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => $colores['texto']],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => $colores['fondo']],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        }
    }

    private function tituloHoja(string $nombre): string
    {
        return mb_substr(str_replace(['/', '\\', '?', '*', '[', ']'], '-', $nombre), 0, 31);
    }
}
