<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ordencompra_producto', function (Blueprint $table) {
            // Ensanchar precisión para soportar valores grandes
            $table->decimal('trm_oc', 16, 2)->nullable()->change();
            $table->decimal('trm_factura', 16, 2)->nullable()->change();
            $table->decimal('precio_original', 24, 2)->nullable()->change();
            $table->decimal('precio_factura', 24, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordencompra_producto', function (Blueprint $table) {
            // Revertir a una precisión más pequeña (ajuste conservador)
            $table->decimal('trm_oc', 12, 2)->nullable()->change();
            $table->decimal('trm_factura', 12, 2)->nullable()->change();
            $table->decimal('precio_original', 20, 2)->nullable()->change();
            $table->decimal('precio_factura', 20, 2)->nullable()->change();
        });
    }
};
