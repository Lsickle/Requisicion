<?php

namespace App\Exports;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EstatusRequisicionExport extends BaseStyledExport
{
    protected function afterSheet(AfterSheet $event): void
    {
        $sheet   = $event->sheet->getDelegate();
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // Encabezados azul suave
        $sheet->getStyle("A2:{$lastCol}2")->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setARGB('E7F0FE');

        // Fecha Creación
        if ($col = $this->col('Fecha Creación')) {
            $sheet->getStyle("{$col}3:{$col}{$lastRow}")
                  ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }

        // Historial ancho
        if ($col = $this->col('Historial')) {
            $sheet->getColumnDimension($col)->setWidth(40);
        }
    }
}
