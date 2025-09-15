<?php

namespace App\Http\Controllers\ordencompra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraProducto; // corregido el import
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
    public function index()
    {
        $ordenes = OrdenCompra::with(['requisicion', 'ordencompraProductos.proveedor'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('ordenes_compra.lista', compact('ordenes'));
    }

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
                $productosDisponibles = Producto::select('productos.*', 'producto_requisicion.pr_amount')
                    ->join('producto_requisicion', 'productos.id', '=', 'producto_requisicion.id_producto')
                    ->where('producto_requisicion.id_requisicion', $requisicion->id)
                    ->whereNull('productos.deleted_at')
                    ->whereNotExists(function ($subquery) use ($requisicion) {
                        $subquery->select(DB::raw(1))
                            ->from('ordencompra_producto')
                            ->join('orden_compras', 'ordencompra_producto.orden_compras_id', '=', 'orden_compras.id')
                            ->whereRaw('ordencompra_producto.producto_id = productos.id')
                            ->where('orden_compras.requisicion_id', $requisicion->id)
                            ->whereNull('ordencompra_producto.deleted_at');
                    })
                    ->orderBy('productos.id', 'asc')
                    ->get();

                foreach ($productosDisponibles as $producto) {
                    $producto->setRelation('pivot', (object) [
                        'pr_amount' => $producto->pr_amount
                    ]);
                }
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
            'productos.*.centros' => 'nullable|array',
            'productos.*.centros.*' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Buscar una orden existente para esta requisición (no distribuida)
            $orden = OrdenCompra::where('requisicion_id', $request->requisicion_id)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('order_oc')
                      ->orWhere('order_oc', 'not like', 'OC-DIST-%');
                })
                ->latest('id')
                ->first();

            if (!$orden) {
                // No existe: crear nueva orden para la requisición
                $ultimaOrden = OrdenCompra::withTrashed()->orderBy('id', 'desc')->first();
                $numeroOrden = 'OC-' . (($ultimaOrden ? $ultimaOrden->id : 0) + 1) . '-' . now()->format('Ymd');

                $orden = OrdenCompra::create([
                    'requisicion_id' => $request->requisicion_id,
                    'observaciones'  => $request->observaciones,
                    'methods_oc'     => $request->methods_oc,
                    'plazo_oc'       => $request->plazo_oc,
                    'date_oc'        => now(),
                    'order_oc'       => $numeroOrden,
                ]);
            } else {
                // Existe: actualizar campos si vienen en la solicitud, mantener número y fecha
                $orden->update([
                    'observaciones' => $request->observaciones ?? $orden->observaciones,
                    'methods_oc'    => $request->methods_oc ?? $orden->methods_oc,
                    'plazo_oc'      => $request->plazo_oc ?? $orden->plazo_oc,
                ]);
            }

            foreach ($request->productos as $productoId => $productoData) {
                if (!isset($productoData['id']) || !$productoData['id']) continue;

                $productoId = $productoData['id'];
                $cantidadIngresada = (int)($productoData['cantidad'] ?? 0);

                OrdenCompraProducto::create([
                    'producto_id'      => $productoId,
                    'orden_compras_id' => $orden->id,
                    'proveedor_id'     => $request->proveedor_id,
                    'total'            => $cantidadIngresada,
                ]);

                if (!empty($productoData['centros'])) {
                    foreach ($productoData['centros'] as $centroId => $cantidad) {
                        if ((int)$cantidad > 0) {
                            OrdenCompraCentroProducto::updateOrCreate(
                                [
                                    'orden_compra_id' => $orden->id,
                                    'producto_id' => $productoId,
                                    'centro_id' => $centroId,
                                ],
                                [
                                    'amount' => (int)$cantidad,
                                ]
                            );
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('ordenes_compra.create', ['requisicion_id' => $request->requisicion_id])
                ->with('success', 'Orden de compra ' . ($orden->order_oc ?? $orden->id) . ' actualizada/creada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando orden de compra: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function distribuirProveedores(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:productos,id',
            'requisicion_id' => 'required|exists:requisicion,id',
            'distribucion' => 'required|array|min:1',
            'distribucion.*.proveedor_id' => 'required|exists:proveedores,id',
            'distribucion.*.cantidad' => 'required|integer|min:1',
            'distribucion.*.methods_oc' => 'required|string',
            'distribucion.*.plazo_oc' => 'required|string',
            'distribucion.*.observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $productoId = $request->producto_id;
            $requisicionId = $request->requisicion_id;
            $distribuciones = $request->distribucion;

            $totalDistribucion = 0;
            foreach ($distribuciones as $dist) {
                $totalDistribucion += (int)$dist['cantidad'];
            }

            $cantidadOriginal = DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicionId)
                ->where('id_producto', $productoId)
                ->value('pr_amount');

            if ($totalDistribucion != $cantidadOriginal) {
                return redirect()->back()->with('error', 
                    'La distribución total (' . $totalDistribucion . ') debe ser igual a la cantidad original (' . $cantidadOriginal . ')');
            }

            $producto = Producto::find($productoId);

            $ultimaOrden = OrdenCompra::withTrashed()->orderBy('id', 'desc')->first();
            $baseOrdenId = ($ultimaOrden ? $ultimaOrden->id : 0) + 1;

            foreach ($distribuciones as $index => $dist) {
                $numeroOrden = 'OC-DIST-' . $baseOrdenId . '-' . ($index + 1) . '-' . now()->format('Ymd');

                $orden = OrdenCompra::create([
                    'requisicion_id' => $requisicionId,
                    'date_oc'        => now(),
                    'order_oc'       => $numeroOrden,
                    'observaciones'  => $dist['observaciones'] ?? ('Distribución de ' . $producto->name_produc),
                    'methods_oc'     => $dist['methods_oc'] ?? null,
                    'plazo_oc'       => $dist['plazo_oc'] ?? null,
                ]);

                OrdenCompraProducto::create([
                    'producto_id'      => $productoId,
                    'orden_compras_id' => $orden->id,
                    'proveedor_id'     => $dist['proveedor_id'],
                    'total'            => $dist['cantidad'],
                ]);
            }

            DB::commit();
            return redirect()->route('ordenes_compra.create', ['requisicion_id' => $requisicionId])
                ->with('success', 'Distribución guardada correctamente. Se crearon ' . count($distribuciones) . ' órdenes.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error distribuyendo producto: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Descargar ZIP de órdenes
     */
    public function downloadZip($requisicionId)
    {
        $requisicion = Requisicion::findOrFail($requisicionId);
        $ordenes = OrdenCompra::where('requisicion_id', $requisicionId)
            ->with(['ordencompraProductos.producto'])
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
     * Anular orden (soft delete de orden y sus relaciones)
     */
    public function anular($id)
    {
        DB::beginTransaction();
        try {
            $orden = OrdenCompra::findOrFail($id);

            // Soft delete de productos vinculados
            OrdenCompraProducto::where('orden_compras_id', $id)->delete();

            // Soft delete de distribución por centros
            OrdenCompraCentroProducto::where('orden_compra_id', $id)->delete();

            // Soft delete del encabezado
            $orden->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Orden de compra anulada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al anular la orden de compra: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al anular la orden: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalle
     */
    public function show($id)
    {
        $orden = OrdenCompra::with(['requisicion', 'ordencompraProductos.producto', 'ordencompraProductos.proveedor'])
            ->findOrFail($id);

        return view('ordenes_compra.show', ['ordenCompra' => $orden]);
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        $ordenCompra = OrdenCompra::with([
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
            $ordenCompra->update([
                'observaciones'  => $request->observaciones,
                'methods_oc'     => $request->methods_oc,
                'plazo_oc'       => $request->plazo_oc,
            ]);

            foreach ($request->productos as $productoId => $productoData) {
                $ordenProducto = OrdenCompraProducto::where('orden_compras_id', $id)
                    ->where('producto_id', $productoId)
                    ->first();

                if ($ordenProducto) {
                    $ordenProducto->update([
                        'total' => $productoData['cantidad'] ?? 0,
                    ]);
                }

                OrdenCompraCentroProducto::where('orden_compra_id', $id)
                    ->where('producto_id', $productoId)
                    ->delete();

                if (!empty($productoData['centros'])) {
                    foreach ($productoData['centros'] as $centroId => $cantidad) {
                        if ((int)$cantidad > 0) {
                            OrdenCompraCentroProducto::create([
                                'orden_compra_id' => $id,
                                'producto_id'     => $productoId,
                                'centro_id'       => $centroId,
                                'amount'          => (int)$cantidad,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('ordenes_compra.show', $id)
                ->with('success', 'Orden de compra actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando orden de compra: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function vistaDistribucionProveedores(Request $request, $requisicion_id = null)
    {
        $reqId = $request->query('requisicion_id', $requisicion_id);
        if (!$reqId) {
            abort(404, 'Requisición no especificada');
        }

        $requisicion = Requisicion::findOrFail($reqId);

        $productosDisponibles = Producto::select('productos.*', 'producto_requisicion.pr_amount')
            ->join('producto_requisicion', 'productos.id', '=', 'producto_requisicion.id_producto')
            ->where('producto_requisicion.id_requisicion', $requisicion->id)
            ->whereNull('productos.deleted_at')
            ->orderBy('productos.id', 'asc')
            ->get();

        foreach ($productosDisponibles as $producto) {
            $producto->setRelation('pivot', (object)[
                'pr_amount' => $producto->pr_amount ?? 0
            ]);
        }

        $proveedores = Proveedor::all();

        return view('ordenes_compra.distribucion_proveedores', compact('requisicion', 'productosDisponibles', 'proveedores'));
    }
}
