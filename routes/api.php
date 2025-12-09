<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\trm\TrmController;

Route::get('/users-list', function(Request $request){
    // Seleccionar usuarios activos para asignar como nuevo propietario
    $users = DB::table('users')->select('id','name','email')->whereNull('deleted_at')->limit(200)->get();
    return response()->json($users);
});

Route::post('/trm/sync', [TrmController::class, 'sync'])->name('api.trm.sync');
Route::get('/trm/latest', [TrmController::class, 'latest'])->name('api.trm.latest');