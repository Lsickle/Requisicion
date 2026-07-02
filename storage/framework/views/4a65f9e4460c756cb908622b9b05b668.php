<?php $__env->startSection('title', 'Crear Orden de Compra'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex pt-20">
    <style>
        /* Contenedor: permite scroll horizontal y vertical local sin afectar al body */
        .table-responsive { width:100%; max-width:100%; overflow-x:auto; overflow-y:auto; -webkit-overflow-scrolling: touch; max-height:48vh; }

        /* Usar layout fijo para que las columnas respeten anchos asignados y no 'colapsen' en anchos
           muy pequeños que provocan el salto de línea letra por letra en los headers. */
        .table-responsive table { min-width: 760px; width:100%; table-layout: fixed; border-collapse: collapse; }

        /* Encabezados en una línea (no se partan). Las celdas de datos sí pueden hacer wrap. */
        .table-responsive thead th { position: sticky; top: 0; z-index: 10; background: inherit; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .table-responsive td { white-space: normal; word-break: break-word; vertical-align: middle; }

        /* Limitar ancho de la columna 'Producto' para que no empuje las demás */
        .table-responsive td:first-child, .table-responsive th:first-child { max-width: 360px; }

        /* Reducir ancho de la columna 'Distribución' para que no ocupe tanto espacio
           y dejar espacio a las columnas clave (precio, stock, acciones). Se usa !important
           para sobreescribir los estilos inline si existen. */
        .table-responsive th:nth-child(9), .table-responsive td:nth-child(9) { width:220px !important; max-width:220px !important; }

        /* Botón de basura compacto y sin texto para evitar desacomodar filas */
        .btn-trash { background: transparent; border: none; display: inline-flex; align-items: center; justify-content: center; width:36px; height:36px; border-radius:6px; cursor:pointer; }
        .btn-trash svg { width:18px; height:18px; }
        .btn-trash:hover { background-color: rgba(239,68,68,0.08); }

        /* Mostrar como máximo 2 elementos de distribución; si hay más, habilitar scroll vertical aquí
           para evitar que la fila crezca y rompa el layout. Estimamos ~2.5rem por item (ajustable). */
        .table-responsive td:nth-child(9) .max-h-40 {
            max-height: 5.2rem !important; /* aprox. 2 items */
            overflow-y: auto !important;
        }

        /* Si el contenido interior tiene 'space-y-2', asegurar que el bloque sea display:block para que
           el overflow funcione correctamente en todos los navegadores. */
        .table-responsive td:nth-child(9) .space-y-2 { display: block; }

        /* Ajustes para pantallas pequeñas */
        @media (max-width: 1024px) {
            .table-responsive td:first-child, .table-responsive th:first-child { max-width: 260px; }
            .table-responsive th:nth-child(9), .table-responsive td:nth-child(9) { width:180px !important; max-width:180px !important; }
        }
        @media (max-width: 640px) {
            .table-responsive table { min-width: 640px; }
            .table-responsive td:first-child, .table-responsive th:first-child { max-width: 180px; }
            .table-responsive th:nth-child(9), .table-responsive td:nth-child(9) { width:140px !important; max-width:140px !important; }
        }

        /* Nuevo: truncar el nombre de producto a 2 líneas con elipsis y permitir cortes de palabra seguros */
        .table-responsive .product-name {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
            white-space: normal;
            line-height: 1.2;
            max-width: 100%;
        }

        /* Mejora visual general */
        .oc-create-scope .main-card{background:rgba(255,255,255,0.95);border:1px solid #e2e8f0;border-radius:1rem;box-shadow:0 10px 25px -5px rgba(0,0,0,0.08),0 8px 10px -6px rgba(0,0,0,0.04);}
        .oc-create-scope .section-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.75rem;}
        .oc-create-scope h1,.oc-create-scope h2,.oc-create-scope h3{letter-spacing:.5px;}
        /* Encabezados sticky refinados */
        .oc-create-scope table thead{background:#eef2ff;color:#1e3a8a;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;}
        .oc-create-scope table thead th{font-weight:600;}
        /* Zebra + hover */
        .oc-create-scope table tbody tr:nth-child(odd){background:#ffffff;}
        .oc-create-scope table tbody tr:nth-child(even){background:#f1f5f9;}
        .oc-create-scope table tbody tr:hover{background:#e0e7ff;}
        /* Botones reutilizables */
        .btn-base{display:inline-flex;align-items:center;justify-content:center;font-weight:500;border-radius:.6rem;transition:.25s;box-shadow:0 1px 2px rgba(0,0,0,.12);} 
        .btn-base:focus-visible{outline:2px solid #6366f1;outline-offset:2px;}
        .btn-primary{background:#2563eb;color:#fff;}
        .btn-primary:hover{background:#1d4ed8;}
        .btn-secondary{background:#6366f1;color:#fff;}
        .btn-secondary:hover{background:#4f46e5;}
        .btn-danger{background:#dc2626;color:#fff;}
        .btn-danger:hover{background:#b91c1c;}
        .btn-warning{background:#d97706;color:#fff;}
        .btn-warning:hover{background:#b45309;}
        /* Scrollbar fino */
        .thin-scrollbar{scrollbar-width:thin;scrollbar-color:#94a3b8 #e2e8f0;}
        .thin-scrollbar::-webkit-scrollbar{width:8px;height:8px;}
        .thin-scrollbar::-webkit-scrollbar-track{background:#e2e8f0;border-radius:8px;}
        .thin-scrollbar::-webkit-scrollbar-thumb{background:#94a3b8;border-radius:8px;}
        .thin-scrollbar::-webkit-scrollbar-thumb:hover{background:#64748b;}
        /* Ajustar tabla responsive borde */
        .table-responsive{border:1px solid #e2e8f0;border-radius:.75rem;background:#fff;}
        /* Chips / badges */
        .badge-mini{display:inline-flex;align-items:center;font-size:.65rem;font-weight:600;padding:.25rem .5rem;border-radius:999px;letter-spacing:.03em;}
        /* Precio/IVA etiquetas */
        .precio-cop-span{font-size:.6rem;font-weight:500;color:#475569;display:block;margin-top:2px;}
    </style>
    <!-- Sidebar -->
    <?php if (isset($component)) { $__componentOriginal2880b66d47486b4bfeaf519598a469d6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2880b66d47486b4bfeaf519598a469d6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $attributes = $__attributesOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $component = $__componentOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__componentOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>

    <!-- Contenido principal -->
    <div class="flex-1 px-4 md:px-8 pb-10 oc-create-scope">
        <div class="max-w-7xl mx-auto main-card p-6 flex flex-col min-h-[80vh]">

            <!-- Encabezado -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-inner"><i class="fas fa-file-signature text-xl"></i></div>
                    <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Crear Orden de Compra</h1>
                </div>
                <a href="<?php echo e(route('ordenes_compra.lista')); ?>" class="btn-base bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 text-sm">Volver</a>
            </div>

            <!-- Mensaje éxito -->
            <?php if(session('success')): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: '<?php echo e(session('success')); ?>',
                    confirmButtonText: 'Aceptar'
                });
            </script>
            <?php endif; ?>

            <!-- ================= Datos de la Requisición ================= -->
            <?php if($requisicion): ?>
            <div class="mb-8 border rounded-lg bg-gray-50 p-6 shadow-sm">
                <h2 class="text-xl font-medium text-gray-700 mb-4">Requisición #<?php echo e($requisicion->id); ?></h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="p-4 rounded-lg border bg-white">
                        <h3 class="font-medium text-gray-700 mb-2">Solicitante</h3>
                        <p><strong>Nombre:</strong> <?php echo e($requisicion->name_user); ?></p>
                        <p><strong>Email:</strong> <?php echo e($requisicion->email_user); ?></p>
                        <p><strong>Operación:</strong> <?php echo e($requisicion->operacion_user); ?></p>
                    </div>
                    <div class="p-4 rounded-lg border bg-white">
                        <h3 class="font-medium text-gray-700 mb-2">Información General</h3>
                        <p>
                            <strong>Prioridad:</strong>
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                <?php echo e($requisicion->prioridad_requisicion == 'alta' ? 'bg-red-100 text-red-700' :
                                   ($requisicion->prioridad_requisicion == 'media' ? 'bg-yellow-100 text-yellow-700' :
                                   'bg-green-100 text-green-700')); ?>">
                                <?php echo e(ucfirst($requisicion->prioridad_requisicion)); ?>

                            </span>
                        </p>
                        <p><strong>Recobrable:</strong> <?php echo e($requisicion->Recobrable); ?></p>
                    </div>
                </div>

                <div class="mb-4 text-sm text-gray-700">
                    <p><strong>Detalle:</strong> <?php echo e($requisicion->detail_requisicion); ?></p>
                    <p><strong>Justificación:</strong> <?php echo e($requisicion->justify_requisicion); ?></p>
                </div>

                <!-- Distribución -->
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-700 mb-3">Distribución Original por Centros</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 text-sm table-fixed">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left" style="width:20%">Producto</th>
                                    <th class="px-3 py-2 text-center" style="width:90px">Unidad</th>
                                    <th class="px-3 py-2 text-center" style="width:70px">Total</th>
                                    <th class="px-3 py-2 text-center" style="width:110px">Precio unitario</th>
                                    <th class="px-3 py-2 text-center" style="width:120px">Precio total</th>
                                    <th class="px-4 py-2 text-left" style="width:30%">Distribución</th>
                                </tr>
                            </thead>
                            <tbody>
                                 <?php $grandTotal = 0; ?>
                                <?php $__currentLoopData = $requisicion->productos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                $distribucion = DB::table('centro_producto')
                                ->where('requisicion_id', $requisicion->id)
                                ->where('producto_id', $prod->id)
                                ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                                ->select('centro.name_centro', 'centro_producto.amount')
                                ->get();
                                $confirmadoEntrega = (int) DB::table('entrega')->where('requisicion_id', $requisicion->id)->where('producto_id', $prod->id)->whereNull('deleted_at')->sum(DB::raw('COALESCE(cantidad_recibido,0)'));
                                // Ignorar tabla `recepcion` aquí: considerar solo entregas
                                $confirmadoStock = 0;
                                $totalConfirmado = $confirmadoEntrega + $confirmadoStock;
                                ?>
                                <?php
                                    // Obtener precio desde productoxproveedor (nuevo esquema)
                                    try {
                                        $pp = DB::table('productoxproveedor')
                                            ->where('producto_id', $prod->id)
                                            ->orderBy('id')
                                            ->first();
                                        $precioUnit = (float) ($pp->price_produc ?? 0);
                                    } catch (\Throwable $e) {
                                        $precioUnit = 0.0;
                                    }
                                 $precioTotal = $precioUnit * (int)($prod->pivot->pr_amount ?? 0);
                                 $grandTotal += $precioTotal;
                                ?>
                                <tr class="border-t">
                                    <td class="px-4 py-2"><?php echo e($prod->name_produc); ?></td>
                                    <td class="px-3 py-2 text-center"><?php echo e($prod->unit_produc ?? '-'); ?></td>
                                    <td class="px-3 py-2 text-center font-medium w-20"><?php echo e($prod->pivot->pr_amount); ?> <?php if($totalConfirmado>0): ?><span class="text-xs text-gray-500">(<?php echo e($totalConfirmado); ?> recibido)</span><?php endif; ?></td>
                                    <td class="px-3 py-2 text-center">$<?php echo e(number_format($precioUnit,2)); ?></td>
                                    <td class="px-3 py-2 text-center font-semibold">$<?php echo e(number_format($precioTotal,2)); ?></td>
                                    <td class="px-4 py-2">
                                         <?php if($distribucion->count() > 0): ?>
                                         <div class="max-h-36 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-2 p-1">
                                             <?php $__currentLoopData = $distribucion; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $centro): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                             <div class="flex justify-between items-center bg-gray-50 px-2 py-1 rounded text-sm">
                                                 <span class="truncate mr-2"><?php echo e($centro->name_centro); ?></span>
                                                 <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium"><?php echo e($centro->amount); ?></span>
                                             </div>
                                             <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                         </div>
                                         <?php else: ?>
                                         <span class="text-gray-500 text-sm">No hay distribución registrada</span>
                                         <?php endif; ?>
                                     </td>
                                 </tr>
                                 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <tr class="border-t bg-gray-50">
                                    <td class="px-4 py-3 font-semibold">Total general</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="px-3 py-3 font-semibold">$<?php echo e(number_format($grandTotal,2)); ?></td>
                                    <td></td>
                                </tr>
                             </tbody>
                        </table>
                     </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Mensajes de error -->
            <?php if($errors->any()): ?>
            <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700">
                <ul class="list-disc ml-5 text-sm">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php if(session('error')): ?>
            <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700 text-sm">
                <?php echo e(session('error')); ?>

            </div>
            <?php endif; ?>

            <!-- Formulario para Crear Orden -->
            <?php if($requisicion): ?>
            <div class="border p-6 mb-6 rounded-lg shadow bg-gray-50">
                <h2 class="text-xl font-medium text-gray-700 mb-4">Nueva Orden de Compra</h2>

                <form id="orden-form" action="<?php echo e(route('ordenes_compra.store')); ?>" method="POST" class="space-y-6">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="requisicion_id" value="<?php echo e($requisicion->id); ?>">

                    <!-- Ubicación -->
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-2">Ubicación de Almacenamiento</label>
                        <select name="ubicacion" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400">
                            <option value="">Seleccione una ubicación</option>
                            <option value="bodega 12G">Bodega 12G</option>
                            <option value="bodega 6C">Bodega 6C</option>
                            <option value="bodega 16C">Bodega 16C</option>
                            <option value="bodega 8H">Bodega 8H</option>
                            <option value="bodega 1E">Bodega 1E</option>
                            <option value="bodega 3A">Bodega 3A</option>
                            <option value="Coltabaco">Coltabaco</option>
                        </select>
                    </div>

                    <!-- Selector de productos -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-600 mb-2">Añadir Producto</label>
                        <div class="flex gap-3">
                            <select id="producto-selector"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400">
                                <option value="">Seleccione un producto</option>
                                <?php if($productosDisponibles->count()): ?>
                                <optgroup label="Productos sin distribuir">
                                <?php $__currentLoopData = $productosDisponibles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $producto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    // obtener lista de proveedores para este producto desde productoxproveedor
                                    try {
                                        $ppList = \Illuminate\Support\Facades\DB::table('productoxproveedor as pxp')
                                            ->join('proveedores as prov','pxp.proveedor_id','=','prov.id')
                                            ->where('pxp.producto_id', $producto->id)
                                            ->whereNull('pxp.deleted_at')
                                            ->select('pxp.id as pxp_id','pxp.proveedor_id','prov.prov_name','pxp.price_produc','pxp.moneda')
                                            ->orderBy('prov.prov_name')
                                            ->get();
                                        // Precalcular price_cop usando TRM más reciente enviado a la vista
                                        try {
                                            $trmIdx = collect($trmLatest ?? [])->keyBy(function($r){ return strtoupper($r->moneda ?? ''); });
                                            $pTo = (float) ($trmIdx->get('COP')->price ?? 0);
                                            if ($pTo > 0) {
                                                $ppList = $ppList->map(function($r) use ($trmIdx, $pTo){
                                                    try {
                                                        $from = strtoupper($r->moneda ?? 'COP');
                                                        $unit = (float) ($r->price_produc ?? 0);
                                                        if ($from === 'COP') {
                                                            $r->price_cop = round($unit, 2);
                                                        } else {
                                                            $pFrom = (float) ($trmIdx->get($from)->price ?? 0);
                                                            if ($pFrom > 0) {
                                                                $rate = $pTo / $pFrom; // FROM->COP
                                                                $r->price_cop = round($unit * $rate, 2);
                                                            } else { $r->price_cop = null; }
                                                        }
                                                    } catch (\Throwable $e) { $r->price_cop = null; }
                                                    return $r;
                                                });
                                            }
                                        } catch (\Throwable $e) { /* ignore */ }
                                    } catch (\Throwable $e) {
                                        $ppList = collect();
                                    }
                                ?>
                                <option value="<?php echo e($producto->id); ?>"
                                     data-cantidad="<?php echo e($producto->pivot->pr_amount ?? 1); ?>"
                                     data-nombre="<?php echo e($producto->name_produc); ?>"
                                     data-unidad="<?php echo e($producto->unit_produc); ?>"
                                     data-proveedor="<?php echo e($producto->proveedor_id ?? ''); ?>"
                                     data-iva="<?php echo e($producto->iva ?? 0); ?>"
                                     data-price="<?php echo e(($ppList->first()->price_produc ?? 0)); ?>"
                                     data-price-currency="<?php echo e(($ppList->first()->moneda ?? 'COP')); ?>"
                                     data-providers='<?php echo json_encode($ppList, 15, 512) ?>'>
                                      <?php echo e($producto->name_produc); ?> (<?php echo e($producto->unit_produc); ?>) - Cantidad: <?php echo e($producto->pivot->pr_amount ?? 1); ?>

                                 </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </optgroup>
                                <?php endif; ?>
                                <?php if(isset($lineasDistribuidas) && $lineasDistribuidas->count()): ?>
                                <optgroup label="Líneas distribuidas pendientes">
                                    <?php $__currentLoopData = $lineasDistribuidas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ld): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php $ldPrice = $ld->price_produc ?? 0; ?>
                                        <?php
                                            try {
                                                $ppList = \Illuminate\Support\Facades\DB::table('productoxproveedor as pxp')
                                                    ->join('proveedores as prov','pxp.proveedor_id','=','prov.id')
                                                    ->where('pxp.producto_id', $ld->producto_id)
                                                    ->whereNull('pxp.deleted_at')
                                                    ->select('pxp.id as pxp_id','pxp.proveedor_id','prov.prov_name','pxp.price_produc','pxp.moneda')
                                                    ->orderBy('prov.prov_name')
                                                    ->get();
                                            } catch (\Throwable $e) { $ppList = collect(); }
                                        ?>
                                        <option value="<?php echo e($ld->producto_id); ?>"
                                            data-distribuido="1"
                                            data-ocp-id="<?php echo e($ld->ocp_id); ?>"
                                            data-nombre="<?php echo e($ld->name_produc); ?>"
                                            data-unidad="<?php echo e($ld->unit_produc); ?>"
                                            data-cantidad="<?php echo e($ld->cantidad); ?>"
                                            data-iva="<?php echo e($ld->iva ?? 0); ?>"
                                            data-price="<?php echo e(($ppList->first()->price_produc ?? $ldPrice)); ?>"
                                            data-price-currency="<?php echo e(($ppList->first()->moneda ?? ($ld->moneda ?? 'COP'))); ?>"
                                            data-providers='<?php echo json_encode($ppList, 15, 512) ?>'>
                                             <?php echo e($ld->name_produc); ?> (<?php echo e($ld->unit_produc); ?>) - Cantidad: <?php echo e($ld->cantidad); ?>

                                         </option>
                                     <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                 </optgroup>
                                 <?php endif; ?>
                            </select>
                            <button type="button" id="btn-add-product" onclick="if(window.openProvidersModal){ window.openProvidersModal(); } else if(window.quickAddProduct){ window.quickAddProduct(); } else { Swal.fire({icon:'info', title:'Seleccione', text:'Seleccione un producto primero.'}); }"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                ➕ Añadir
                            </button>
                            <script>
                                // Fallback ligero para añadir productos si el script principal falla
                                window.quickAddProduct = function(){
                                    try {
                                        const sel = document.getElementById('producto-selector');
                                        const opt = sel?.options?.[sel.selectedIndex];
                                        if (!opt || !opt.value) { Swal.fire({icon:'info', title:'Seleccione', text:'Seleccione un producto primero.'}); return; }
                                        const productoId = opt.value;
                                        const productoNombre = opt.dataset.nombre || opt.textContent || 'Producto';
                                        const cantidad = parseInt(opt.dataset.cantidad || '1', 10) || 1;
                                        const table = document.getElementById('productos-table');
                                        const rowKey = `${productoId}-0`;
                                        if (document.getElementById(`producto-${rowKey}`)) { Swal.fire({icon:'warning', title:'Atención', text:'Esta línea ya fue agregada'}); return; }
                                        const tr = document.createElement('tr');
                                        tr.id = `producto-${rowKey}`;
                                        tr.innerHTML = `<td class="p-3"><div class="font-semibold">${productoNombre}</div>
                                            <input type="hidden" name="productos[${rowKey}][id]" value="${productoId}"></td>
                                            <td class="p-3 text-center"><input type="number" name="productos[${rowKey}][cantidad]" min="1" value="${cantidad}" class="w-16 border rounded p-1 text-center" required></td>
                                            <td class="p-3 text-center">-</td>
                                            <td class="p-3 text-center">COP</td>
                                            <td class="p-3 text-right">-</td>
                                            <td class="p-3 text-center">-</td>
                                            <td class="p-3">-</td>
                                            <td class="p-3 text-center"><button type="button" class="btn-trash text-red-600" onclick="(function(rid, key){ document.getElementById(rid).remove(); window.productosAgregados = (window.productosAgregados||[]).filter(k => k !== key); })(\`producto-${rowKey}\`, \`${rowKey}\`)">✕</button></td>`;
                                        table.appendChild(tr);
                                        window.productosAgregados = window.productosAgregados || [];
                                        window.productosAgregados.push(rowKey);
                                        // remove option from selector to avoid duplicates
                                        try { opt.remove(); } catch(e){}
                                    } catch(e){ console.error('quickAddProduct error', e); Swal.fire({icon:'error', title:'Error', text: 'No se pudo añadir el producto.'}); }
                                };
                            </script>
                            <button type="button" id="btn-abrir-modal"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                                📊 Distribuir entre Proveedores
                            </button>
                            <button type="button" id="btn-abrir-undo-dist" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                                ↩️ Deshacer distribución
                            </button>
                        </div>
                    </div>

                    <!-- Tabla productos (editable para crear la orden) -->
                    <div class="overflow-x-auto mt-6 max-h-[60vh] overflow-y-auto">
                        <h3 class="text-lg font-medium text-gray-700 mb-2">Productos en la Orden</h3>
                        <div class="table-responsive">
                        <table class="w-full border text-sm rounded-lg overflow-hidden bg-white table-auto">
                             <thead class="bg-gray-100 sticky top-0 z-10">
                                 <tr>
                                     <th class="p-3 text-left" style="width:25%">Producto</th>
                                     <th class="p-3 text-center" style="width:70px">Total</th>
                                     <th class="p-3 text-center" style="width:90px">Unidad</th>
                                     <th class="p-3 text-center" style="width:80px">Moneda</th>
                                     <th class="p-3 text-center" style="width:160px">Precio unitario</th>
                                     <th class="p-3 text-center" style="width:90px">IVA</th>
                                     <th class="p-3 text-center" style="width:100px">Entregado</th>
                                     <th class="p-3" style="width:40%">Distribución</th>
                                     <th class="p-3 text-center" style="width:90px">Acciones</th>
                                 </tr>
                             </thead>
                             <tbody id="productos-table"></tbody>
                        </table>
                        </div>
                     </div>

                    <!-- Modal Proveedores por Producto -->
                    <div id="modal-proveedores" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-start justify-center overflow-y-auto">
                        <div class="bg-white w-11/12 sm:max-w-xl my-10 rounded-lg shadow-lg overflow-hidden max-h-[70vh] flex flex-col">
                            <div class="flex justify-between items-center px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold">Proveedores disponibles</h3>
                                <button type="button" id="btn-cerrar-proveedores" class="text-gray-600 hover:text-gray-800">✕</button>
                            </div>
                            <div class="p-4 overflow-y-auto" id="prov-list-container">
                                <div class="text-sm text-gray-500">Seleccione un proveedor para el producto seleccionado.</div>
                                <div id="prov-list" class="mt-3 space-y-2"></div>
                            </div>
                            <div class="flex justify-end gap-3 px-6 py-3 border-t bg-gray-50">
                                <button type="button" id="btn-cancel-proveedores" class="px-4 py-2 border rounded">Cancelar</button>
                                <button type="button" id="btn-select-prov" class="px-4 py-2 bg-indigo-600 text-white rounded">Seleccionar</button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Distribución -->
                    <div id="modal-distribucion" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-start justify-center overflow-y-auto">
                        <div class="bg-white w-11/12 sm:max-w-3xl my-10 rounded-lg shadow-lg overflow-hidden max-h-[85vh] flex flex-col">
                            <div class="flex justify-between items-center px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold">Distribuir producto (solo cantidades)</h3>
                                <button type="button" id="btn-cerrar-modal" class="text-gray-600 hover:text-gray-800">✕</button>
                            </div>
                            <div class="p-6 space-y-4 grow overflow-y-auto">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Producto a distribuir</label>
                                    <select id="dist-producto-id" class="w-full border rounded-lg p-2">
                                        <option value="">Seleccione un producto</option>
                                        <?php $__currentLoopData = $productosDisponibles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $producto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($producto->id); ?>" data-max="<?php echo e($producto->pivot->pr_amount ?? 1); ?>" data-nombre="<?php echo e($producto->name_produc); ?>" data-unidad="<?php echo e($producto->unit_produc); ?>">
                                            <?php echo e($producto->name_produc); ?> (<?php echo e($producto->unit_produc); ?>) - Cantidad: <?php echo e($producto->pivot->pr_amount ?? 1); ?>

                                        </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <small class="text-gray-500">Cantidad total a distribuir: <span id="dist-max">0</span> <span id="dist-unidad"></span></small>
                                </div>

                                <div class="overflow-x-auto max-h-[50vh] overflow-y-auto">
                                    <table class="w-full border text-sm rounded-lg bg-white">
                                        <thead class="bg-gray-100 sticky top-0 z-10">
                                            <tr>
                                                <th class="p-2 text-center">Cantidad</th>
                                                <th class="p-2 text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabla-items-dist"></tbody>
                                        <tfoot>
                                            <tr>
                                                <td class="p-2 text-center"><span id="dist-total">0</span> / <span id="dist-total-max">0</span></td>
                                                <td class="p-2 text-center">
                                                    <button type="button" id="btn-add-fila" class="px-3 py-1 bg-green-600 text-white rounded text-sm">+ Agregar segmento</button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 px-6 py-4 border-t">
                                <button type="button" id="btn-cancelar-modal" class="px-4 py-2 border rounded">Cancelar</button>
                                <button type="button" id="btn-guardar-dist" class="px-4 py-2 bg-blue-600 text-white rounded">Guardar</button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Deshacer Distribución -->
                    <div id="modal-undo-distribucion" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-start justify-center overflow-y-auto">
                        <div class="bg-white w-11/12 sm:max-w-3xl my-10 rounded-lg shadow-lg overflow-hidden max-h-[85vh] flex flex-col">
                            <div class="flex justify-between items-center px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold">Deshacer distribución</h3>
                                <button type="button" id="btn-cerrar-undo" class="text-gray-600 hover:text-gray-800">✕</button>
                            </div>
                            <div class="p-6 space-y-4 grow overflow-y-auto">
                                <?php if(($lineasDistribuidas ?? collect())->count() > 0): ?>
                                <?php
                                    $agrupadas = ($lineasDistribuidas ?? collect())
                                        ->groupBy('producto_id')
                                        ->map(function($g){
                                            return (object) [
                                                'producto_id' => $g->first()->producto_id,
                                                'name_produc' => $g->first()->name_produc,
                                                'cantidad_total' => $g->sum('cantidad'),
                                                'proveedores' => $g->pluck('prov_name')->filter()->unique()->values()->all(),
                                                'ocp_ids' => $g->pluck('ocp_id')->filter()->values()->all(),
                                            ];
                                        })->values();
                                ?>
                                <table class="w-full border text-sm rounded bg-white">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-center"><input type="checkbox" id="chk-undo-all"></th>
                                            <th class="p-2 text-left">Producto</th>
                                            <th class="p-2 text-left">Proveedor</th>
                                            <th class="p-2 text-center">Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $agrupadas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr class="border-t">
                                            <td class="p-2 text-center">
                                                <input type="checkbox" class="chk-undo-item" value="<?php echo e($grp->producto_id); ?>" data-ocp-ids="<?php echo e(implode(',', $grp->ocp_ids)); ?>">
                                            </td>
                                            <td class="p-2"><?php echo e($grp->name_produc); ?></td>
                                            <td class="p-2"><?php echo e(count($grp->proveedores) > 1 ? 'Varios' : ($grp->proveedores[0] ?? 'Proveedor')); ?></td>
                                            <td class="p-2 text-center"><?php echo e($grp->cantidad_total); ?></td>
                                        </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <div class="text-gray-600 text-sm">No hay líneas distribuidas pendientes.</div>
                                <?php endif; ?>
                            </div>
                            <div class="flex justify-end gap-3 px-6 py-4 border-t">
                                <button type="button" id="btn-cancelar-undo" class="px-4 py-2 border rounded">Cancelar</button>
                                <button type="button" id="btn-confirmar-undo" class="px-4 py-2 bg-orange-600 text-white rounded">Deshacer seleccionados</button>
                            </div>
                        </div>
                    </div>

                    <!-- Botón submit -->
                    <div class="flex justify-end mt-4">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow">
                            Crear Orden de Compra
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla de órdenes creadas (sección aparte) -->
            <div class="border p-6 mt-10 rounded-lg shadow bg-gray-50">
                <h2 class="text-xl font-medium text-gray-700 mb-4">Órdenes de Compra Creadas</h2>
                <table class="w-full border text-sm rounded-lg overflow-hidden bg-white" id="ordenes-table">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3">#</th>
                            <th class="p-3">Número</th>
                            <th class="p-3">Proveedor</th>
                            <th class="p-3">Productos</th>
                            <th class="p-3">Fecha de creación</th>
                            <th class="p-3">Fecha y Observación</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = ($ordenes ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $orden): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="border-t">
                            <td class="p-3"><?php echo e($loop->iteration); ?></td>
                            <td class="p-3"><?php echo e($orden->order_oc ?? 'N/A'); ?></td>
                            <td class="p-3">
                                <?php $prov = optional($orden->ordencompraProductos->first())->proveedor; ?>
                                <?php echo e($prov ? $prov->prov_name : 'Proveedor no disponible'); ?>

                            </td>
                            <td class="p-3">
                                <?php $__currentLoopData = $orden->ordencompraProductos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($p->producto): ?>
                                        <?php echo e($p->producto->name_produc); ?> (<?php echo e($p->total); ?> <?php echo e($p->producto->unit_produc); ?>)<br>
                                    <?php else: ?>
                                        Producto eliminado (<?php echo e($p->total); ?>)<br>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </td>
                            <td class="p-3"><?php echo e($orden->created_at ? $orden->created_at->format('d/m/Y') : 'Sin fecha'); ?></td>
                            <td class="p-3">
                                <?php 
                                    $hasDate = !is_null($orden->date_oc);
                                    $hasObs  = !is_null($orden->observaciones);
                                ?>
                                <?php if($hasDate || $hasObs): ?>
                                    <div class="space-y-1">
                                        <div>
                                            <span class="text-gray-600 text-xs">Fecha:</span>
                                            <span class="font-medium"><?php echo e($hasDate ? \Carbon\Carbon::parse($orden->date_oc)->format('Y-m-d') : 'Sin fecha'); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 text-xs">F. Estimada:</span>
                                            <span class="font-medium"><?php echo e($orden->fecha_estimada_recepcion ? \Carbon\Carbon::parse($orden->fecha_estimada_recepcion)->format('Y-m-d') : 'Sin fecha'); ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 text-xs">Observación:</span>
                                            <span class="font-medium"><?php echo e($hasObs ? $orden->observaciones : 'Sin observación'); ?></span>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <form action="<?php echo e(route('ordenes_compra.updateBasicos', $orden->id)); ?>" method="POST" class="oc-basicos-form flex flex-col gap-2">
                                        <?php echo csrf_field(); ?>
                                        <div class="flex flex-wrap gap-2 items-center">
                                            <input type="date" name="date_oc" min="<?php echo e(now()->format('Y-m-d')); ?>"
                                                   value="<?php echo e($orden->date_oc ? \Carbon\Carbon::parse($orden->date_oc)->format('Y-m-d') : ''); ?>"
                                                   class="border rounded p-1 text-sm" required>
                                            <input type="date" name="fecha_estimada_recepcion"
                                                   value="<?php echo e($orden->fecha_estimada_recepcion ? \Carbon\Carbon::parse($orden->fecha_estimada_recepcion)->format('Y-m-d') : ''); ?>"
                                                   class="border rounded p-1 text-sm" required>
                                            <input type="text" name="observaciones" placeholder="Observación"
                                                   value="<?php echo e(old('observaciones', $orden->observaciones)); ?>"
                                                   class="border rounded p-1 text-sm flex-1 min-w-[150px]">
                                        </div>
                                        <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm self-start">Guardar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-center">
                                <form action="<?php echo e(route('ordenes_compra.anular', $orden->id)); ?>" method="POST" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="button" onclick="confirmarAnulacion(this)" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm">Anular</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>

                <!-- Botón descargar PDF/ZIP -->
                <div class="mt-6 text-right" id="zip-container">
                    <?php
                        $estatusActual = DB::table('estatus_requisicion')
                            ->where('requisicion_id', $requisicion->id)
                            ->whereNull('deleted_at')
                            ->where('estatus', 1)
                            ->value('estatus_id');
                        $hayOrdenes = ($ordenes ?? collect())->count() > 0;
                    ?>
                    <a href="<?php echo e(route('ordenes_compra.download', $requisicion->id)); ?>" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow" id="btn-download-zip" data-hay="<?php echo e($hayOrdenes ? 1 : 0); ?>">
                        Descargar PDF/ZIP
                    </a>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
</div>





<script>
    // Cambiar a llave compuesta para permitir líneas distribuidas del mismo producto
    let productosAgregados = [];
    let centros = [];
    let proveedoresMap = {};
    let productosConRecepcion = [];
    let entregasPrevPorProducto = [];
    let totalConfirmadoPorProducto = [];
    let distribucionOriginal = {};

        // No-op: debug removed to avoid side effects in production.

        // Simple loader utilities used by several actions (PDF/ZIP generation)
        function showStockLoader(message) {
            // If already present, update message
            let el = document.getElementById('stock-loader');
            if (!el) {
                el = document.createElement('div');
                el.id = 'stock-loader';
                el.style.position = 'fixed';
                el.style.left = '0';
                el.style.top = '0';
                el.style.width = '100%';
                el.style.height = '100%';
                el.style.display = 'flex';
                el.style.alignItems = 'center';
                el.style.justifyContent = 'center';
                el.style.background = 'rgba(0,0,0,0.4)';
                el.style.zIndex = '9999';
                el.innerHTML = '<div style="background:#fff;padding:20px 24px;border-radius:8px;display:flex;gap:12px;align-items:center;box-shadow:0 6px 20px rgba(0,0,0,0.15)">'
                    + '<div class="fas fa-spinner fa-spin" style="font-size:20px;color:#6b7280"></div>'
                    + '<div id="stock-loader-msg" style="font-size:14px;color:#374151">'+(message||'Procesando...')+'</div>'
                    + '</div>';
                document.body.appendChild(el);
            } else {
                const msg = document.getElementById('stock-loader-msg');
                if (msg) msg.textContent = message || 'Procesando...';
                el.style.display = 'flex';
            }
        }

        function hideStockLoader() {
            const el = document.getElementById('stock-loader');
            if (el) el.style.display = 'none';
        }

        // Cargar datos en DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        centros = <?php echo json_encode($centros, 15, 512) ?>;
        proveedoresMap = <?php echo json_encode($proveedores->pluck('prov_name', 'id'), 512) ?>;
        totalConfirmadoPorProducto = <?php echo json_encode($totalConfirmadoPorProducto ?? [], 15, 512) ?>;
    });

    function agregarProducto() {
        const selector = document.getElementById('producto-selector');
        const proveedorSelect = document.getElementById('proveedor_id');
        const selectedOption = selector.options[selector.selectedIndex];
        const productoId = selector.value;
        if (!selectedOption || !productoId) {
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Seleccione un producto'});
            return;
        }

        const productoNombre = selectedOption.dataset.nombre;
        const proveedorId = selectedOption.dataset.proveedor;
        const unidad = selectedOption.dataset.unidad || '';
        const cantidadOriginal = parseInt(selectedOption.dataset.cantidad || '1', 10);
        const iva = parseFloat(selectedOption.dataset.iva ?? '0');
        const precio = parseFloat(selectedOption.dataset.price ?? '0');
        const precioCurrency = selectedOption.dataset.priceCurrency || selectedOption.dataset['price-currency'] || 'COP';
        const currency = selectedOption.dataset.priceCurrency || 'COP';
        const ocpId = selectedOption.dataset.ocpId || null;
        const rowKey = `${productoId}-${ocpId||'0'}`;

        if (productosAgregados.includes(rowKey)) {
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Esta línea ya fue agregada'});
            return;
        }

        if (selectedOption.dataset.distribuido === '1' && proveedorId) {
            proveedorSelect.value = proveedorId;
        }

        agregarProductoFinal(rowKey, productoId, productoNombre, proveedorId, cantidadOriginal, selector, ocpId, selectedOption.dataset.distribuido === '1', iva, precio, selectedOption.dataset.priceCurrency || selectedOption.dataset['price-currency'] || 'COP');
    }

    function agregarProductoFinal(rowKey, productoId, productoNombre, proveedorId, cantidadOriginal, selector, ocpId = null, esDistribuido = false, iva = 0, precio = 0, precioCurrency = 'COP', providersJson = '', priceCop = '', productoxproveedorId = '') {
         const table = document.getElementById('productos-table');
         const rowId = `producto-${rowKey}`;
         if (document.getElementById(rowId)) return;

         // cantidad a comprar para esta fila (se usa para reescalar la distribución)
         let cantidadParaComprar = cantidadOriginal;
         // Asegurar que exista `unidad` incluso si no se pasa como argumento
         let unidad = '';
         try {
             unidad = selector?.options?.[selector.selectedIndex]?.dataset?.unidad || selector?.dataset?.unidad || '';
         } catch(e) { unidad = ''; }
         let distribucionProducto = distribucionOriginal[productoId] || {};
         let centrosHtml = '';
         let centrosConDistribucion = [];
         for (let centroId in distribucionProducto) {
             // incluir centros que tengan algún valor base o todos si no hay valores
             let centro = centros.find(c => c.id == centroId);
             if (centro) centrosConDistribucion.push(centro);
         }
         if (centrosConDistribucion.length === 0) centrosConDistribucion = centros;

         // Calcular valores base y reescalar para que la suma sea exactamente cantidadParaComprar
         const baseValues = centrosConDistribucion.map(c => parseInt(distribucionProducto[c.id] || 0));
         const baseSum = baseValues.reduce((a,b) => a + (b||0), 0);
         let assigned = [];
         if (cantidadParaComprar <= 0) {
             assigned = centrosConDistribucion.map(() => 0);
         } else if (baseSum > 0) {
             // repartir proporcionalmente según los valores base, usando floor y distribuyendo el resto
             let total = cantidadParaComprar;
             let acc = 0;
             for (let i = 0; i < centrosConDistribucion.length; i++) {
                 const val = Math.floor(((baseValues[i] || 0) / baseSum) * total);
                 assigned[i] = val;
                 acc += val;
             }
             let rem = total - acc;
             for (let i = 0; i < centrosConDistribucion.length && rem > 0; i++, rem--) {
                 assigned[i] = (assigned[i] || 0) + 1;
             }
         } else {
             // sin base: repartir uniformemente para sumar cantidadParaComprar
             const n = centrosConDistribucion.length || 1;
             const per = Math.floor(cantidadParaComprar / n);
             let rem = cantidadParaComprar % n;
             assigned = centrosConDistribucion.map((_, idx) => idx < rem ? per + 1 : per);
         }

         centrosConDistribucion.forEach((centro, idx) => {
             let cantidadCentro = assigned[idx] || 0;
             centrosHtml += `
                 <div class="flex items-center justify-between bg-gray-50 px-2 py-1 rounded">
                     <span class="font-medium text-sm break-words">${centro.name_centro}</span>
                     <input type="number" name="productos[${rowKey}][centros][${centro.id}]" 
                            min="0" value="${cantidadCentro}" class="w-24 border rounded p-1 text-center ml-3 distribucion-centro"
                            data-rowkey="${rowKey}" onchange="actualizarTotal('${rowKey}', this)">
                 </div>
             `;
         });

         const precioNum = isNaN(precio) ? 0 : precio;
         // Formateo del precio original: si la moneda es COP mostrar directamente el precio formateado en COP
         let formattedOriginal = '';
         try {
             const cur = (precioCurrency || 'COP').toString().toUpperCase();
             if (cur === 'COP') {
                 formattedOriginal = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(precioNum);
             } else {
                 // mostrar el número tal cual si no podemos formatear la moneda original
                 try { formattedOriginal = new Intl.NumberFormat(undefined, { style: 'currency', currency: cur }).format(precioNum); }
                 catch(e) { formattedOriginal = precioNum.toFixed(2) + ' ' + cur; }
             }
         } catch (e) { formattedOriginal = precioNum.toFixed(2); }

         const row = document.createElement('tr');
         row.id = rowId;
         row.innerHTML = `
             <td class="p-3">
                 <div class="flex items-start gap-3">
                     <div class="flex-1 min-w-0">
                         <div class="font-semibold text-gray-800 product-name" title="${productoNombre}">${productoNombre} ${esDistribuido && proveedorId ? `<span class=\"text-xs text-gray-500\">(Distribuido)</span>`:''}</div>
                     </div>
                 </div>
<input type="hidden" name="productos[${rowKey}][id]" value="${productoId}" 
                      data-proveedor="${proveedorId||''}" data-unidad="${unidad}" data-nombre="${productoNombre}" data-cantidad="${cantidadOriginal}" data-iva="${iva}" data-price="${precio}" data-price-currency="${precioCurrency}">
                 <input type="hidden" name="productos[${rowKey}][proveedor_id]" value="${proveedorId||''}">
                 <input type="hidden" name="productos[${rowKey}][productoxproveedor_id]" id="productoxproveedor-${rowKey}" value="${productoxproveedorId || ''}">
                 <input type="hidden" name="productos[${rowKey}][trm_oc]" id="trm_oc-${rowKey}" value="">
                 <input type="hidden" name="productos[${rowKey}][iva]" value="${iva}">
                 <input type="hidden" name="productos[${rowKey}][price]" value="${precio}">
                 <input type="hidden" name="productos[${rowKey}][currency]" value="${precioCurrency}">
                 ${ocpId ? `<input type=\"hidden\" name=\"productos[${rowKey}][ocp_id]\" value=\"${ocpId}\">` : ``}
             </td>
             <td class="p-3 text-center align-middle">
                 <input type="number" name="productos[${rowKey}][cantidad]" min="1" value="${cantidadParaComprar}" 
                     class="w-16 border rounded p-1 text-center cantidad-total" 
                     id="cantidad-total-${rowKey}" 
                     onchange="onCantidadTotalChange('${rowKey}')" required>
             </td>
             <td class="p-3 text-center"><?php echo e($prod->unit_produc ?? '-'); ?></td>
             <td class="p-3 text-center" id="moneda-${rowKey}">${precioCurrency}</td>
             <td class="p-3 text-right whitespace-nowrap" id="precio-${rowKey}">
                <div>${formattedOriginal}</div>
                ${precioCurrency && precioCurrency.toUpperCase() !== 'COP' ? '<div class="text-xs text-gray-500 precio-cop-span"></div>' : ''}
             </td>
             <td class="p-3 text-center whitespace-nowrap" id="iva-${rowKey}">${iva}%</td>
<td class="p-3 text-center" id="entregado-stock-${rowKey}">${( (totalConfirmadoPorProducto[productoId] || 0) > 0 ? `${totalConfirmadoPorProducto[productoId]}` : '0' )}</td>
             <td class="p-3">
                 <div class="max-h-40 overflow-y-auto">
                     <div class="space-y-2 text-sm pr-1">
                         ${centrosHtml}
                     </div>
                 </div>
             </td>
             <td class="p-3 text-center align-middle">
                <div class="flex flex-col items-center gap-2">
                  <div class="text-sm font-medium text-gray-700">Aplicar IVA</div>
                  <label class="mt-1 inline-flex items-center">
                    <input type="checkbox" name="productos[${rowKey}][apply_iva]" class="apply-iva-checkbox form-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" value="1">
                  </label>
                  <button type="button" id="btn-quitar-${rowKey}" onclick="quitarProducto('${rowId}', '${rowKey}', ${ocpId?`'${ocpId}'`:'null'})" title="Quitar producto" class="btn-trash text-red-600" aria-label="Quitar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M9 3a1 1 0 00-.894.553L7 5H4a1 1 0 100 2h16a1 1 0 100-2h-3l-1.106-1.447A1 1 0 0015 3H9zM6 8a1 1 0 011 1v9a2 2 0 002 2h6a2 2 0 002-2V9a1 1 0 112 0v9a4 4 0 01-4 4H9a4 4 0 01-4-4V9a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>
             </td>
         `;
         table.appendChild(row);

        // Guardar providers JSON en el input oculto para poder restaurarlo al quitar
        try {
            const inputHiddenStored = row.querySelector('input[type="hidden"][name$="[id]"]');
            // preferir el providersJson pasado como parámetro (flujo modal), si no, intentar leer del selector
            let storedProviders = '';
            let storedPriceCop = '';
            try {
                const selOpt = selector?.options?.[selector.selectedIndex];
                storedProviders = selOpt?.dataset?.providers || '';
                storedPriceCop = selOpt?.dataset?.priceCop || '';
            } catch(e) { /* ignore */ }
            if (providersJson && String(providersJson).trim() !== '') storedProviders = providersJson;
            if (priceCop && String(priceCop).trim() !== '') storedPriceCop = priceCop;
            if (inputHiddenStored) inputHiddenStored.dataset.providers = storedProviders;
            if (inputHiddenStored) inputHiddenStored.dataset.priceCop = storedPriceCop || '';

            // Si se recibió un priceCop (precio ya convertido a COP), poblar el input trm_oc y la vista inmediatamente
            try {
                if (storedPriceCop && storedPriceCop !== '') {
                    const trmInput = document.getElementById(`trm_oc-${rowKey}`);
                    const priceCell = document.getElementById(`precio-${rowKey}`);
                    const spanCop = priceCell?.querySelector('.precio-cop-span');
                    const priceDiv = priceCell?.querySelector('div');
                    const numeric = parseLocalizedNumber(storedPriceCop) ?? 0;
                    const rounded = Math.round((numeric + Number.EPSILON) * 100) / 100;
                    if (trmInput) trmInput.value = rounded;
                    const formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rounded);
                    if (spanCop) {
                        // moneda original != COP: mostrar precio en COP en el span
                        spanCop.textContent = `COP ${formatted}`;
                    } else if (priceDiv) {
                        // moneda original es COP: actualizar la principal (evitar duplicado)
                        priceDiv.textContent = formatted;
                    }
                }
            } catch(e) { /* ignore */ }
        } catch(e) { /* ignore */ }
         
        // Actualizar precio mostrado incluyendo conversión a COP (async) — llamada segura
        if (typeof updatePriceToCOP === 'function') {
            try {
                const maybePromise = updatePriceToCOP(rowKey, precioNum, precioCurrency);
                if (maybePromise && typeof maybePromise.then === 'function') {
                    maybePromise.catch(()=>{});
                }
            } catch(e) { /* ignore */ }
        }
 
         productosAgregados.push(rowKey);

        // Establecer la cantidad inicial sin modificar la distribución
        const totalInput = document.getElementById(`cantidad-total-${rowKey}`);
        totalInput.value = cantidadParaComprar;

        // Quitar opción usada del selector
        if (esDistribuido && ocpId) {
            const opts = Array.from(selector.options);
            const idx = opts.findIndex(o => o.value == String(productoId) && (o.dataset.ocpId || '') == String(ocpId));
            if (idx > -1) selector.remove(idx);
        } else {
            for (let i = 0; i < selector.options.length; i++) {
                const o = selector.options[i];
                if (o.value == String(productoId) && !o.dataset.distribuido) {
                    selector.remove(i);
                    break;
                }
            }
        }
        selector.value = "";
    }

    // Helper robusto para parsear números localizados (e.g., 1.808.795,81 o 1,808,795.81)
    function parseLocalizedNumber(val){
        try {
            if (val === null || val === undefined) return null;
            if (typeof val === 'number') return isFinite(val) ? val : null;
            let s = String(val).trim();
            if (s === '') return null;
            // Mantener solo dígitos, coma, punto y signo
            s = s.replace(/[^0-9,\.\-]/g, '');
            if (s === '' || s === '-' ) return null;
            const hasComma = s.indexOf(',') > -1;
            const hasDot = s.indexOf('.') > -1;
            if (hasComma && hasDot) {
                // Asumir punto = miles, coma = decimales
                s = s.replace(/\./g,'').replace(/,/g,'.');
            } else if (hasComma && !hasDot) {
                // Solo coma => decimales
                s = s.replace(/,/g,'.');
            } else if (!hasComma && hasDot) {
                // Si hay múltiples puntos, asumir puntos de miles y eliminar todos menos el último como decimal
                if ((s.match(/\./g) || []).length > 1) {
                    const last = s.lastIndexOf('.');
                    s = s.slice(0, last).replace(/\./g,'') + '.' + s.slice(last+1);
                }
            }
            const n = Number(s);
            return isNaN(n) ? null : n;
        } catch (_) { return null; }
    }

    function actualizarTotal(rowKey) {
        // backward compatible: allow calling without changed element
        const changedInput = arguments[1] || null;
        const inputs = document.querySelectorAll(`input[name^="productos[${rowKey}][centros]"]`);
        const totalInput = document.getElementById(`cantidad-total-${rowKey}`);
        const maxAllowed = totalInput ? parseInt(totalInput.value || totalInput.getAttribute('data-base') || '0', 10) : 0;
        let total = 0;
        inputs.forEach(input => { total += parseInt(input.value) || 0; });
        if (maxAllowed && total > maxAllowed) {
            // reduce the changed input if provided, otherwise reduce the last input
            if (changedInput) {
                const current = parseInt(changedInput.value) || 0;
                // compute sum of others
                let others = 0;
                inputs.forEach(i => { if (i !== changedInput) others += parseInt(i.value) || 0; });
                const allowed = Math.max(0, maxAllowed - others);
                if (current > allowed) changedInput.value = allowed;
            } else {
                // fallback: shrink last input
                const last = inputs[inputs.length - 1];
                if (last) {
                    const excess = total - maxAllowed;
                    const curr = parseInt(last.value) || 0;
                    last.value = Math.max(0, curr - excess);
                }
            }
            // recompute total
            total = 0;
            inputs.forEach(input => { total += parseInt(input.value) || 0; });
        }
        if (totalInput) totalInput.value = total;
    }

    function distribuirAutomaticamente(rowKey) {
        const total = parseInt(document.getElementById(`cantidad-total-${rowKey}`)?.value) || 0;
        const inputs = Array.from(document.querySelectorAll(`input[name^="productos[${rowKey}][centros]"]`));
        if (inputs.length > 0 && total >= 0) {
            // distribuir proporcionalmente si hay valores base; si no, uniforme
            const base = inputs.map(i => parseInt(i.value)||0);
            const baseSum = base.reduce((a,b)=>a+b,0);
            let asignaciones = new Array(inputs.length).fill(0);
            if (baseSum > 0) {
                let asignado = 0;
                for (let i=0;i<inputs.length;i++) {
                    asignaciones[i] = Math.floor((base[i] / baseSum) * total);
                    asignado += asignaciones[i];
                }
                let resto = total - asignado;
                for (let i=0; i<inputs.length && resto>0; i++, resto--) asignaciones[i]++;
            } else {
                const porCentro = Math.floor(total / inputs.length);
                let resto = total % inputs.length;
                asignaciones = inputs.map((_, idx) => idx < resto ? porCentro + 1 : porCentro);
            }
            inputs.forEach((input,i)=> input.value = asignaciones[i]);
        }
    }

    function quitarProducto(rowId, rowKey, ocpId = null) {
        const row = document.getElementById(rowId);
        const selector = document.getElementById('producto-selector');
        if (row) {
            const inputHidden = row.querySelector('input[type="hidden"][name$="[id]"]');
            const proveedorId = inputHidden?.dataset?.proveedor || '';
            const unidad = inputHidden?.dataset?.unidad || '';
            const nombre = inputHidden?.dataset?.nombre || '';
            const cantidad = inputHidden?.dataset?.cantidad || 0;
            const iva = inputHidden?.dataset?.iva || 0;
            const price = inputHidden?.dataset?.price || 0;
            const esDistribuido = !!ocpId;
            const productoId = inputHidden?.value;

            row.remove();
            productosAgregados = productosAgregados.filter(k => k !== rowKey);

            // Volver a agregar la opción al selector
            const opt = document.createElement('option');
            opt.value = productoId;
            opt.dataset.proveedor = proveedorId;
            opt.dataset.unidad = unidad;
            opt.dataset.nombre = nombre;
            opt.dataset.cantidad = cantidad;
            opt.dataset.iva = iva;
            opt.dataset.price = price;
            if (esDistribuido) {
                opt.dataset.distribuido = '1';
                opt.dataset.ocpId = ocpId;
                const provName = proveedoresMap?.[proveedorId] || 'Proveedor';
                opt.textContent = `${nombre} - ${provName} - Cant: ${cantidad}`;
            } else {
                opt.textContent = `${nombre} (${unidad}) - Cantidad: ${cantidad}`;
            }
            // Restaurar lista de proveedores original si estaba guardada
            if (inputHidden?.dataset?.providers) opt.dataset.providers = inputHidden.dataset.providers;
            if (inputHidden?.dataset?.priceCurrency) opt.dataset.priceCurrency = inputHidden.dataset.priceCurrency;
             selector.appendChild(opt);
         }
     }

    function confirmarAnulacion(button) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción anulará la orden de compra.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                button.closest('form').submit();
            }
        });
    }

    // Validación al enviar con rowKey
    const ordenForm = document.getElementById('orden-form');
    ordenForm.addEventListener('submit', async function(e) {
        // Si hay salidas pendientes de confirmar, impedir continuar y avisar que se debe esperar la confirmación del usuario
        if (window.haySalidasPendientes) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Acción bloqueada', text: 'Espere la confirmación de recibido del usuario para continuar.' });
            return;
        }

        // Si no hay productos agregados, intentar añadir el producto seleccionado automáticamente
        if (productosAgregados.length === 0) {
            const sel = document.getElementById('producto-selector');
            const opt = sel?.options?.[sel.selectedIndex];
            if (opt && opt.value) {
                // intentar leer lista de proveedores del option
                let provs = [];
                try { provs = JSON.parse(opt.dataset.providers || '[]'); } catch(_) { provs = []; }
                // Si no hay proveedores asociados, agregar directamente
                if (!provs || provs.length === 0) {
                    try { agregarProducto(); } catch(_) { }
                } else if (provs.length === 1) {
                    // Si hay exactamente 1 proveedor, usarlo automáticamente
                    const p = provs[0];
                    const provId = p.proveedor_id ?? p.proveedorId ?? p.id;
                    const price = p.price_produc ?? p.price ?? 0;
                    const currency = p.moneda ?? p.currency ?? 'COP';
                    // calcular precio en COP inmediatamente
                    let priceCop = '';
                    try {
                        const r = getExchangeRateSync(String(currency||'COP'), 'COP');
                        if (r) priceCop = String(Number(price||0) * Number(r));
                    } catch(_) {}
                    const productoId = opt.value;
                    const productoNombre = opt.dataset.nombre || '';
                    const unidad = opt.dataset.unidad || '';
                    const cantidadOriginal = parseInt(opt.dataset.cantidad || '1', 10);
                    const ocpId = opt.dataset.ocpId || null;
                    try {
                        // compute rowKey as other flows do
                        const rowKey = `${productoId}-${ocpId||'0'}`;
                        // remove option so it no longer appears
                        try { opt.remove(); } catch(e) {}
                        agregarProductoFinal(rowKey, productoId, productoNombre, provId, cantidadOriginal, sel, ocpId, opt.dataset.distribuido === '1', parseFloat(opt.dataset.iva || '0'), Number(price||0), currency, opt.dataset.providers || '', priceCop);
                    } catch(e) { console.error('auto add single prov failed', e); }
                } else {
                    // Múltiples proveedores: abrir modal y cancelar envío
                    e.preventDefault();
                    try { openProvidersModal(); } catch(_) {}
                    Swal.fire({ icon: 'info', title: 'Seleccione proveedor', text: 'Elija un proveedor antes de crear la orden.' });
                    return;
                }
            }
        }

        // Re-evaluar productosAgregados después del intento automático
        if (productosAgregados.length ===  0) {
            e.preventDefault();
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Debe añadir al menos un producto.'});
            return;
        }

        const mismatches = [];

        productosAgregados.forEach(key => {
            const totalInput = document.getElementById(`cantidad-total-${key}`);
            const expected = parseInt(totalInput?.value) || 0;
            const distribucionInputs = Array.from(document.querySelectorAll(`input[name^="productos[${key}][centros]"]`));
            let distribucionTotal = 0;
            const detalles = [];
            distribucionInputs.forEach(input => {
                const val = parseInt(input.value) || 0;
                distribucionTotal += val;
                // obtener nombre del centro desde la etiqueta en la misma estructura
                const label = input.parentElement?.querySelector('label')?.textContent?.trim() || '';
                const centroName = label.replace(/:$/,'');
                detalles.push({ centro: centroName, cantidad: val });
            });

            if (expected < 1) {
                mismatches.push({ key, type: 'invalid', expected, distribucionTotal, detalles });
            } else if (expected !== distribucionTotal) {
                mismatches.push({ key, type: 'mismatch', expected, distribucionTotal, detalles });
            }
        });

        if (mismatches.length > 0) {
            e.preventDefault();
            // Construir lista simple de productos con mismatch
            const items = mismatches.map(m => {
                const nameInput = document.querySelector(`input[name="productos[${m.key}][id]"]`);
                const prodName = nameInput?.dataset?.nombre || m.key;
                return `<li style="margin-bottom:6px;">${prodName} - La cantidad no concuerda con la distribución</li>`;
            }).join('');
            const html = `<div class="text-left"><p>Corrija los siguientes productos:</p><ul style="text-align:left;margin-top:8px;">${items}</ul></div>`;
            Swal.fire({ icon: 'error', title: 'Error de validación', html: html });
            return;
        }

         // Asegurar que todos los inputs hidden trmm_oc-... estén calculados (espera las conversiones async)
         await ensureTrmInputsFilled();
    });

    function configurarAutoCargaProveedor() {
        const productoSelector = document.getElementById('producto-selector');
        const proveedorSelect = document.getElementById('proveedor_id');
        productoSelector.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.proveedor) {
                proveedorSelect.value = selectedOption.dataset.proveedor;
            }
            const ivaSpan = document.getElementById('producto-iva');
            if (ivaSpan) ivaSpan.textContent = `${parseFloat(selectedOption?.dataset?.iva || '0')}%`;
        });
    }

    // Exponer funciones al scope global
    window.agregarProductoFinal = agregarProductoFinal;
    // Exponer atajo para debug y compatibilidad con handlers inline
    try { window.agregarProducto = agregarProducto; } catch(e) {}

    // openProvidersModal: mostrar modal de proveedores o auto-agregar si no hay proveedores
    async function openProvidersModal(){
        const sel = document.getElementById('producto-selector');
        if (!sel) { 
            Swal.fire({icon:'info', title:'Error', text:'No se encontró el selector.'}); 
            return; 
        }

        // Get the selected option
        let opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) {
            opt = Array.from(sel.options).find(function(o) { return o.value; });
        }

        if (!opt || !opt.value) { 
            Swal.fire({icon:'info', title:'Seleccione', text:'Seleccione un producto primero.'}); 
            return; 
        }

        // Parse providers
        const provsJson = opt.dataset.providers || '[]';
        let provs = [];
        try { provs = JSON.parse(provsJson); } catch(e){ provs = []; }
        
        // If no providers, auto-add product
        if (!provs || provs.length === 0) {
            try {
                const productoId = opt.value;
                const productoNombre = opt.dataset.nombre || '';
                const cantidadOriginal = parseInt(opt.dataset.cantidad || '1', 10);
                const ocpId = opt.dataset.ocpId || null;
                const rowKey = productoId + '-' + (ocpId || '0');
                const providersJson = opt.dataset.providers || '';
                
                try { opt.remove(); } catch(e){}
                
                agregarProductoFinal(rowKey, productoId, productoNombre, '', cantidadOriginal, sel, ocpId, opt.dataset.distribuido === '1', parseFloat(opt.dataset.iva || '0'), Number(opt.dataset.price || 0), (opt.dataset.priceCurrency || 'COP'), providersJson, '');
                try { Swal.fire({icon:'success', title:'Añadido', text:'Producto agregado.'}); } catch(_){}
            } catch(e){ console.error('Error adding product:', e); }
            return;
        }
        
        // If providers exist, show modal (show simple provider list)
        const container = document.getElementById('prov-list');
        container.innerHTML = '';
        
        // Show one option per provider
        provs.forEach(function(p, idx) {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between p-2 border rounded';
            const radioId = 'prov_choice_' + idx;
            const price = Number(p.price_produc || 0);
            
            const labelHtml = '<label class="flex items-center gap-3 w-full" for="' + radioId + '">' +
                '<input type="radio" name="prov_choice" id="' + radioId + '" value="' + p.proveedor_id + '" ' + (idx === 0 ? 'checked' : '') + '>' +
                '<div class="flex-1">' +
                '<div class="font-medium">' + (p.prov_name || 'Proveedor') + '</div>' +
                '<div class="text-xs text-gray-500">Precio: ' + price + ' ' + (p.moneda || 'COP') + '</div>' +
                '</div>' +
                '</label>';
            
            div.innerHTML = labelHtml;
            container.appendChild(div);
        });
        
        // Show modal
        const modal = document.getElementById('modal-proveedores');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }
    // Exponer al global
    window.openProvidersModal = openProvidersModal;

    // Modal: abrir, cerrar, validar y guardar por AJAX
    document.addEventListener('DOMContentLoaded', function() {
        configurarAutoCargaProveedor();
        // Confirmar guardar sin observación en cada fila (y forzar fecha)
        document.querySelectorAll('.oc-basicos-form').forEach(function(form){
            form.addEventListener('submit', async function(e){
                if (form.dataset.confirmed === '1') return; // evitar doble envío
                const dateInp = form.querySelector('input[name="date_oc"]');
                const obsInp = form.querySelector('input[name="observaciones"]');
                const dateVal = (dateInp?.value || '').trim();
                const obsVal = (obsInp?.value || '').trim();
                if (!dateVal) {
                    e.preventDefault();
                    Swal.fire({ icon:'warning', title:'Fecha obligatoria', text:'Ingrese la fecha de la OC.' });
                    return;
                }
                if (!obsVal) {
                    e.preventDefault();
                    const res = await Swal.fire({
                        icon:'question',
                        title:'Guardar sin observación',
                        text:'La observación está vacía. ¿Desea guardar así?',
                                               showCancelButton:true,
                        confirmButtonText:'Sí, guardar',
                        cancelButtonText:'Cancelar'
                    });
                    if (res.isConfirmed) { form.dataset.confirmed = '1'; form.submit(); }
                }
            });
        });
        // Bloquear descarga si no hay datos o si existen salidas pendientes por confirmar
        const btnZip = document.getElementById('btn-download-zip');
        if (btnZip) {
            // Utilidad para revisar faltantes en la tabla
            function getOCBasicosStatus(){
                let missingDate = 0, missingObs = 0;
                const rows = document.querySelectorAll('#ordenes-table tbody tr');
                rows.forEach(tr => {
                    const cell = tr.querySelector('td:nth-child(6)');
                    if (!cell) return;
                    if (cell.querySelector('.oc-basicos-form')) { // ambos faltan
                        missingDate++; missingObs++;
                        return;
                    }
                    const txt = (cell.textContent || '').toLowerCase();
                    if (txt.includes('sin fecha')) missingDate++;
                    if (txt.includes('sin observación') || txt.includes('sin observacion')) missingObs++;
                });
                return { missingDate, missingObs };
            }
            btnZip.addEventListener('click', async function(e){
                e.preventDefault();
                if ((this.dataset?.hay || '0') !== '1') {
                    Swal.fire({ icon:'info', title:'Sin datos', text:'No hay órdenes para descargar.' });
                    return;
                }
                const status = getOCBasicosStatus();
                if (status.missingDate > 0) {
                    Swal.fire({ icon:'warning', title:'Falta fecha', text:'Hay órdenes sin fecha de OC. Complete las fechas antes de descargar.' });
                    return;
                }
                // Nota: permitir descarga aunque falten observaciones (sin alerta)
                try {
                    showStockLoader('Generando hashes y preparando descarga...');
                    const resp = await fetch(`<?php echo e(route('ordenes_compra.ensure_hashes', $requisicion->id)); ?>`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({})
                    });
                    const data = await resp.json();
                    hideStockLoader();
                    if (!resp.ok) throw new Error(data.message || 'Error preparando la descarga');
                    const href = this.getAttribute('href');
                    if (href) window.location.href = href;
                } catch ( err) {
                    hideStockLoader();
                    Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Ocurrió un error al preparar la descarga.' });
                }
            });
        }
        const modal = document.getElementById('modal-distribucion');
        const btnAbrir = document.getElementById('btn-abrir-modal');
        const btnCerrar = document.getElementById('btn-cerrar-modal');
        const btnCancelar = document.getElementById('btn-cancelar-modal');
        const selectProd = document.getElementById('dist-producto-id');
        const spanMax = document.getElementById('dist-max');
        const spanTotalMax = document.getElementById('dist-total-max');
        const spanUnidad = document.getElementById('dist-unidad');
        const tbody = document.getElementById('tabla-items-dist');
        const spanTotal = document.getElementById('dist-total');
        const btnAddFila = document.getElementById('btn-add-fila');
        const btnGuardar = document.getElementById('btn-guardar-dist');

        function abrirModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
        function cerrarModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); limpiarModal(); }

        btnAbrir?.addEventListener('click', abrirModal);
        btnCerrar?.addEventListener('click', cerrarModal);
        btnCancelar?.addEventListener('click', cerrarModal);

        // Deshabilitar "+ Agregar" hasta seleccionar producto
        if (btnAddFila) btnAddFila.disabled = true;

        selectProd.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const max = parseInt(opt?.dataset?.max || '0', 10);







            spanMax.textContent = max;
            spanTotalMax.textContent = max;
                       spanUnidad.textContent = opt?.dataset?.unidad || '';
            calcularTotal();
            if (btnAddFila) btnAddFila.disabled = !this.value || (parseInt(spanTotal.textContent||'0',10) >= max);
        });

        btnAddFila.addEventListener('click', function() {
            const max = parseInt(spanTotalMax.textContent || '0', 10);
            const totalActual = parseInt(spanTotal.textContent || '0', 10);
            const restante = max - totalActual;
            if (!selectProd.value) {
                Swal.fire({icon: 'info', title: 'Seleccione', text: 'Debe elegir un producto antes de añadir cantidades', confirmButtonText: 'Cerrar'});
                return;
            }
            if (restante <= 0) {
                               Swal.fire({icon: 'info', title: 'Cantidad completa', text: 'Ya alcanzó la cantidad total, no puede añadir más segmentos.', confirmButtonText: 'Cerrar'});
                return;
            }
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td class="p-2 text-center">
                    <input type="number" min="1" max="${restante}" value="${Math.min(1, restante)}" class="w-24 border rounded p-1 text-center cant-item" />
                </td>
                <td class="p-2 text-center">
                    <button type="button" class="px-2 py-1 bg-red-500 text-white rounded text-sm btn-del-fila">✕</button>
                </td>
            `;
            tbody.appendChild(fila);
            fila.querySelector('.btn-del-fila').addEventListener('click', function(){ fila.remove(); calcularTotal(); });
            fila.querySelector('.cant-item').addEventListener('input', function(e){ onCantInput(e.target); });
            calcularTotal();
        });

        function onCantInput(inp){
            const max = parseInt(spanTotalMax.textContent || '0', 10);
            let totalOtros = 0;
            tbody.querySelectorAll('.cant-item').forEach(el => { if (el !== inp) totalOtros += (parseInt(el.value)||0); });
            const permitido = Math.max(0, max - totalOtros);
            let val = parseInt(inp.value)||0;
            if (val < 1 && permitido > 0) val = 1;
            if (val > permitido) val = permitido;
            inp.value = val;
            calcularTotal();
        }

        function calcularTotal(){
            const max = parseInt(spanTotalMax.textContent || '0', 10);
            let total = 0;
            tbody.querySelectorAll('.cant-item').forEach(inp => total += (parseInt(inp.value)||0));
            spanTotal.textContent = total;
            if (btnAddFila) btnAddFila.disabled = !selectProd.value || total >= max;
            const restante = Math.max(0, max - total);
            tbody.querySelectorAll('.cant-item').forEach(inp => {
                const actual = parseInt(inp.value)||0;
                inp.max = actual + restante;
            });
        }

        function limpiarModal(){
            selectProd.value = '';
            spanMax.textContent = '0';
            spanTotalMax.textContent = '0';
            spanUnidad.textContent = '';
            tbody.innerHTML = '';
            spanTotal.textContent = '0';
            if (btnAddFila) btnAddFila.disabled = true;
        }

        btnGuardar.addEventListener('click', async function(){
            const prodId = selectProd.value;
            const max = parseInt(spanTotalMax.textContent || '0', 10);
            let total = 0;
            const filas = Array.from(tbody.querySelectorAll('tr'));

            if (!prodId) {
                Swal.fire({icon: 'warning', title: 'Atención', text: 'Seleccione un producto'});
                return;
            }

            const distribucionData = [];
            for (const tr of filas){
                const cant = parseInt(tr.querySelector('.cant-item').value||'0', 10);
                if (cant <= 0){
                    Swal.fire({icon: 'warning', title: 'Atención', text: 'Ingrese una cantidad válida (> 0)'});
                    return;
                }
                total += cant;
                distribucionData.push({cantidad: cant}); // sin proveedor
            }

            if (total !== max){
                Swal.fire({icon: 'error', title: 'Error', text: 'El total distribuido debe ser igual a la cantidad disponible'});
                return;
            }

            try {
                const resp = await fetch(`<?php echo e(route('ordenes_compra.distribuirProveedores')); ?>`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ producto_id: prodId, requisicion_id: <?php echo e($requisicion->id); ?>, distribucion: distribucionData, comentario: null })
                });
                const data = await resp.json();
                if (!resp.ok) throw new Error(data.message || 'Error al guardar la distribución');

                cerrarModal();
                Swal.fire({icon:'success', title:'Producto(s) añadidos', text:'La distribución se guardó. Se actualizará la página para mostrar las líneas distribuidas.', confirmButtonText:'Aceptar'}).then(()=> {
                    location.reload();
                });
            } catch (e) {
                Swal.fire({icon:'error', title:'Error', text: e.message});
            }
        });

        // Modal Deshacer Distribución
        const modalUndo = document.getElementById('modal-undo-distribucion');
        const btnAbrirUndo = document.getElementById('btn-abrir-undo-dist');
        const btnCerrarUndo = document.getElementById('btn-cerrar-undo');
        const btnCancelarUndo = document.getElementById('btn-cancelar-undo');
        const btnConfirmarUndo = document.getElementById('btn-confirmar-undo');

        btnAbrirUndo.addEventListener('click', function() {
            modalUndo.classList.remove('hidden');
            modalUndo.classList.add('flex');
        });

        btnCerrarUndo.addEventListener('click', function() {
            modalUndo.classList.add('hidden');
            modalUndo.classList.remove('flex');
        });

        btnCancelarUndo.addEventListener('click', function() {
            modalUndo.classList.add('hidden');
            modalUndo.classList.remove('flex');
        });

        // Seleccionar/Deseleccionar todos
        const chkAll = document.getElementById('chk-undo-all');
        chkAll?.addEventListener('change', function() {
            const checked = this.checked;
            document.querySelectorAll('.chk-undo-item').forEach(chk => {
                chk.checked = checked;
            });
        });

        btnConfirmarUndo.addEventListener('click', async function() {
            const idsSeleccionados = [];
            document.querySelectorAll('.chk-undo-item:checked').forEach(chk => {
                const raw = (chk.dataset?.ocpIds || '').split(',').map(s => s.trim()).filter(Boolean);
                idsSeleccionados.push(...raw);
            });
            if (idsSeleccionados.length === 0) {
                Swal.fire({icon: 'info', title: 'Sin selección', text: 'Seleccione al menos una línea para deshacer la distribución.'});
                return;
            }

            const confirm = await Swal.fire({
                title: 'Confirmar deshacer',
                text: "Esto deshará la distribución seleccionada(s) y actualizará la orden.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, deshacer',
                cancelButtonText: 'Cancelar'
            });

            if (confirm.isConfirmed) {
                try {
                    const resp = await fetch(`<?php echo e(route('ordenes_compra.undoDistribucion')); ?>`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ requisicion_id: <?php echo e($requisicion->id); ?>, ocp_ids: idsSeleccionados, comentario: null })
                    });
                    const data = await resp.json();
                    if (!resp.ok) throw new Error(data.message || 'Error al deshacer distribución');

                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'La distribución se deshizo correctamente.',
                        confirmButtonText: 'Aceptar'
                    }).then(() => { location.reload(); });
                } catch (e) {
                    Swal.fire({icon:'error', title:'Error', text: e.message});
                }
            }
        });

        // Enlazar botones 'Ver proveedores' a la implementación global (definida más abajo)
        document.getElementById('btn-ver-proveedores')?.addEventListener('click', function(){
            if (typeof window.openProvidersModal === 'function') return window.openProvidersModal();
            Swal.fire({icon:'info', title:'Seleccione', text:'Seleccione un producto primero.'});
        });
        // Nota: no se agrega listener para 'btn-add-product' aquí porque el botón ya tiene un onclick inline que invoca openProvidersModal(). Attaching an extra listener caused double invocation
        // (add -> inline handler -> openProvidersModal adds product when no providers -> attached
        // listener runs again and re-opens modal). Keeping only the inline onclick prevents that.

        // Cuando se confirma un proveedor elegido, propagar currency al option (se asume handler global más abajo)
        document.getElementById('btn-select-prov')?.addEventListener('click', function(){
            // fallback: if global handler exists it will run; otherwise, try to perform a minimal propagation
            if (typeof window.handleSelectProv === 'function') return window.handleSelectProv();
            const sel = document.getElementById('producto-selector');
            const opt = sel.options[sel.selectedIndex];
            if (!opt || !opt.value) { Swal.fire({icon:'info', title:'Error', text:'No hay producto seleccionado.'}); return; }
            const chosen = document.querySelector('input[name="prov_choice"]:checked');
            if (!chosen) { Swal.fire({icon:'info', title:'Error', text:'Seleccione un proveedor.'}); return; }
            const provId = chosen.value;
            const price = chosen.dataset.price || 0;
            const currency = chosen.dataset.currency || 'COP';
            // calcular priceCop si aún no está en el dataset
            let priceCop = chosen.dataset.priceCop || '';
            if (!priceCop) {
                try {
                    const r = getExchangeRateSync(String(currency||'COP'), 'COP');
                    if (r) priceCop = String(Number(price||0) * Number(r));
                } catch(_) {}
            }
            const provSelect = document.getElementById('proveedor_id');
            if (provSelect) provSelect.value = provId;

            // Gather product info from the selected option BEFORE removing it
            const productoId = opt.value;
            const productoNombre = opt.dataset.nombre || '';
            const unidad = opt.dataset.unidad || '';
            const cantidadOriginal = parseInt(opt.dataset.cantidad || '1', 10);
            const ocpId = opt.dataset.ocpId || null;
            const rowKey = `${productoId}-${ocpId||'0'}`;
            const providersJson = opt.dataset.providers || '';

            // remove option from selector so it no longer appears
            try { opt.remove(); } catch(e) { /* ignore */ }

            // Close modal and add the product row directly with provider info
            if (typeof hideProvidersModal === 'function') hideProvidersModal();
            agregarProductoFinal(rowKey, productoId, productoNombre, provId, cantidadOriginal, sel, ocpId, opt.dataset.distribuido === '1', parseFloat(opt.dataset.iva || '0'), Number(price||0), currency, providersJson, priceCop);
        });
    });

    function onCantidadTotalChange(rowKey) {
        const totalInput = document.getElementById(`cantidad-total-${rowKey}`);
        let nuevaCantidad = parseInt(totalInput?.value) || 0;
        if (nuevaCantidad < 1) nuevaCantidad = 1;
        totalInput.value = nuevaCantidad;
        // If distribution sum exceeds new total, reduce last inputs to fit
        const inputs = Array.from(document.querySelectorAll(`input[name^="productos[${rowKey}][centros]"]`));
        let sum = inputs.reduce((s,i)=> s + (parseInt(i.value)||0), 0);
        if (sum > nuevaCantidad) {
            let excess = sum - nuevaCantidad;
            // reduce from the last input backwards
            for (let i = inputs.length -1; i >=0 && excess>0; i--) {
                const val = parseInt(inputs[i].value)||0;
                const reduce = Math.min(val, excess);
                inputs[i].value = Math.max(0, val - reduce);
                excess -= reduce;
            }
            // update displayed total
            actualizarTotal(rowKey);
        }
    }

    // Fallback ligero para el modal de entrega parcial (simplificado)
    document.addEventListener('click', function(e){
        const overlay = document.getElementById('modal-entrega-parcial');
        if (!overlay) return;
        const openBtn = e.target.closest('#btn-abrir-entrega-parcial');
        const closeBtn = e.target.closest('#ep-close') || e.target.closest('#ep-cancel');
        if (openBtn) {
            e.preventDefault();
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            return;
        }
        if (closeBtn || e.target === overlay) {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }
    });

    // Llamadas a bindRestore protegidas (si la función existe)
    try {
        if (typeof bindRestore === 'function') {
            bindRestore(tbody);
            bindRestore(tbodySalidas);
        }
    } catch(e) { console.warn('bindRestore guardado: ', e); }

    

    // Si el servidor creó la orden y devolvió el hash en sesión, descargarlo automáticamente
    <?php if(session('created_hash')): ?>
    (function(){
        try {
            const hash = <?php echo json_encode(session('created_hash')); ?>;
            const orderId = <?php echo json_encode(session('created_order_id') ?? ''); ?>;
            const filename = orderId ? `orden_${orderId}_validation_hash.txt` : 'validation_hash.txt';
            const content = `Validation Hash: ${hash}\nOrder ID: ${orderId || 'N/A'}\nGenerated: ${new Date().toISOString()}`;
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
            Swal.fire({ icon: 'success', title: 'Hash descargado', text: 'Se ha descargado el hash de validación. Guárdelo para futuras verificaciones.' });
        } catch (e) {
            console.error('Error descargando hash:', e);
        }
    })();
    <?php endif; ?>

    // CACHE + helper para obtener tasa de cambio en tiempo real usando open.er-api.com (sin API key)
    const exchangeCache = {};
    const exchangeBaseCache = {}; // cache completo por base (rates map)

    // Seed TRM desde servidor: objeto con filas recientes de la tabla `trm`
    const serverTrmRows = <?php echo json_encode($trmLatest ?? [], 15, 512) ?>;
    // trmMap: moneda => price (units per 1 USD)
    const trmMap = {};
    try { (serverTrmRows || []).forEach(r => { if (r && r.moneda) trmMap[String(r.moneda).toUpperCase()] = Number(r.price); }); } catch(e) { /* noop */ }
    // Asegurar fallback razonable para USD
    if (trmMap['USD'] == null) { trmMap['USD'] = 1; }

    // Evitar llamadas externas: getExchangeRate consultará solo trmMap
    async function getExchangeRate(from = 'USD', to = 'COP', retries = 0, timeout = 3000){
        from = (from || 'COP').toUpperCase();
        to = (to || 'COP').toUpperCase();
        if (from === to) return 1;
        const key = `${from}_${to}`;
        if (exchangeCache[key]) return exchangeCache[key];

        // Intentar obtener precios desde trmMap
        const pFrom = trmMap[from] ?? null;
        const pTo = trmMap[to] ?? null;
        if (pFrom != null && pTo != null && Number(pFrom) !== 0) {
            const rate = Number(pTo) / Number(pFrom);
            exchangeCache[key] = rate;
            return rate;
        }

        // Si no hay datos suficientes en trmMap, devolver null (no intentar API externa)
        return null;
    }

    // Helper síncrono usando trmMap para obtener una tasa inmediata
    function getExchangeRateSync(from = 'USD', to = 'COP'){
        try {
            from = (from || 'COP').toUpperCase();
            to = (to || 'COP').toUpperCase();
            if (from === to) return 1;
            const pFrom = trmMap[from];
            const pTo = trmMap[to];
            if (pFrom != null && pTo != null && Number(pFrom) !== 0) {
                return Number(pTo) / Number(pFrom);
            }
        } catch(e) { /* noop */ }
        return null;
    }

    // Convierte precio unitario a COP y guarda en el campo hidden trm_oc-{rowKey}; actualiza la vista del precio en COP.
    async function updatePriceToCOP(rowKey, price, currency = 'COP'){
        try {
            const pk = String(rowKey || '');
            const input = document.getElementById(`trm_oc-${pk}`);
            const span = document.querySelector(`#precio-${pk} .precio-cop-span`);

            currency = (currency || 'COP').toString().trim().toUpperCase();
            const numPrice = Number(price || 0);

            // Solo respetar un valor existente si la moneda del producto es COP; de lo contrario, forzar recálculo
            try {
                const prodIdInput = document.querySelector(`input[name="productos[${pk}][id]"]`);
                const prodCurrency = (prodIdInput?.dataset?.priceCurrency || prodIdInput?.dataset?.pricecurrency || prodIdInput?.dataset?.currency || 'COP').toString().toUpperCase();
                if (input && input.value !== '' && !isNaN(Number(input.value)) && prodCurrency === 'COP') {
                    const existing = Number(input.value);
                    if (span) span.textContent = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP' }).format(existing);
                    return existing;
                }
            } catch(e){ /* ignore and continue */ }

            // Intentar usar priceCop almacenado (ya en COP)
            try {
                const prodIdInput = document.querySelector(`input[name="productos[${pk}][id]"]`);
                const stored = prodIdInput?.dataset?.priceCop || prodIdInput?.dataset?.pricecop || '';
                if (stored && String(stored).trim() !== '') {
                    const numeric = parseLocalizedNumber(stored) ?? 0;
                    const rounded = Math.round((numeric + Number.EPSILON) * 100) / 100;
                    try {
                        input.value = rounded;
                        const span = document.querySelector(`#precio-${pk} .precio-cop-span`);
                        const formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rounded);
                        if (span) span.textContent = `COP ${formatted}`;
                    } catch(e){}
                    return rounded;
                }
            } catch(e){ /* ignore */ }

            if (currency === 'COP' || !currency) {
                const rounded = Math.round((Number(price || 0) + Number.EPSILON) * 100) / 100;
                if (input) input.value = rounded;
                if (span) {
                    const formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rounded);
                    span.textContent = `COP ${formatted}`;
                } else {
                    // si no existe el span (moneda COP), actualizar el primer div con el formato COP
                    const priceDiv = document.querySelector(`#precio-${pk} div`);
                    if (priceDiv) { priceDiv.textContent = (new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP' }).format(rounded)); }
                }
                return rounded;
            }

            // Obtener tasa y convertir a COP
            let rate = getExchangeRateSync(currency, 'COP');
            if (!rate) {
                try { rate = await getExchangeRate(currency, 'COP'); } catch(e){ rate = null; }
                if (!rate) {
                    try { rate = await getExchangeRate(currency, 'COP', 3, 8000); } catch(e){ rate = null; }
                }
            }

            if (!rate) {
                if (span) span.textContent = '';
                // No establecer el valor oculto cuando no hay tasa para evitar guardar un precio en moneda extranjera
                return null;
            }

            // Guardar la tasa (COP por 1 unidad de la moneda) en el hidden trm_oc-{rowKey}
            const trmValue = Math.round((Number(rate) + Number.EPSILON) * 100) / 100;
            if (input) input.value = trmValue;

            // Calcular precio unitario en COP usando la tasa
            const copUnit = Math.round((Number(price || 0) * Number(rate) + Number.EPSILON) * 100) / 100;
            if (span) {
                const formattedUnit = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(copUnit);
                const formattedRate = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(trmValue);
                span.textContent = `COP ${formattedUnit} (1 ${currency} = ${formattedRate})`;
            } else {
                // si no existe el span (moneda COP), actualizar el primer div con el formato COP
                const priceDiv = document.querySelector(`#precio-${pk} div`);
                if (priceDiv) {
                    const formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(copUnit);
                    priceDiv.textContent = formatted + ` (1 ${currency} = ` + (new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP' }).format(trmValue)) + `)`;
                }
            }
            return copUnit;
        } catch (err) {
            console.warn('updatePriceToCOP error', err);
            // No escribir el precio original en el hidden para evitar guardar moneda extranjera
            return null;
        }
    }

    // Asegura que todos los inputs hidden trm_oc-... estén completados (espera las conversiones async)
    async function ensureTrmInputsFilled(timeoutPer = 3000){
        try {
            const hiddenInputs = Array.from(document.querySelectorAll('input[id^="trm_oc-"]'));
            const promises = hiddenInputs.map(input => {
                return new Promise(async (resolve) => {
                    try {
                        const pk = input.id.replace('trm_oc-','');
                        if (input.value !== '' && !isNaN(Number(input.value))) { return resolve(true); }
                        const prodIdInput = document.querySelector(`input[name="productos[${pk}][id]"]`);
                        const stored = prodIdInput?.dataset?.priceCop || prodIdInput?.dataset?.pricecop || '';
                        if (stored && String(stored).trim() !== '') {
                            const numeric = parseLocalizedNumber(stored) ?? 0;
                            const rounded = Math.round((numeric + Number.EPSILON) * 100) / 100;
                            try {
                                input.value = rounded;
                                const span = document.querySelector(`#precio-${pk} .precio-cop-span`);
                                const formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rounded);
                                if (span) span.textContent = `COP ${formatted}`;
                            } catch(e){}
                            return resolve(true);
                        }
                        // si no hay priceCop, intentar leer price+currency y convertir
                        let price = Number(prodIdInput?.dataset?.price || 0);
                        let currency = (prodIdInput?.dataset?.priceCurrency || prodIdInput?.dataset?.pricecurrency || prodIdInput?.dataset?.currency || 'COP');
                        if (!price || price === 0) {
                            const precioDiv = document.querySelector(`#precio-${pk} div`);
                            if (precioDiv) {
                                const numeric = parseLocalizedNumber(precioDiv.textContent);
                                price = numeric ?? 0;
                            }
                        }
                        let settled = false;
                        const p = updatePriceToCOP(pk, price, currency).then((res)=>{ settled = true; resolve(Boolean(res)); }).catch(()=>{ settled = true; resolve(false); });
                        setTimeout(()=>{ if(!settled) resolve(false); }, timeoutPer);
                    } catch(e){ resolve(false); }
                });
            });
            await Promise.all(promises);
        } catch(e){ /* noop */ }
    }

    // openProvidersModal moved BEFORE DOMContentLoaded
    
    
    // Función global para confirmar proveedor seleccionado (con guardia y auto-selección)
    window.handleSelectProv = function(){
        if (window.__provSelectBusy) return;
        window.__provSelectBusy = true;
        try {
            const sel = document.getElementById('producto-selector');
            const opt = sel?.options?.[sel.selectedIndex];
            if (!opt || !opt.value) { Swal.fire({icon:'info', title:'Error', text:'No hay producto seleccionado.'}); return; }
            const modal = document.getElementById('modal-proveedores');
            let chosen = modal?.querySelector('input[name="prov_choice"]:checked');
            if (!chosen) {
                chosen = modal?.querySelector('input[name="prov_choice"]');
                if (chosen) chosen.checked = true;
            }
            if (!chosen) { Swal.fire({icon:'info', title:'Error', text:'Seleccione un proveedor.'}); return; }
            // No permitir elegir una opción vacía (sin proveedor)
            if (!chosen.value || String(chosen.value).trim() === '') {
                Swal.fire({icon:'warning', title:'Sin proveedor', text:'No hay proveedores válidos para este producto.'});
                return;
            }
            const provId = chosen.value;
            const price = chosen.dataset.price || 0;
            const currency = chosen.dataset.currency || 'COP';
            // calcular priceCop si aún no está en el dataset
            let priceCop = chosen.dataset.priceCop || '';
            if (!priceCop) {
                try {
                    const r = getExchangeRateSync(String(currency||'COP'), 'COP');
                    if (r) priceCop = String(Number(price||0) * Number(r));
                } catch(_) {}
            }
            const provSelect = document.getElementById('proveedor_id');
            if (provSelect) provSelect.value = provId;

            // Gather product info from the selected option BEFORE removing it
            const productoId = opt.value;
            const productoNombre = opt.dataset.nombre || '';
            const unidad = opt.dataset.unidad || '';
            const cantidadOriginal = parseInt(opt.dataset.cantidad || '1', 10);
            const ocpId = opt.dataset.ocpId || null;
            const rowKey = `${productoId}-${ocpId||'0'}`;
            const providersJson = opt.dataset.providers || '';

            // remove option from selector so it no longer appears
            try { opt.remove(); } catch(e) { /* ignore */ }

            // Close modal and add the product row directly with provider info
            if (typeof hideProvidersModal === 'function') hideProvidersModal();
            agregarProductoFinal(rowKey, productoId, productoNombre, provId, cantidadOriginal, sel, ocpId, opt.dataset.distribuido === '1', parseFloat(opt.dataset.iva || '0'), Number(price||0), currency, providersJson, priceCop);
        } finally {
            setTimeout(()=>{ window.__provSelectBusy = false; }, 500);
        }
    };

    // Helpers para cerrar y limpiar modal
     function hideProvidersModal(){
         const modal = document.getElementById('modal-proveedores');
         if (!modal) return;
         modal.classList.add('hidden');
         modal.classList.remove('flex');
         const container = document.getElementById('prov-list'); if (container) container.innerHTML = '';
     }
     document.getElementById('btn-cerrar-proveedores')?.addEventListener('click', hideProvidersModal);
     document.getElementById('btn-cancel-proveedores')?.addEventListener('click', hideProvidersModal);
     document.getElementById('modal-proveedores')?.addEventListener('click', function(e){ if (e.target === this) hideProvidersModal(); });
 
     // Cuando se confirma un proveedor elegido, propagar currency al option
     document.getElementById('btn-select-prov')?.addEventListener('click', function(){
        if (typeof window.handleSelectProv === 'function') return window.handleSelectProv();
     });
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Requisicion\resources\views/ordenes_compra/create.blade.php ENDPATH**/ ?>