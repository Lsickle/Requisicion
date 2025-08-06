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
        Schema::create('estatusxrequisicion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_estatus')->constrained('estatus')->onDelete('cascade'); #id del estatus
            $table->foreignId('id_requisicion')->constrained('requisicion')->onDelete('cascade'); #id de la requisicion
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estatusxrequisicion');
    }
};
