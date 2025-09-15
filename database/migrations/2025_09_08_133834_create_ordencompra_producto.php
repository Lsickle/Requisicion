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
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade'); #id del producto
            $table->foreignId('orden_compras_id')->constrained('orden_compras')->onDelete('cascade'); #id de la orden de compra
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade')->nullable(); #id de proveedores
            $table->integer('total')->nullable(); #cantidad de la orden de compra
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
