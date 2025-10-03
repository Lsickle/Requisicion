<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class BaseStyledExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithCustomStartCell, WithEvents, WithTitle, WithProperties, WithDrawings
{
    protected Collection $data;
    protected string $title;
    protected ?string $logoPath;
    protected array $headings;

    public function __construct($data, string $title, ?string $logoPath = null, ?array $headings = null)
    {
        $this->data     = collect($data);
        $this->title    = $title;
        $this->logoPath = $logoPath;
        $this->headings = $headings ?? array_keys($this->data->first() ?? []);
    }

    public function collection(): Collection { return $this->data; }
    public function headings(): array { return $this->headings; }

    // Encabezados en la fila 2; datos desde la 3
    public function startCell(): string { return 'A2'; }

    // Título de la pestaña (máx. 31 chars)
    public function title(): string { return mb_strimwidth($this->title, 0, 31); }

    // Propiedades del archivo
    public function properties(): array
    {
        return [
            'title'       => $this->title,
            'creator'     => 'Sistema de Compras',
            'description' => $this->title,
        ];
    }

    // Logo opcional
    public function drawings()
    {
        if ($this->logoPath && file_exists($this->logoPath)) {
            $d = new Drawing();
            $d->setName('Logo');
            $d->setPath($this->logoPath);
            $d->setHeight(42);
            $d->setCoordinates('A1');
            $d->setOffsetX(6);
            $d->setOffsetY(2);
            return [$d];
        }
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = $sheet->getHighestColumn();
        // Encabezados en negrita y centrados
        $sheet->getStyle("A2:{$lastCol}2")->getFont()->setBold(true);
        $sheet->getStyle("A2:{$lastCol}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                // Título
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', $this->title);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                      ->setVertical(Alignment::VERTICAL_CENTER);

                // Congelar encabezados
                $sheet->freezePane('A3');

                // Autofiltro
                $sheet->setAutoFilter("A2:{$lastCol}2");

                // Ajuste de texto
                $sheet->getStyle("A3:{$lastCol}{$lastRow}")
                      ->getAlignment()->setWrapText(true);

                // Bordes
                $sheet->getStyle("A2:{$lastCol}{$lastRow}")
                      ->getBorders()->getAllBorders()
                      ->setBorderStyle(Border::BORDER_THIN);

                // Hook para diseños específicos
                $this->afterSheet($event);
            }
        ];
    }

    // Para que los hijos apliquen su propio diseño
    protected function afterSheet(AfterSheet $event): void {}

    // Helper: obtener la letra de columna por encabezado
    protected function col(string $header): ?string
    {
        $idx = array_search($header, $this->headings, true);
        return $idx === false ? null : Coordinate::stringFromColumnIndex($idx + 1);
    }
}
