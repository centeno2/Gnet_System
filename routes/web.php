<?php

use App\Http\Controllers\FacturaVentaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reportes\InventarioReporteController;

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
