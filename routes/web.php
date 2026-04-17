<?php

Route::livewire('/', 'pages::users.index');
Route::livewire('/usersystem', 'pages::usersystem')->name('usersystem');
Route::livewire('/clientes', 'pages::clientes')->name('clientes');
Route::livewire('/proveedores', 'pages::proveedores')->name('proveedores');
Route::livewire('/trabajadores', 'pages::trabajadores')->name('trabajadores');
Route::livewire('/compras', 'pages::compras')->name('compras');
Route::livewire('/planillapago', 'pages::planillapago')->name('planillapago');


Route::livewire('/ventas', 'pages::ventas.index')->name('ventas.index');
<<<<<<< HEAD
Route::livewire('/credito', 'pages::creditos')->name('creditos');
Route::livewire('/compras', 'pages::compras.index')->name('compras.index');
=======
Route::livewire('/credito', 'pages::credito.index')->name('credito.index');
>>>>>>> 1a51bde2d51d1cf87fbf138639605d11933be68d
Route::livewire('/productos', 'pages::productos.index')->name('productos.index');
Route::livewire('/salidas', 'pages::otras_salidas')->name('otras_salidas');
Route::livewire('/devoluciones', 'pages::devoluciones.index')->name('devoluciones.index');
Route::livewire('/servicios', 'pages::servicios.index')->name('servicios.index');
Route::livewire('/arqueo', 'pages::arqueo.index')->name('arqueo.index');
Route::livewire('/mantenimiento', 'pages::mantenimiento')->name('mantenimiento');
Route::livewire('/informes', 'pages::informes.index')->name('informes.index');
Route::livewire('/acerca-de', 'pages::acerca.index')->name('acerca.index');
Route::livewire('/devoluciones', 'pages::devoluciones')->name('devoluciones');