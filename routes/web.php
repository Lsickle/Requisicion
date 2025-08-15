<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequisicionController;  // Import requisiciones
use App\Http\Controllers\OrdenCompraController;  // Import orden de compra
use App\Http\Controllers\EstatusRequisicionController;  // Import orden estatus de requisiciones
use App\Http\Controllers\Api\UserController;  // Import usuarios
use App\Http\Controllers\excel\ExcelController;  // exportar excel
use App\Http\Controllers\PDF\PdfController;  // exportar pdf



Route::get('/', function () {
    return view('welcome');
});

// Generar PDF genérico según tipo y id
Route::get('/pdf/{tipo}/{id}', [PdfController::class, 'generar'])
    ->where('tipo', 'orden|requisicion|estatus')
    ->name('pdf.generar');

// Reportes excel
Route::prefix('exportar')->group(function () {
    Route::get('/productos', [ExcelController::class, 'export'])->name('export.productos')->defaults('type', 'productos');
    Route::get('/ordenes-compra', [ExcelController::class, 'export'])->name('export.ordenes-compra')->defaults('type', 'ordenes-compra');
    Route::get('/requisiciones', [ExcelController::class, 'export'])->name('export.requisiciones')->defaults('type', 'requisiciones');
    Route::get('/estatus-requisicion', [ExcelController::class, 'export'])->name('export.estatus-requisicion')->defaults('type', 'estatus-requisicion');
});

Route::view('/index', 'index')->name('index');
Route::view('/requisicion', 'requisiciones.crear')->name('requisiciones.crear');

