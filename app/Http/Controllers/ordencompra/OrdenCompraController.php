<?php

namespace App\Http\Controllers\ordencompra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Centro;
use App\Mail\OrdenCompraCreada;
use Illuminate\Support\Facades\Mail;

class OrdenCompraController extends Controller
{
    public function index()
    {
        $ordenes = OrdenCompra::with(['proveedor', 'productos.proveedor'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ordenes_compra.index', compact('ordenes'));
    }

    public function create()
    {
        $proveedores = Proveedor::all();
        $productos   = Producto::all();
        $centros     = Centro::all();

        return view('ordenes_compra.create', compact('proveedores', 'productos', 'centros'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id'                => 'required|exists:proveedores,id',
            'date_oc'                     => 'required|date',
            'methods_oc'                  => 'required|string|max:255',
            'plazo_oc'                    => 'required|string|max:255',
            'order_oc'                    => 'required|integer|unique:orden_compras,order_oc',
            'observaciones'               => 'nullable|string',
            'productos'                   => 'required|array|min:1',
            'productos.*.id'              => 'required|exists:productos,id',
            'productos.*.cantidad'        => 'required|integer|min:1',
            'productos.*.precio'          => 'required|numeric|min:0',
            'productos.*.centros'         => 'required|array|min:1',
            'productos.*.centros.*.id'    => 'required|exists:centros,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $orden = OrdenCompra::create([
                'proveedor_id'  => $request->proveedor_id,
                'date_oc'       => $request->date_oc,
                'methods_oc'    => $request->methods_oc,
                'plazo_oc'      => $request->plazo_oc,
                'order_oc'      => $request->order_oc,
                'observaciones' => $request->observaciones,
                'estado'        => 'pendiente',
            ]);

            foreach ($request->productos as $productoData) {
                $orden->productos()->attach($productoData['id'], [
                    'po_amount'       => $productoData['cantidad'],
                    'precio_unitario' => $productoData['precio'],
                    'observaciones'   => $request->observaciones ?? null
                ]);

                foreach ($productoData['centros'] as $centroData) {
                    DB::table('centro_ordencompra')->insert([
                        'producto_id'      => $productoData['id'],
                        'centro_id'        => $centroData['id'],
                        'orden_compra_id'  => $orden->id,
                        'rc_amount'        => $centroData['cantidad'],
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }

            // Enviar correo electrónico
            $destinatarios = ['compras@empresa.com', 'proveedor@empresa.com']; // Reemplaza con tus destinatarios reales
            Mail::to($destinatarios)->send(new OrdenCompraCreada($orden));

            return redirect()->route('ordenes-compra.index')->with('success', 'Orden de compra creada exitosamente');
        });
    }

    public function show(string $id)
    {
        $orden = OrdenCompra::with([
            'proveedor',
            'productos.proveedor',
            'productos.centrosOrdenCompra' => function ($q) use ($id) {
                $q->where('centro_ordencompra.orden_compra_id', $id);
            }
        ])->findOrFail($id);

        return view('ordenes_compra.show', compact('orden'));
    }

    public function edit(string $id)
    {
        $orden = OrdenCompra::with([
            'productos.proveedor',
            'productos.centrosOrdenCompra' => function ($q) use ($id) {
                $q->where('centro_ordencompra.orden_compra_id', $id);
            }
        ])->findOrFail($id);

        $proveedores = Proveedor::all();
        $productos   = Producto::all();
        $centros     = Centro::all();

        $centrosProductos = DB::table('centro_ordencompra')
            ->where('orden_compra_id', $id)
            ->get()
            ->groupBy(['producto_id', 'centro_id']);

        return view('ordenes_compra.edit', compact('orden', 'proveedores', 'productos', 'centros', 'centrosProductos'));
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'proveedor_id'                    => ['required', 'exists:proveedores,id'],
            'date_oc'                         => ['required', 'date'],
            'methods_oc'                      => ['required', 'string', 'max:255'],
            'plazo_oc'                        => ['required', 'string', 'max:255'],
            'order_oc'                        => ['required', 'integer', 'unique:orden_compras,order_oc,' . $id],
            'observaciones'                   => ['nullable', 'string'],
            'productos'                       => ['required', 'array', 'min:1'],
            'productos.*.id'                  => ['exists:productos,id'],
            'productos.*.cantidad'            => ['integer', 'min:1'],
            'productos.*.precio'              => ['numeric', 'min:0'],
            'productos.*.centros'             => ['array', 'min:1'],
            'productos.*.centros.*.id'        => ['exists:centros,id'],
            'productos.*.centros.*.cantidad'  => ['integer', 'min:1'],
        ], [
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists'   => 'El proveedor seleccionado no existe.',
            'productos.required'    => 'Debe agregar al menos un producto.',
            'productos.min'         => 'Debe agregar al menos un producto.',
            'productos.*.id.exists' => 'Alguno de los productos seleccionados no existe.',
            'productos.*.centros.min' => 'Cada producto debe estar asignado a al menos un centro.',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $orden = OrdenCompra::findOrFail($id);

            $orden->update([
                'proveedor_id'  => $validated['proveedor_id'],
                'date_oc'       => $validated['date_oc'],
                'methods_oc'    => $validated['methods_oc'],
                'plazo_oc'      => $validated['plazo_oc'],
                'order_oc'      => $validated['order_oc'],
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            $productosSync = [];
            foreach ($validated['productos'] as $productoData) {
                $productosSync[$productoData['id']] = [
                    'po_amount'       => $productoData['cantidad'],
                    'precio_unitario' => $productoData['precio'],
                    'observaciones'   => $validated['observaciones'] ?? null
                ];
            }
            $orden->productos()->sync($productosSync);

            DB::table('centro_ordencompra')->where('orden_compra_id', $orden->id)->delete();
            foreach ($validated['productos'] as $productoData) {
                foreach ($productoData['centros'] as $centroData) {
                    DB::table('centro_ordencompra')->insert([
                        'producto_id'     => $productoData['id'],
                        'centro_id'       => $centroData['id'],
                        'orden_compra_id' => $orden->id,
                        'rc_amount'       => $centroData['cantidad'],
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }

            return redirect()
                ->route('ordenes-compra.show', $id)
                ->with('success', 'Orden de compra actualizada exitosamente.');
        });
    }

    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $orden = OrdenCompra::findOrFail($id);

            DB::table('centro_ordencompra')->where('orden_compra_id', $orden->id)->delete();
            $orden->productos()->detach();
            $orden->delete();
        });

        return redirect()->route('ordenes-compra.index')->with('success', 'Orden de compra eliminada exitosamente');
    }
}