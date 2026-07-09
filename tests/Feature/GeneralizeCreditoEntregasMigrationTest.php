<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class GeneralizeCreditoEntregasMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('La extensión pdo_sqlite no está disponible.');
        }

        Schema::create('cliente', function (Blueprint $table) {
            $table->integer('Id_Cliente')->primary();
        });

        Schema::create('venta', function (Blueprint $table) {
            $table->integer('Id_Venta')->primary();
            $table->integer('Id_Cliente')->nullable();
        });

        Schema::create('credito', function (Blueprint $table) {
            $table->integer('Id_Credito')->primary();
            $table->integer('Id_Venta');
        });

        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->integer('Id_Detalle_Venta')->primary();
            $table->integer('Id_Venta');
        });

        Schema::create('entrega_credito', function (Blueprint $table) {
            $table->increments('Id_Entrega_Credito');
            $table->integer('Id_Venta');
            $table->integer('Id_Credito');
        });

        Schema::create('entrega_credito_detalle', function (Blueprint $table) {
            $table->increments('Id_Entrega_Credito_Detalle');
            $table->integer('Id_Entrega_Credito');
            $table->integer('Id_Detalle_Venta');
        });

        DB::table('cliente')->insert(['Id_Cliente' => 10]);
        DB::table('venta')->insert([
            ['Id_Venta' => 100, 'Id_Cliente' => 10],
            ['Id_Venta' => 101, 'Id_Cliente' => 10],
        ]);
        DB::table('credito')->insert([
            ['Id_Credito' => 200, 'Id_Venta' => 100],
            ['Id_Credito' => 201, 'Id_Venta' => 101],
        ]);
        DB::table('detalle_venta')->insert([
            ['Id_Detalle_Venta' => 300, 'Id_Venta' => 100],
            ['Id_Detalle_Venta' => 301, 'Id_Venta' => 101],
        ]);
        DB::table('entrega_credito')->insert([
            'Id_Entrega_Credito' => 400,
            'Id_Venta' => 100,
            'Id_Credito' => 200,
        ]);
    }

    protected function tearDown(): void
    {
        if (extension_loaded('pdo_sqlite')) {
            Schema::disableForeignKeyConstraints();
            Schema::dropIfExists('entrega_credito_detalle');
            Schema::dropIfExists('entrega_credito');
            Schema::dropIfExists('detalle_venta');
            Schema::dropIfExists('credito');
            Schema::dropIfExists('venta');
            Schema::dropIfExists('cliente');
            Schema::enableForeignKeyConstraints();
        }

        parent::tearDown();
    }

    public function test_it_generalizes_a_delivery_from_multiple_invoices(): void
    {
        DB::table('entrega_credito_detalle')->insert([
            ['Id_Entrega_Credito' => 400, 'Id_Detalle_Venta' => 300],
            ['Id_Entrega_Credito' => 400, 'Id_Detalle_Venta' => 301],
        ]);

        $migration = $this->migration();
        $migration->up();

        $entrega = DB::table('entrega_credito')->where('Id_Entrega_Credito', 400)->first();

        $this->assertSame(10, $entrega->Id_Cliente);
        $this->assertNull($entrega->Id_Venta);
        $this->assertNull($entrega->Id_Credito);

        $this->expectException(RuntimeException::class);
        $migration->down();
    }

    public function test_it_can_roll_back_when_all_deliveries_have_one_invoice(): void
    {
        DB::table('entrega_credito_detalle')->insert([
            'Id_Entrega_Credito' => 400,
            'Id_Detalle_Venta' => 300,
        ]);

        $migration = $this->migration();
        $migration->up();
        $migration->down();

        $entrega = DB::table('entrega_credito')->where('Id_Entrega_Credito', 400)->first();

        $this->assertFalse(Schema::hasColumn('entrega_credito', 'Id_Cliente'));
        $this->assertSame(100, $entrega->Id_Venta);
        $this->assertSame(200, $entrega->Id_Credito);
    }

    private function migration()
    {
        return require database_path('migrations/2026_07_09_000004_generalize_credito_entregas_table.php');
    }
}
