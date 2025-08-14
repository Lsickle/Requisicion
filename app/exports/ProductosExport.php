<?php

namespace App\Exports;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ProductosExport extends BaseStyledExport
{
    protected function afterSheet(AfterSheet $event): void
    {
        $sheet   = $event->sheet->getDelegate();
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // Encabezados con fondo verde suave
        $sheet->getStyle("A2:{$lastCol}2")->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setARGB('E6F4EA');

        // Formato moneda COP en "Precio Unitario"
        if ($col = $this->col('Precio Unitario')) {
            $sheet->getStyle("{$col}3:{$col}{$lastRow}")
                  ->getNumberFormat()->setFormatCode('"COP" #,##0.00');
        }

        // Formato fecha en "Fecha Creación"
        if ($col = $this->col('Fecha Creación')) {
            $sheet->getStyle("{$col}3:{$col}{$lastRow}")
                  ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        }

        // Stock bajo (< 5) en rojo claro
        if ($col = $this->col('Stock')) {
            $range = "{$col}3:{$col}{$lastRow}";

            $cond = new Conditional();
            $cond->setConditionType(Conditional::CONDITION_CELLIS)
                 ->setOperatorType(Conditional::OPERATOR_LESSTHAN)
                 ->addCondition('5');
            $cond->getStyle()->getFont()->getColor()->setARGB(Color::COLOR_DARKRED);
            $cond->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FDECEA');

            $conditionalStyles = $sheet->getStyle($range)->getConditionalStyles();
            $conditionalStyles[] = $cond;
            $sheet->getStyle($range)->setConditionalStyles($conditionalStyles);
        }

        // Ajustar ancho extra para Descripción
        if ($col = $this->col('Descripción')) {
            $sheet->getColumnDimension($col)->setWidth(50);
        }
    }
}
