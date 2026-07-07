<?php

namespace App\Http\Controllers\trm;

use App\Http\Controllers\Controller;
use App\Jobs\SyncTrmRatesJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrmController extends Controller
{
    public function sync(Request $request)
    {
        SyncTrmRatesJob::dispatch();
        return response()->json(['ok' => true]);
    }

    public function latest(Request $request)
    {
        $currency = strtoupper($request->query('currency','COP'));

        // Intentar obtener desde DB
        try {
            $row = DB::table('trm')
                ->where('moneda', $currency)
                ->orderByDesc('update_date')
                ->orderByDesc('id')
                ->first();
            if ($row) return response()->json($row);
        } catch (\Throwable $e) {
            // seguir a fallback
        }

        // Fallback: leer archivo JSON más reciente en storage/app/trm_rates_*.json
        try {
            $files = glob(storage_path('app/trm_rates_*.json'));
            if (!empty($files)) {
                usort($files, function($a,$b){ return filemtime($b) <=> filemtime($a); });
                foreach ($files as $f) {
                    $raw = @file_get_contents($f);
                    if (!$raw) continue;
                    $j = @json_decode($raw, true);
                    if (empty($j) || empty($j['rates'])) continue;
                    if (isset($j['rates'][$currency])) {
                        return response()->json([
                            'moneda' => $currency,
                            'price' => $j['rates'][$currency],
                            'update_date' => $j['update_date'] ?? ($j['fetched_at'] ?? null),
                            'source' => 'file',
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) { /* noop */ }

        return response()->json(['message' => 'No data'], 404);
    }
}
