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
        Schema::create('centro_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisicion_id')->constrained('requisicion')->onDelete('cascade'); #id de la requisicion
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade'); #id del producto
            $table->foreignId('centro_id')->constrained('centro')->onDelete('cascade'); #id del centro de costos
            $table->integer('amount'); #cantidad de productos x centro de costos
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centro_producto');
    }
};
