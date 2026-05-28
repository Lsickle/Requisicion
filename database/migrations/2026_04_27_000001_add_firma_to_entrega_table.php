<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('entrega', function (Blueprint $table) {
            $table->text('firma_base64')->nullable()->after('reception_user');
            $table->string('firma_nombre')->nullable()->after('firma_base64');
            $table->text('observaciones')->nullable()->after('firma_nombre');
        });
    }

    public function down()
    {
        Schema::table('entrega', function (Blueprint $table) {
            $table->dropColumn(['firma_base64', 'firma_nombre', 'observaciones']);
        });
    }
};