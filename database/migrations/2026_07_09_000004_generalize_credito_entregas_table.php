<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('entrega_credito')) {
            return;
        }

        if (! Schema::hasColumn('entrega_credito', 'Id_Cliente')) {
            Schema::table('entrega_credito', function (Blueprint $table) {
                $table->integer('Id_Cliente')->nullable()->after('Id_Entrega_Credito');
            });
        }

        $this->completarClientesExistentes();

        Schema::table('entrega_credito', function (Blueprint $table) {
            $table->integer('Id_Cliente')->nullable(false)->change();
            $table->integer('Id_Venta')->nullable()->change();
            $table->integer('Id_Credito')->nullable()->change();
            $table->index('Id_Cliente', 'idx_entrega_credito_cliente');
            $table->foreign('Id_Cliente', 'fk_entrega_credito_cliente')
                ->references('Id_Cliente')
                ->on('cliente')
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        $this->normalizarEntregasGenerales();
    }

    public function down(): void
    {
        if (! Schema::hasTable('entrega_credito') || ! Schema::hasColumn('entrega_credito', 'Id_Cliente')) {
            return;
        }

        if ($this->idsEntregasGenerales()->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede revertir esta migración porque ya existen vouchers con varias facturas.'
            );
        }

        $this->completarReferenciasDeUnaFactura();

        Schema::table('entrega_credito', function (Blueprint $table) {
            $table->dropForeign('fk_entrega_credito_cliente');
            $table->dropIndex('idx_entrega_credito_cliente');
            $table->integer('Id_Venta')->nullable(false)->change();
            $table->integer('Id_Credito')->nullable(false)->change();
            $table->dropColumn('Id_Cliente');
        });
    }

    private function completarClientesExistentes(): void
    {
        $entregas = DB::table('entrega_credito as ec')
            ->join('venta as v', 'v.Id_Venta', '=', 'ec.Id_Venta')
            ->whereNull('ec.Id_Cliente')
            ->get([
                'ec.Id_Entrega_Credito',
                'v.Id_Cliente',
            ]);

        foreach ($entregas as $entrega) {
            DB::table('entrega_credito')
                ->where('Id_Entrega_Credito', $entrega->Id_Entrega_Credito)
                ->update(['Id_Cliente' => $entrega->Id_Cliente]);
        }

        if (DB::table('entrega_credito')->whereNull('Id_Cliente')->exists()) {
            throw new RuntimeException('No se pudo identificar el cliente de todos los vouchers existentes.');
        }
    }

    private function normalizarEntregasGenerales(): void
    {
        foreach ($this->idsEntregasGenerales()->chunk(500) as $ids) {
            DB::table('entrega_credito')
                ->whereIn('Id_Entrega_Credito', $ids->all())
                ->update([
                    'Id_Venta' => null,
                    'Id_Credito' => null,
                ]);
        }
    }

    private function completarReferenciasDeUnaFactura(): void
    {
        $entregaIds = DB::table('entrega_credito')
            ->where(function ($query) {
                $query->whereNull('Id_Venta')
                    ->orWhereNull('Id_Credito');
            })
            ->pluck('Id_Entrega_Credito');

        foreach ($entregaIds as $entregaId) {
            $ventaId = DB::table('entrega_credito_detalle as ecd')
                ->join('detalle_venta as dv', 'dv.Id_Detalle_Venta', '=', 'ecd.Id_Detalle_Venta')
                ->where('ecd.Id_Entrega_Credito', $entregaId)
                ->value('dv.Id_Venta');

            $creditoId = $ventaId
                ? DB::table('credito')->where('Id_Venta', $ventaId)->value('Id_Credito')
                : null;

            if (! $ventaId || ! $creditoId) {
                throw new RuntimeException("No se pudo restaurar la factura del voucher #{$entregaId}.");
            }

            DB::table('entrega_credito')
                ->where('Id_Entrega_Credito', $entregaId)
                ->update([
                    'Id_Venta' => $ventaId,
                    'Id_Credito' => $creditoId,
                ]);
        }
    }

    private function idsEntregasGenerales()
    {
        return DB::table('entrega_credito_detalle as ecd')
            ->join('detalle_venta as dv', 'dv.Id_Detalle_Venta', '=', 'ecd.Id_Detalle_Venta')
            ->groupBy('ecd.Id_Entrega_Credito')
            ->havingRaw('COUNT(DISTINCT dv.Id_Venta) > 1')
            ->pluck('ecd.Id_Entrega_Credito');
    }
};
