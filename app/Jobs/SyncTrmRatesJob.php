<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncTrmRatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    public function handle(): void
    {
        Log::info('SyncTrmRatesJob start');
        try {
            $url = 'https://open.er-api.com/v6/latest/COP';
            $ctx = stream_context_create(['http'=>['timeout'=>10]]);
            $raw = @file_get_contents($url, false, $ctx);
            if (!$raw) { throw new \Exception('No response'); }
            $json = @json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($json) || ($json['result'] ?? '') !== 'success' || empty($json['rates']) ) {
                throw new \Exception('Invalid payload');
            }

            $base = (string)($json['base_code'] ?? 'COP');
            $quoteDate = null;
            // Preferir fecha UTC del payload; fallback a hoy
            if (!empty($json['time_last_update_utc'])) {
                try { $quoteDate = (new \DateTime($json['time_last_update_utc']))->format('Y-m-d'); } catch (\Throwable $e) { $quoteDate = date('Y-m-d'); }
            } else { $quoteDate = date('Y-m-d'); }

            $now = now();
            $rows = [];
            foreach ($json['rates'] as $code => $rate) {
                if (!is_string($code) || !is_numeric($rate)) continue;
                $code = strtoupper(substr($code, 0, 3));
                $rows[] = [
                    'moneda'    => $code,
                    'price'     => (float)$rate,
                    'update_date'=> $quoteDate,
                    'created_at'=> $now,
                    'updated_at'=> $now,
                ];
            }

            Log::info('SyncTrmRatesJob fetched '.count($rows).' rates for base '.$base.' date '.$quoteDate);

            if (!empty($rows)) {
                // Intentar guardar en DB; si falla, fallback a archivo JSON en storage/app
                try {
                    foreach ($rows as $r) {
                        DB::table('trm')->updateOrInsert(
                            ['moneda' => $r['moneda'], 'update_date' => $r['update_date']],
                            ['price' => $r['price'], 'updated_at' => $r['updated_at'], 'created_at' => $r['created_at']]
                        );
                    }
                    Log::info('SyncTrmRatesJob saved rates to DB');
                } catch (\Throwable $e) {
                    Log::warning('DB save failed in SyncTrmRatesJob: '.$e->getMessage());

                    // Preparar payload de fallback
                    $payload = [
                        'fetched_at' => (string)$now,
                        'base' => $base,
                        'update_date' => $quoteDate,
                        'rates' => []
                    ];
                    foreach ($rows as $r) { $payload['rates'][$r['moneda']] = $r['price']; }

                    try {
                        $file = storage_path('app/trm_rates_'.$quoteDate.'.json');
                        @mkdir(dirname($file), 0777, true);
                        file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        Log::info('SyncTrmRatesJob wrote fallback file: '.$file);
                    } catch (\Throwable $e2) {
                        Log::error('Failed to write fallback trm file: '.$e2->getMessage());
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('SyncTrmRatesJob failed: '.$e->getMessage());
        }
    }
}
