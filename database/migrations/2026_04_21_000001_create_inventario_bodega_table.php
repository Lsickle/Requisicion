<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_bodega', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bodega_id')->constrained('centro')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->integer('cantidad')->default(0);
            $table->timestamps();

            $table->unique(['bodega_id', 'producto_id']);
            $table->index('bodega_id');
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_bodega');
    }
};