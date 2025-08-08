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
        Schema::create('estatus_requisicion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estatus_id')->constrained('estatus')->onDelete('cascade');  #id del estatus
            $table->foreignId('requisicion_id')->constrained('requisicion')->onDelete('cascade');  #id de la requisicion
            $table->string('estatus')->default(0);
            $table->date('date_update')->nullable;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estatus_requisicion');
    }
};
