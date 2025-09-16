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
            $table->foreignId('orden_compras_id')->nullable()->constrained('orden_compras')->onDelete('set null'); #id de la orden de compra (nullable)
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->onDelete('cascade'); #id de proveedores (nullable)
            $table->integer('total')->nullable(); #cantidad de la orden de compra
            $table->string('stock_e')->nullable(); #salido de stock
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
