<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReporteGeneralExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $data;
    protected $title;

    public function __construct($data, $title)
    {
        $this->data = $data;
        $this->title = $title;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        if (count($this->data) > 0) {
            return array_keys($this->data[0]);
        }
        return [];
    }

    public function title(): string
    {
        return $this->title;
    }
}