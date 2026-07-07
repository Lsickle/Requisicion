<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bodega_origen_id')->constrained('centro')->onDelete('cascade');
            $table->foreignId('bodega_destino_id')->constrained('centro')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->integer('cantidad')->default(1);
            $table->text('observaciones')->nullable();
            $table->string('nombre_origen')->nullable();
            $table->string('cedula_origen')->nullable();
            $table->string('firma_origen')->nullable();
            $table->string('nombre_destino')->nullable();
            $table->string('cedula_destino')->nullable();
            $table->string('firma_destino')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias');
    }
};