<?php

use App\Http\Controllers\FacturaVentaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reportes\InventarioReporteController;
use App\Http\Controllers\Reportes\StockProximoAgotarseReporteController;
use App\Http\Controllers\Reportes\VentasPeriodoReporteController;
use App\Http\Controllers\Reportes\OtrasSalidasReporteController;
use App\Http\Controllers\Reportes\CreditosInstitucionalesReporteController;
use App\Http\Controllers\Reportes\DevolucionesReporteController;
use App\Http\Controllers\Reportes\ArqueoCajaCierreReporteController;
use App\Http\Controllers\Reportes\ArqueosCajaReporteController;
use App\Http\Controllers\Ventas\CotizacionVoucherController;
use App\Http\Controllers\Ventas\VentaVoucherController;
use App\Http\Controllers\Reportes\ComprasProveedorReporteController;
use App\Http\Controllers\Reportes\PlanillaExportController;
use App\Http\Controllers\Compras\CompraComprobanteController;
use App\Http\Controllers\Reportes\PlanillaPagoReporteController;
use App\Http\Controllers\Reportes\CreditoFacturaReporteController;
use App\Http\Controllers\Reportes\FacturaContadoReporteController;
use App\Http\Controllers\Reportes\InstalacionCamaraFacturaReporteController;
use App\Http\Controllers\Reportes\ServicioTecnicoFacturaReporteController;
use App\Http\Controllers\Reportes\CompraGeneralReporteController;
use App\Http\Controllers\Ventas\CreditoEntregaReciboController;
use App\Http\Controllers\Ventas\ContratoInstalacionCamaraController;
use App\Http\Controllers\Ventas\ServicioTecnicoVoucherController;
use App\Http\Controllers\Creditos\CreditoVoucherController;




Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('main')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');

    Route::livewire('/forgot-password', 'pages::auth.forgot-password')
        ->name('password.request');

    Route::livewire('/reset-password/{token}', 'pages::auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        auth()->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::get('/ventas/factura/{venta}', [FacturaVentaController::class, 'show'])
        ->name('ventas.factura');
});

Route::middleware(['auth', 'cargo: 2'])->group(function () {
    Route::livewire('/usersystem', 'pages::usersystem')->name('usersystem');
    Route::livewire('/trabajadores', 'pages::trabajadores')->name('trabajadores');
    Route::livewire('/compras', 'pages::compras')->name('compras');
    Route::livewire('/planillapago', 'pages::planillapago')->name('planillapago');
    Route::livewire('/creditos', 'pages::creditos')->name('creditos');
    Route::livewire('/mantenimiento', 'pages::mantenimiento')->name('mantenimiento');

    Route::livewire('/acerca', 'pages::acerca')->name('acerca');
    Route::livewire('/proveedores', 'pages::proveedores')->name('proveedores');
    Route::livewire('/salidas', 'pages::otras_salidas')->name('otras_salidas');
    Route::livewire('/ventas/servicio-tecnico', 'pages::ventas.servicio-tecnico')->name('ventas.servicio-tecnico');
    Route::livewire('/ventas/instalacion-camaras', 'pages::ventas.instalacion-camaras')->name('ventas.instalacion-camaras');
    Route::livewire('/productos', 'pages::productos')->name('productos.index');
    Route::livewire('/productos/listado', 'pages::components.productos.listado')->name('productos.listado');
    Route::livewire('/reportes', 'pages::informes')->name('Informes');

    Route::get('/reportes/inventario/pdf', [InventarioReporteController::class, 'pdf'])
        ->name('reportes.inventario.pdf');

    Route::get('/reportes/inventario/excel', [InventarioReporteController::class, 'excel'])
        ->name('reportes.inventario.excel');

    Route::get('/reportes/inventario/word', [InventarioReporteController::class, 'word'])
        ->name('reportes.inventario.word');

    //stock proximo a agotarse
    Route::get('/reportes/stock-proximo-agotarse/pdf', [StockProximoAgotarseReporteController::class, 'pdf'])
        ->name('reportes.stock-proximo-agotarse.pdf');

    Route::get('/reportes/stock-proximo-agotarse/excel', [StockProximoAgotarseReporteController::class, 'excel'])
        ->name('reportes.stock-proximo-agotarse.excel');

    Route::get('/reportes/stock-proximo-agotarse/word', [StockProximoAgotarseReporteController::class, 'word'])
        ->name('reportes.stock-proximo-agotarse.word');

    //ventas por periodo
    Route::get('/reportes/ventas-periodo/pdf', [VentasPeriodoReporteController::class, 'pdf'])
        ->name('reportes.ventas-periodo.pdf');

    Route::get('/reportes/ventas-periodo/excel', [VentasPeriodoReporteController::class, 'excel'])
        ->name('reportes.ventas-periodo.excel');

    Route::get('/reportes/ventas-periodo/word', [VentasPeriodoReporteController::class, 'word'])
        ->name('reportes.ventas-periodo.word');

    //otras salidas
    Route::get('/reportes/otras-salidas/pdf', [OtrasSalidasReporteController::class, 'pdf'])
        ->name('reportes.otras-salidas.pdf');

    Route::get('/reportes/otras-salidas/excel', [OtrasSalidasReporteController::class, 'excel'])
        ->name('reportes.otras-salidas.excel');

    Route::get('/reportes/otras-salidas/word', [OtrasSalidasReporteController::class, 'word'])
        ->name('reportes.otras-salidas.word');

    //creditos institucionales
    Route::get('/reportes/creditos-institucionales/pdf', [CreditosInstitucionalesReporteController::class, 'pdf'])
        ->name('reportes.creditos-institucionales.pdf');

    Route::get('/reportes/creditos-institucionales/excel', [CreditosInstitucionalesReporteController::class, 'excel'])
        ->name('reportes.creditos-institucionales.excel');

    Route::get('/reportes/creditos-institucionales/word', [CreditosInstitucionalesReporteController::class, 'word'])
        ->name('reportes.creditos-institucionales.word');

    //devoluciones
    Route::get('/reportes/devoluciones/pdf', [DevolucionesReporteController::class, 'pdf'])
        ->name('reportes.devoluciones.pdf');

    Route::get('/reportes/devoluciones/excel', [DevolucionesReporteController::class, 'excel'])
        ->name('reportes.devoluciones.excel');

    Route::get('/reportes/devoluciones/word', [DevolucionesReporteController::class, 'word'])
        ->name('reportes.devoluciones.word');

    //arqueos de caja
    Route::get('/reportes/arqueos-caja/pdf', [ArqueosCajaReporteController::class, 'pdf'])
        ->name('reportes.arqueos-caja.pdf');

    Route::get('/reportes/arqueos-caja/excel', [ArqueosCajaReporteController::class, 'excel'])
        ->name('reportes.arqueos-caja.excel');

    Route::get('/reportes/arqueos-caja/word', [ArqueosCajaReporteController::class, 'word'])
        ->name('reportes.arqueos-caja.word');

    //vouchers
    Route::get('/ventas/cotizacion/{key}', [CotizacionVoucherController::class, 'show'])
        ->name('ventas.cotizacion');

    Route::get('/ventas/voucher/{venta}', [VentaVoucherController::class, 'show'])
        ->name('ventas.voucher');

    Route::get('/reportes/compras-proveedor/pdf', [ComprasProveedorReporteController::class, 'pdf'])
        ->name('reportes.compras-proveedor.pdf');

    Route::get('/reportes/compras-proveedor/excel', [ComprasProveedorReporteController::class, 'excel'])
        ->name('reportes.compras-proveedor.excel');

    Route::get('/reportes/compras-proveedor/word', [ComprasProveedorReporteController::class, 'word'])
        ->name('reportes.compras-proveedor.word');

    Route::get('/compras/comprobante/{compra}', [CompraComprobanteController::class, 'show'])
    ->name('compras.comprobante');

    Route::prefix('reportes/planilla-pago')
    ->name('reportes.planilla-pago.')
    ->controller(PlanillaPagoReporteController::class)
    ->group(function () {
        Route::get('/pdf', 'pdf')->name('pdf');
        Route::get('/excel', 'excel')->name('excel');
        Route::get('/word', 'word')->name('word');
    });

    Route::prefix('reportes/credito-factura')
    ->name('reportes.credito-factura.')
    ->controller(CreditoFacturaReporteController::class)
    ->group(function () {
        Route::get('/pdf', 'pdf')->name('pdf');
        Route::get('/excel', 'excel')->name('excel');
        Route::get('/word', 'word')->name('word');
    });

    Route::prefix('reportes/factura-contado')
    ->name('reportes.factura-contado.')
    ->controller(FacturaContadoReporteController::class)
    ->group(function () {
        Route::get('/pdf', 'pdf')->name('pdf');
        Route::get('/excel', 'excel')->name('excel');
        Route::get('/word', 'word')->name('word');
    });

Route::prefix('reportes/servicio-tecnico-factura')
    ->name('reportes.servicio-tecnico-factura.')
    ->controller(ServicioTecnicoFacturaReporteController::class)
    ->group(function () {
        Route::get('/pdf', 'pdf')->name('pdf');
        Route::get('/excel', 'excel')->name('excel');
        Route::get('/word', 'word')->name('word');
    });

Route::prefix('reportes/instalacion-camara-factura')
    ->name('reportes.instalacion-camara-factura.')
    ->controller(InstalacionCamaraFacturaReporteController::class)
    ->group(function () {
        Route::get('/pdf', 'pdf')->name('pdf');
        Route::get('/excel', 'excel')->name('excel');
        Route::get('/word', 'word')->name('word');
    });

    Route::prefix('reportes/compra-general')
    ->name('reportes.compra-general.')
    ->controller(CompraGeneralReporteController::class)
    ->group(function () {
        Route::get('/pdf', 'pdf')->name('pdf');
        Route::get('/excel', 'excel')->name('excel');
        Route::get('/word', 'word')->name('word');
    });

    Route::get('/ventas/credito/entregas/{entrega}/recibo', [CreditoEntregaReciboController::class, 'show'])
        ->whereNumber('entrega')
        ->name('ventas.credito.entrega.recibo');

    Route::get('/ventas/instalacion-camaras/{contrato}/contrato', [ContratoInstalacionCamaraController::class, 'show'])
        ->whereNumber('contrato')
        ->name('ventas.instalacion-camaras.contrato');

    Route::get('/creditos/voucher/{recibo}', [CreditoVoucherController::class, 'show'])
        ->where('recibo', '[A-Za-z0-9\-]+')
        ->name('creditos.voucher');
});

Route::middleware(['auth', 'cargo: 1, 2'])->group(function () {

    Route::livewire('/trabajadores', 'pages::trabajadores')->name('trabajadores');
    Route::livewire('/planillapago', 'pages::planillapago')->name('planillapago');
    Route::livewire('/creditos', 'pages::creditos')->name('creditos');
    Route::livewire('/mantenimiento', 'pages::mantenimiento')->name('mantenimiento');

    Route::livewire('/acerca', 'pages::acerca')->name('acerca');
    Route::livewire('/ventas/servicio-tecnico', 'pages::ventas.servicio-tecnico')->name('ventas.servicio-tecnico');
    Route::livewire('/ventas/instalacion-camaras', 'pages::ventas.instalacion-camaras')->name('ventas.instalacion-camaras');
    Route::livewire('/productos', 'pages::productos')->name('productos.index');
    Route::livewire('/productos/listado', 'pages::components.productos.listado')->name('productos.listado');

    //exportación de comprobante, liquidación y reporte anual de planilla
    Route::get('/planillapago/exportar/comprobante/{planilla}/{formato}', [PlanillaExportController::class, 'comprobante'])
        ->whereNumber('planilla')
        ->where('formato', 'pdf|excel|xlsx|word|docx')
        ->name('planillapago.exportar.comprobante');

    Route::get('/planillapago/exportar/anual/{year}/{formato}', [PlanillaExportController::class, 'anual'])
        ->whereNumber('year')
        ->where('formato', 'pdf|excel|xlsx|word|docx')
        ->name('planillapago.exportar.anual');

    Route::get('/ventas/servicio-tecnico/{servicio}/voucher', [ServicioTecnicoVoucherController::class, 'show'])
        ->whereNumber('servicio')
        ->name('ventas.servicio-tecnico.voucher');
});

Route::middleware(['auth', 'cargo: 1,2,3'])->group(function () {

    Route::livewire('/main', 'pages::main')->name('main');
    Route::livewire('/ventas/facturacion', 'pages::ventas.facturacion')->name('ventas.facturacion');
    Route::livewire('/ventas/servicio-tecnico', 'pages::ventas.servicio-tecnico')->name('ventas.servicio-tecnico');
    Route::livewire('/ventas/instalacion-camaras', 'pages::ventas.instalacion-camaras')->name('ventas.instalacion-camaras');
    Route::livewire('/clientes', 'pages::clientes')->name('clientes');
    Route::livewire('/devoluciones', 'pages::devoluciones')->name('devoluciones');
    Route::livewire('/arqueodecaja', 'pages::arqueodecaja')->name('arqueodecaja');

    Route::get('/ventas/cotizacion/{key}', [CotizacionVoucherController::class, 'show'])
        ->name('ventas.cotizacion');

    Route::get('/ventas/voucher/{venta}', [VentaVoucherController::class, 'show'])
        ->name('ventas.voucher');

    Route::get('/reportes/cierre-caja/{arqueo}/pdf', [ArqueoCajaCierreReporteController::class, 'show'])
        ->whereNumber('arqueo')
        ->name('reportes.cierre-caja.pdf');

});
