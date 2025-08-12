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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade'); #id de proveedores
            $table->text('categoria_produc'); #categoria del producto
            $table->string('name_produc', 255); #nombre del producto
            $table->integer('stock_produc'); #stock del producto
            $table->text('description_produc'); #descripcion 
            $table->decimal('price_produc'); #precio del producto
            $table->text('unit_produc'); #unidad de medida del producto
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
