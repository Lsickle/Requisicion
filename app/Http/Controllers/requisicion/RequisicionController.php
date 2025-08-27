<?php

namespace App\Http\Controllers\requisicion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\Centro;
use App\Models\Estatus;
use App\Models\Estatus_Requisicion;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Jobs\RequisicionCreadaJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RequisicionController extends Controller
{
    public function index()
    {
        $requisiciones = Requisicion::with('productos', 'estatusHistorial.estatus')->get();
        return view('index', compact('requisiciones'));
    }

    public function create()
    {
        $centros = Centro::all();
        $productos = Producto::all();

        return view('requisiciones.create', compact('centros', 'productos'));
    }

    public function menu()
    {
        return view('requisiciones.menu');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Recobrable' => 'required|in:Recobrable,No recobrable',
            'prioridad_requisicion' => 'required|in:baja,media,alta',
            'justify_requisicion' => 'required|string|min:3|max:500',
            'detail_requisicion' => 'required|string|min:3|max:1000',

            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.proveedor_id' => 'nullable|exists:proveedores,id',
            'productos.*.requisicion_amount' => 'required|integer|min:1',

            'productos.*.centros' => 'required|array|min:1',
            'productos.*.centros.*.id' => 'required|exists:centro,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
        ], [
            'productos.required' => 'Agrega al menos un producto.',
            'productos.*.requisicion_amount.required' => 'La cantidad total por producto es obligatoria.',
        ]);

        DB::beginTransaction();

        try {
            $totalRequisicion = 0;
            foreach ($validated['productos'] as $prod) {
                $totalRequisicion += (int)$prod['requisicion_amount'];
                $sumaCentros = array_sum(array_column($prod['centros'], 'cantidad'));
                if ($sumaCentros !== (int)$prod['requisicion_amount']) {
                    throw ValidationException::withMessages([
                        'productos' => "Para el producto {$prod['id']}, la suma de cantidades por centros ({$sumaCentros}) no coincide con la cantidad total indicada ({$prod['requisicion_amount']})."
                    ]);
                }
            }

            // Obtener información del usuario desde la SESIÓN
            $userId = session('user.id');
            $nombreUsuario = session('user.name', 'Usuario Desconocido');
            $emailUsuario = session('user.email', 'email@desconocido.com');
            $operacionUsuario = session('user.operaciones', 'Operación no definida');

            $requisicion = new Requisicion();
            $requisicion->user_id = $userId;
            $requisicion->name_user = $nombreUsuario; // Guardar el nombre
            $requisicion->email_user = $emailUsuario; // Guardar el email
            $requisicion->operacion_user = $operacionUsuario; // Guardar la operación
            $requisicion->Recobrable = $validated['Recobrable'];
            $requisicion->prioridad_requisicion = $validated['prioridad_requisicion'];
            $requisicion->justify_requisicion = $validated['justify_requisicion'];
            $requisicion->detail_requisicion = $validated['detail_requisicion'];
            $requisicion->amount_requisicion = $totalRequisicion;
            $requisicion->save();

            // 🔹 Asignar estatus inicial "Iniciada"
            $estatusInicial = Estatus::where('status_name', 'Iniciada')->first();
            if ($estatusInicial) {
                Estatus_Requisicion::create([
                    'requisicion_id' => $requisicion->id,
                    'estatus_id'     => $estatusInicial->id,
                    'estatus'        => 1, // activo
                    'date_update'    => now(),
                    'comentario'     => 'Requisición iniciada automáticamente.'
                ]);
            }

            foreach ($validated['productos'] as $prod) {
                $cantidadTotalCentros = array_sum(array_column($prod['centros'], 'cantidad'));
                $requisicion->productos()->attach($prod['id'], [
                    'pr_amount' => $cantidadTotalCentros
                ]);

                foreach ($prod['centros'] as $centro) {
                    DB::table('centro_producto')->insert([
                        'producto_id' => $prod['id'],
                        'centro_id'   => $centro['id'],
                        'requisicion_id' => $requisicion->id,
                        'amount'      => $centro['cantidad'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            DB::commit();

            RequisicionCreadaJob::dispatch($requisicion, $nombreUsuario);

            return redirect()->route('requisiciones.menu')->with('success', 'Requisición creada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al crear la requisición: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $requisicion = Requisicion::with([
            'productos',
            'productos.centros',
            'estatusHistorial.estatus'
        ])->findOrFail($id);

        return view('requisiciones.show', compact('requisicion'));
    }

    public function pdf($id)
    {
        $requisicion = Requisicion::with([
            'productos',
            'productos.centros',
            'estatusHistorial.estatus'
        ])->findOrFail($id);

        // Usar los datos guardados en la requisición en lugar de hacer una nueva consulta
        $nombreSolicitante = $requisicion->name_user;
        $emailSolicitante = $requisicion->email_user;
        $operacionSolicitante = $requisicion->operacion_user;

        $pdf = Pdf::loadView('requisiciones.pdf', [
            'requisicion' => $requisicion,
            'nombreSolicitante' => $nombreSolicitante,
            'emailSolicitante' => $emailSolicitante,
            'operacionSolicitante' => $operacionSolicitante,
            'logo' => asset('logo_empresa.png'),
        ])->setPaper('A4', 'portrait');

        return $pdf->download("requisicion_{$requisicion->id}.pdf");
    }

    public function historial()
    {
        $userId = session('user.id');

        $requisiciones = Requisicion::with(['productos', 'estatusHistorial.estatus'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('requisiciones.historial', compact('requisiciones'));
    }

    private function obtenerInformacionUsuario($userId)
    {
        try {
            $apiUrl = env('VPL_CORE') . "/api/users/{$userId}";
            $response = Http::withoutVerifying()->get($apiUrl);

            if ($response->ok()) {
                $userData = $response->json();
                return [
                    'name' => $userData['user']['name'] ?? $userData['user']['email'] ?? 'Usuario Desconocido',
                    'email' => $userData['user']['email'] ?? 'email@desconocido.com',
                    'operaciones' => $userData['user']['operaciones'] ?? 'Operación no definida'
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Error obteniendo información del usuario {$userId}: {$e->getMessage()}");
        }

        return [
            'name' => 'Usuario Desconocido',
            'email' => 'email@desconocido.com',
            'operaciones' => 'Operación no definida'
        ];
    }

    private function obtenerNombreUsuario($userId)
    {
        $userInfo = $this->obtenerInformacionUsuario($userId);
        return $userInfo['name'];
    }
}