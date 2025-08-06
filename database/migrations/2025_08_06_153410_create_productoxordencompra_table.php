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
        Schema::create('productoxordencompra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_productoxrequisicion')->constrained('productoxrequisicion')->onDelete('cascade'); #id del producto de requisicion
            $table->foreignId('id_orden_compra')->constrained('orden_compra')->onDelete('cascade'); #id de la orden de compra
            $table->foreignId('id_proveedor')->constrained('proveedores')->onDelete('cascade'); #id del proveedor
            $table->decimal('po_amount'); #cantidad de productos por orden de compra
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productoxordencompra');
    }
};
