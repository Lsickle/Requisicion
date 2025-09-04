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
        $proveedores = Proveedor::all();
        $productos   = Producto::all();
        $centros     = Centro::all();

        $requisicion = null;
        $distribucionCentros = [];
        $proveedoresProductos = collect();
        $proveedorPreseleccionado = null;

        // Generar número de orden único
        $orderNumber = 'OC-' . str_pad(OrdenCompra::max('id') + 1, 6, '0', STR_PAD_LEFT);

        // Si se pasa un ID de requisición, cargarla con la distribución de centros
        if ($request->has('requisicion_id') && $request->requisicion_id != 0) {
            $requisicion = Requisicion::with(['productos', 'productos.proveedor'])->find($request->requisicion_id);

            if ($requisicion) {
                // Obtener proveedores de los productos de la requisición
                $proveedoresProductos = $requisicion->productos->map(function ($producto) {
                    return $producto->proveedor;
                })->filter()->unique('id');

                $proveedorPreseleccionado = $proveedoresProductos->count() === 1
                    ? $proveedoresProductos->first()->id
                    : null;

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
            'proveedores',
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

    // Guardar orden
    public function store(Request $request)
    {
        $validaciones = [
            'proveedor_id'  => 'required|exists:proveedores,id',
            'order_oc'      => 'required|string',
            'date_oc'       => 'required|date',
            'methods_oc'    => 'nullable|string',
            'plazo_oc'      => 'nullable|string',
            'observaciones' => 'nullable|string',
            'productos'     => 'required|array',
        ];

        // Solo validar requisicion_id si no es 0 (creación desde cero)
        if ($request->requisicion_id != 0) {
            $validaciones['requisicion_id'] = 'required|exists:requisicion,id';
        }

        $request->validate($validaciones);

        // Crear la orden de compra
        $ordenCompra = OrdenCompra::create([
            'order_oc'       => $request->order_oc,
            'date_oc'        => $request->date_oc,
            'proveedor_id'   => $request->proveedor_id,
            'methods_oc'     => $request->methods_oc,
            'plazo_oc'       => $request->plazo_oc,
            'observaciones'  => $request->observaciones,
            'requisicion_id' => $request->requisicion_id != 0 ? $request->requisicion_id : null,
        ]);

        // Guardar productos
        foreach ($request->productos as $producto) {
            // Adjuntar producto a la orden de compra
            $ordenCompra->productos()->attach($producto['id'], [
                'po_amount'       => $producto['cantidad'],
                'precio_unitario' => $producto['precio'],
                'order_oc'        => $request->order_oc,
                'date_oc'         => $request->date_oc,
                'methods_oc'      => $request->methods_oc,
                'plazo_oc'        => $request->plazo_oc,
                'observaciones'   => $request->observaciones,
            ]);

            // Guardar distribución por centros si existe (para requisiciones)
            if (isset($producto['centros'])) {
                foreach ($producto['centros'] as $centro) {
                    DB::table('centro_ordencompra')->insert([
                        'producto_id'     => $producto['id'],
                        'centro_id'       => $centro['id'],
                        'rc_amount'       => $centro['cantidad'],
                        'orden_compra_id' => $ordenCompra->id,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
            // Para órdenes creadas desde cero
            elseif (isset($producto['centro_id']) && $producto['centro_id']) {
                DB::table('centro_ordencompra')->insert([
                    'producto_id'     => $producto['id'],
                    'centro_id'       => $producto['centro_id'],
                    'rc_amount'       => $producto['cantidad'],
                    'orden_compra_id' => $ordenCompra->id,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        return redirect()->route('ordenes_compra.index')
            ->with('success', 'Orden de compra creada correctamente.');
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
