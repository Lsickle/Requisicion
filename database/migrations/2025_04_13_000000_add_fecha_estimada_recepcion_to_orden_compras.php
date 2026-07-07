<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_compras', function (Blueprint $table) {
            $table->date('fecha_estimada_recepcion')->nullable()->after('date_oc');
        });
    }

    public function down(): void
    {
        Schema::table('orden_compras', function (Blueprint $table) {
            $table->dropColumn('fecha_estimada_recepcion');
        });
    }
};
