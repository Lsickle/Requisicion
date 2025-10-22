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
        Schema::create('productoxproveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade'); #id del producto
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade'); #id del proveedor
            $table->string('methods_oc', 255)->nullable(); #metodos de pago
            $table->string('plazo_oc', 255)->nullable(); #plazos de pago
            $table->decimal('price_produc', 20, 2); # precio por proveedor
            $table->string('moneda')->nullable(); # moneda del precio
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productoxproveedor');
    }
};
