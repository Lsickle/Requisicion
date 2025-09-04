<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ordencompra\OrdenCompraController;
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
use App\Http\Controllers\productos\ProductosController;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\AuthSession;
use App\Models\Nuevo_Producto;
use App\Http\Controllers\Proveedores\ProveedoresController;

// Página de login (index.blade.php)
Route::get('/', function () {
    return view('index');
})->name('login');

// Login contra API externo
Route::post('/auth/api-login', [ApiAuthController::class, 'login'])->name('api.login');

// Rutas públicas para estadísticas
Route::get('/estadisticas-requisiciones', [EstatusRequisicionController::class, 'getStats'])
    ->name('requisiciones.estadisticas');

// Rutas protegidas
Route::middleware([AuthSession::class])->group(function () {

    //  RUTAS DE APROBACIÓN DE REQUISICIONES 
    // Panel de aprobación de requisiciones
    Route::get('/requisiciones/aprobacion', [EstatusRequisicionController::class, 'index'])
        ->name('requisiciones.aprobacion')
        ->middleware(CheckPermission::class . ':aprobar requisicion');

    // Actualizar estatus
    Route::post('/requisiciones/{requisicionId}/estatus', [EstatusRequisicionController::class, 'updateStatus'])
        ->name('requisiciones.estatus.update')
        ->middleware(CheckPermission::class . ':aprobar requisicion');

    // Obtener detalles
    Route::get('/requisiciones/{id}/detalles', [EstatusRequisicionController::class, 'getRequisicionDetails'])
        ->name('requisiciones.detalles')
        ->middleware(CheckPermission::class . ':ver requisicion');

    //  RUTAS EXISTENTES DE REQUISICIONES 
    // Vista de menú protegida
    Route::get('/requisiciones/menu', function () {
        return view('requisiciones.menu');
    })->name('requisiciones.menu');

    // Crear requisiciones con permiso
    Route::get('/requisiciones/create', [RequisicionController::class, 'create'])
        ->name('requisiciones.create')
        ->middleware(CheckPermission::class . ':crear requisicion');

    // Solicitar nuevo producto con permiso
    Route::get('/productos/nuevoproducto', [NuevoProductoController::class, 'create'])
        ->name('productos.nuevoproducto')
        ->middleware(CheckPermission::class . ':solicitar producto');

    // Historial de requisiciones
    Route::get('/requisiciones/historial', [RequisicionController::class, 'historial'])
        ->name('requisiciones.historial')
        ->middleware(CheckPermission::class . ':ver requisicion');

    // VER requisición específica 
    Route::get('/requisiciones/{id}', [RequisicionController::class, 'show'])
        ->name('requisiciones.show')
        ->middleware(CheckPermission::class . ':ver requisicion');

    // Generar PDF genérico según tipo y id
    Route::get('/requisiciones/{id}/pdf', [RequisicionController::class, 'pdf'])
        ->name('requisiciones.pdf');


    // STORE de requisiciones 
    Route::post('/requisiciones', [RequisicionController::class, 'store'])
        ->name('requisiciones.store')
        ->middleware(CheckPermission::class . ':crear requisicion');

    // Estatus de la requisición
    Route::get('/requisiciones/{requisicion}/estatus', [EstatusRequisicionController::class, 'show'])
        ->name('requisiciones.estatus')
        ->middleware(CheckPermission::class . ':ver requisicion');

    // Asegúrate de tener estas rutas definidas
    Route::get('/requisiciones/{id}/edit', [RequisicionController::class, 'edit'])
        ->name('requisiciones.edit');

    Route::put('/requisiciones/{id}', [RequisicionController::class, 'update'])
        ->name('requisiciones.update');

    // Rutas para productos
    Route::get('/productos/gestor', [ProductosController::class, 'gestor'])
        ->name('productos.gestor');
    Route::post('/productos', [ProductosController::class, 'store'])
        ->name('productos.store');
    Route::put('/productos/{producto}', [ProductosController::class, 'update'])
        ->name('productos.update');
    Route::delete('/productos/{producto}', [ProductosController::class, 'destroy'])
        ->name('productos.destroy');
    Route::post('/productos/{id}/restore', [ProductosController::class, 'restore'])
        ->name('productos.restore');
    Route::delete('/productos/{id}/force-delete', [ProductosController::class, 'forceDelete'])
        ->name('productos.forceDelete');
    // Ruta para obtener datos de solicitud
    Route::get('/productos/solicitud/{id}', [ProductosController::class, 'getSolicitudData'])
        ->name('productos.solicitud.data');

    // Rutas para proveedores
    Route::post('/proveedores', [ProductosController::class, 'storeProveedor'])
        ->name('proveedores.store');

    // Rutas para solicitud de nuevo producto
    Route::resource('nuevo_producto', NuevoProductoController::class);

    Route::post('nuevo_producto/{id}/restore', [NuevoProductoController::class, 'restore'])
        ->name('nuevo_producto.restore');

    Route::delete('nuevo_producto/{id}/force-delete', [NuevoProductoController::class, 'forceDelete'])
        ->name('nuevo_producto.forceDelete');

    // Para mostrar la lista de requisiciones aprobadas estatus 4
    Route::get('/ordenes_compra/lista-aprobadas', [RequisicionController::class, 'listaAprobadas'])
        ->name('ordenes_compra.lista');

    // Add this route inside the middleware group, before the createFromRequisicion route
    Route::get('/ordenes-compra/create', [OrdenCompraController::class, 'create'])
        ->name('ordenes_compra.create')
        ->middleware([AuthSession::class]);

    Route::get('ordenes-compra/requisicion/{id}/create', [OrdenCompraController::class, 'createFromRequisicion'])
        ->name('ordenes_compra.createFromRequisicion')
        ->middleware([AuthSession::class]);

    // Add the store route as well
    Route::post('ordenes-compra', [OrdenCompraController::class, 'store'])
        ->name('ordenes_compra.store')
        ->middleware([AuthSession::class]);

    Route::get('ordenes-compra/requisicion/{id}/create', [OrdenCompraController::class, 'createFromRequisicion'])
        ->name('ordenes_compra.createFromRequisicion')
        ->middleware([AuthSession::class]);

    Route::get('/', [OrdenCompraController::class, 'index'])
        ->name('ordenes_compra.index');

    Route::get('/{id}', [OrdenCompraController::class, 'show'])
        ->name('ordenes_compra.show');

    Route::get('/{id}/edit', [OrdenCompraController::class, 'edit'])
        ->name('ordenes_compra.edit');

    Route::put('/{id}', [OrdenCompraController::class, 'update'])
        ->name('ordenes_compra.update');

    Route::delete('/{id}', [OrdenCompraController::class, 'destroy'])
        ->name('ordenes_compra.destroy');

    // Generar PDF genérico según tipo y id
    Route::get('/pdf/{tipo}/{id}', [PdfController::class, 'generar'])
        ->where('tipo', 'orden|requisicion|estatus')
        ->name('pdf.generar');

    // Dentro del grupo de rutas de órdenes de compra
    Route::get('/generar-pdf/{requisicionId}', [OrdenCompraController::class, 'generarPDF'])
        ->name('ordenes_compra.generarPDF');
});

Route::resource('nuevo_producto', NuevoProductoController::class);

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


Route::resource('proveedores', ProveedoresController::class);
