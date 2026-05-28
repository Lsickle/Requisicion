<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('producto_requisicion')) {
            Schema::table('producto_requisicion', function (Blueprint $table) {
                if (! Schema::hasColumn('producto_requisicion', 'unidad')) {
                    $table->string('unidad')->nullable()->after('pr_amount');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('producto_requisicion')) {
            Schema::table('producto_requisicion', function (Blueprint $table) {
                if (Schema::hasColumn('producto_requisicion', 'unidad')) {
                    $table->dropColumn('unidad');
                }
            });
        }
    }
};
