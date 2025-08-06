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
        Schema::create('cliente', function (Blueprint $table) {
            $table->id();
            $table->string('cli_name', 255);          # Nombre del cliente
            $table->string('cli_nit', 20)->unique();   # NIT del cliente
            $table->text('cli_descrip')->nullable();   # Descripción del cliente
            $table->string('cli_contacto', 255);       # Persona de contacto
            $table->string('cli_telefono', 20);        # Teléfono de contacto
            $table->string('cli_mail', 255)->nullable(); # Correo electrónico
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente');
    }
};
