<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductoxProveedorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $productos = DB::table('productos')->pluck('id')->toArray();
            $proveedores = DB::table('proveedores')->pluck('id')->toArray();

            if (empty($productos) || empty($proveedores)) {
                Log::warning('ProductoxProveedorSeeder: no hay productos o proveedores para relacionar');
                return;
            }

            $now = now();
            $currencyCodes = [
                'COP','AED','AFN','ALL','AMD','ANG','AOA','ARS','AUD','AWG','AZN','BAM','BBD','BDT','BGN','BHD','BIF','BMD','BND','BOB','BRL','BSD','BTN','BWP','BYN','BZD','CAD','CDF','CHF','CLP','CNY','CRC','CUP','CVE','CZK','DJF','DKK','DOP','DZD','EGP','ERN','ETB','EUR','FJD','FKP','FOK','GBP','GEL','GGP','GHS','GIP','GMD','GNF','GTQ','GYD','HKD','HNL','HRK','HTG','HUF','IDR','ILS','IMP','INR','IQD','IRR','ISK','JEP','JMD','JOD','JPY','KES','KGS','KHR','KID','KMF','KRW','KWD','KYD','KZT','LAK','LBP','LKR','LRD','LSL','LYD','MAD','MDL','MGA','MKD','MMK','MNT','MOP','MRU','MUR','MVR','MWK','MXN','MYR','MZN','NAD','NGN','NIO','NOK','NPR','NZD','OMR','PAB','PEN','PGK','PHP','PKR','PLN','PYG','QAR','RON','RSD','RUB','RWF','SAR','SBD','SCR','SDG','SEK','SGD','SHP','SLE','SLL','SOS','SRD','SSP','STN','SYP','SZL','THB','TJS','TMT','TND','TOP','TRY','TTD','TVD','TWD','TZS','UAH','UGX','USD','UYU','UZS','VES','VND','VUV','WST','XAF','XCD','XCG','XDR','XOF','XPF','YER','ZAR','ZMW','ZWL'
            ];

            $inserts = [];
            foreach ($productos as $p) {
                // asignar aleatoriamente entre 1 y 3 proveedores por producto
                shuffle($proveedores);
                $count = min(3, max(1, rand(1, count($proveedores))));
                $selected = array_slice($proveedores, 0, $count);
                foreach ($selected as $prov) {
                    $inserts[] = [
                        'producto_id' => $p,
                        'proveedor_id' => $prov,
                        'price_produc' => round(rand(1000, 100000) / 100, 2),
                        'moneda' => $currencyCodes[array_rand($currencyCodes)],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (!empty($inserts)) {
                DB::table('productoxproveedor')->insert($inserts);
            }
        } catch (\Throwable $e) {
            Log::error('ProductoxProveedorSeeder error: ' . $e->getMessage());
        }
    }
}
