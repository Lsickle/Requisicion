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

    public function verifyFile($id, Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:10240',
        ]);

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

        // Obtener hash esperado almacenado en la orden
        $expectedHash = $orden->validation_hash;

        // Leer ruta del PDF subido
        $path = $request->file('pdf')->getRealPath();
        $provided = '';

        // Intentar extraer texto con pdftotext si está disponible (mejor para búsquedas fiables)
        $tmpTxt = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'oc_text_' . uniqid() . '.txt';
        $pdftotextCmd = 'pdftotext ' . escapeshellarg($path) . ' ' . escapeshellarg($tmpTxt) . ' 2>&1';
        $txt = '';
        try {
            @exec($pdftotextCmd, $outputLines, $retCode);
            if (file_exists($tmpTxt) && filesize($tmpTxt) > 0) {
                $txt = file_get_contents($tmpTxt);
            }
        } catch (\Throwable $e) {
            $txt = '';
        } finally {
            @unlink($tmpTxt);
        }

        // Función utilitaria para buscar un hash de 64 hex en un texto
        $find64 = function($haystack) {
            if (preg_match('/\b([A-Fa-f0-9]{64})\b/', $haystack, $mm)) {
                return $mm[1];
            }
            return '';
        };

        // 1) Si pdftotext devolvió texto, buscar en ese texto primero
        if (!empty($txt)) {
            if (!empty($expectedHash) && strpos($txt, $expectedHash) !== false) {
                $provided = $expectedHash;
            }
            if (empty($provided)) {
                // buscar patrón marcado
                if (preg_match('/VALIDATION[_\s-]*HASH\s*[:\-]?\s*([A-Fa-f0-9]{64})/i', $txt, $m)) {
                    $provided = $m[1];
                }
            }
            if (empty($provided)) {
                $provided = $find64($txt);
            }
        }

        // 2) Si no se encontró en texto, intentar en el contenido binario como fallback
        if (empty($provided)) {
            $content = @file_get_contents($path) ?: '';

            // 2a) Revisar metadatos HTML (si el PDF conserva <meta name>)
            if (preg_match('/<meta[^>]+name=["\']validation_hash["\'][^>]+content=["\']([A-Fa-f0-9]{32,256})["\']/i', $content, $m)) {
                $provided = $m[1];
            }

            // 2b) Si sigue vacío, buscar el hash esperado literalmente en binario
            if (empty($provided) && !empty($expectedHash) && strpos($content, $expectedHash) !== false) {
                $provided = $expectedHash;
            }

            // 2c) Extraer sólo caracteres hex del contenido y buscar secuencia de 64 hex para evitar fragmentos extraños
            if (empty($provided)) {
                $hexOnly = preg_replace('/[^A-Fa-f0-9]/', '', $content);
                if (preg_match('/([A-Fa-f0-9]{64})/', $hexOnly, $m)) {
                    $provided = $m[1];
                }
            }
        }

        if (empty($provided)) {
            return view('ordenes_compra.verify_form', [
                'orden' => $orden,
                'valid' => false,
                'expected' => $expectedHash,
                'provided' => '',
                'message' => 'No se encontró un hash válido en el PDF subido.'
            ]);
        }

        // Normalizar y sanear antes de comparar
        $providedSan = strtolower(trim($provided));
        $providedSan = preg_replace('/[^a-f0-9]/', '', $providedSan);
        $expectedSan = strtolower(trim($expectedHash ?? ''));

        $valid = false;
        if ($expectedSan !== '' && $providedSan !== '') {
            $valid = hash_equals($expectedSan, $providedSan);
        }

        if (!$valid && !empty($expectedSan)) {
            $message = 'El documento ha sido alterado o no es igual al original.';
        }

        return view('ordenes_compra.verify_form', [
            'orden' => $orden,
            'valid' => $valid,
            'expected' => $expectedHash,
            'provided' => $providedSan,
            'message' => $valid ? null : (empty($expectedHash) ? 'No hay hash almacenado para esta orden.' : null),
        ]);
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

        // esperado: preferir el blob almacenado
        $expectedHash = $orden->getPdfHash() ?? $orden->validation_hash;

        $uploadedPath = $request->file('pdf')->getRealPath();
        $providedHash = hash_file('sha256', $uploadedPath);

        $providedSan = strtolower(trim(preg_replace('/[^a-f0-9]/', '', $providedHash)));
        $expectedSan = strtolower(trim(preg_replace('/[^a-f0-9]/', '', (string)$expectedHash)));

        $valid = false;
        if ($providedSan !== '' && $expectedSan !== '') {
            $valid = hash_equals($expectedSan, $providedSan);
        }

        $message = '';
        if (empty($expectedSan)) {
            $message = 'No hay PDF almacenado para esta orden para comparar.';
        } else if ($valid) {
            $message = 'El archivo coincide con el original (hash SHA256 igual).';
        } else {
            $message = 'El documento ha sido alterado o no es igual al original.';
        }

        return view('ordenes_compra.verify_upload', [
            'valid' => $valid,
            'message' => $message,
            'expected' => $expectedSan,
            'provided' => $providedSan,
            'orden' => $orden,
        ]);
    }
}
