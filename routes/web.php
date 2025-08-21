<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\EstatusRequisicionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\excel\ExcelController;
use App\Http\Controllers\PDF\PdfController;
use App\Http\Controllers\requisicion\RequisicionController;
use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use App\Models\OrdenCompra;
use App\Http\Controllers\Mailto\MailtoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\api\ApiAuthController;
use App\Http\Middleware\CheckPermission;

// Página de login (index.blade.php)
Route::get('/', function () {
    return view('index');
})->name('login');

// Generar PDF genérico según tipo y id
Route::get('/pdf/{tipo}/{id}', [PdfController::class, 'generar'])
    ->where('tipo', 'orden|requisicion|estatus')
    ->name('pdf.generar');  

// Login contra API externo
Route::post('/auth/api-login', [ApiAuthController::class, 'login'])->name('api.login');

// Rutas protegidas
Route::middleware(['auth.session'])->group(function () {
    // Ruta para crear requisiciones (requiere permiso)
    Route::get('/requisiciones/create', function () {
        return view('requisiciones.create');
    })->name('requisiciones.create')->middleware(CheckPermission::class . ':crear requisicion');
    
    // Ruta para ver requisiciones (requiere permiso)
    Route::get('/requisiciones/menu', function () {
        return view('requisiciones.menu');
    })->name('requisiciones.menu')->middleware(CheckPermission::class . ':ver requisicion');
});

// Logout
Route::post('/logout', [ApiAuthController::class, 'logout'])->name('logout');

// Reportes excel
Route::prefix('exportar')->group(function () {
    Route::get('/productos', [ExcelController::class, 'export'])->name('export.productos')->defaults('type', 'productos');
    Route::get('/ordenes-compra', [ExcelController::class, 'export'])->name('export.ordenes-compra')->defaults('type', 'ordenes-compra');
    Route::get('/requisiciones', [ExcelController::class, 'export'])->name('export.requisiciones')->defaults('type', 'requisiciones');
    Route::get('/estatus-requisicion', [ExcelController::class, 'export'])->name('export.estatus-requisicion')->defaults('type', 'estatus-requisicion');
});

Route::view('/index', 'index')->name('index');

Route::resource('requisiciones', RequisicionController::class)->except(['show']);
Route::get('/test-requisicion-creada', function () {
    $requisicion = Requisicion::first(); // O usa factory/faker para crear una
    (new MailtoController())->sendRequisicionCreada($requisicion);
    return "Correo de requisición creada enviado!";
});

// Ruta para probar EstatusRequisicionActualizado
Route::get('/test-estatus-actualizado', function () {
    $requisicion = Requisicion::first();
    $estatus = Estatus_Requisicion::first();
    (new MailtoController())->sendEstatusRequisicionActualizado($requisicion, $estatus);
    return "Correo de estatus actualizado enviado!";
});

// Ruta para probar OrdenCompraCreada
Route::get('/test-orden-compra', function () {
    $orden = OrdenCompra::first();
    (new MailtoController())->sendOrdenCompraCreada($orden);
    return "Correo de orden de compra enviado!";
});