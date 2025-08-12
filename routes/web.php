<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequisicionController;  // Import requisiciones
use App\Http\Controllers\OrdenCompraController;  // Import orden de compra
use App\Http\Controllers\EstatusRequisicionController;  // Import orden estatus de requisiciones

Route::get('/', function () {
    return view('welcome');
});

Route::get('requisiciones/{requisicion}/pdf', [RequisicionController::class, 'pdf'])
    ->name('requisiciones.pdf');

Route::get('/orden-compras/{orden}/pdf', [OrdenCompraController::class, 'pdf'])
    ->name('orden-compras.pdf');

Route::get('/estatus-requisicion/pdf/{id}', [EstatusRequisicionController::class, 'descargarPDF'])
    ->name('estatus-requisicion.pdf');