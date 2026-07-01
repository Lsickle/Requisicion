<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class InventarioPlantillaExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    private $operacionNombre;

    public function __construct($operacionNombre = 'Operación')
    {
        $this->operacionNombre = $operacionNombre;
    }

    public function array(): array
    {
        return [
            // Fila de instrucciones
            ['INSTRUCCIONES: Completar los campos obligatorios marcados con *'],
            [],
            // Encabezados
        ];
    }

    public function headings(): array
    {
        return [
            'Producto *',
            'SKU',
            'Unidad',
            'Cantidad *',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,
            'B' => 15,
            'C' => 12,
            'D' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Estilo para fila de instrucciones
        $sheet->getStyle('A1:D1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF2CC'],
            ],
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '856404'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Estilo para encabezados
        $sheet->getStyle('A3:D3')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3366CC'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Altura para encabezados
        $sheet->getRowDimension(3)->setRowHeight(25);

        // Datos de ejemplo (para referencia)
        $sheet->getStyle('A4:D4')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E7E6E6'],
            ],
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '595959'],
                'size' => 9,
            ],
        ]);

        // Proteger columnas para que el usuario sepa cuál editar
        $sheet->getStyle('A3:D3')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        return [];
    }
}
