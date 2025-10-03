<?php

namespace App\Exports;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OrdenesCompraExport extends BaseStyledExport
{
    protected function afterSheet(AfterSheet $event): void
    {
        $sheet   = $event->sheet->getDelegate();
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // Encabezados gris
        $sheet->getStyle("A2:{$lastCol}2")->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setARGB('EEEEEE');

        // Fechas
        foreach (['Fecha Orden', 'Fecha Creación'] as $hdr) {
            if ($col = $this->col($hdr)) {
                $sheet->getStyle("{$col}3:{$col}{$lastRow}")
                      ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
            }
        }

        // Productos ancho extra
        if ($col = $this->col('Productos')) {
            $sheet->getColumnDimension($col)->setWidth(70);
        }

        // Número de Orden centrado
        if ($col = $this->col('Número de Orden')) {
            $sheet->getStyle("{$col}3:{$col}{$lastRow}")
                  ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }
    }
}
