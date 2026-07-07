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
        Schema::table('recepcion', function (Blueprint $table) {
            if (!Schema::hasColumn('recepcion', 'firma_digital')) {
                $table->string('firma_digital')->nullable()->after('reception_user')->comment('Ruta del archivo de firma digital (PNG)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recepcion', function (Blueprint $table) {
            if (Schema::hasColumn('recepcion', 'firma_digital')) {
                $table->dropColumn('firma_digital');
            }
        });
    }
};
