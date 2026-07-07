<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class InventarioExport implements FromCollection, WithHeadings
{
    private $inventario;
    private $movimientos;
    private $nombreBodega;

    public function __construct($inventario, $movimientos, $nombreBodega)
    {
        $this->inventario = $inventario;
        $this->movimientos = $movimientos;
        $this->nombreBodega = $nombreBodega;
    }

    public function collection(): Collection
    {
        $data = [];
        $nombre = $this->nombreBodega ?? 'Excel Inventario';
        $data[] = [$nombre];
        $data[] = ['Fecha de generación: ' . now()->format('d/m/Y H:i')];
        $data[] = [];
        $data[] = ['STOCK ACTUAL'];

        $headers = ['Producto', 'SKU', 'Unidad', 'Stock', 'Bodega'];
        $data[] = $headers;

        if ($this->inventario && $this->inventario->count() > 0) {
            foreach ($this->inventario as $item) {
                $data[] = [
                    (string) ($item->producto->name_produc ?? ''),
                    (string) ($item->producto->sku ?? ''),
                    (string) ($item->producto->unit_produc ?? ''),
                    (int) ($item->cantidad ?? 0),
                    (string) ($item->bodega->name_centro ?? ''),
                ];
            }
        }

        $data[] = [];
        $data[] = ['MOVIMIENTOS (Entradas y Salidas)'];

        $movHeaders = ['Fecha', 'Tipo', 'Producto', 'Usuario', 'Cantidad', 'Comentario'];
        $data[] = $movHeaders;

        if ($this->movimientos && $this->movimientos->count() > 0) {
            foreach ($this->movimientos as $mov) {
                $userName = '';
                if ($mov->user) {
                    $userName = (string) ($mov->user->name ?? $mov->user->email ?? '');
                }
                $fecha = '';
                if ($mov->created_at) {
                    $fecha = $mov->created_at->format('d/m/Y H:i');
                }
                $data[] = [
                    (string) $fecha,
                    (string) ucfirst($mov->tipo ?? ''),
                    (string) ($mov->inventarioBodega->producto->name_produc ?? ''),
                    (string) $userName,
                    (int) ($mov->cantidad ?? 0),
                    (string) ($mov->comentario ?? ''),
                ];
            }
        }

        return new \Illuminate\Support\Collection($data);
    }

    public function headings(): array
    {
        return [];
    }
}