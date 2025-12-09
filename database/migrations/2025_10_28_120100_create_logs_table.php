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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 100)->default('ordencompra_producto');
            $table->unsignedBigInteger('ordencompra_producto_id');
            $table->string('user_name')->nullable();
            $table->string('field_name', 100);
            $table->text('new_value')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ordencompra_producto_id']);
            $table->foreign('ordencompra_producto_id')
                ->references('id')
                ->on('ordencompra_producto')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
