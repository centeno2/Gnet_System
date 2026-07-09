<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('credito', 'Formato')) {
            Schema::table('credito', function (Blueprint $table) {
                $table->string('Formato', 255)->nullable()->after('Firma_Recibido');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('credito', 'Formato')) {
            Schema::table('credito', function (Blueprint $table) {
                $table->dropColumn('Formato');
            });
        }
    }
};
