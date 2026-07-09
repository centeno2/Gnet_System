<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cargo')) {
            return;
        }

        DB::table('cargo')->updateOrInsert(
            ['Id_Cargo' => 5],
            ['Cargo_Asignado' => 'Super usuario']
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('cargo')) {
            return;
        }

        $enUso = Schema::hasTable('trabajador')
            && DB::table('trabajador')->where('Id_Cargo', 5)->exists();

        if (! $enUso) {
            DB::table('cargo')->where('Id_Cargo', 5)->delete();
        }
    }
};
