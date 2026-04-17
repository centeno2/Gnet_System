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
    Route::livewire('/proveedores', 'pages::proveedores')->name('proveedores');

    Route::livewire('/productos', 'pages::productos')->name('productos.index');
    Route::livewire('/productos/listado', 'pages::components.productos.listado')->name('productos.listado');

    Route::livewire('/ventas/facturacion', 'pages::ventas.facturacion')->name('ventas.facturacion');
    Route::livewire('/ventas/servicio-tecnico', 'pages::ventas.servicio-tecnico')->name('ventas.servicio-tecnico');
    Route::livewire('/ventas/instalacion-camaras', 'pages::ventas.instalacion-camaras')->name('ventas.instalacion-camaras');


Route::livewire('/', 'pages::users.index');
Route::livewire('/usersystem', 'pages::usersystem')->name('usersystem');
Route::livewire('/clientes', 'pages::clientes')->name('clientes');
Route::livewire('/proveedores', 'pages::proveedores')->name('proveedores');
Route::livewire('/trabajadores', 'pages::trabajadores')->name('trabajadores');
Route::livewire('/compras', 'pages::compras')->name('compras');
Route::livewire('/planillapago', 'pages::planillapago')->name('planillapago');


Route::livewire('/ventas', 'pages::ventas.index')->name('ventas.index');
Route::livewire('/credito', 'pages::credito.index')->name('credito.index');
Route::livewire('/productos', 'pages::productos.index')->name('productos.index');
Route::livewire('/salidas', 'pages::salidas.index')->name('salidas.index');
Route::livewire('/devoluciones', 'pages::devoluciones.index')->name('devoluciones.index');
Route::livewire('/servicios', 'pages::servicios.index')->name('servicios.index');
Route::livewire('/arqueo', 'pages::arqueo.index')->name('arqueo.index');
Route::livewire('/mantenimiento', 'pages::mantenimiento.index')->name('mantenimiento.index');
Route::livewire('/informes', 'pages::informes.index')->name('informes.index');
Route::livewire('/acerca-de', 'pages::acerca.index')->name('acerca.index');
