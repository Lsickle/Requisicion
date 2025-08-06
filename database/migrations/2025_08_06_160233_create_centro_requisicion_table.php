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
            $table->foreignId('productoxrequisicion_id')->constrained('productoxrequisicion')->onDelete('cascade'); #id del producto de requisicion
            $table->foreignId('centro_id')->constrained('centro')->onDelete('cascade'); #centro de costos
            $table->decimal('rc_amount'); #cantidad de productos por centro de costos
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
