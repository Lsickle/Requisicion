<?php

namespace App\Http\Controllers\ordencompra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\DB;

class OrdenCompraVerifyController extends Controller
{
    public function verify($id, Request $request)
    {
        $orden = OrdenCompra::with(['ordencompraProductos.producto'])->find($id);
        if (!$orden || (method_exists($orden, 'trashed') && $orden->trashed())) {
            return view('ordenes_compra.verify_form', [
                'orden' => null,
                'valid' => false,
                'expected' => null,
                'provided' => null,
                'message' => 'La orden de compra no existe o fue eliminada.'
            ]);
        }

        $items = [];
        $porProducto = $orden->ordencompraProductos->groupBy('producto_id');
        foreach ($porProducto as $productoId => $lineas) {
            $producto = optional($lineas->first())->producto;
            $cantidad = (int) $lineas->sum('total');
            if ($producto) {
                $items[] = [
                    'producto_id' => $producto->id,
                    'name_produc' => $producto->name_produc,
                    'po_amount' => $cantidad,
                    'precio_unitario' => (float) ($producto->price_produc ?? 0),
                ];
            }
        }

        $subtotal = 0;
        foreach ($items as $i) { $subtotal += ($i['po_amount'] * $i['precio_unitario']); }

        $secret = config('app.key') ?? env('APP_KEY');
        $hashSource = $orden->id . '|' . ($orden->order_oc ?? '') . '|' . number_format($subtotal, 2) . '|' . ($orden->created_at ? $orden->created_at->toDateTimeString() : '');
        $expectedHash = hash_hmac('sha256', $hashSource, $secret);

        $provided = $request->query('h', '');
        // Normalizar y sanear hashes a minúsculas hex
        $providedSan = strtolower(trim($provided));
        $providedSan = preg_replace('/[^a-f0-9]/', '', $providedSan);
        $expectedSan = strtolower(trim($expectedHash ?? ''));
        $valid = ($providedSan !== '' && $expectedSan !== '') ? hash_equals($expectedSan, $providedSan) : false;

        return view('ordenes_compra.verify_form', [
            'orden' => $orden,
            'valid' => $valid,
            'expected' => $expectedHash,
            'provided' => $providedSan,
            'message' => null,
        ]);
    }

    public function showForm()
    {
        return view('ordenes_compra.verify_form');
    }


    // Handler for form post where id is provided together with the uploaded PDF
    public function verifyFilePost(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'pdf' => 'required|file|mimes:pdf|max:10240',
        ]);

        $id = (int) $request->input('id');
        $orden = OrdenCompra::find($id);
        if (!$orden || (method_exists($orden, 'trashed') && $orden->trashed())) {
            return view('ordenes_compra.verify_upload', [
                'valid' => false,
                'message' => 'La orden de compra no existe o fue eliminada.',
                'expected' => null,
                'provided' => null,
                'orden' => null,
            ]);
        }

        // Obtener todos los hashes históricos de esta orden desde orden_hash
        $expectedHashes = DB::table('orden_hash')
            ->where('orden_compra_id', $orden->id)
            ->orderByDesc('created_at')
            ->pluck('validation_hash')
            ->map(function($h){ return strtolower(trim(preg_replace('/[^a-f0-9]/', '', (string)$h))); })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $uploadedPath = $request->file('pdf')->getRealPath();
        $providedHash = hash_file('sha256', $uploadedPath);
        $providedSan = strtolower(trim(preg_replace('/[^a-f0-9]/', '', $providedHash)));

        $valid = false; $matched = null;
        foreach ($expectedHashes as $h) {
            if ($providedSan !== '' && $h !== '' && hash_equals($h, $providedSan)) {
                $valid = true; $matched = $h; break;
            }
        }

        $latest = $expectedHashes[0] ?? null;
        $outdated = $valid && $matched !== null && $latest !== null && !hash_equals($matched, $latest);

        $message = '';
        if (empty($expectedHashes)) {
            $message = 'No hay hashes registrados para esta orden.';
        } elseif ($valid && $outdated) {
            $message = 'El archivo coincide con un hash válido, pero no es el más reciente. El documento está desactualizado.';
        } elseif ($valid) {
            $message = 'El archivo coincide con el hash de validación registrado.';
        } else {
            $message = 'El documento no coincide con ninguno de los hashes registrados.';
        }

        return view('ordenes_compra.verify_upload', [
            'valid' => $valid,
            'outdated' => $outdated,
            'message' => $message,
            'expected' => $matched ?? ($latest ?? null),
            'provided' => $providedSan,
            'orden' => $orden,
        ]);
    }

    /**
     * Ensure validation_hash exists for all orders in a requisition; generate & save if missing.
     */
    public function ensureHashesForRequisition($requisicionId, Request $request)
    {
        $orders = OrdenCompra::with(['ordencompraProductos.producto'])
            ->where('requisicion_id', $requisicionId)
            ->whereNull('deleted_at')
            ->get();

            
        $secret = config('app.key') ?? env('APP_KEY');
        $result = [];

        foreach ($orders as $orden) {
            // compute subtotal similar to verify()
            $subtotal = 0;
            $porProducto = $orden->ordencompraProductos->groupBy('producto_id');
            foreach ($porProducto as $lineas) {
                $producto = optional($lineas->first())->producto;
                $cantidad = (int) $lineas->sum('total');
                $precio = (float) ($producto->price_produc ?? 0);
                $subtotal += ($cantidad * $precio);
            }
            $hashSource = $orden->id . '|' . ($orden->order_oc ?? '') . '|' . number_format($subtotal, 2) . '|' . ($orden->created_at ? $orden->created_at->toDateTimeString() : '');
            $h = hash_hmac('sha256', $hashSource, $secret);

            if (empty($orden->validation_hash)) {
                $orden->validation_hash = $h;
                try { $orden->save(); } catch (\Throwable $e) { /* ignore save errors per-order */ }
            }

            $result[$orden->id] = $orden->validation_hash ?? $h;
        }

        return response()->json(['ok' => true, 'hashes' => $result]);
    }
}
