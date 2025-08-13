<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequisicionController;  // Import requisiciones
use App\Http\Controllers\OrdenCompraController;  // Import orden de compra
use App\Http\Controllers\EstatusRequisicionController;  // Import orden estatus de requisiciones
use App\Http\Controllers\Api\UserController;  // Import usuarios

Route::get('/', function () {
    return view('welcome');
});

Route::get('requisiciones/{requisicion}/pdf', [RequisicionController::class, 'pdf'])
    ->name('requisiciones.pdf');


Route::get('/ordenes-compra/{orden}/pdf', [OrdenCompraController::class, 'pdf'])
    ->name('ordenes-compra.pdf');

Route::get('/estatus-requisicion/pdf/{id}', [EstatusRequisicionController::class, 'descargarPDF'])
    ->name('estatus-estatus.pdf');

Route::get('/usuarios', [UserController::class, 'getUsuarios'])
    ->middleware('auth:sanctum', 'check.role:admin');
