<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdenCompraController;  // Import orden de compra
use App\Http\Controllers\EstatusRequisicionController;  // Import orden estatus de requisiciones
use App\Http\Controllers\Api\UserController;  // Import usuarios
use App\Http\Controllers\excel\ExcelController;  // exportar excel
use App\Http\Controllers\PDF\PdfController;  // exportar pdf
use App\Http\Controllers\Requisicion\RequisicionController;
use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use App\Models\OrdenCompra;
use App\Http\Controllers\Mailto\MailtoController;



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
Route::view('/requisicion', 'requisiciones.index')->name('requisiciones.index');

Route::resource('requisicion', RequisicionController::class);
// routes/web.php

Route::prefix('requisiciones')->group(function() {
    Route::get('/', [RequisicionController::class, 'index'])->name('requisiciones.index');
    Route::get('/crear', [RequisicionController::class, 'create'])->name('requisiciones.create');
    Route::post('/', [RequisicionController::class, 'store'])->name('requisiciones.store');
});

Route::get('/test-requisicion-creada', function() {
    $requisicion = Requisicion::first(); // O usa factory/faker para crear una
    (new MailtoController())->sendRequisicionCreada($requisicion);
    return "Correo de requisición creada enviado!";
});

// Ruta para probar EstatusRequisicionActualizado
Route::get('/test-estatus-actualizado', function() {
    $requisicion = Requisicion::first();
    $estatus = Estatus_Requisicion::first();
    (new MailtoController())->sendEstatusRequisicionActualizado($requisicion, $estatus);
    return "Correo de estatus actualizado enviado!";
});

// Ruta para probar OrdenCompraCreada
Route::get('/test-orden-compra', function() {
    $orden = OrdenCompra::first();
    (new MailtoController())->sendOrdenCompraCreada($orden);
    return "Correo de orden de compra enviado!";
});