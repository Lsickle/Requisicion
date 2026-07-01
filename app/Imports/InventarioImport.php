<?php

namespace App\Imports;

use App\Models\Producto;
use App\Models\InventarioBodega;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Log;

class InventarioImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private $subcentroId;
    public $errores = [];
    public $exitosos = 0;

    public function __construct($subcentroId)
    {
        $this->subcentroId = $subcentroId;
    }

    public function collection(Collection $rows)
    {
        $fila = 4; // Comienza después de encabezados
        
        foreach ($rows as $row) {
            $fila++;
            
            // Validar que tenga datos mínimos
            $producto_nombre = trim($row['Producto *'] ?? '');
            $cantidad = trim($row['Cantidad *'] ?? '');

            if (!$producto_nombre || !$cantidad) {
                continue;
            }

            // Validar cantidad
            if (!is_numeric($cantidad) || (int)$cantidad < 0) {
                $this->errores[] = [
                    'fila' => $fila,
                    'error' => 'Cantidad inválida: ' . $cantidad,
                ];
                continue;
            }

            try {
                // Buscar el producto por nombre o SKU
                $sku = trim($row['SKU'] ?? '');
                
                $producto = Producto::where('name_produc', 'LIKE', "%{$producto_nombre}%")
                    ->orWhere('sku', $sku)
                    ->first();

                if (!$producto) {
                    $this->errores[] = [
                        'fila' => $fila,
                        'error' => "Producto no encontrado: {$producto_nombre}",
                    ];
                    continue;
                }

                // Buscar o crear registro de inventario
                $inventario = InventarioBodega::firstOrNew([
                    'subcentro_id' => $this->subcentroId,
                    'producto_id' => $producto->id,
                ]);

                // Sumar cantidad (no reemplazar)
                $inventario->cantidad = ($inventario->cantidad ?? 0) + (int)$cantidad;
                $inventario->save();

                $this->exitosos++;

            } catch (\Exception $e) {
                Log::error('Error importando inventario: ' . $e->getMessage());
                $this->errores[] = [
                    'fila' => $fila,
                    'error' => 'Error al procesar: ' . substr($e->getMessage(), 0, 100),
                ];
            }
        }
    }
}
