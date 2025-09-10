<?php

namespace App\Http\Controllers\ordencompra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\OrdencompraProducto;
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
                    ->whereNull('productos.deleted_at')
                    ->orderBy('productos.id', 'asc');
            })
            ->get();

        $requisicion = null;
        $productosDisponibles = collect();
        $productoSeleccionado = null;
        $proveedores = Proveedor::all();

        if ($request->has('requisicion_id') && $request->requisicion_id != 0) {
            $requisicion = Requisicion::find($request->requisicion_id);

            if ($requisicion) {
                // Obtener productos que aún no tienen orden de compra
                $productosDisponibles = Producto::select('productos.*')
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
            'proveedores'
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
            'productos.*.cantidad' => 'required|integer|min:1',
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
                OrdencompraProducto::create([
                    'producto_id'      => $productoData['id'],
                    'orden_compras_id' => $orden->id,
                    'proveedor_id'     => $request->proveedor_id,
                    'cantidad'         => $productoData['cantidad'],
                    'observaciones'    => $request->observaciones,
                    'methods_oc'       => $request->methods_oc,
                    'plazo_oc'         => $request->plazo_oc,
                    'date_oc'          => now(),
                    'order_oc'         => $numeroOrden,
                ]);
            }

            DB::commit();
            return redirect()->route('ordenes_compra.create', ['requisicion_id' => $request->requisicion_id])
                ->with('success', 'Orden de compra ' . $numeroOrden . ' creada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
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

        return view('ordenes_compra.create', ['ordenCompra' => $orden]);
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        Log::info("=== EDIT ORDEN ===");
        Log::info("ID recibido (Orden): " . $id);

        $ordenCompra = OrdenCompra::with(['proveedor', 'requisicion'])->findOrFail($id);
        $requisicion = Requisicion::with('productos')->findOrFail($ordenCompra->requisicion_id);

        $centros = Centro::all();

        $distribucion = [];
        foreach ($requisicion->productos as $producto) {
            $distribucion[$producto->id] = DB::table('centro_producto')
                ->where('requisicion_id', $requisicion->id)
                ->where('producto_id', $producto->id)
                ->whereNull('deleted_at')
                ->pluck('amount', 'centro_id')
                ->toArray();
        }

        return view('ordenes_compra.edit', [
            'ordenCompra' => $ordenCompra,
            'requisicion' => $requisicion,
            'centros' => $centros,
            'distribucion' => $distribucion,
        ]);
    }

    /**
     * Actualizar orden
     */
    public function update(Request $request, $id)
    {
        $ordenCompra = OrdenCompra::with('requisicion')->findOrFail($id);
        $requisicion = $ordenCompra->requisicion;

        DB::beginTransaction();
        try {
            // Actualiza datos generales de la orden de compra
            $ordenCompra->update([
                'date_oc' => now(),
                'observaciones' => $request->observaciones,
                'methods_oc' => $request->methods_oc,
                'plazo_oc' => $request->plazo_oc,
            ]);

            // Actualiza productos existentes
            if ($request->has('productos')) {
                foreach ($request->productos as $productoId => $data) {
                    // Eliminar producto (soft delete)
                    if (!empty($data['eliminar'])) {
                        // Soft delete en ordencompra_producto
                        OrdencompraProducto::where('orden_compras_id', $ordenCompra->id)
                            ->where('producto_id', $productoId)
                            ->delete();

                        // Soft delete en producto_requisicion
                        DB::table('producto_requisicion')
                            ->where('id_producto', $productoId)
                            ->where('id_requisicion', $requisicion->id)
                            ->update(['deleted_at' => now()]);

                        // Soft delete en centro_producto
                        DB::table('centro_producto')
                            ->where('requisicion_id', $requisicion->id)
                            ->where('producto_id', $productoId)
                            ->update(['deleted_at' => now()]);

                        continue;
                    }

                    // Actualizar cantidad total en producto_requisicion
                    DB::table('producto_requisicion')
                        ->where('id_producto', $productoId)
                        ->where('id_requisicion', $requisicion->id)
                        ->update([
                            'pr_amount' => $data['cantidad'] ?? 0,
                            'updated_at' => now(),
                            'deleted_at' => null, // Reactivar si estaba softdeleted
                        ]);

                    // Reactivar en ordencompra_producto si estaba softdeleted
                    OrdencompraProducto::withTrashed()
                        ->where('orden_compras_id', $ordenCompra->id)
                        ->where('producto_id', $productoId)
                        ->restore();

                    // Actualizar distribución: primero softdelete todos los centros de ese producto
                    DB::table('centro_producto')
                        ->where('requisicion_id', $requisicion->id)
                        ->where('producto_id', $productoId)
                        ->update(['deleted_at' => now()]);

                    // Insertar/actualizar distribución por centro
                    if (!empty($data['centros'])) {
                        $totalDistribucion = array_sum($data['centros']);
                        if ($totalDistribucion != $data['cantidad']) {
                            throw new \Exception("La suma de la distribución ($totalDistribucion) no coincide con la cantidad total ({$data['cantidad']}) para producto $productoId");
                        }

                        foreach ($data['centros'] as $centroId => $cantidad) {
                            DB::table('centro_producto')->updateOrInsert(
                                [
                                    'requisicion_id' => $requisicion->id,
                                    'producto_id'    => $productoId,
                                    'centro_id'      => $centroId,
                                ],
                                [
                                    'amount'      => $cantidad,
                                    'updated_at'  => now(),
                                    'deleted_at'  => null, // Reactivar si estaba softdeleted
                                    'created_at'  => now(),
                                ]
                            );
                        }
                    }
                }
            }

            // Agregar nuevos productos
            if ($request->has('nuevos')) {
                foreach ($request->nuevos as $nuevo) {
                    if (empty($nuevo['nombre']) || empty($nuevo['cantidad']) || intval($nuevo['cantidad']) < 1) {
                        continue;
                    }
                    $producto = Producto::create([
                        'name_produc' => $nuevo['nombre'],
                    ]);

                    DB::table('producto_requisicion')->insert([
                        'id_producto'   => $producto->id,
                        'id_requisicion' => $requisicion->id,
                        'pr_amount'     => $nuevo['cantidad'],
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    OrdencompraProducto::create([
                        'producto_id'      => $producto->id,
                        'orden_compras_id' => $ordenCompra->id,
                        'proveedor_id'     => $ordenCompra->proveedor_id,
                        'observaciones'    => $ordenCompra->observaciones,
                        'methods_oc'       => $ordenCompra->methods_oc,
                        'plazo_oc'         => $ordenCompra->plazo_oc,
                        'date_oc'          => now(),
                        'order_oc'         => $ordenCompra->order_oc,
                    ]);
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
        $orden = OrdenCompra::with(['requisicion', 'proveedor', 'ordencompraProductos.producto'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('ordenes_compra.pdf', ['ordenCompra' => $orden]);
        return $pdf->download("orden_compra_{$orden->id}.pdf");
    }
}