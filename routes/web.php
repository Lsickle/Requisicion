<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrdenCompraController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\excel\ExcelController;
use App\Http\Controllers\PDF\PdfController;
use App\Http\Controllers\requisicion\RequisicionController;
use App\Http\Controllers\estatusrequisicion\EstatusRequisicionController;
use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use App\Models\OrdenCompra;
use App\Http\Controllers\Mailto\MailtoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\api\ApiAuthController;
use App\Http\Controllers\nuevo_producto\NuevoProductoController;
use App\Http\Middleware\CheckPermission;
use App\Models\Nuevo_Producto;

// Página de login
Route::get('/', function () {
    return view('index');
})->name('login');

// Generar PDF genérico según tipo y id
Route::get('/pdf/{tipo}/{id}', [PdfController::class, 'generar'])
    ->where('tipo', 'orden|requisicion|estatus')
    ->name('pdf.generar');

// Login contra API externo
Route::post('/auth/api-login', [ApiAuthController::class, 'login'])->name('api.login');

// =======================
// Rutas protegidas
// =======================
Route::middleware(['auth.session'])->group(function () {

    // Vista menú
    Route::get('/requisiciones/menu', function () {
        return view('requisiciones.menu');
    })->name('requisiciones.menu');

    // Crear requisiciones
    Route::get('/requisiciones/create', [RequisicionController::class, 'create'])
        ->name('requisiciones.create')
        ->middleware(CheckPermission::class . ':crear requisicion');

    // Solicitar nuevo producto
    Route::get('/productos/nuevoproducto', [NuevoProductoController::class, 'create'])
        ->name('productos.nuevoproducto')
        ->middleware(CheckPermission::class . ':solicitar producto');

    // Historial
    Route::get('/requisiciones/historial', [RequisicionController::class, 'historial'])
        ->name('requisiciones.historial')
        ->middleware(CheckPermission::class . ':ver requisicion');

    // --- Aprobación de requisiciones (debe ir ANTES de /{id})
    Route::get('/requisiciones/aprobacion', [EstatusRequisicionController::class, 'aprobacion'])
        ->name('requisiciones.aprobacion');

    Route::post('/requisiciones/{requisicion}/actualizar-estatus-aprobacion', [EstatusRequisicionController::class, 'actualizarEstatusAprobacion'])
        ->name('requisiciones.actualizar-estatus-aprobacion');

    // Estatus de la requisición
    Route::get('/requisiciones/{requisicion}/estatus', [EstatusRequisicionController::class, 'show'])
        ->name('requisiciones.estatus')
        ->middleware(CheckPermission::class . ':ver requisicion');

    // Ver requisición específica
    Route::get('/requisiciones/{id}', [RequisicionController::class, 'show'])
        ->name('requisiciones.show')
        ->middleware(CheckPermission::class . ':ver requisicion');

    // PDF de requisición
    Route::get('/requisiciones/pdf/{id}', [RequisicionController::class, 'pdf'])
        ->name('requisiciones.pdf')
        ->middleware(CheckPermission::class . ':ver requisicion');

    // Store requisición
    Route::post('/requisiciones', [RequisicionController::class, 'store'])
        ->name('requisiciones.store')
        ->middleware(CheckPermission::class . ':crear requisicion');

});

// =======================
// Logout
// =======================
Route::post('/logout', [ApiAuthController::class, 'logout'])->name('logout');

// =======================
// Exportaciones Excel
// =======================
Route::prefix('exportar')->group(function () {
    Route::get('/productos', [ExcelController::class, 'export'])->name('export.productos')->defaults('type', 'productos');
    Route::get('/ordenes-compra', [ExcelController::class, 'export'])->name('export.ordenes-compra')->defaults('type', 'ordenes-compra');
    Route::get('/requisiciones', [ExcelController::class, 'export'])->name('export.requisiciones')->defaults('type', 'requisiciones');
    Route::get('/estatus-requisicion', [ExcelController::class, 'export'])->name('export.estatus-requisicion')->defaults('type', 'estatus-requisicion');
});

// =======================
// Rutas de prueba
// =======================
Route::view('/index', 'index')->name('index');

Route::get('/test-requisicion-creada', function () {
    $requisicion = Requisicion::first();
    (new MailtoController())->sendRequisicionCreada($requisicion);
    return "Correo de requisición creada enviado!";
});

Route::get('/test-estatus-actualizado', function () {
    $requisicion = Requisicion::first();
    $estatus = Estatus_Requisicion::first();
    (new MailtoController())->sendEstatusRequisicionActualizado($requisicion, $estatus);
    return "Correo de estatus actualizado enviado!";
});

Route::get('/test-orden-compra', function () {
    $orden = OrdenCompra::first();
    (new MailtoController())->sendOrdenCompraCreada($orden);
    return "Correo de orden de compra enviado!";
});

// =======================
// Rutas para solicitud de nuevo producto
// =======================
Route::resource('nuevo-producto', NuevoProductoController::class);
Route::post('nuevo-producto/{id}/restore', [NuevoProductoController::class, 'restore'])->name('nuevo-producto.restore');
Route::delete('nuevo-producto/{id}/force-delete', [NuevoProductoController::class, 'forceDelete'])->name('nuevo-producto.force-delete');
