<?php

use Illuminate\Support\Facades\Route;
use App\Controllers\Http\RequisicionController;

Route::get('/', function () {
    return view('welcome');
});
