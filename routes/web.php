<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ordencompra\OrdenCompraController;
use App\Http\Controllers\Api\UserController;
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
use App\Http\Controllers\EntregasController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SalidaStockController;
use App\Http\Controllers\ordencompra\OrdenCompraVerifyController;
use App\Http\Controllers\ordencompra\OrdenCompraExtrasController;
use App\Http\Controllers\centros\CentroController;
use App\Http\Controllers\centros\UserSubcentroController;
use App\Http\Controllers\requisicion\AprobadoresController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    // (Aprobaciones removidas) -- routes para revisión/aprobación eliminadas intencionalmente.

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

    // Nueva ruta para requisición especial
    Route::get('/requisiciones/especial', [RequisicionController::class, 'createEspecial'])
        ->name('requisiciones.especial')
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
    // Lista simple de productos (SKU, nombre, categoría, unidad)
    Route::get('/productos/lista', [ProductosController::class, 'lista'])->name('productos.lista');

    // Rutas para proveedores
    Route::post('/proveedores', [ProductosController::class, 'storeProveedor'])
        ->name('proveedores.store');
    Route::put('/proveedores/{id}', [ProductosController::class, 'updateProveedor'])
        ->name('proveedores.update');
    // Aceptar también POST para actualizar (para fetch con _method=PUT) sin nombre
    Route::post('/proveedores/{id}', [ProductosController::class, 'updateProveedor']);

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

    // Ruta para terminar una orden (por id)
    Route::post('ordenes_compra/terminar/{id}', [OrdenCompraController::class, 'terminar'])->name('ordenes_compra.terminar');

    // Ruta para cancelar un producto de la orden
    Route::post('ordenes_compra/{id}/cancelar-producto', [OrdenCompraController::class, 'cancelarProducto'])->name('ordenes_compra.cancelarProducto');

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

    // Formulario de Salida de Stock con firma
    Route::get('/salida-stock', [SalidaStockController::class, 'index'])->name('salida_stock.index');
    Route::get('/salida-stock/buscar', [SalidaStockController::class, 'buscar'])->name('salida_stock.buscar');
    Route::post('/salida-stock', [SalidaStockController::class, 'store'])->name('salida_stock.store');
    Route::get('/salida-stock/historial', [SalidaStockController::class, 'historial'])->name('salida_stock.historial');

    Route::post('/requisiciones/{requisicion}/entregar', [\App\Http\Controllers\requisicion\RequisicionController::class, 'entregarRequisicion'])->name('requisiciones.entregar');

    // Mostrar formulario si alguien hace GET accidentalmente a la ruta de verificación por archivo
    Route::get('/ordenes/verify-file', function() {
        return view('ordenes_compra.verify_upload');
    })->name('ordenes.verify_file_get');

    // Rutas para verificación de OC
    Route::get('/ordenes/verify', [OrdenCompraVerifyController::class, 'showForm'])->name('ordenes.verify_form');
    Route::get('/ordenes/{id}/verify', [OrdenCompraVerifyController::class, 'verify'])->name('ordenes.verify');
    Route::post('/ordenes/verify-file', [OrdenCompraVerifyController::class, 'verifyFilePost'])->name('ordenes.verify_file');
    Route::get('/ordenes/verify-upload', function() { return view('ordenes_compra.verify_upload'); })->name('ordenes.verify_upload');

    // Endpoint para generar/asegurar hashes de validación para órdenes de unphp a requisición
    Route::post('/ordenes/ensure-hashes/{requisicion}', [OrdenCompraVerifyController::class, 'ensureHashesForRequisition'])->name('ordenes_compra.ensure_hashes');

    // Transferir titularidad (solo Admin requisicion)
    Route::get('/requisiciones/transferir', [RequisicionController::class, 'transferIndex'])->name('requisiciones.transferir');
    Route::post('/requisiciones/{id}/transferir', [RequisicionController::class, 'transferir'])->name('requisiciones.transferir.post');

    // Endpoint para obtener usuarios desde servicio VPL_CORE (proxy)
    Route::get('requisiciones/usuarios-external', [RequisicionController::class, 'fetchExternalUsers'])
        ->name('requisiciones.usuarios_external');

    // Rutas para gestión de centros y subcentros
    Route::prefix('centros')->name('centros.')->group(function(){
        Route::get('/', [CentroController::class, 'index'])->name('index');
        Route::post('/', [CentroController::class, 'store'])->name('store');
        Route::put('/{id}', [CentroController::class, 'update'])->name('update');
        Route::delete('/{id}', [CentroController::class, 'destroy'])->name('destroy');

        Route::post('/{centro}/subcentros', [CentroController::class, 'storeSubcentro'])->name('subcentros.store');
        Route::put('/subcentros/{id}', [CentroController::class, 'updateSubcentro'])->name('subcentros.update');
        Route::delete('/subcentros/{id}', [CentroController::class, 'destroySubcentro'])->name('subcentros.destroy');
    });

    Route::get('/centros/user_subcentros', [UserSubcentroController::class, 'index'])->name('centros.user_subcentros.index');
    Route::post('/centros/user_subcentros', [UserSubcentroController::class, 'store'])->name('centros.user_subcentros.store');
    Route::get('/centros/user_subcentros/list/{email}', [UserSubcentroController::class, 'listForUser'])->name('centros.user_subcentros.list');
    Route::get('/centros/user_subcentros/fetch', [UserSubcentroController::class, 'fetchUsers'])->name('centros.user_subcentros.fetch');

    // Rutas para gestión de bodegas y operaciones
    Route::get('/centros/bodegas', [\App\Http\Controllers\centros\BodegaController::class, 'index'])->name('centros.bodegas.index');
    Route::post('/centros/bodegas', [\App\Http\Controllers\centros\BodegaController::class, 'store'])->name('centros.bodegas.store');
    Route::put('/centros/bodegas/{id}', [\App\Http\Controllers\centros\BodegaController::class, 'update'])->name('centros.bodegas.update');
    Route::delete('/centros/bodegas/{id}', [\App\Http\Controllers\centros\BodegaController::class, 'destroy'])->name('centros.bodegas.destroy');
    Route::post('/centros/bodegas/subcentro', [\App\Http\Controllers\centros\BodegaController::class, 'storeSubcentro'])->name('centros.bodegas.subcentro.store');
    Route::put('/centros/bodegas/subcentro/{id}', [\App\Http\Controllers\centros\BodegaController::class, 'updateSubcentro'])->name('centros.bodegas.subcentro.update');
    Route::delete('/centros/bodegas/subcentro/{id}', [\App\Http\Controllers\centros\BodegaController::class, 'destroySubcentro'])->name('centros.bodegas.subcentro.destroy');
    Route::get('/centros/bodegas/get-subcentros', [\App\Http\Controllers\centros\BodegaController::class, 'getSubcentros'])->name('centros.bodegas.getSubcentros');
    Route::get('/centros/bodegas/usuarios', [\App\Http\Controllers\centros\BodegaController::class, 'getUsuariosBodega'])->name('centros.bodegas.usuarios');

    // (Aprobadores removidos) -- gestión de aprobadores eliminada.

    // Rutas para Módulo de Inventarios
    Route::prefix('inventario')->name('inventario.')->group(function () {
        Route::get('/', [\App\Http\Controllers\InventarioController::class, 'index'])
            ->name('index')
            ->middleware([AuthSession::class, CheckPermission::class . ':ver inventario|inventario solicitante']);

        Route::get('/buscar', [\App\Http\Controllers\InventarioController::class, 'buscar'])
            ->name('buscar')
            ->middleware([AuthSession::class, CheckPermission::class . ':ver inventario|inventario solicitante']);

        Route::post('/entrada', [\App\Http\Controllers\InventarioController::class, 'storeEntrada'])
            ->name('entrada')
            ->middleware([AuthSession::class]);

        Route::post('/salida', [\App\Http\Controllers\InventarioController::class, 'storeSalida'])
            ->name('salida')
            ->middleware([AuthSession::class]);

        Route::get('/historial', [\App\Http\Controllers\InventarioController::class, 'historial'])
            ->name('historial')
            ->middleware([AuthSession::class, CheckPermission::class . ':ver inventario|inventario solicitante']);

        Route::get('/exportar', [\App\Http\Controllers\InventarioController::class, 'exportar'])
            ->name('exportar')
            ->middleware([AuthSession::class, CheckPermission::class . ':ver inventario|inventario solicitante']);

        Route::get('/plantilla', [\App\Http\Controllers\InventarioController::class, 'descargarPlantilla'])
            ->name('plantilla')
            ->middleware([AuthSession::class, CheckPermission::class . ':ver inventario|inventario solicitante']);

        Route::post('/importar', [\App\Http\Controllers\InventarioController::class, 'importarInventario'])
            ->name('importar')
            ->middleware([AuthSession::class, CheckPermission::class . ':ver inventario|inventario solicitante']);

        Route::put('/editar', [\App\Http\Controllers\InventarioController::class, 'editar'])
            ->name('editar')
            ->middleware([AuthSession::class]);

        Route::delete('/eliminar', [\App\Http\Controllers\InventarioController::class, 'eliminar'])
            ->name('eliminar')
            ->middleware([AuthSession::class]);

        // Rutas para Transferencias
        Route::get('/transferencia', [\App\Http\Controllers\TransferenciaController::class, 'index'])
            ->name('transferencia.index')
            ->middleware([AuthSession::class, CheckPermission::class . ':crear requisicion']);

        Route::get('/transferencia/inventario', [\App\Http\Controllers\TransferenciaController::class, 'getInventario'])
            ->name('transferencia.inventario')
            ->middleware([AuthSession::class, CheckPermission::class . ':crear requisicion']);

        Route::post('/transferencia', [\App\Http\Controllers\TransferenciaController::class, 'store'])
            ->name('transferencia.store')
            ->middleware([AuthSession::class, CheckPermission::class . ':crear requisicion']);

        Route::get('/transferencia/pdf/{id}', [\App\Http\Controllers\TransferenciaController::class, 'pdf'])
            ->name('transferencia.pdf')
            ->middleware([AuthSession::class, CheckPermission::class . ':crear requisicion']);
    });

});

// Ruta para confirmar recepciones/entregas en lote desde la vista
Route::post('/recepciones/confirmar-masivo', [RequisicionController::class, 'confirmarRecepcionesMasivo'])->name('recepciones.confirmar.masivo');

Route::resource('nuevo_producto', NuevoProductoController::class);

// Logout
Route::post('/logout', [ApiAuthController::class, 'logout'])->name('logout');


Route::view('/index', 'index')->name('index');


// Ruta para notificar por correo al añadir el producto solicitado
Route::post('/nuevo-producto/{id}/notify-added', [NuevoProductoController::class, 'notifyAdded'])->name('nuevo_producto.notifyAdded');

// Ruta para actualizar proveedores de un producto
Route::post('productos/{id}/providers', [ProductosController::class, 'updateProviders'])->name('productos.updateProviders');

// Ruta para actualizar date_oc y observaciones por orden de compra
Route::post('/ordenes-compra/{id}/basicos', [OrdenCompraController::class, 'updateBasicos'])->name('ordenes_compra.updateBasicos');

// Ruta específica para actualizar precios de factura (debe ir antes del resource para no colisionar con {orden_compra})
Route::post('/ordenes_compra/actualizar-precios-factura', [OrdenCompraController::class, 'actualizarPreciosFactura'])
    ->name('ordenes_compra.actualizar_precios_factura');

// Aceptar GET en /centros/subcentros (redirige a /centros si el servicio REST tiene solo PUT/DELETE)
Route::get('/centros/subcentros', function() {
    return redirect('/centros');
});

// Finalizar requisición (estatus 10)
Route::post('/requisiciones/{id}/finalizar', [RequisicionController::class, 'finalizar'])->name('requisiciones.finalizar');

// Rutas para recepción de órdenes de compra
Route::get('/recepciones/crear', [\App\Http\Controllers\RecepcionController::class, 'create'])->name('recepciones.create');
Route::post('/recepciones/buscar-oc', [\App\Http\Controllers\RecepcionController::class, 'buscarOc'])->name('recepciones.buscarOc');
Route::post('/recepciones/guardar', [\App\Http\Controllers\RecepcionController::class, 'store'])->name('recepciones.store');
Route::get('/recepciones/{orden_compra_id}/pdf', [\App\Http\Controllers\RecepcionController::class, 'generarPdf'])->name('recepciones.pdf');
