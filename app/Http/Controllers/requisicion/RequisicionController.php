<?php

namespace App\Http\Controllers\requisicion;

use App\Http\Controllers\Controller;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\Centro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Mail\RequisicionCreada;
use Illuminate\Support\Facades\Mail;

class RequisicionController extends Controller
{
    /**
     * Redirigir al formulario de creación
     */
    public function index()
    {
        return redirect()->route('requisiciones.create');
    }

    /**
     * Mostrar el formulario para crear una nueva requisición
     */
    public function create()
    {
        $productos = Producto::with('proveedor')->get();
        $centros = Centro::all();

        return view('requisiciones.crear', compact('productos', 'centros'));
    }

    /**
     * Guardar una nueva requisición en la base de datos
     */
    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        return DB::transaction(function () use ($validated) {
            $requisicion = Requisicion::create([
                'prioridad_requisicion' => $validated['prioridad_requisicion'],
                'recobrable' => $validated['recobrable'],
                'detail_requisicion' => $validated['detail_requisicion'],
                'justify_requisicion' => $validated['justify_requisicion'],
                'date_requisicion' => $validated['date_requisicion'],
                'amount_requisicion' => $validated['amount_requisicion'] ?? 0,
            ]);

            $this->attachProductosCentros($requisicion, $validated['productos']);

            // Enviar correo electrónico
            $destinatarios = ['compras@empresa.com', 'finanzas@empresa.com'];
            Mail::to($destinatarios)->send(new RequisicionCreada($requisicion));

            return redirect()->route('requisiciones.create')
                ->with('success', 'Requisición creada exitosamente');
        });
    }

    /**
     * Validar los datos de la requisición
     */
    private function validateRequest(Request $request)
    {
        return $request->validate([
            'prioridad_requisicion' => 'required|in:alta,media,baja',
            'recobrable' => 'required|in:Recobrable,No recobrable',
            'detail_requisicion' => 'nullable|string|max:500',
            'justify_requisicion' => 'required|string|max:500',
            'date_requisicion' => 'required|date',
            'amount_requisicion' => 'nullable|integer|min:0',

            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.proveedor_id' => 'required|exists:proveedores,id',

            'productos.*.centros' => 'required|array|min:1',
            'productos.*.centros.*.id' => 'required|exists:centros,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
        ], [
            'prioridad_requisicion.required' => 'La prioridad es obligatoria.',
            'recobrable.required' => 'Debe indicar si es recobrable o no.',
            'productos.required' => 'Debe agregar al menos un producto.',
            'productos.*.id.exists' => 'Uno de los productos seleccionados no existe.',
            'productos.*.cantidad.min' => 'La cantidad del producto debe ser al menos 1.',
            'productos.*.centros' => 'Debe asignar al menos un centro de costo.',
            'productos.*.centros.*.id.exists' => 'Uno de los centros seleccionados no existe.',
        ]);
    }

    /**
     * Guardar productos y sus centros de costo asociados a la requisición
     */
    private function attachProductosCentros(Requisicion $requisicion, array $productos)
    {
        foreach ($productos as $productoData) {
            $producto = Producto::find($productoData['id']);

            if ($productoData['cantidad'] > $producto->stock_produc) {
                throw ValidationException::withMessages([
                    "productos.{$productoData['id']}.cantidad" =>
                        "La cantidad supera el stock disponible para {$producto->name_produc}"
                ]);
            }

            $productoRequisicionId = DB::table('producto_requisicion')->insertGetId([
                'id_producto' => $producto->id,
                'id_requisicion' => $requisicion->id,
                'pr_amount' => $productoData['cantidad'],
                'proveedor_id' => $productoData['proveedor_id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            foreach ($productoData['centros'] as $centroData) {
                DB::table('centro_producto')->insert([
                    'producto_requisicion_id' => $productoRequisicionId,
                    'centro_id' => $centroData['id'],
                    'amount' => $centroData['cantidad'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}