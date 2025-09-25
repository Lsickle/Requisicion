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
        Schema::create('orden_compra_estatus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estatus_id')->constrained('estatus_orden_compra')->onDelete('cascade');
            $table->foreignId('orden_compra_id')->constrained('orden_compras')->onDelete('cascade');
            $table->foreignId('recepcion_id')->nullable()->constrained('recepcion')->nullOnDelete();
            $table->boolean('activo')->default(0);
            $table->dateTime('date_update')->nullable();
            $table->string('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_compra_estatus');
    }
};
