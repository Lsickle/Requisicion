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
            $table->boolean('estatus')->default(0);  #estatus de la requisicion activa que trae por defecto 0
            $table->date('date_update')->nullable();  #fecha en la que se realizo el cambio de estatus
            $table->text('comentario')->nullable();  #comentario de la requisicion
            $table->foreignId('entrega_id')->nullable()->constrained('entrega')->nullOnDelete(); #id de la entrega
            $table->timestamps();
            $table->softDeletes();
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
