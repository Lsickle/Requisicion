<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordencompra_producto', function (Blueprint $table) {
            if (!Schema::hasColumn('ordencompra_producto', 'requisicion_id')) {
                $table->foreignId('requisicion_id')
                    ->nullable()
                    ->after('orden_compras_id')
                    ->constrained('requisicion')
                    ->onDelete('cascade');
            }
        });

        // Backfill desde encabezados existentes
        DB::statement("UPDATE ordencompra_producto ocp INNER JOIN orden_compras oc ON ocp.orden_compras_id = oc.id SET ocp.requisicion_id = oc.requisicion_id WHERE ocp.orden_compras_id IS NOT NULL AND ocp.requisicion_id IS NULL");
    }

    public function down(): void
    {
        Schema::table('ordencompra_producto', function (Blueprint $table) {
            if (Schema::hasColumn('ordencompra_producto', 'requisicion_id')) {
                try { $table->dropForeign(['requisicion_id']); } catch (\Throwable $e) {}
                $table->dropColumn('requisicion_id');
            }
        });
    }
};
