<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/forgot-password', 'pages::auth.forgot-password')->name('password.request');
    Route::livewire('/reset-password/{token}', 'pages::auth.reset-password')->name('password.reset');
});
    Route::livewire('/main', 'pages::main')->name('main');
    Route::livewire('/usersystem', 'pages::usersystem')->name('usersystem');
    Route::livewire('/clientes', 'pages::clientes')->name('clientes');
    
    Route::livewire('/acerca', 'pages::acerca')->name('acerca');
    Route::livewire('/proveedores', 'pages::proveedores')->name('proveedores');

    Route::livewire('/productos', 'pages::productos')->name('productos.index');
    Route::livewire('/productos/listado', 'pages::components.productos.listado')->name('productos.listado');

    Route::livewire('/ventas/facturacion', 'pages::ventas.facturacion')->name('ventas.facturacion');
    Route::livewire('/ventas/servicio-tecnico', 'pages::ventas.servicio-tecnico')->name('ventas.servicio-tecnico');
    Route::livewire('/ventas/instalacion-camaras', 'pages::ventas.instalacion-camaras')->name('ventas.instalacion-camaras');

Route::livewire('/trabajadores', 'pages::trabajadores')->name('trabajadores');
Route::livewire('/compras', 'pages::compras')->name('compras');
Route::livewire('/planillapago', 'pages::planillapago')->name('planillapago');
Route::livewire('/devoluciones', 'pages::devoluciones')->name('devoluciones');
Route::livewire('/creditos', 'pages::creditos')->name('creditos');
Route::livewire('/salidas', 'pages::otras_salidas')->name('otras_salidas');

Route::livewire('/mantenimiento', 'pages::mantenimiento')->name('mantenimiento');


