<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompra;
use App\Models\Recepcion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecepcionController extends Controller
{
    /**
     * Mostrar vista de recepción con búsqueda por order_oc
     */
    public function create()
    {
        return view('recepciones.create');
    }

    /**
     * Buscar OC por order_oc y obtener detalles
     */
    public function buscarOc(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_oc' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Order_oc requerido'], 422);
        }

        $orderOc = $request->input('order_oc');

        $ordenCompra = OrdenCompra::where('order_oc', $orderOc)
            ->with(['ordencompraProductos.producto', 'requisicion'])
            ->first();

        if (!$ordenCompra) {
            return response()->json(['message' => 'Orden de compra no encontrada'], 404);
        }

        // Preparar datos para el formulario
        $datos = [
            'id' => $ordenCompra->id,
            'order_oc' => $ordenCompra->order_oc,
            'requisicion_id' => $ordenCompra->requisicion_id,
            'requisicion_numero' => optional($ordenCompra->requisicion)->id,
            'solicitante_nombre' => optional($ordenCompra->requisicion)->name_user,
            'solicitante_email' => optional($ordenCompra->requisicion)->email_user,
            'solicitante_operacion' => optional($ordenCompra->requisicion)->operacion_user,
            'fecha_creacion' => $ordenCompra->created_at->format('d/m/Y H:i'),
            'productos' => [],
        ];

        foreach ($ordenCompra->ordencompraProductos as $linea) {
            $datos['productos'][] = [
                'id' => $linea->id,
                'producto_id' => $linea->producto_id,
                'nombre_producto' => optional($linea->producto)->name_produc,
                'unidad' => optional($linea->producto)->unit_produc,
                'cantidad_solicitada' => $linea->total,
                'cantidad_recibida' => 0,
            ];
        }

        return response()->json($datos);
    }

    /**
     * Guardar recepción con firma digital
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orden_compra_id' => 'required|integer|exists:orden_compras,id',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|integer|exists:productos,id',
            'productos.*.cantidad_recibida' => 'required|integer|min:0',
            'firma_digital' => 'required|string',
            'solicitante_nombre' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $ordenCompraId = (int) $request->input('orden_compra_id');
            $productos = $request->input('productos');
            $firmaDigital = $request->input('firma_digital');
            $solicitanteNombre = $request->input('solicitante_nombre');

            // Generar nombre único para la firma (guardarla como PNG)
            $firmaFileName = 'firma_' . $ordenCompraId . '_' . time() . '.png';
            $firmaPath = storage_path('app/public/firmas/' . $firmaFileName);

            // Crear directorio si no existe
            if (!file_exists(dirname($firmaPath))) {
                mkdir(dirname($firmaPath), 0755, true);
            }

            // Decodificar y guardar la imagen de firma
            $data = str_replace('data:image/png;base64,', '', $firmaDigital);
            file_put_contents($firmaPath, base64_decode($data));

            // Guardar recepciones en la base de datos
            $recepciones = [];
            foreach ($productos as $prod) {
                $recepcion = Recepcion::create([
                    'orden_compra_id' => $ordenCompraId,
                    'producto_id' => (int) $prod['producto_id'],
                    'cantidad' => (int) $prod['cantidad_solicitada'] ?? 0,
                    'cantidad_recibido' => (int) $prod['cantidad_recibida'],
                    'reception_user' => $solicitanteNombre,
                    'fecha' => now()->toDateString(),
                    'firma_digital' => $firmaFileName,
                ]);
                $recepciones[] = $recepcion;
            }

            // Cerrar OC automáticamente si está completamente recibida
            $this->cerrarOrdenCompraSiCompleta($ordenCompraId);

            DB::commit();

            return response()->json([
                'message' => 'Recepción guardada correctamente',
                'orden_compra_id' => $ordenCompraId,
                'firma_path' => $firmaFileName,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generar PDF de documento de entrada
     */
    public function generarPdf($orden_compra_id)
    {
        $ordenCompra = OrdenCompra::with([
            'ordencompraProductos.producto',
            'requisicion',
        ])->findOrFail($orden_compra_id);

        $recepciones = Recepcion::where('orden_compra_id', $orden_compra_id)
            ->whereNull('deleted_at')
            ->get();

        if ($recepciones->isEmpty()) {
            abort(404, 'No hay recepciones registradas para esta orden');
        }

        // Obtener la firma (primera recepción tiene la firma)
        $firmaPath = null;
        $solicitante = null;
        if ($recepciones->first()->firma_digital) {
            $firmaPath = storage_path('app/public/firmas/' . $recepciones->first()->firma_digital);
            $solicitante = $recepciones->first()->reception_user;
        }

        $pdf = Pdf::loadView('recepciones.documento-entrada-pdf', [
            'ordenCompra' => $ordenCompra,
            'recepciones' => $recepciones,
            'firmaPath' => $firmaPath,
            'solicitante' => $solicitante,
            'fecha' => now()->format('d/m/Y H:i'),
        ]);

        $nombreArchivo = 'Documento-Entrada-' . $ordenCompra->order_oc . '-' . now()->format('YmdHis') . '.pdf';

        return $pdf->download($nombreArchivo);
    }

    /**
     * Cierra automáticamente una orden de compra cuando todos los productos han sido recibidos.
     */
    private function cerrarOrdenCompraSiCompleta(int $ordenCompraId): void
    {
        try {
            $currentStatus = DB::table('orden_compra_estatus')
                ->where('orden_compra_id', $ordenCompraId)
                ->where('activo', 1)
                ->value('estatus_id');

            if ($currentStatus == 3) {
                return;
            }

            $totOrdered = (int) DB::table('ordencompra_producto')
                ->where('orden_compras_id', $ordenCompraId)
                ->whereNull('deleted_at')
                ->sum('total');

            $totReceived = (int) DB::table('recepcion')
                ->where('orden_compra_id', $ordenCompraId)
                ->whereNull('deleted_at')
                ->sum(DB::raw('COALESCE(cantidad_recibido,0)'));

            if ($totOrdered > 0 && $totReceived >= $totOrdered) {
                DB::table('orden_compra_estatus')
                    ->where('orden_compra_id', $ordenCompraId)
                    ->where('activo', 1)
                    ->update(['activo' => 0, 'updated_at' => now()]);

                $terminado = DB::table('estatus_orden_compra')->where('id', 3)->first()
                    ?? DB::table('estatus_orden_compra')->first();

                DB::table('orden_compra_estatus')->insert([
                    'estatus_id' => $terminado->id ?? 3,
                    'orden_compra_id' => $ordenCompraId,
                    'recepcion_id' => null,
                    'activo' => 1,
                    'date_update' => now(),
                    'user_id' => session('user.id') ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('RecepcionController: error al cerrar OC ' . $ordenCompraId . ': ' . $e->getMessage());
        }
    }
}
