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
use App\Http\Controllers\dashboard\DashboardController;
use App\Http\Controllers\EntregasController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ordencompra\OrdenCompraVerifyController;

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

    // Ver todas las requisiciones (permiso: total requisiciones)
    Route::get('/requisiciones/todas', [RequisicionController::class, 'todas'])
        ->name('requisiciones.todas')
        ->middleware(CheckPermission::class . ':total requisiciones');

    // VER requisición específica 
    Route::get('/requisiciones/{id}', [RequisicionController::class, 'show'])
        ->whereNumber('id')
        ->name('requisiciones.show')
        ->middleware(CheckPermission::class . ':ver requisicion');

    // Generar PDF genérico según tipo y id
    Route::get('/requisiciones/{id}/pdf', [RequisicionController::class, 'pdf'])
        ->whereNumber('id')
        ->name('requisiciones.pdf');


    // STORE de requisiciones 
    Route::post('/requisiciones', [RequisicionController::class, 'store'])
        ->name('requisiciones.store')
        ->middleware(CheckPermission::class . ':crear requisicion');

    // Estatus de la requisición
    Route::get('/requisiciones/{requisicion}/estatus', [EstatusRequisicionController::class, 'show'])
        ->whereNumber('requisicion')
        ->name('requisiciones.estatus')
        ->middleware(CheckPermission::class . ':ver requisicion|total requisiciones');

    // Asegúrate de tener estas rutas definidas
    Route::get('/requisiciones/{id}/edit', [RequisicionController::class, 'edit'])
        ->whereNumber('id')
        ->name('requisiciones.edit');

    Route::put('/requisiciones/{id}', [RequisicionController::class, 'update'])
        ->whereNumber('id')
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

    // Historial de órdenes de compra
    Route::get('/ordenes_compra/historial', [OrdenCompraController::class, 'historial'])
        ->name('ordenes_compra.historial')
        ->middleware(CheckPermission::class . ':ver oc');

    // Para mostrar la lista de requisiciones aprobadas estatus 4
    Route::get('/ordenes_compra/lista-aprobadas', [RequisicionController::class, 'listaAprobadas'])
        ->name('ordenes_compra.lista');

    Route::get('ordenes-compra/requisicion/{id}/create', [OrdenCompraController::class, 'createFromRequisicion'])
        ->name('ordenes_compra.createFromRequisicion')
        ->middleware([AuthSession::class]);

    Route::get('ordenes-compra/requisicion/{id}/create', [OrdenCompraController::class, 'createFromRequisicion'])
        ->name('ordenes_compra.createFromRequisicion')
        ->middleware([AuthSession::class]);

    // Generar PDF genérico según tipo y id
    Route::get('/pdf/{tipo}/{id}', [PdfController::class, 'generar'])
        ->where('tipo', 'orden|requisicion')
        ->name('pdf.generar');

    // Dentro del grupo de rutas de órdenes de compra
    Route::get('/generar-pdf/{requisicionId}', [OrdenCompraController::class, 'generarPDF'])
        ->name('ordenes_compra.generarPDF');

    // Rutas para cancelar y reenviar requisiciones
    Route::post('/requisiciones/{id}/cancelar', [RequisicionController::class, 'cancelar'])
        ->whereNumber('id')
        ->name('requisiciones.cancelar');

    Route::post('/requisiciones/{id}/reenviar', [RequisicionController::class, 'reenviar'])
        ->whereNumber('id')
        ->name('requisiciones.reenviar');

    Route::get('ordenes_compra/create', [OrdenCompraController::class, 'create'])
        ->name('ordenes_compra.create');

    Route::post('ordenes_compra', [OrdenCompraController::class, 'store'])
        ->name('ordenes_compra.store');

    Route::get('ordenes_compra/{id}/edit', [OrdenCompraController::class, 'edit'])
        ->name('ordenes_compra.edit');

    Route::put('ordenes_compra/{id}', [OrdenCompraController::class, 'update'])
        ->name('ordenes_compra.update');

    Route::delete('ordenes_compra/{id}', [OrdenCompraController::class, 'destroy'])
        ->name('ordenes_compra.destroy');

    // Ruta para anular órdenes de compra (usando POST en lugar de DELETE)
    Route::post('ordenes_compra/{id}/anular', [OrdenCompraController::class, 'anular'])
        ->name('ordenes_compra.anular');

    // Ruta para descargar ZIP de órdenes
    Route::get('ordenes_compra/{requisicionId}/download-zip', [OrdenCompraController::class, 'downloadZip'])
        ->name('ordenes_compra.downloadZip');

    // Ruta para mostrar la vista de distribución de proveedores (acepta requisicion_id por query)
    Route::get('ordenes_compra/distribucion_proveedores', [OrdenCompraController::class, 'vistaDistribucionProveedores'])
        ->name('ordenes_compra.distribucionProveedores');

    // Ruta alternativa que acepta el id en la URL (sin nombre para no duplicar nombres de ruta)
    Route::get('ordenes_compra/{requisicion_id}/distribucion_proveedores', [OrdenCompraController::class, 'vistaDistribucionProveedores']);

    // Ruta para mostrar orden específica
    Route::get('ordenes_compra/{id}', [OrdenCompraController::class, 'show'])
        ->name('ordenes_compra.show');

    // Ruta para terminar una orden (por id) — closure para evitar dependencia del método del controlador
    Route::post('ordenes_compra/terminar/{id}', function (\Illuminate\Http\Request $request, $id) {
        try {
            \Illuminate\Support\Facades\DB::table('orden_compra_estatus')
                ->where('orden_compra_id', $id)
                ->where('activo', 1)
                ->update(['activo' => 0, 'updated_at' => now()]);

            $terminado = \Illuminate\Support\Facades\DB::table('estatus_orden_compra')->where('id', 3)->first()
                ?? \Illuminate\Support\Facades\DB::table('estatus_orden_compra')->first();

            \Illuminate\Support\Facades\DB::table('orden_compra_estatus')->insert([
                'estatus_id' => $terminado->id ?? 3,
                'orden_compra_id' => $id,
                'recepcion_id' => null,
                'activo' => 1,
                'date_update' => now(),
                'user_id' => session('user.id') ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    })->name('ordenes_compra.terminar');

    // Ruta para exportar PDF individual
    Route::get('ordenes_compra/{id}/pdf', [OrdenCompraController::class, 'exportPDF'])
        ->name('ordenes_compra.pdf');

    // Ruta para distribuir productos entre proveedores
    Route::post('ordenes_compra/distribuir-proveedores', [OrdenCompraController::class, 'distribuirProveedores'])
        ->name('ordenes_compra.distribuirProveedores');

    // Ruta para deshacer distribución de proveedores (soft delete de líneas distribuidas)
    Route::post('ordenes_compra/undo-distribucion', [OrdenCompraController::class, 'undoDistribucion'])
        ->name('ordenes_compra.undoDistribucion');

    // Descargar PDF único o ZIP según cantidad de órdenes
    Route::get('ordenes_compra/{requisicionId}/download', [OrdenCompraController::class, 'download'])
        ->name('ordenes_compra.download');

    // Ruta para recibir entrega parcial de productos
    Route::post('/recepciones/entrega-parcial', [OrdenCompraController::class, 'storeEntregaParcial'])->name('recepciones.storeEntregaParcial');
    Route::post('/recepciones/restaurar-stock', [OrdenCompraController::class, 'restaurarStock'])->name('recepciones.restaurarStock');
    Route::post('/recepciones/confirmar', [OrdenCompraController::class, 'confirmarRecepcion'])->name('recepciones.confirmar');

    // Entregas masivas (guardar en tabla entrega) -> estatus 8, comentario null
    Route::post('/entregas/store-masiva', [EntregasController::class, 'storeMasiva'])->name('entregas.storeMasiva');
    // Confirmar recepción sobre tabla entrega (actualizar cantidad_recibido)
    Route::post('/entregas/confirmar', [EntregasController::class, 'confirmar'])->name('entregas.confirmar');

    // Restaurar stock de líneas con stock_e y volver a estatus 5, comentario null
    Route::post('/ordenes-compra/restaurar-stock', [StockController::class, 'restaurarStock'])->name('ordenes_compra.restaurarStock');

    Route::post('/recepciones/completar-si-listo', [OrdenCompraController::class, 'completarSiListo'])->name('recepciones.completarSiListo');

    // Salida de stock directa a entrega
    Route::post('/recepciones/salida-stock', [\App\Http\Controllers\ordencompra\OrdenCompraController::class, 'storeSalidaStockEnEntrega'])
        ->name('recepciones.storeSalidaStockEnEntrega');

    Route::post('/requisiciones/{requisicion}/entregar', [RequisicionController::class, 'entregarRequisicion'])
        ->name('requisiciones.entregar');

    // Mostrar formulario si alguien hace GET accidentalmente a la ruta de verificación por archivo
    Route::get('/ordenes/verify-file', function() {
        return view('ordenes_compra.verify_upload');
    })->name('ordenes.verify_file_get');

    // Rutas para verificación de OC
    Route::get('/ordenes/verify', [OrdenCompraVerifyController::class, 'showForm'])->name('ordenes.verify_form');
    Route::get('/ordenes/{id}/verify', [OrdenCompraVerifyController::class, 'verify'])->name('ordenes.verify');
    Route::post('/ordenes/verify-file', [OrdenCompraVerifyController::class, 'verifyFilePost'])->name('ordenes.verify_file');
    Route::get('/ordenes/verify-upload', function() { return view('ordenes_compra.verify_upload'); })->name('ordenes.verify_upload');

    // Endpoint para generar/asegurar hashes de validación para órdenes de una requisición
    Route::post('/ordenes/ensure-hashes/{requisicion}', [OrdenCompraVerifyController::class, 'ensureHashesForRequisition'])->name('ordenes_compra.ensure_hashes');

    // Transferir titularidad (solo Admin requisicion)
    Route::get('/requisiciones/transferir', [RequisicionController::class, 'transferIndex'])->name('requisiciones.transferir');
    Route::post('/requisiciones/{id}/transferir', [RequisicionController::class, 'transferir'])->name('requisiciones.transferir.post');
});

// Ruta para confirmar recepciones/entregas en lote desde la vista
Route::post('/recepciones/confirmar-masivo', [RequisicionController::class, 'confirmarRecepcionesMasivo'])->name('recepciones.confirmar.masivo');

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
