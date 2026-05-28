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
        Schema::table('requisicion', function (Blueprint $table) {
            if (! Schema::hasColumn('requisicion', 'fecha_estimada_recepcion')) {
                $table->date('fecha_estimada_recepcion')->nullable()->after('detail_requisicion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisicion', function (Blueprint $table) {
            if (Schema::hasColumn('requisicion', 'fecha_estimada_recepcion')) {
                $table->dropColumn('fecha_estimada_recepcion');
            }
        });
    }
};
