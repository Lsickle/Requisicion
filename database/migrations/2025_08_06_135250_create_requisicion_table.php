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
        Schema::create('requisicion', function (Blueprint $table) {
            $table->id();
            $table->string('user_id'); #id del usuario en la api
            $table->text('justify_requisicion'); #justificacion de la requisicion
            $table->text('detail_requisicion'); #detalles adicionales de la requisicion
            $table->string('prioridad_requisicion', 255); #prioridad de la reuquisicion
            $table->text('amount_requisicion'); #cantidad de productos
            $table->string('Recobrable', 255); #opcion recobrable no recobrable
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisicion');
    }
};
