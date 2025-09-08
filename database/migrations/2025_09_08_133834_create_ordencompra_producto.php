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
            $table->foreignId('producto_requisicion_id')->constrained('producto_requisicion')->onDelete('cascade'); #id de la pivot entre producto y requiscion
            $table->integer('proveedor_seleccionado'); #proveedor de orden de compra
            $table->text('observaciones')->nullable(); #observaciones por orden de compra
            $table->date('date_oc')->nullable(); #fecha de orden de compra
            $table->string('methods_oc', 255)->nullable(); #metodos de pago
            $table->string('plazo_oc', 255)->nullable(); #plazos de pago
            $table->string('order_oc')->nullable(); #numero de orden
            $table->timestamps();
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
