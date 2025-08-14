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
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('orden_compra_id')->constrained('orden_compras')->onDelete('cascade');
            $table->integer('po_amount');  # Cantidad de productos en la orden
            $table->decimal('precio_unitario');  # Precio de la orden
            $table->text('observaciones')->nullable(); #observaciones por orden de compra
            $table->date('date_oc')->nullable(); #fecha de orden de compra
            $table->string('methods_oc', 255)->nullable(); #metodos de pago
            $table->string('plazo_oc', 255); #plazos de pago
            $table->string('order_oc'); #numero de orden
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
