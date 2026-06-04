<?php

use App\Http\Controllers\FacturaVentaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reportes\InventarioReporteController;
use App\Http\Controllers\Reportes\StockProximoAgotarseReporteController;
use App\Http\Controllers\Reportes\VentasPeriodoReporteController;
use App\Http\Controllers\Reportes\OtrasSalidasReporteController;
use App\Http\Controllers\Reportes\CreditosInstitucionalesReporteController;
use App\Http\Controllers\Reportes\DevolucionesReporteController;
use App\Http\Controllers\Reportes\ArqueosCajaReporteController;

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

});

Route ::middleware (['auth', 'cargo: 1, 2'])->group(function () {

    Route::livewire('/trabajadores', 'pages::trabajadores')->name('trabajadores');
    Route::livewire('/planillapago', 'pages::planillapago')->name('planillapago');
    Route::livewire('/creditos', 'pages::creditos')->name('creditos');
    Route::livewire('/mantenimiento', 'pages::mantenimiento')->name('mantenimiento');

    Route::livewire('/acerca', 'pages::acerca')->name('acerca');
    Route::livewire('/ventas/servicio-tecnico', 'pages::ventas.servicio-tecnico')->name('ventas.servicio-tecnico');
    Route::livewire('/ventas/instalacion-camaras', 'pages::ventas.instalacion-camaras')->name('ventas.instalacion-camaras');
    Route::livewire('/productos', 'pages::productos')->name('productos.index');
    Route::livewire('/productos/listado', 'pages::components.productos.listado')->name('productos.listado');

});
Route::middleware(['auth', 'cargo: 1,2,3'])->group(function () {

    Route::livewire('/main', 'pages::main')->name('main');
    Route::livewire('/ventas/facturacion', 'pages::ventas.facturacion')->name('ventas.facturacion');
    Route::livewire('/ventas/servicio-tecnico', 'pages::ventas.servicio-tecnico')->name('ventas.servicio-tecnico');
    Route::livewire('/ventas/instalacion-camaras', 'pages::ventas.instalacion-camaras')->name('ventas.instalacion-camaras');
    Route::livewire('/clientes', 'pages::clientes')->name('clientes');
    Route::livewire('/devoluciones', 'pages::devoluciones')->name('devoluciones');
    Route::livewire('/arqueodecaja', 'pages::arqueodecaja')->name('arqueodecaja');

});
