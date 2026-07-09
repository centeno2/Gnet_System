<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cliente', 'RUC')) {
            Schema::table('cliente', function (Blueprint $table) {
                $table->string('RUC', 30)->nullable()->after('Institucion');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cliente', 'RUC')) {
            Schema::table('cliente', function (Blueprint $table) {
                $table->dropColumn('RUC');
            });
        }
    }
};
