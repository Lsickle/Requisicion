<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisicion_id')->constrained('requisicion')->onDelete('cascade');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->text('observaciones')->nullable(); #observaciones por orden de compra
            $table->date('date_oc')->nullable(); #fecha de orden de compra
            $table->string('methods_oc', 255)->nullable(); #metodos de pago
            $table->string('plazo_oc', 255)->nullable(); #plazos de pago
            $table->string('order_oc')->nullable(); #numero de orden
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compras');
    }
};