<?php

namespace App\Http\Controllers\requisicion;

use App\Http\Controllers\Controller;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\Centro;
use App\Models\Estatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Mail\RequisicionCreada;
use Illuminate\Support\Facades\Mail;

class RequisicionController extends Controller
{
    public function index()
    {
        return redirect()->route('requisiciones.create');
    }

    public function create()
    {
        $productos = Producto::with('proveedor')->orderBy('name_produc')->get();
        $centros = Centro::orderBy('name_centro')->get();
        
        return view('requisiciones.create', compact('productos', 'centros'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        return DB::transaction(function () use ($validated) {
            // Crear la requisición
            $requisicion = Requisicion::create([
                'prioridad_requisicion' => $validated['prioridad_requisicion'],
                'Recobreble' => $validated['recobrable'],
                'detail_requisicion' => $validated['detail_requisicion'],
                'justify_requisicion' => $validated['justify_requisicion'],
                'date_requisicion' => $validated['date_requisicion'],
                'amount_requisicion' => $validated['amount_requisicion'] ?? 0,
            ]);

            // Asociar productos y centros
            $this->attachProductosCentros($requisicion, $validated['productos']);

            // Establecer estatus inicial
            $estatusInicial = Estatus::where('status_name', 'Pendiente')->first();
            $requisicion->estatus()->attach($estatusInicial->id, [
                'date_update' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Enviar correo electrónico
            $destinatarios = ['compras@empresa.com', 'finanzas@empresa.com'];
            Mail::to($destinatarios)->send(new RequisicionCreada($requisicion));

            return redirect()->route('requisiciones.create')
                ->with('success', 'Requisición creada exitosamente');
        });
    }

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

    private function attachProductosCentros(Requisicion $requisicion, array $productos)
    {
        foreach ($productos as $productoData) {
            $producto = Producto::findOrFail($productoData['id']);

            // Verificar stock
            if ($productoData['cantidad'] > $producto->stock_produc) {
                throw ValidationException::withMessages([
                    "productos.{$productoData['id']}.cantidad" => 
                        "La cantidad supera el stock disponible para {$producto->name_produc}"
                ]);
            }

            // Asociar producto a la requisición
            $requisicion->productos()->attach($producto->id, [
                'pr_amount' => $productoData['cantidad'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Asociar centros de costo
            foreach ($productoData['centros'] as $centroData) {
                $requisicion->centros()->attach($centroData['id'], [
                    'rc_amount' => $centroData['cantidad'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}