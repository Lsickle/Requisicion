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
        Schema::create('centro_requisicion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_requisicion_id')->constrained('producto_requisicion')->onDelete('cascade');  #id de la requisicion
            $table->foreignId('centro_id')->constrained('centro')->onDelete('cascade');  #id del centro
            $table->decimal('rc_amount');  #cantidad de productos por centro
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centro_requisicion');
    }
};
