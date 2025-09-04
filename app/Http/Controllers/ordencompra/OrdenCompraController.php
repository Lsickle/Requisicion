<?php

namespace App\Http\Controllers\ordencompra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Centro;
use App\Models\Requisicion;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OrdenCompraController extends Controller
{
    public function index()
    {
        $ordenes = OrdenCompra::with(['proveedor', 'productos'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ordenes_compra.index', compact('ordenes'));
    }

    public function create(Request $request)
    {
        $productos   = Producto::with('proveedor')->get();
        $centros     = Centro::all();

        $requisicion = null;
        $distribucionCentros = [];
        $proveedoresProductos = collect();
        $proveedorPreseleccionado = null;

        $orderNumber = 'OC-' . str_pad(OrdenCompra::max('id') + 1, 6, '0', STR_PAD_LEFT);

        if ($request->has('requisicion_id') && $request->requisicion_id != 0) {
            $requisicion = Requisicion::with(['productos.proveedor'])->find($request->requisicion_id);

            if ($requisicion) {
                // Proveedores que sí están en los productos de la requisición
                $proveedoresProductos = $requisicion->productos->map(fn($p) => $p->proveedor)->filter()->unique('id');

                $proveedorPreseleccionado = $proveedoresProductos->count() === 1
                    ? $proveedoresProductos->first()->id
                    : null;

                // Distribución por centros
                $distribucionCentros = DB::table('centro_producto')
                    ->where('requisicion_id', $requisicion->id)
                    ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                    ->join('productos', 'centro_producto.producto_id', '=', 'productos.id')
                    ->select(
                        'centro_producto.producto_id',
                        'centro.name_centro',
                        'centro_producto.amount',
                        'centro_producto.centro_id'
                    )
                    ->get()
                    ->groupBy('producto_id');
            }
        }

        return view('ordenes_compra.create', compact(
            'requisicion',
            'centros',
            'productos',
            'distribucionCentros',
            'orderNumber',
            'proveedoresProductos',
            'proveedorPreseleccionado'
        ));
    }


    public function createFromRequisicion($id)
    {
        $requisicion = Requisicion::with(['productos', 'productos.proveedor'])->findOrFail($id);

        // Generar número de orden único
        $orderNumber = 'OC-' . str_pad(OrdenCompra::max('id') + 1, 6, '0', STR_PAD_LEFT);

        // Obtener proveedores únicos de los productos de la requisición
        $proveedoresProductos = $requisicion->productos->map(function ($producto) {
            return $producto->proveedor;
        })->filter()->unique('id');

        $proveedorPreseleccionado = $proveedoresProductos->count() === 1
            ? $proveedoresProductos->first()->id
            : null;

        // Distribución de productos en centros
        $distribucionCentros = DB::table('centro_producto')
            ->where('requisicion_id', $requisicion->id)
            ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
            ->join('productos', 'centro_producto.producto_id', '=', 'productos.id')
            ->select(
                'centro_producto.producto_id',
                'centro.name_centro',
                'centro_producto.amount',
                'centro_producto.centro_id'
            )
            ->get()
            ->groupBy('producto_id');

        // Obtener todos los productos y centros para la vista
        $productos = Producto::all();
        $centros = Centro::all();
        $proveedores = Proveedor::all();

        return view('ordenes_compra.create', compact(
            'requisicion',
            'orderNumber',
            'proveedoresProductos',
            'proveedorPreseleccionado',
            'distribucionCentros',
            'productos',
            'centros',
            'proveedores'
        ));
    }

    public function store(Request $request)
    {
        // Validación más robusta
        $validator = Validator::make($request->all(), [
            'date_oc'       => 'required|date',
            'proveedor_id'  => 'required|exists:proveedores,id',
            'productos'     => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.centros' => 'required|array|min:1',
            'productos.*.centros.*.id' => 'required|exists:centro,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Generar el número de orden automáticamente
        $ultimoOrderOc = DB::table('ordencompra_producto')
            ->orderByDesc('id')
            ->value('order_oc');

        if ($ultimoOrderOc) {
            $numero = intval(str_replace('OC-', '', $ultimoOrderOc)) + 1;
        } else {
            $numero = 1;
        }
        $nuevoOrderOc = 'OC-' . str_pad($numero, 6, '0', STR_PAD_LEFT);

        DB::beginTransaction();

        try {
            // Crear la orden de compra principal
            $ordenCompra = OrdenCompra::create([
                // Ya no guardes order_oc aquí, solo en la pivote
                'date_oc'        => $request->date_oc,
                'proveedor_id'   => $request->proveedor_id,
                'methods_oc'     => $request->methods_oc,
                'plazo_oc'       => $request->plazo_oc,
                'observaciones'  => $request->observaciones,
                'requisicion_id' => $request->requisicion_id != 0 ? $request->requisicion_id : null,
            ]);

            // Procesar cada producto
            foreach ($request->productos as $productoData) {
                $sumaCentros = array_sum(array_column($productoData['centros'], 'cantidad'));
                if ($sumaCentros != $productoData['cantidad']) {
                    throw new \Exception("La suma de cantidades por centros no coincide con la cantidad total para el producto {$productoData['id']}");
                }

                $producto = Producto::findOrFail($productoData['id']);
                $precioUnitario = $producto->price_produc;

                // Adjuntar producto a la orden de compra (tabla pivote)
                $ordenCompra->productos()->attach($productoData['id'], [
                    'po_amount'       => $productoData['cantidad'],
                    'precio_unitario' => $precioUnitario,
                    'order_oc'        => $nuevoOrderOc, // Usar el nuevo número generado
                    'date_oc'         => $request->date_oc,
                    'methods_oc'      => $request->methods_oc,
                    'plazo_oc'        => $request->plazo_oc,
                    'observaciones'   => $request->observaciones,
                ]);

                foreach ($productoData['centros'] as $centroData) {
                    if ($centroData['cantidad'] > 0) {
                        DB::table('centro_ordencompra')->insert([
                            'producto_id'     => $productoData['id'],
                            'centro_id'       => $centroData['id'],
                            'rc_amount'       => $centroData['cantidad'],
                            'orden_compra_id' => $ordenCompra->id,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('ordenes_compra.show', $ordenCompra->id)
                ->with('success', 'Orden de compra creada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al crear la orden de compra: ' . $e->getMessage()]);
        }
    }


    public function listaAprobadas()
    {
        $requisiciones = Requisicion::with([
            'productos',
            'productos.centrosRequisicion' => function ($query) {
                $query->wherePivot('requisicion_id', DB::raw('requisicion.id'));
            },
            'ultimoEstatus.estatus',
            'estatusHistorial.estatus'
        ])
            ->whereHas('ultimoEstatus', function ($query) {
                $query->where('estatus_id', 4);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ordenes_compra.lista', compact('requisiciones'));
    }

    public function generarPDF($requisicionId)
    {
        $requisicion = Requisicion::with(['productos', 'ordenesCompra.productos'])->findOrFail($requisicionId);

        $productosConOrden = [];
        foreach ($requisicion->ordenesCompra as $orden) {
            foreach ($orden->productos as $producto) {
                $productosConOrden[$producto->id] = true;
            }
        }

        $productosFaltantes = [];
        foreach ($requisicion->productos as $producto) {
            if (!isset($productosConOrden[$producto->id])) {
                $productosFaltantes[] = $producto->name_produc;
            }
        }

        if (!empty($productosFaltantes)) {
            return back()->with('error', 'Faltan crear órdenes de compra para: ' . implode(', ', $productosFaltantes));
        }

        return redirect()->route('pdf.generar', ['tipo' => 'orden', 'id' => $requisicionId]);
    }

    public function show(string $id)
    {
        $orden = OrdenCompra::with(['proveedor', 'productos', 'requisicion'])->findOrFail($id);
        return view('ordenes_compra.show', compact('orden'));
    }

    public function edit(string $id)
    {
        $orden = OrdenCompra::with(['proveedor', 'productos'])->findOrFail($id);
        $proveedores = Proveedor::all();
        $centros = Centro::all();

        return view('ordenes_compra.edit', compact('orden', 'proveedores', 'centros'));
    }

    public function update(Request $request, string $id)
    {
        // TODO
    }

    public function destroy(string $id)
    {
        // TODO
    }
}
