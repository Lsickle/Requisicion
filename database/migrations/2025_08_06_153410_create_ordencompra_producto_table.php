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
    Schema::create('ordencompra_producto', function (Blueprint $table) {
        $table->id();
        $table->foreignId('producto_requisicion_id')->constrained('producto_requisicion')->onDelete('cascade');  #id de la requisicion
        $table->foreignId('orden_compra_id')->constrained('orden_compras')->onDelete('cascade');  #id de la orden de compra
        $table->integer('po_amount');  #cantidad de productos por proveedor
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordencompra_producto');
    }
};
