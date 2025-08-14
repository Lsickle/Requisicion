<?php

namespace App\Http\Controllers\excel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteGeneralExport;
use App\Models\Producto;
use App\Models\OrdenCompra;
use App\Models\Requisicion;
use Carbon\Carbon;

class ExcelController extends Controller
{
    public function export($type)
    {
        switch ($type) {
            case 'productos':
                return $this->reporteProductos();
            case 'ordenes-compra':
                return $this->reporteOrdenesCompra();
            case 'requisiciones':
                return $this->reporteRequisiciones();
            case 'estatus-requisicion':
                return $this->reporteEstatusRequisicion();
            default:
                return back()->with('error', 'Tipo de exportación no válido');
        }
    }

    public function generarReporte(Request $request)
    {
        $request->validate([
            'tipo_reporte' => 'required|in:productos,ordenes_compra,requisiciones,estatus_requisicion',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio'
        ]);

        $tipo = $request->input('tipo_reporte');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        return match ($tipo) {
            'productos'           => $this->reporteProductos($fechaInicio, $fechaFin),
            'ordenes_compra'      => $this->reporteOrdenesCompra($fechaInicio, $fechaFin),
            'requisiciones'       => $this->reporteRequisiciones($fechaInicio, $fechaFin),
            'estatus_requisicion' => $this->reporteEstatusRequisicion($fechaInicio, $fechaFin),
        };
    }

    private function reporteProductos($fechaInicio = null, $fechaFin = null)
    {
        $query = Producto::with(['proveedor', 'centros'])
            ->orderBy('name_produc', 'asc');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('created_at', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $productos = $query->get();

        $data = $productos->map(function ($producto) {
            $centros = $producto->centros->map(function ($centro) {
                return $centro->name_centro . ' (' . ($centro->pivot->amount ?? '0') . ')';
            })->implode(', ');

            return [
                'ID'                 => $producto->id,
                'Nombre'             => $producto->name_produc ?? 'N/A',
                'Descripción'        => $producto->description_produc ?? 'N/A',
                'Categoría'          => $producto->categoria_produc ?? 'N/A',
                'Proveedor'          => $producto->proveedor->prov_name ?? 'N/A',
                'Precio Unitario'    => $producto->price_produc ?? '0',
                'Unidad de Medida'   => $producto->unit_produc ?? 'N/A',
                'Stock'              => $producto->stock_produc ?? '0',
                'Fecha Creación'     => $producto->created_at?->format('d/m/Y') ?? 'N/A',
            ];
        });

        return Excel::download(new ReporteGeneralExport($data, 'Reporte de Productos'), 'reporte_productos.xlsx');
    }

    private function reporteOrdenesCompra($fechaInicio = null, $fechaFin = null)
    {
        $query = OrdenCompra::with(['proveedor', 'requisicion', 'productos'])
            ->orderBy('created_at', 'desc');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('created_at', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $ordenes = $query->get();

        $data = $ordenes->map(function ($orden) {
            $productos = $orden->productos->map(function ($producto) {
                return $producto->name_produc . ' (Cant: ' . ($producto->pivot->po_amount ?? '0') . ', Precio: ' . ($producto->pivot->precio_unitario ?? '0') . ')';
            })->implode('; ');

            $pivotData = $orden->productos->first()->pivot ?? null;

            return [
                'ID Orden'             => $orden->id,
                'Número de Orden'      => $pivotData->order_oc ?? 'N/A',
                'Proveedor'            => $orden->proveedor->prov_name ?? 'N/A',
                'Fecha Orden'          => $pivotData->date_oc ?? 'N/A',
                'Método de Pago'       => $pivotData->methods_oc ?? 'N/A',
                'Plazo de Pago'        => $pivotData->plazo_oc ?? 'N/A',
                'Productos'            => $productos,
                'Requisición Asociada' => $orden->requisicion->id ?? 'N/A',
                'Fecha Creación'       => $orden->created_at?->format('d/m/Y') ?? 'N/A',
            ];
        });

        return Excel::download(new ReporteGeneralExport($data, 'Reporte de Órdenes de Compra'), 'reporte_ordenes_compra.xlsx');
    }

    private function reporteRequisiciones($fechaInicio = null, $fechaFin = null)
    {
        $query = Requisicion::with(['productos', 'estatus'])->orderBy('date_requisicion', 'desc');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('date_requisicion', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $requisiciones = $query->get();

        $data = $requisiciones->map(function ($requisicion) {
            $productos = $requisicion->productos->map(function ($producto) {
                return $producto->name_produc . ' (Cant: ' . ($producto->pivot->pr_amount ?? '0') . ')';
            })->implode('; ');

            // Último estatus ordenado en memoria
            $ultimoEstatus = $requisicion->estatus->sortByDesc(fn($e) => $e->pivot?->created_at)->first();

            return [
                'ID Requisición'       => $requisicion->id,
                'Fecha Requisición'    => $requisicion->date_requisicion?->format('d/m/Y') ?? 'N/A',
                'Justificación'        => $requisicion->justify_requisicion ?? 'N/A',
                'Prioridad'            => $requisicion->prioridad_requisicion ?? 'N/A',
                'Recobrable'           => $requisicion->Recobreble ?? 'N/A',
                'Productos Solicitados'=> $productos,
                'Estatus Actual'       => $ultimoEstatus->status_name ?? 'Sin estatus',
                'Fecha Último Estatus' => $ultimoEstatus?->pivot?->created_at
                                            ? Carbon::parse($ultimoEstatus->pivot->created_at)->format('d/m/Y H:i')
                                            : 'N/A',
                'Fecha Creación'       => $requisicion->created_at?->format('d/m/Y') ?? 'N/A',
            ];
        });

        return Excel::download(new ReporteGeneralExport($data, 'Reporte de Requisiciones'), 'reporte_requisiciones.xlsx');
    }

    private function reporteEstatusRequisicion($fechaInicio = null, $fechaFin = null)
    {
        $query = Requisicion::with('estatus')->orderBy('id', 'desc');

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('created_at', [
                Carbon::parse($fechaInicio)->startOfDay(),
                Carbon::parse($fechaFin)->endOfDay()
            ]);
        }

        $requisiciones = $query->get();

        $data = $requisiciones->map(function ($requisicion) {
            $estadoActual = $requisicion->estatus->sortByDesc(fn($e) => $e->pivot?->created_at)->first();
            $historial = $requisicion->estatus
                ->sortByDesc(fn($e) => $e->pivot?->created_at)
                ->map(fn($estatus) => $estatus->status_name . ' (' . Carbon::parse($estatus->pivot->created_at)->format('d/m/Y H:i') . ')')
                ->implode(' -> ');

            return [
                'ID Requisición' => $requisicion->id,
                'Estatus Actual' => $estadoActual->status_name ?? 'Sin estatus',
                'Historial'      => $historial,
                'Fecha Creación' => $requisicion->created_at?->format('d/m/Y') ?? 'N/A',
            ];
        });

        return Excel::download(new ReporteGeneralExport($data, 'Reporte de Estatus de Requisiciones'), 'reporte_estatus_requisiciones.xlsx');
    }
}
