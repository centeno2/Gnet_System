<?php

Route::livewire('/', 'pages::users.index');
Route::livewire('/ventas', 'pages::ventas.index')->name('ventas.index');
Route::livewire('/credito', 'pages::credito.index')->name('credito.index');
Route::livewire('/compras', 'pages::compras.index')->name('compras.index');
Route::livewire('/productos', 'pages::productos.index')->name('productos.index');
Route::livewire('/salidas', 'pages::salidas.index')->name('salidas.index');
Route::livewire('/devoluciones', 'pages::devoluciones.index')->name('devoluciones.index');
Route::livewire('/trabajadores', 'pages::trabajadores.index')->name('trabajadores.index');
Route::livewire('/servicios', 'pages::servicios.index')->name('servicios.index');
Route::livewire('/arqueo', 'pages::arqueo.index')->name('arqueo.index');
Route::livewire('/mantenimiento', 'pages::mantenimiento.index')->name('mantenimiento.index');
Route::livewire('/informes', 'pages::informes.index')->name('informes.index');
Route::livewire('/acerca-de', 'pages::acerca.index')->name('acerca.index');
Route::livewire('/devoluciones', 'pages::devoluciones')->name('devoluciones');