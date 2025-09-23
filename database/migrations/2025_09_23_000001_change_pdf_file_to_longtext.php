<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar tipo de columna a LONGTEXT para almacenar base64 de forma segura
        if (Schema::hasTable('orden_compras') && Schema::hasColumn('orden_compras', 'pdf_file')) {
            // Usar DB statement para ALTER TABLE (MySQL)
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `orden_compras` MODIFY `pdf_file` LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orden_compras') && Schema::hasColumn('orden_compras', 'pdf_file')) {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `orden_compras` MODIFY `pdf_file` LONGBLOB NULL');
        }
    }
};
