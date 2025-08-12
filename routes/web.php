<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequisicionController;  // Import correcto

Route::get('requisiciones/{requisicion}/pdf', [RequisicionController::class, 'pdf'])
    ->name('requisiciones.pdf');