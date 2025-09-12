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
    public function index()
    {
        $ordenes = OrdenCompra::with(['requisicion', 'proveedor'])
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
                // Buscar productos que no tienen orden de compra completa (con campos null)
                // EXCLUYENDO los que ya fueron distribuidos
                $productosDisponibles = Producto::select('productos.*', 'producto_requisicion.pr_amount')
                    ->join('producto_requisicion', 'productos.id', '=', 'producto_requisicion.id_producto')
                    ->where('producto_requisicion.id_requisicion', $requisicion->id)
                    ->whereNull('productos.deleted_at')
                    ->where(function ($query) use ($requisicion) {
                        $query->whereNotExists(function ($subquery) use ($requisicion) {
                            $subquery->select(DB::raw(1))
                                ->from('ordencompra_producto')
                                ->join('orden_compras', 'ordencompra_producto.orden_compras_id', '=', 'orden_compras.id')
                                ->whereRaw('ordencompra_producto.producto_id = productos.id')
                                ->where('orden_compras.requisicion_id', $requisicion->id)
                                ->whereNull('ordencompra_producto.deleted_at');
                        })
                            ->orWhereExists(function ($subquery) use ($requisicion) {
                                $subquery->select(DB::raw(1))
                                    ->from('ordencompra_producto')
                                    ->join('orden_compras', 'ordencompra_producto.orden_compras_id', '=', 'orden_compras.id')
                                    ->whereRaw('ordencompra_producto.producto_id = productos.id')
                                    ->where('orden_compras.requisicion_id', $requisicion->id)
                                    ->whereNull('ordencompra_producto.deleted_at')
                                    ->where(function ($q) {
                                        $q->whereNull('ordencompra_producto.observaciones')
                                            ->orWhereNull('ordencompra_producto.methods_oc')
                                            ->orWhereNull('ordencompra_producto.plazo_oc');
                                    });
                            });
                    })
                    // EXCLUIR productos que ya tienen órdenes de compra distribuidas completas
                    ->whereNotExists(function ($subquery) use ($requisicion) {
                        $subquery->select(DB::raw(1))
                            ->from('ordencompra_producto')
                            ->join('orden_compras', 'ordencompra_producto.orden_compras_id', '=', 'orden_compras.id')
                            ->whereRaw('ordencompra_producto.producto_id = productos.id')
                            ->where('orden_compras.requisicion_id', $requisicion->id)
                            ->whereNull('ordencompra_producto.deleted_at')
                            ->where('ordencompra_producto.order_oc', 'like', 'OC-DIST-%')
                            ->whereNotNull('ordencompra_producto.observaciones')
                            ->whereNotNull('ordencompra_producto.methods_oc')
                            ->whereNotNull('ordencompra_producto.plazo_oc');
                    })
                    ->orderBy('productos.id', 'asc')
                    ->get();

                // Carga manualmente el pivot para cada producto
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
            // Generar número único
            $ultimaOrden = OrdenCompra::withTrashed()->orderBy('id', 'desc')->first();
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

            foreach ($request->productos as $productoId => $productoData) {
                if (!isset($productoData['id']) || !$productoData['id']) continue;

                $productoId = $productoData['id'];
                $cantidadIngresada = (int)($productoData['cantidad'] ?? 0);

                // ... validaciones de distribución ...

                // Verificar si es un producto distribuido (buscar por order_oc que comience con OC-DIST)
                $ordenProductoDistribuido = OrdencompraProducto::where('producto_id', $productoId)
                    ->where('order_oc', 'like', 'OC-DIST-%')
                    ->whereNull('observaciones')
                    ->whereNull('methods_oc')
                    ->whereNull('plazo_oc')
                    ->first();

                if ($ordenProductoDistribuido) {
                    // Actualizar el registro distribuido existente
                    $ordenProductoDistribuido->update([
                        'orden_compras_id' => $orden->id,
                        'observaciones'    => $request->observaciones,
                        'methods_oc'       => $request->methods_oc,
                        'plazo_oc'         => $request->plazo_oc,
                        'date_oc'          => now(),
                        'order_oc'         => $numeroOrden,
                    ]);
                } else {
                    // Crear nuevo registro (producto normal)
                    OrdencompraProducto::create([
                        'producto_id'      => $productoId,
                        'orden_compras_id' => $orden->id,
                        'proveedor_id'     => $request->proveedor_id,
                        'total'            => $cantidadIngresada,
                        'observaciones'    => $request->observaciones,
                        'methods_oc'       => $request->methods_oc,
                        'plazo_oc'         => $request->plazo_oc,
                        'date_oc'          => now(),
                        'order_oc'         => $numeroOrden,
                    ]);
                }

                // Guardar distribución por centros
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
                ->with('success', 'Orden de compra ' . $numeroOrden . ' creada exitosamente.');
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
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $productoId = $request->producto_id;
            $requisicionId = $request->requisicion_id;
            $distribuciones = $request->distribucion;

            // Calcular total de la distribución
            $totalDistribucion = 0;
            foreach ($distribuciones as $dist) {
                $totalDistribucion += (int)$dist['cantidad'];
            }

            // Obtener la cantidad original del producto en la requisición
            $cantidadOriginal = DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicionId)
                ->where('id_producto', $productoId)
                ->value('pr_amount');

            // Validar que la distribución sea igual a la cantidad original
            if ($totalDistribucion != $cantidadOriginal) {
                return response()->json([
                    'success' => false,
                    'message' => 'La distribución total (' . $totalDistribucion . ') debe ser igual a la cantidad original (' . $cantidadOriginal . ')'
                ], 422);
            }

            // Obtener información del producto
            $producto = Producto::find($productoId);

            // Obtener el último ID de orden de compra para generar números únicos
            $ultimaOrden = OrdenCompra::withTrashed()->orderBy('id', 'desc')->first();
            $baseOrdenId = ($ultimaOrden ? $ultimaOrden->id : 0) + 1;

            // Crear órdenes de compra INDIVIDUALES para cada proveedor
            foreach ($distribuciones as $index => $dist) {
                // Generar número de orden único para cada proveedor
                $numeroOrden = 'OC-DIST-' . $baseOrdenId . '-' . ($index + 1) . '-' . now()->format('Ymd');

                // Obtener información del proveedor
                $proveedor = Proveedor::find($dist['proveedor_id']);

                // Crear orden de compra individual para este proveedor
                $orden = OrdenCompra::create([
                    'requisicion_id' => $requisicionId,
                    'proveedor_id'   => $dist['proveedor_id'],
                    'date_oc'        => now(), // Fecha actual
                    'order_oc'       => $numeroOrden,
                    'observaciones'  => 'Distribución de ' . $producto->name_produc . ' - Proveedor: ' . $proveedor->prov_name,
                    'methods_oc'     => null, // Se deja como null para completar después
                    'plazo_oc'       => null, // Se deja como null para completar después
                ]);

                // Crear registro del producto para esta orden individual
                OrdencompraProducto::create([
                    'producto_id'      => $productoId,
                    'orden_compras_id' => $orden->id,
                    'proveedor_id'     => $dist['proveedor_id'],
                    'total'            => $dist['cantidad'],
                    'order_oc'         => $numeroOrden,
                    'date_oc'          => now(), // Fecha actual
                    // Los campos que deben quedar como null para completar después
                    'observaciones'    => null,
                    'methods_oc'       => null,
                    'plazo_oc'         => null,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Distribución guardada correctamente. Se crearon ' . count($distribuciones) . ' órdenes individuales para cada proveedor.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error distribuyendo producto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function anular($id)
    {
        DB::beginTransaction();
        try {
            $orden = OrdenCompra::findOrFail($id);

            OrdencompraProducto::where('orden_compras_id', $id)->delete();
            OrdenCompraCentroProducto::where('orden_compra_id', $id)->delete();

            $orden->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Orden de compra anulada correctamente.');
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
            // Actualizar datos generales
            $ordenCompra->update([
                'proveedor_id'   => $request->proveedor_id,
                'observaciones'  => $request->observaciones,
                'methods_oc'     => $request->methods_oc,
                'plazo_oc'       => $request->plazo_oc,
            ]);

            // Actualizar productos
            foreach ($request->productos as $productoId => $productoData) {
                // Actualizar registro en ordencompra_producto
                $ordenProducto = OrdencompraProducto::where('orden_compras_id', $id)
                    ->where('producto_id', $productoId)
                    ->first();

                if ($ordenProducto) {
                    $ordenProducto->update([
                        'total' => $productoData['cantidad'] ?? 0,
                        'proveedor_id' => $request->proveedor_id,
                    ]);
                }

                // Eliminar distribución anterior
                OrdenCompraCentroProducto::where('orden_compra_id', $id)
                    ->where('producto_id', $productoId)
                    ->delete();

                // Guardar nueva distribución
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
}
