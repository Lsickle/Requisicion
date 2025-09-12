<?php

namespace App\Http\Controllers\ordencompra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\OrdencompraProducto;
use App\Models\OrdenCompraCentroProducto;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\Centro;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Illuminate\Support\Facades\Response;

class OrdenCompraController extends Controller
{
    /**
     * Listado de órdenes de compra
     */
    public function index()
    {
        $ordenes = OrdenCompra::with(['requisicion', 'proveedor'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('ordenes_compra.lista', compact('ordenes'));
    }

    /**
     * Formulario de creación
     */
    public function create(Request $request)
    {
        $requisiciones = Requisicion::select('requisicion.*')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('productos')
                    ->join('producto_requisicion', 'productos.id', '=', 'producto_requisicion.id_producto')
                    ->whereColumn('requisicion.id', 'producto_requisicion.id_requisicion')
                    ->whereNull('productos.deleted_at');
            })
            ->get();

        $requisicion = null;
        $productosDisponibles = collect();
        $productoSeleccionado = null;
        $proveedores = Proveedor::all();
        $centros = Centro::all();

        if ($request->has('requisicion_id') && $request->requisicion_id != 0) {
            $requisicion = Requisicion::find($request->requisicion_id);

            if ($requisicion) {
                // Obtener productos que aún no tienen orden de compra
                // incluimos pr_amount si existe en pivot
                $productosDisponibles = Producto::select('productos.*', 'producto_requisicion.pr_amount')
                    ->join('producto_requisicion', 'productos.id', '=', 'producto_requisicion.id_producto')
                    ->where('producto_requisicion.id_requisicion', $requisicion->id)
                    ->whereNull('productos.deleted_at')
                    ->whereNotExists(function ($query) use ($requisicion) {
                        $query->select(DB::raw(1))
                            ->from('ordencompra_producto')
                            ->join('orden_compras', 'ordencompra_producto.orden_compras_id', '=', 'orden_compras.id')
                            ->whereRaw('ordencompra_producto.producto_id = productos.id')
                            ->where('orden_compras.requisicion_id', $requisicion->id)
                            ->whereNull('ordencompra_producto.deleted_at');
                    })
                    ->orderBy('productos.id', 'asc')
                    ->get();
            }
        }

        if ($request->has('producto_id') && $request->producto_id != 0) {
            $productoSeleccionado = Producto::with('proveedor')->find($request->producto_id);
        }

        return view('ordenes_compra.create', compact(
            'requisiciones',
            'requisicion',
            'productosDisponibles',
            'productoSeleccionado',
            'proveedores',
            'centros'
        ));
    }

    /**
     * Guardar nueva orden
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proveedor_id'   => 'required|exists:proveedores,id',
            'methods_oc'     => 'nullable|string|max:255',
            'plazo_oc'       => 'nullable|string|max:255',
            'observaciones'  => 'nullable|string',
            'requisicion_id' => 'required|exists:requisicion,id',
            'productos'      => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'nullable|integer|min:0',
            'productos.*.centros' => 'nullable|array',
            'productos.*.centros.*' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Generar número de orden automático
            $ultimaOrden = OrdenCompra::orderBy('id', 'desc')->first();
            $numeroOrden = 'OC-' . (($ultimaOrden ? $ultimaOrden->id : 0) + 1) . '-' . now()->format('Ymd');

            $orden = OrdenCompra::create([
                'requisicion_id' => $request->requisicion_id,
                'proveedor_id'   => $request->proveedor_id,
                'observaciones'  => $request->observaciones,
                'methods_oc'     => $request->methods_oc,
                'plazo_oc'       => $request->plazo_oc,
                'date_oc'        => now(),
                'order_oc'       => $numeroOrden,
            ]);

            foreach ($request->productos as $productoData) {
                // Asegurarnos de tener el id del producto
                $productoId = $productoData['id'] ?? null;
                if (!$productoId) {
                    continue;
                }

                // 1) Obtener pr_amount (cantidad solicitada en la requisición) si existe
                $prAmount = (int) DB::table('producto_requisicion')
                    ->where('id_requisicion', $request->requisicion_id)
                    ->where('id_producto', $productoId)
                    ->value('pr_amount') ?? 0;

                // 2) Sumar la distribución por centros (si viene)
                $sumCentros = 0;
                if (!empty($productoData['centros']) && is_array($productoData['centros'])) {
                    foreach ($productoData['centros'] as $centroId => $cantidadCentro) {
                        $sumCentros += (int)$cantidadCentro;
                    }
                }

                // 3) Determinar total a guardar en la columna `total` (de tu migración)
                // Prioridad: suma centros > cantidad enviada en formulario > prAmount
                $totalToSave = 0;
                if ($sumCentros > 0) {
                    $totalToSave = $sumCentros;
                } elseif (isset($productoData['cantidad']) && $productoData['cantidad'] !== null) {
                    $totalToSave = (int) $productoData['cantidad'];
                } else {
                    $totalToSave = $prAmount;
                }

                // Crear registro en ordencompra_producto
                // IMPORTANT: tu migración tiene la columna `total` (no `cantidad`), por eso guardamos en 'total'
                $ordenProducto = OrdencompraProducto::create([
                    'producto_id'      => $productoId,
                    'orden_compras_id' => $orden->id,
                    'proveedor_id'     => $request->proveedor_id,
                    'total'            => $totalToSave, // <- guardamos en la columna que existe
                    'observaciones'    => $request->observaciones,
                    'methods_oc'       => $request->methods_oc,
                    'plazo_oc'         => $request->plazo_oc,
                    'date_oc'          => now(),
                    'order_oc'         => $numeroOrden,
                ]);

                // Crear distribución por centros de costo para la orden de compra
                if (!empty($productoData['centros']) && is_array($productoData['centros'])) {
                    foreach ($productoData['centros'] as $centroId => $cantidad) {
                        $cantidad = (int)$cantidad;
                        if ($cantidad > 0) {
                            OrdenCompraCentroProducto::create([
                                'orden_compra_id' => $orden->id,
                                'producto_id'     => $productoId,
                                'centro_id'       => $centroId,
                                'amount'          => $cantidad,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('ordenes_compra.create', ['requisicion_id' => $request->requisicion_id])
                ->with('success', 'Orden de compra ' . $numeroOrden . ' creada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando orden de compra: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Anular orden (soft delete)
     */
    public function anular($id)
    {
        DB::beginTransaction();
        try {
            $orden = OrdenCompra::findOrFail($id);

            // Soft delete de los productos relacionados
            OrdencompraProducto::where('orden_compras_id', $id)->delete();

            // Soft delete de la distribución por centros
            OrdenCompraCentroProducto::where('orden_compra_id', $id)->delete();

            // Soft delete de la orden
            $orden->delete();

            DB::commit();

            return redirect()->back()
                ->with('success', 'Orden de compra anulada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al anular la orden: ' . $e->getMessage());
        }
    }

    /**
     * Descargar ZIP de órdenes
     */
    public function downloadZip($requisicionId)
    {
        $requisicion = Requisicion::findOrFail($requisicionId);
        $ordenes = OrdenCompra::where('requisicion_id', $requisicionId)
            ->with(['proveedor', 'ordencompraProductos.producto'])
            ->get();

        if ($ordenes->isEmpty()) {
            return redirect()->back()->with('error', 'No hay órdenes de compra para descargar.');
        }

        $zip = new ZipArchive();
        $zipFileName = 'ordenes_compra_requisicion_' . $requisicionId . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($ordenes as $orden) {
                $pdf = Pdf::loadView('ordenes_compra.pdf', ['ordenCompra' => $orden]);
                $pdfContent = $pdf->output();
                $zip->addFromString('orden_' . $orden->order_oc . '.pdf', $pdfContent);
            }
            $zip->close();

            return Response::download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        }

        return redirect()->back()->with('error', 'Error al crear el archivo ZIP.');
    }

    /**
     * Mostrar detalle
     */
    public function show($id)
    {
        $orden = OrdenCompra::with(['requisicion', 'proveedor', 'ordencompraProductos.producto'])
            ->findOrFail($id);

        return view('ordenes_compra.show', ['ordenCompra' => $orden]);
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        $ordenCompra = OrdenCompra::with([
            'proveedor',
            'requisicion',
            'distribucionCentrosProductos.centro',
            'distribucionCentrosProductos.producto'
        ])->findOrFail($id);

        $requisicion = Requisicion::with('productos')->findOrFail($ordenCompra->requisicion_id);
        $centros = Centro::all();

        // Obtener distribución de la orden de compra
        $distribucionOrden = [];
        foreach ($ordenCompra->distribucionCentrosProductos as $dist) {
            if (!isset($distribucionOrden[$dist->producto_id])) {
                $distribucionOrden[$dist->producto_id] = [];
            }
            $distribucionOrden[$dist->producto_id][$dist->centro_id] = $dist->amount;
        }

        return view('ordenes_compra.edit', [
            'ordenCompra' => $ordenCompra,
            'requisicion' => $requisicion,
            'centros' => $centros,
            'distribucion' => $distribucionOrden,
        ]);
    }

    /**
     * Actualizar orden
     */
    public function update(Request $request, $id)
    {
        $ordenCompra = OrdenCompra::with('requisicion')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'proveedor_id'   => 'required|exists:proveedores,id',
            'methods_oc'     => 'nullable|string|max:255',
            'plazo_oc'       => 'nullable|string|max:255',
            'observaciones'  => 'nullable|string',
            'productos'      => 'required|array|min:1',
            'productos.*.cantidad' => 'nullable|integer|min:0',
            'productos.*.centros' => 'nullable|array',
            'productos.*.centros.*' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Actualiza datos generales de la orden de compra
            $ordenCompra->update([
                'proveedor_id' => $request->proveedor_id,
                'date_oc' => now(),
                'observaciones' => $request->observaciones,
                'methods_oc' => $request->methods_oc,
                'plazo_oc' => $request->plazo_oc,
            ]);

            // Actualizar productos y distribución
            if ($request->has('productos')) {
                foreach ($request->productos as $productoId => $data) {
                    // Recalcular total desde centros o desde la cantidad enviada
                    $sumCentros = 0;
                    if (!empty($data['centros']) && is_array($data['centros'])) {
                        foreach ($data['centros'] as $centroId => $cantidadCentro) {
                            $sumCentros += (int)$cantidadCentro;
                        }
                    }

                    $totalToSave = $sumCentros > 0 ? $sumCentros : (isset($data['cantidad']) ? (int)$data['cantidad'] : 0);

                    // Actualizar total en ordencompra_producto (nota: columna 'total' en migración)
                    OrdencompraProducto::where('orden_compras_id', $ordenCompra->id)
                        ->where('producto_id', $productoId)
                        ->update([
                            'total' => $totalToSave,
                            'proveedor_id' => $request->proveedor_id,
                            'updated_at' => now()
                        ]);

                    // Eliminar distribución existente para este producto
                    OrdenCompraCentroProducto::where('orden_compra_id', $ordenCompra->id)
                        ->where('producto_id', $productoId)
                        ->delete();

                    // Crear nueva distribución por centros
                    if (!empty($data['centros']) && is_array($data['centros'])) {
                        foreach ($data['centros'] as $centroId => $cantidad) {
                            $cantidad = (int)$cantidad;
                            if ($cantidad > 0) {
                                OrdenCompraCentroProducto::create([
                                    'orden_compra_id' => $ordenCompra->id,
                                    'producto_id' => $productoId,
                                    'centro_id' => $centroId,
                                    'amount' => $cantidad,
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('ordenes_compra.lista')
                ->with('success', 'Orden de compra actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar permanentemente
     */
    public function destroy($id)
    {
        $orden = OrdenCompra::findOrFail($id);
        $orden->forceDelete();

        return redirect()->route('ordenes_compra.lista')
            ->with('success', 'Orden de compra eliminada permanentemente.');
    }

    /**
     * Exportar PDF
     */
    public function exportPdf($id)
    {
        $orden = OrdenCompra::with([
            'requisicion',
            'proveedor',
            'ordencompraProductos.producto',
            'distribucionCentrosProductos.centro'
        ])->findOrFail($id);

        $pdf = Pdf::loadView('ordenes_compra.pdf', ['ordenCompra' => $orden]);
        return $pdf->download("orden_compra_{$orden->id}.pdf");
    }
}
