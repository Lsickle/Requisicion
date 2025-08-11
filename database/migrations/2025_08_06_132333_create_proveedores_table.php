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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('prov_name', 255); #nombre del proveedor
            $table->text('prov_descrip'); #descripcion del proveedor
            $table->string('prov_nit', 255);# NIT del proveedor
            $table->string('prov_name_c', 255); #nombre contacto
            $table->string('prov_phone', 255); #telefono del proveedor
            $table->string('prov_adress', 255); #direccion del proveedor
            $table->string('prov_city', 255); #ciudad del proveedor
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
