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
        Schema::create('ordencompra_producto_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('orden_compras')->onDelete('cascade'); #id de orden de compra
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade')->nullable(); #id de proveedores
            $table->string('cantidad')->nullable(); #cantidad de productos por orden de compra
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordencompra_producto_proveedor');
    }
};
