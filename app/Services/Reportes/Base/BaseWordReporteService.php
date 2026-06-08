<?php

namespace App\Services\Reportes\Base;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Writer\Word2007;

class BaseWordReporteService
{
    public function generar(BaseReporteService $reporte): Word2007
    {
        $datos = $reporte->datos();
        $filas = $reporte->filas($datos);
        $resumen = $reporte->resumen($datos);
        $columnas = $reporte->columnas();

        $phpWord = new PhpWord();

        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        $phpWord->addTableStyle('HeaderTable', [
            'borderSize' => 0,
            'cellMargin' => 80,
            'alignment' => 'center',
        ]);

        $phpWord->addTableStyle('SummaryTable', [
            'borderSize' => 6,
            'borderColor' => 'D7E4F3',
            'cellMargin' => 90,
            'alignment' => 'center',
        ]);

        $phpWord->addTableStyle('ReportTable', [
            'borderSize' => 6,
            'borderColor' => 'D7E4F3',
            'cellMargin' => 65,
            'alignment' => 'center',
        ]);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop' => 450,
            'marginBottom' => 450,
            'marginLeft' => 450,
            'marginRight' => 450,
        ]);

        $this->encabezado($section, $reporte);
        $this->resumen($section, $resumen);
        $this->tabla($section, $reporte, $filas, $columnas);
        $this->footer($section, $reporte);

        return new Word2007($phpWord);
    }

    private function encabezado(Section $section, BaseReporteService $reporte): void
    {
        $headerTable = $section->addTable('HeaderTable');
        $headerTable->addRow(1000);

        $logoCell = $headerTable->addCell(1800, [
            'bgColor' => 'F0F3F7',
            'valign' => 'center',
        ]);

        $logo = $reporte->logoPath();

        if ($logo) {
            $logoCell->addImage($logo, [
                'width' => 55,
                'height' => 55,
                'alignment' => 'center',
            ]);
        } else {
            $logoCell->addText(
                'GNET',
                ['bold' => true, 'size' => 15, 'color' => '2E8BC0'],
                ['alignment' => 'center']
            );
        }

        $titleCell = $headerTable->addCell(11800, [
            'bgColor' => 'F0F3F7',
            'valign' => 'center',
        ]);

        $titleCell->addText(
            $reporte->titulo(),
            ['bold' => true, 'size' => 17, 'color' => '1A2B42']
        );

        $titleCell->addText(
            'Generado el: ' . now()->format('d/m/Y h:i A'),
            ['bold' => true, 'size' => 8, 'color' => '5F6B7A']
        );

        $section->addTextBreak(1);
    }

    private function resumen(Section $section, array $resumen): void
    {
        if (empty($resumen)) {
            return;
        }

        $section->addText(
            'Resumen general',
            ['bold' => true, 'size' => 11, 'color' => '1A2B42']
        );

        $tabla = $section->addTable('SummaryTable');
        $tabla->addRow(620);

        foreach (array_slice($resumen, 0, 5, true) as $label => $value) {
            $cell = $tabla->addCell(2600, [
                'bgColor' => 'FFFFFF',
                'valign' => 'center',
            ]);

            $cell->addText(
                mb_strtoupper((string) $label),
                ['bold' => true, 'size' => 7, 'color' => '5F6B7A'],
                ['alignment' => 'center']
            );

            $cell->addText(
                (string) $value,
                ['bold' => true, 'size' => 10, 'color' => '1A2B42'],
                ['alignment' => 'center']
            );
        }

        $section->addTextBreak(1);
    }

    private function tabla(
        Section $section,
        BaseReporteService $reporte,
        $filas,
        array $columnas
    ): void {
        $tabla = $section->addTable('ReportTable');

        $tabla->addRow(430);

        foreach ($columnas as $columna) {
            $tabla->addCell($columna['word'] ?? 1200, [
                'bgColor' => '2E8BC0',
                'valign' => 'center',
            ])->addText(
                $columna['label'],
                ['bold' => true, 'color' => 'FFFFFF', 'size' => 8],
                ['alignment' => 'center']
            );
        }

        $numeroFila = 0;

        foreach ($filas as $fila) {
            $numeroFila++;
            $bg = $numeroFila % 2 === 0 ? 'F7F9FC' : 'FFFFFF';

            $tabla->addRow(390);

            foreach ($columnas as $columna) {
                $key = $columna['key'];
                $tipo = $columna['tipo'] ?? 'text';
                $valor = data_get($fila, $key, '');

                if ($tipo === 'badge') {
                    $colores = $reporte->colorEstado((string) $valor);

                    $tabla->addCell($columna['word'] ?? 1200, [
                        'bgColor' => $colores['fondo'],
                        'valign' => 'center',
                    ])->addText(
                        (string) $valor,
                        [
                            'bold' => true,
                            'color' => $colores['texto'],
                            'size' => 8,
                        ],
                        ['alignment' => 'center']
                    );

                    continue;
                }

                $texto = $reporte->valorFormateado($valor, $tipo);

                $tabla->addCell($columna['word'] ?? 1200, [
                    'bgColor' => $bg,
                    'valign' => 'center',
                ])->addText(
                    $texto,
                    ['size' => 8, 'color' => '1A2B42'],
                    [
                        'alignment' => $columna['align_word']
                            ?? (in_array($tipo, ['money', 'number'], true) ? 'right' : 'left'),
                    ]
                );
            }
        }

        if ($filas->isEmpty()) {
            $tabla->addRow(500);

            $tabla->addCell(15000, [
                'bgColor' => 'F7F9FC',
                'gridSpan' => count($columnas),
                'valign' => 'center',
            ])->addText(
                'No hay datos disponibles para mostrar.',
                ['bold' => true, 'color' => '5F6B7A'],
                ['alignment' => 'center']
            );
        }
    }

    private function footer(Section $section, BaseReporteService $reporte): void
    {
        $section->addTextBreak(1);

        $section->addText(
            'Gnet System - ' . $reporte->titulo(),
            ['size' => 8, 'color' => '5F6B7A'],
            ['alignment' => 'right']
        );
    }
}
