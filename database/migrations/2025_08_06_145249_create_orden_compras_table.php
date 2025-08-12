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
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->date('date_oc');
            $table->string('methods_oc', 255);
            $table->string('plazo_oc', 255);
            $table->integer('order_oc');
            $table->text('observaciones')->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compras');
    }
};