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
use App\Http\Controllers\estatusrequisicion\EstatusRequisicionController;

class OrdenCompraController extends Controller
{
    public function index()
    {
        $ordenes = OrdenCompra::with(['requisicion', 'ordencompraProductos.proveedor'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('ordenes_compra.lista', compact('ordenes'));
    }

    public function create(Request $request, $requisicion_id = null)
    {
        $reqId = $requisicion_id ?? $request->query('requisicion_id');
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
        $lineasDistribuidas = collect();
        $ordenes = collect();

        if ($reqId) {
            $requisicion = Requisicion::find($reqId);

            if ($requisicion) {
                // Productos disponibles: excluir solo si ya existen en OCs principales (no OC-DIST)
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
                    ->whereNotExists(function ($subquery) use ($requisicion) {
                        // Excluir si el producto tiene líneas distribuidas pendientes para esta requisición
                        $subquery->select(DB::raw(1))
                            ->from('ordencompra_producto as ocp0')
                            ->whereColumn('ocp0.producto_id', 'productos.id')
                            ->where('ocp0.requisicion_id', $requisicion->id)
                            ->whereNull('ocp0.orden_compras_id')
                            ->whereNull('ocp0.deleted_at');
                    })
                    ->orderBy('productos.id', 'asc')
                    ->get();

                foreach ($productosDisponibles as $producto) {
                    $producto->setRelation('pivot', (object) [
                        'pr_amount' => $producto->pr_amount
                    ]);
                }

                // Líneas distribuidas (pendientes, sin OC): orden_compras_id IS NULL
                $lineasDistribuidas = DB::table('ordencompra_producto as ocp')
                    ->join('productos as p', 'ocp.producto_id', '=', 'p.id')
                    ->leftJoin('proveedores as prov', 'ocp.proveedor_id', '=', 'prov.id')
                    ->whereNull('ocp.deleted_at')
                    ->whereNull('ocp.orden_compras_id')
                    ->where('ocp.requisicion_id', $requisicion->id)
                    ->select(
                        'ocp.id as ocp_id',
                        'ocp.producto_id',
                        'ocp.proveedor_id',
                        'ocp.total as cantidad',
                        'p.name_produc',
                        'p.unit_produc',
                        'p.stock_produc',
                        'prov.prov_name'
                    )
                    ->get();

                // Ya no ocultar líneas distribuidas por existir OC principal; se mostrarán hasta asociarlas

                // Órdenes principales (no OC-DIST)
                $ordenes = OrdenCompra::with('ordencompraProductos.producto', 'ordencompraProductos.proveedor')
                    ->where('requisicion_id', $requisicion->id)
                    ->whereNull('deleted_at')
                    ->where(function ($q) {
                        $q->whereNull('order_oc')
                          ->orWhere('order_oc', 'not like', 'OC-DIST-%');
                    })
                    ->orderBy('id', 'desc')
                    ->get();

                // Si ya está completa, actualizar estatus a 10 si no lo está
                try {
                    if ($this->isRequisitionComplete((int)$requisicion->id)) {
                        $estatusActual = (int) DB::table('estatus_requisicion')
                            ->where('requisicion_id', $requisicion->id)
                            ->whereNull('deleted_at')
                            ->where('estatus', 1)
                            ->value('estatus_id');
                        if ($estatusActual !== 10) {
                            $this->setRequisicionStatus((int)$requisicion->id, 10, 'Requisición completada automáticamente');
                        }
                    }
                } catch (\Throwable $e) { /* noop */ }
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
            'centros',
            'lineasDistribuidas',
            'ordenes'
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
            'productos.*.ocp_id' => 'nullable|integer|exists:ordencompra_producto,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.centros' => 'nullable|array',
            'productos.*.centros.*' => 'nullable|integer|min:0',
            'productos.*.stock_e' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Crear SIEMPRE una nueva OC con el proveedor seleccionado
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

            foreach ($request->productos as $rowKey => $productoData) {
                if (empty($productoData['id'])) continue;

                $productoId = (int) $productoData['id'];
                $cantidadIngresada = (int) ($productoData['cantidad'] ?? 0);
                $ocpId = $productoData['ocp_id'] ?? null;
                $stockE = isset($productoData['stock_e']) && $productoData['stock_e'] !== '' ? (int)$productoData['stock_e'] : null;

                if ($ocpId) {
                    // Asociar línea pre-distribuida y forzar proveedor del formulario
                    $ocp = OrdenCompraProducto::where('id', $ocpId)
                        ->whereNull('orden_compras_id')
                        ->where('requisicion_id', $request->requisicion_id)
                        ->firstOrFail();

                    // Forzar proveedor al seleccionado por el usuario
                    $ocp->proveedor_id = (int)$request->proveedor_id;

                    // Actualizar cantidad si el usuario la editó
                    if ($cantidadIngresada > 0 && $cantidadIngresada !== (int)$ocp->total) {
                        $ocp->total = $cantidadIngresada;
                    }
                    $ocp->orden_compras_id = $orden->id;
                    if ($stockE !== null) { $ocp->stock_e = $stockE; }
                    $ocp->save();

                    if ($stockE !== null && $stockE > 0) {
                        $producto = Producto::lockForUpdate()->findOrFail($productoId);
                        $producto->stock_produc = max(0, (int)$producto->stock_produc - $stockE);
                        $producto->save();
                    }

                    // Distribución por centros (recrear)
                    OrdenCompraCentroProducto::where('orden_compra_id', $orden->id)
                        ->where('producto_id', $productoId)
                        ->delete();

                    if (!empty($productoData['centros'])) {
                        foreach ($productoData['centros'] as $centroId => $cantidad) {
                            if ((int)$cantidad > 0) {
                                OrdenCompraCentroProducto::create([
                                    'orden_compra_id' => $orden->id,
                                    'producto_id'     => $productoId,
                                    'centro_id'       => $centroId,
                                    'amount'          => (int)$cantidad,
                                ]);
                            }
                        }
                    }

                    continue;
                }

                // Línea normal: usar el proveedor del formulario
                OrdenCompraProducto::create([
                    'producto_id'      => $productoId,
                    'orden_compras_id' => $orden->id,
                    'requisicion_id'   => $request->requisicion_id,
                    'proveedor_id'     => $request->proveedor_id,
                    'total'            => $cantidadIngresada,
                    'stock_e'          => $stockE,
                ]);

                if ($stockE !== null && $stockE > 0) {
                    $producto = Producto::lockForUpdate()->findOrFail($productoId);
                    $producto->stock_produc = max(0, (int)$producto->stock_produc - $stockE);
                    $producto->save();
                }

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
                ->with('success', 'Orden guardada correctamente.');
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
            'distribucion' => 'required|array|min:2',
            'distribucion.*.proveedor_id' => 'required|exists:proveedores,id',
            'distribucion.*.cantidad' => 'required|integer|min:1',
            'distribucion.*.methods_oc' => 'nullable|string',
            'distribucion.*.plazo_oc' => 'nullable|string',
            'distribucion.*.observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $productoId = (int)$request->producto_id;
            $requisicionId = (int)$request->requisicion_id;
            $distribuciones = $request->distribucion;

            // Validar suma exacta
            $cantidadOriginal = (int) DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicionId)
                ->where('id_producto', $productoId)
                ->value('pr_amount');

            $totalDistribucion = collect($distribuciones)->sum(function($d){ return (int)$d['cantidad']; });
            if ($totalDistribucion !== $cantidadOriginal) {
                $msg = 'La distribución total ('.$totalDistribucion.') debe ser igual a la cantidad original ('.$cantidadOriginal.').';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            // Evitar proveedores duplicados
            $provIds = array_map(fn($d) => (int)$d['proveedor_id'], $distribuciones);
            if (count($provIds) !== count(array_unique($provIds))) {
                $msg = 'No se permite repetir proveedores en la distribución.';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            $producto = Producto::findOrFail($productoId);

            $lineas = [];
            foreach ($distribuciones as $dist) {
                $ocp = OrdenCompraProducto::create([
                    'producto_id'      => $productoId,
                    // No establecer orden_compras_id aquí; quedará NULL por defecto
                    'requisicion_id'   => $requisicionId,
                    'proveedor_id'     => (int)$dist['proveedor_id'],
                    'total'            => (int)$dist['cantidad'],
                ]);

                $prov = Proveedor::find($dist['proveedor_id']);

                $lineas[] = [
                    'ocp_id' => $ocp->id,
                    'producto_id' => $productoId,
                    'producto_nombre' => $producto->name_produc,
                    'unidad' => $producto->unit_produc,
                    'stock' => $producto->stock_produc,
                    'cantidad' => (int)$dist['cantidad'],
                    'proveedor_id' => (int)$dist['proveedor_id'],
                    'proveedor_nombre' => $prov?->prov_name ?? 'Proveedor',
                ];
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'lineas' => $lineas], 200);
            }

            return redirect()->route('ordenes_compra.create', ['requisicion_id' => $requisicionId])
                ->with('success', 'Distribución guardada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error distribuyendo producto: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
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

        // Marcar estatus 5 (OC generada) o 10 si ya está completa
        try { 
            $estatus = $this->isRequisitionComplete((int)$requisicionId) ? 10 : 5;
            $this->setRequisicionStatus((int)$requisicionId, $estatus, $estatus===10?'Requisición completa':'Cambio automático al descargar OC'); 
        } catch (\Throwable $e) {}
        
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

            // Procesar líneas: si se originaron antes que la OC (fueron distribuidas), volver a pendientes; si no, eliminar lógicamente
            $lineas = OrdenCompraProducto::where('orden_compras_id', $id)->get();
            foreach ($lineas as $linea) {
                if ($linea->created_at && $orden->created_at && $linea->created_at->lt($orden->created_at)) {
                    // Línea proveniente de distribución previa: volver a pendiente
                    $linea->orden_compras_id = null;
                    $linea->save();
                } else {
                    // Línea normal creada con la OC: soft delete
                    $linea->delete();
                }
            }

            // Borrar distribución por centros
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

    public function undoDistribucion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'requisicion_id' => 'required|exists:requisicion,id',
            'ocp_ids' => 'required|array|min:1',
            'ocp_ids.*' => 'integer|exists:ordencompra_producto,id',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $requisicionId = (int) $request->requisicion_id;
            $ids = array_map('intval', $request->ocp_ids);

            $lineas = OrdenCompraProducto::whereIn('id', $ids)
                ->where('requisicion_id', $requisicionId)
                ->whereNull('orden_compras_id')
                ->get();

            foreach ($lineas as $l) {
                $l->delete();
            }

            DB::commit();
            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'count' => $lineas->count()], 200);
            }
            return redirect()->back()->with('success', 'Distribución deshecha correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al deshacer distribución: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function download($requisicionId)
    {
        // Marcar estatus 5 (OC generada) o 10 si ya está completa
        try { 
            $estatus = $this->isRequisitionComplete((int)$requisicionId) ? 10 : 5;
            $this->setRequisicionStatus((int)$requisicionId, $estatus, $estatus===10?'Requisición completa':'Cambio automático al descargar OC'); 
        } catch (\Throwable $e) {}

        $ordenes = OrdenCompra::where('requisicion_id', $requisicionId)
            ->with(['ordencompraProductos.producto', 'ordencompraProductos.proveedor'])
            ->get();

        if ($ordenes->isEmpty()) {
            return redirect()->back()->with('error', 'No hay órdenes de compra para descargar.');
        }

        if ($ordenes->count() === 1) {
            $orden = $ordenes->first();
            $data = $this->buildPdfData($orden);
            $pdf = Pdf::loadView('ordenes_compra.pdf', $data);
            $fileName = 'orden_' . ($orden->order_oc ?? ('OC-' . $orden->id)) . '.pdf';
            return $pdf->download($fileName);
        }

        // Varias órdenes: generar ZIP
        $zip = new ZipArchive();
        $zipFileName = 'ordenes_compra_requisicion_' . $requisicionId . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return redirect()->back()->with('error', 'No se pudo crear el ZIP.');
        }

        foreach ($ordenes as $orden) {
            $data = $this->buildPdfData($orden);
            $pdf = Pdf::loadView('ordenes_compra.pdf', $data);
            $zip->addFromString('orden_' . ($orden->order_oc ?? ('OC-' . $orden->id)) . '.pdf', $pdf->output());
        }
        $zip->close();

        return Response::download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    private function buildPdfData(OrdenCompra $orden): array
    {
        $proveedor = optional($orden->ordencompraProductos->first())->proveedor;

        // Items por producto (suma cantidades) y precio desde productos.price_produc
        $items = [];
        $porProducto = $orden->ordencompraProductos->groupBy('producto_id');
        foreach ($porProducto as $productoId => $lineas) {
            $producto = optional($lineas->first())->producto;
            $cantidad = (int) $lineas->sum('total');
            if ($producto) {
                $items[] = [
                    'producto_id' => $producto->id,
                    'name_produc' => $producto->name_produc,
                    'description_produc' => $producto->description_produc ?? '',
                    'unit_produc' => $producto->unit_produc ?? '',
                    'po_amount' => $cantidad,
                    'precio_unitario' => (float) ($producto->price_produc ?? 0),
                ];
            }
        }

        // Distribución por centros
        $distRows = DB::table('ordencompra_centro_producto as ocp')
            ->join('centro as c', 'ocp.centro_id', '=', 'c.id')
            ->select('ocp.producto_id', 'c.name_centro', 'ocp.amount')
            ->where('ocp.orden_compra_id', $orden->id)
            ->get();
        $distribucion = [];
        foreach ($distRows as $r) {
            $distribucion[$r->producto_id][] = [
                'name_centro' => $r->name_centro,
                'amount' => (int)$r->amount,
            ];
        }

        $subtotal = 0;
        foreach ($items as $i) {
            $subtotal += ($i['po_amount'] * $i['precio_unitario']);
        }

        return [
            'orden' => $orden,
            'proveedor' => $proveedor,
            'items' => $items,
            'distribucion' => $distribucion,
            'subtotal' => $subtotal,
            'observaciones' => $orden->observaciones,
            'fecha_actual' => now()->format('d/m/Y H:i'),
            'logo' => null,
            'date_oc' => ($orden->created_at ? $orden->created_at->format('d/m/Y') : now()->format('d/m/Y')),
            'methods_oc' => $orden->methods_oc,
            'plazo_oc' => $orden->plazo_oc,
        ];
    }

    public function historial()
    {
        $ordenes = OrdenCompra::with([
            'requisicion',
            'ordencompraProductos.producto',
            'ordencompraProductos.proveedor'
        ])->orderBy('id', 'desc')->get();

        return view('ordenes_compra.historial', compact('ordenes'));
    }

    public function exportPDF($id)
    {
        $orden = OrdenCompra::with(['ordencompraProductos.producto', 'ordencompraProductos.proveedor'])
            ->findOrFail($id);

        $data = $this->buildPdfData($orden);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ordenes_compra.pdf', $data);
        $fileName = 'orden_' . ($orden->order_oc ?? ('OC-' . $orden->id)) . '.pdf';
        return $pdf->download($fileName);
    }

    public function storeEntregaParcial(Request $request)
    {
        $data = $request->validate([
            'ocp_id' => 'required|exists:ordencompra_producto,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $ocp = OrdenCompraProducto::lockForUpdate()->findOrFail((int)$data['ocp_id']);
            if (!$ocp->orden_compras_id) throw new \Exception('La línea no pertenece a una Orden de Compra');
            $disponible = (int)($ocp->stock_e ?? 0);
            if ($disponible < 1) throw new \Exception('No hay stock para entregar');
            $entregar = min((int)$data['cantidad'], $disponible);

            // Crear recepción PENDIENTE desde stock (no confirmar aún)
            DB::table('recepcion')->insert([
                'orden_compra_id' => $ocp->orden_compras_id,
                'producto_id' => $ocp->producto_id,
                'cantidad' => $entregar,
                'cantidad_recibido' => null,
                'fecha' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // No modificar stock_e; se conserva como dato histórico de lo que salió de stock

            // Estatus 12: entrega parcial registrada y pendiente de confirmación
            $this->setRequisicionStatus((int)$ocp->requisicion_id, 12, 'Entrega parcial registrada (pendiente de confirmación)');

            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function restaurarStock(Request $request)
    {
        $data = $request->validate([
            'ocp_id' => 'required|exists:ordencompra_producto,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();
            $ocp = OrdenCompraProducto::lockForUpdate()->findOrFail((int)$data['ocp_id']);

            // Si la OC asociada está soft-deleted, no hacer nada
            if (!empty($ocp->orden_compras_id)) {
                $oc = OrdenCompra::withTrashed()->find($ocp->orden_compras_id);
                if ($oc && method_exists($oc, 'trashed') && $oc->trashed()) {
                    DB::commit();
                    return response()->json(['ok' => true]);
                }
            }

            // Sumar al inventario del producto la cantidad indicada, sin tocar stock_e
            $producto = Producto::lockForUpdate()->findOrFail($ocp->producto_id);
            $producto->stock_produc = (int)$producto->stock_produc + (int)$data['cantidad'];
            $producto->save();

            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function setRequisicionStatus(int $requisicionId, int $estatusId, ?string $comentario = null): void
    {
        // Desactivar estatus activos previos y crear el nuevo
        DB::table('estatus_requisicion')
            ->where('requisicion_id', $requisicionId)
            ->update(['estatus' => 0, 'updated_at' => now()]);

        DB::table('estatus_requisicion')->insert([
            'requisicion_id' => $requisicionId,
            'estatus_id' => $estatusId,
            'estatus' => 1,
            'comentario' => $comentario,
            'date_update' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function confirmarRecepcion(Request $request)
    {
        $data = $request->validate([
            'recepcion_id' => 'required|exists:recepcion,id',
            'cantidad' => 'required|integer|min:0',
        ]);
        try {
            DB::beginTransaction();
            $rec = DB::table('recepcion')->lockForUpdate()->where('id', (int)$data['recepcion_id'])->first();
            if (!$rec) throw new \Exception('Registro no encontrado');
            $max = (int)$rec->cantidad;
            $val = (int)$data['cantidad'];
            if ($val > $max) throw new \Exception('No puede recibir más de lo entregado');

            // Actualizar recepción
            DB::table('recepcion')->where('id', $rec->id)->update([
                'cantidad_recibido' => $val,
                'updated_at' => now(),
            ]);

            // No modificar stock_e aquí; ya se descontó al crear la recepción desde stock

            // Actualizar estatus según completitud total
            $oc = DB::table('orden_compras')->where('id', $rec->orden_compra_id)->first();
            if ($oc) {
                $complete = $this->isRequisitionComplete((int)$oc->requisicion_id);
                $this->setRequisicionStatus((int)$oc->requisicion_id, $complete ? 10 : 12, $complete ? 'Requisición completada' : 'Recepción confirmada parcialmente');
            }

            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function completarSiListo(Request $request)
    {
        $data = $request->validate([
            'requisicion_id' => 'required|exists:requisicion,id',
        ]);
        try {
            $reqId = (int)$data['requisicion_id'];
            $complete = $this->isRequisitionComplete($reqId);
            if ($complete) {
                $this->setRequisicionStatus($reqId, 10, 'Requisición completada automáticamente');
                return response()->json(['ok' => true, 'estatus' => 10]);
            }
            return response()->json(['ok' => false, 'message' => 'Aún no está completa'], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function isRequisitionComplete(int $requisicionId): bool
    {
        // Requerido por producto según centros distribuidos
        $reqPorProducto = DB::table('centro_producto')
            ->where('requisicion_id', $requisicionId)
            ->select('producto_id', DB::raw('SUM(amount) as req'))
            ->groupBy('producto_id')
            ->pluck('req', 'producto_id');

        // Fallback: si no hay distribución por centros, usar producto_requisicion
        if ($reqPorProducto->isEmpty()) {
            $reqPorProducto = DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicionId)
                ->select('id_producto as producto_id', DB::raw('SUM(pr_amount) as req'))
                ->groupBy('id_producto')
                ->pluck('req', 'producto_id');
        }
        if ($reqPorProducto->isEmpty()) return false;

        // Recibido por coordinador (entregas confirmadas)
        $recEnt = DB::table('entrega')
            ->where('requisicion_id', $requisicionId)
            ->whereNull('deleted_at')
            ->select('producto_id', DB::raw('SUM(COALESCE(cantidad_recibido,0)) as rec'))
            ->groupBy('producto_id')
            ->pluck('rec', 'producto_id');

        // Recibido desde stock (confirmado)
        $recStock = DB::table('recepcion as r')
            ->join('orden_compras as oc','oc.id','=','r.orden_compra_id')
            ->where('oc.requisicion_id', $requisicionId)
            ->whereNull('r.deleted_at')
            ->select('r.producto_id', DB::raw('SUM(COALESCE(r.cantidad_recibido,0)) as rec'))
            ->groupBy('r.producto_id')
            ->pluck('rec', 'producto_id');

        foreach ($reqPorProducto as $pid => $req) {
            $recibido = (int)($recEnt[$pid] ?? 0) + (int)($recStock[$pid] ?? 0);
            if ($recibido < (int)$req) return false;
        }
        return true;
    }

    public function storeSalidaStockEnEntrega(Request $request)
    {
        $data = $request->validate([
            'requisicion_id' => 'required|exists:requisicion,id',
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
        ]);
        try {
            DB::beginTransaction();

            $producto = Producto::lockForUpdate()->findOrFail((int)$data['producto_id']);
            $cantidad = (int)$data['cantidad'];
            $stockActual = (int)($producto->stock_produc ?? 0);
            if ($cantidad > $stockActual) {
                throw new \Exception('Stock insuficiente para realizar la salida');
            }

            // Descontar inventario
            $producto->stock_produc = $stockActual - $cantidad;
            $producto->save();

            // Registrar en tabla entrega (pendiente de confirmación)
            DB::table('entrega')->insert([
                'requisicion_id' => (int)$data['requisicion_id'],
                'producto_id' => (int)$data['producto_id'],
                'cantidad' => $cantidad,
                'cantidad_recibido' => null,
                'fecha' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Estatus 12: movimiento parcial registrado
            $this->setRequisicionStatus((int)$data['requisicion_id'], 12, 'Salida de stock registrada');

            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
