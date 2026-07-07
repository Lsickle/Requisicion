<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_hash', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('orden_compras')->onDelete('cascade');
            $table->text('validation_hash');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_hash');
    }
};