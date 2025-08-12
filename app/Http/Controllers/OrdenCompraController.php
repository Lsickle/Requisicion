<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Centro;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class OrdenCompraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ordenes = OrdenCompra::with(['proveedor', 'productos'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ordenes_compra.index', compact('ordenes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $proveedores = Proveedor::all();
        $productos = Producto::all();
        $centros = Centro::all();

        return view('ordenes_compra.create', compact('proveedores', 'productos', 'centros'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'date_oc' => 'required|date',
            'methods_oc' => 'required|string|max:255',
            'plazo_oc' => 'required|string|max:255',
            'order_oc' => 'required|integer|unique:orden_compras,order_oc',
            'observaciones' => 'nullable|string',
            'productos' => 'required|array',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.centros' => 'required|array',
            'productos.*.centros.*.id' => 'required|exists:centro,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            // Crear la orden de compra
            $orden = OrdenCompra::create([
                'proveedor_id' => $request->proveedor_id,
                'date_oc' => $request->date_oc,
                'methods_oc' => $request->methods_oc,
                'plazo_oc' => $request->plazo_oc,
                'order_oc' => $request->order_oc,
                'observaciones' => $request->observaciones,
                'estado' => 'pendiente',
            ]);

            // Asociar productos
            foreach ($request->productos as $productoData) {
                $orden->productos()->attach($productoData['id'], [
                    'po_amount' => $productoData['cantidad'],
                    'precio_unitario' => $productoData['precio'],
                ]);

                // Asociar centros de costo para este producto
                foreach ($productoData['centros'] as $centroData) {
                    DB::table('centro_ordencompra')->insert([
                        'producto_id' => $productoData['id'],
                        'centro_id' => $centroData['id'],
                        'orden_compra_id' => $orden->id,
                        'rc_amount' => $centroData['cantidad'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('ordenes-compra.index')
            ->with('success', 'Orden de compra creada exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $orden = OrdenCompra::with([
            'proveedor',
            'productos',
            'productos.centrosOrdenCompra' => function ($query) use ($id) {
                $query->where('centro_ordencompra.orden_compra_id', $id);
            }
        ])->findOrFail($id);

        return view('ordenes_compra.show', compact('orden'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $orden = OrdenCompra::with([
            'productos',
            'productos.centrosOrdenCompra' => function ($query) use ($id) {
                $query->where('centro_ordencompra.orden_compra_id', $id);
            }
        ])->findOrFail($id);

        $proveedores = Proveedor::all();
        $productos = Producto::all();
        $centros = Centro::all();

        // Obtener las cantidades por centro para cada producto
        $centrosProductos = DB::table('centro_ordencompra')
            ->where('orden_compra_id', $id)
            ->get()
            ->groupBy(['producto_id', 'centro_id']);

        return view('ordenes_compra.edit', compact('orden', 'proveedores', 'productos', 'centros', 'centrosProductos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'date_oc' => 'required|date',
            'methods_oc' => 'required|string|max:255',
            'plazo_oc' => 'required|string|max:255',
            'order_oc' => 'required|integer|unique:orden_compras,order_oc,' . $id,
            'observaciones' => 'nullable|string',
            'productos' => 'required|array',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.centros' => 'required|array',
            'productos.*.centros.*.id' => 'required|exists:centro,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request, $id) {
            $orden = OrdenCompra::findOrFail($id);

            // Actualizar datos básicos de la orden
            $orden->update([
                'proveedor_id' => $request->proveedor_id,
                'date_oc' => $request->date_oc,
                'methods_oc' => $request->methods_oc,
                'plazo_oc' => $request->plazo_oc,
                'order_oc' => $request->order_oc,
                'observaciones' => $request->observaciones,
            ]);

            // Sincronizar productos
            $productosSync = [];
            foreach ($request->productos as $productoData) {
                $productosSync[$productoData['id']] = [
                    'po_amount' => $productoData['cantidad'],
                    'precio_unitario' => $productoData['precio'],
                ];
            }
            $orden->productos()->sync($productosSync);

            // Eliminar y recrear relaciones de centros
            DB::table('centro_ordencompra')->where('orden_compra_id', $orden->id)->delete();

            foreach ($request->productos as $productoData) {
                foreach ($productoData['centros'] as $centroData) {
                    DB::table('centro_ordencompra')->insert([
                        'producto_id' => $productoData['id'],
                        'centro_id' => $centroData['id'],
                        'orden_compra_id' => $orden->id,
                        'rc_amount' => $centroData['cantidad'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('ordenes-compra.show', $id)
            ->with('success', 'Orden de compra actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $orden = OrdenCompra::findOrFail($id);

            // Eliminar relaciones con centros
            DB::table('centro_ordencompra')->where('orden_compra_id', $orden->id)->delete();

            // Eliminar relaciones con productos
            $orden->productos()->detach();

            // Eliminar la orden
            $orden->delete();
        });

        return redirect()->route('ordenes-compra.index')
            ->with('success', 'Orden de compra eliminada exitosamente');
    }

    /**
     * Generar PDF de la orden de compra (corregido)
     */
    public function pdf(OrdenCompra $orden)
    {
        $orden->load([
            'proveedor',
            'productos' => function ($query) {
                $query->withPivot(['po_amount', 'precio_unitario'])
                    ->with(['centrosOrdenCompra' => function ($q) {
                        $q->withPivot('rc_amount');
                    }]);
            }
        ]);

        if (is_string($orden->date_oc)) {
            $orden->date_oc = Carbon::parse($orden->date_oc);
        }

        $subtotal = 0;
        $items = [];

        foreach ($orden->productos as $producto) {
            $cantidad = $producto->pivot->po_amount;
            $precio = $producto->pivot->precio_unitario;
            $totalItem = $cantidad * $precio;

            $centros = [];
            foreach ($producto->centrosOrdenCompra as $centro) {
                $centros[] = $centro->name_centro . ' (' . $centro->pivot->rc_amount . ')';
            }

            $items[] = [
                'nombre' => $producto->name_produc,
                'descripcion' => $producto->description_produc,
                'unidad' => $producto->unit_produc,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'total' => $totalItem,
                'centros' => implode(', ', $centros)
            ];

            $subtotal += $totalItem;
        }

        $data = [
            'orden' => $orden,
            'items' => $items,
            'subtotal' => $subtotal,
            'fecha_actual' => Carbon::now()->format('d/m/Y H:i'),
            'logo' => public_path('images/logo.png')
        ];

        $pdf = PDF::loadView('ordenes_compra.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif'
            ]);

        return $pdf->download("Orden-Compra-{$orden->order_oc}.pdf");
    }
}
