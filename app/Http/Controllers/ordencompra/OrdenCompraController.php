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
use App\Mail\OrdenCompraCreada;
use Illuminate\Support\Facades\Mail;

class OrdenCompraController extends Controller
{
    public function index()
    {
        $ordenes = OrdenCompra::with(['proveedor', 'productos'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ordenes_compra.index', compact('ordenes')); // Esta vista no existe según tu estructura
    }

    public function create()
    {
        $proveedores = Proveedor::all();
        $productos   = Producto::all();
        $centros     = Centro::all();

        return view('ordenes_compra.create', compact('proveedores', 'productos', 'centros'));
    }

    public function createFromRequisicion($requisicionId)
    {
        $requisicion = Requisicion::with(['productos'])->findOrFail($requisicionId);
        $proveedores = Proveedor::all();
        $centros     = Centro::all();
        $productos   = Producto::all(); // Agregado para consistencia

        return view('ordenes_compra.create', compact('requisicion', 'proveedores', 'centros', 'productos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'requisicion_id'              => 'required|exists:requisicion,id',
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

        try {
            DB::beginTransaction();

            $orden = OrdenCompra::create([
                'requisicion_id' => $request->requisicion_id,
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
                    'date_oc'         => $request->date_oc,
                    'methods_oc'      => $request->methods_oc,
                    'plazo_oc'        => $request->plazo_oc,
                    'order_oc'        => $request->order_oc
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

            // Actualizar el estatus de la requisición a "En proceso de compra" (estatus 5)
            DB::table('estatus_requisicion')->insert([
                'requisicion_id' => $request->requisicion_id,
                'estatus_id'     => 5, // En proceso de compra
                'estatus'        => 1,
                'date_update'    => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::commit();

            // Enviar correo electrónico (comentado temporalmente para pruebas)
            // $destinatarios = ['compras@empresa.com', 'proveedor@empresa.com'];
            // Mail::to($destinatarios)->send(new OrdenCompraCreada($orden));

            return redirect()->route('ordenes-compra.lista')->with('success', 'Orden de compra creada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear la orden de compra: ' . $e->getMessage());
        }
    }

    // Método para mostrar la lista de requisiciones aprobadas
    public function listaAprobadas()
    {
        // Obtener requisiciones con estatus 4 (Aprobadas)
        $requisiciones = Requisicion::with([
            'productos',
            'productos.centros' => function ($query) {
                $query->wherePivot('requisicion_id', DB::raw('requisicion.id'));
            },
            'ultimoEstatus.estatus',
            'estatusHistorial.estatus'
        ])
            ->whereHas('ultimoEstatus', function ($query) {
                $query->where('estatus_id', 4); // 4 = Aprobado
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ordenes_compra.lista', compact('requisiciones'));
    }

    // Los siguientes métodos son opcionales ya que no tienes las vistas
    public function show(string $id)
    {
        // Implementación si necesitas ver una orden específica
    }

    public function edit(string $id)
    {
        // Implementación si necesitas editar una orden
    }

    public function update(Request $request, string $id)
    {
        // Implementación si necesitas actualizar una orden
    }

    public function destroy(string $id)
    {
        // Implementación si necesitas eliminar una orden
    }
}
