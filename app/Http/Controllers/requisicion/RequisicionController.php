<?php

namespace App\Http\Controllers\requisicion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Centro;

class RequisicionController extends Controller
{
    public function index()
    {
        // Redirige directamente al formulario de creación
        return redirect()->route('requisiciones.create');
    }

    public function create()
    {
        // Productos con su proveedor
        $productos = DB::table('productos')
            ->leftJoin('proveedores', 'productos.proveedor_id', '=', 'proveedores.id')
            ->select('productos.id', 'productos.name_produc', 'productos.proveedor_id')
            ->orderBy('productos.name_produc')
            ->get();

        // Centros usando el método del modelo
        $centros = Centro::obtenerCentros();

        return view('requisiciones.crear', compact('productos', 'centros'));
    }

    public function show($id)
    {
        #
    }

    public function store(Request $request)
    {
        // Validación
        $validated = $request->validate([
            'recobrable' => 'required|in:Recobrable,No recobrable',
            'prioridad_requisicion' => 'required|in:baja,media,alta',
            'justify_requisicion' => 'required|string|min:3',
            'amount_requisicion' => 'required|numeric|min:1',

            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.proveedor_id' => 'nullable|exists:proveedores,id',

            // Mantener el CAMPO en plural (coincide con tu Blade)
            'productos.*.centros' => 'required|array|min:1',

            // Usar el nombre real de la TABLA en la regla exists (singular: centro)
            'productos.*.centros.*.id' => 'required|exists:centro,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            // Crear requisición (SIN 'fecha': se usa created_at/updated_at)
            $requisicionId = DB::table('requisicion')->insertGetId([
                'recobrable' => $validated['recobrable'],
                'prioridad_requisicion' => $validated['prioridad_requisicion'],
                'justify_requisicion' => $validated['justify_requisicion'],
                'amount_requisicion' => $validated['amount_requisicion'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Guardar productos y sus centros
            foreach ($validated['productos'] as $producto) {
                // Sumar cantidades por centros
                $cantidadTotal = array_sum(array_column($producto['centros'], 'cantidad'));
                if ($cantidadTotal < 1) {
                    throw ValidationException::withMessages([
                        'productos' => "La cantidad total para el producto {$producto['id']} debe ser mayor a 0.",
                    ]);
                }

                DB::table('producto_requisicion')->insert([
                    'id_producto' => $producto['id'],
                    'id_requisicion' => $requisicionId,
                    'pr_amount' => $cantidadTotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($producto['centros'] as $centro) {
                    DB::table('centro_producto')->insert([
                        'producto_id' => $producto['id'],
                        'centro_id' => $centro['id'],
                        'amount' => $centro['cantidad'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('requisiciones.create')
                ->with('success', 'Requisición creada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
