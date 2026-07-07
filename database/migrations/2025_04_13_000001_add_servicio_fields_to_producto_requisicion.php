<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_requisicion', function (Blueprint $table) {
            $table->boolean('es_servicio')->default(false)->after('pr_amount');
            $table->text('observacion_servicio')->nullable()->after('es_servicio');
        });
    }

    public function down(): void
    {
        Schema::table('producto_requisicion', function (Blueprint $table) {
            $table->dropColumn(['es_servicio', 'observacion_servicio']);
        });
    }
};
