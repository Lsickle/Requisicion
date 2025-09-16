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
            // Intentar eliminar la FK usando el helper (nombre generado por Laravel)
            try { $table->dropForeign(['orden_compras_id']); } catch (\Throwable $e) {}
        });

        // Asegurar que la columna permita NULL
        DB::statement('ALTER TABLE `ordencompra_producto` MODIFY `orden_compras_id` BIGINT UNSIGNED NULL');

        // Re-crear la FK con ON DELETE SET NULL
        Schema::table('ordencompra_producto', function (Blueprint $table) {
            try { $table->foreign('orden_compras_id')->references('id')->on('orden_compras')->onDelete('set null'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('ordencompra_producto', function (Blueprint $table) {
            try { $table->dropForeign(['orden_compras_id']); } catch (\Throwable $e) {}
        });
        DB::statement('ALTER TABLE `ordencompra_producto` MODIFY `orden_compras_id` BIGINT UNSIGNED NOT NULL');
        Schema::table('ordencompra_producto', function (Blueprint $table) {
            $table->foreign('orden_compras_id')->references('id')->on('orden_compras')->onDelete('cascade');
        });
    }
};
