<?php $__env->startSection('title', 'Editar Orden de Compra'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex pt-20">
    <style>
        .table-responsive { width:100%; max-width:100%; overflow-x:auto; overflow-y:auto; -webkit-overflow-scrolling: touch; max-height:48vh; }
        .table-responsive table { min-width: 760px; width:100%; table-layout: fixed; border-collapse: collapse; }
        .table-responsive thead th { position: sticky; top: 0; z-index: 10; background: inherit; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .table-responsive td { white-space: normal; word-break: break-word; vertical-align: middle; }
        .table-responsive td:first-child, .table-responsive th:first-child { max-width: 360px; }
        .table-responsive th:nth-child(9), .table-responsive td:nth-child(9) { width:220px !important; max-width:220px !important; }
        .btn-trash { background: transparent; border: none; display: inline-flex; align-items: center; justify-content: center; width:36px; height:36px; border-radius:6px; cursor:pointer; }
        .btn-trash svg { width:18px; height:18px; }
        .btn-trash:hover { background-color: rgba(239,68,68,0.08); }
        .table-responsive td:nth-child(9) .max-h-40 { max-height: 5.2rem !important; overflow-y: auto !important; }
        .table-responsive td:nth-child(9) .space-y-2 { display: block; }
        @media (max-width: 1024px) {
            .table-responsive td:first-child, .table-responsive th:first-child { max-width: 260px; }
            .table-responsive th:nth-child(9), .table-responsive td:nth-child(9) { width:180px !important; max-width:180px !important; }
        }
        @media (max-width: 640px) {
            .table-responsive table { min-width: 640px; }
            .table-responsive td:first-child, .table-responsive th:first-child { max-width: 180px; }
            .table-responsive th:nth-child(9), .table-responsive td:nth-child(9) { width:140px !important; max-width:140px !important; }
        }
        .table-responsive .product-name { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; white-space: normal; line-height: 1.2; max-width: 100%; }
        .oc-edit-scope .main-card{background:rgba(255,255,255,0.95);border:1px solid #e2e8f0;border-radius:1rem;box-shadow:0 10px 25px -5px rgba(0,0,0,0.08),0 8px 10px -6px rgba(0,0,0,0.04);}
        .oc-edit-scope .section-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.75rem;}
        .oc-edit-scope h1,.oc-edit-scope h2,.oc-edit-scope h3{letter-spacing:.5px;}
        .oc-edit-scope table thead{background:#eef2ff;color:#1e3a8a;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;}
        .oc-edit-scope table thead th{font-weight:600;}
        .oc-edit-scope table tbody tr:nth-child(odd){background:#ffffff;}
        .oc-edit-scope table tbody tr:nth-child(even){background:#f1f5f9;}
        .oc-edit-scope table tbody tr:hover{background:#e0e7ff;}
        .btn-base{display:inline-flex;align-items:center;justify-content:center;font-weight:500;border-radius:.6rem;transition:.25s;box-shadow:0 1px 2px rgba(0,0,0,.12);} 
        .btn-base:focus-visible{outline:2px solid #6366f1;outline-offset:2px;}
        .btn-primary{background:#2563eb;color:#fff;}
        .btn-primary:hover{background:#1d4ed8;}
        .btn-secondary{background:#6366f1;color:#fff;}
        .btn-secondary:hover{background:#4f46e5;}
        .btn-danger{background:#dc2626;color:#fff;}
        .btn-danger:hover{background:#b91c1c;}
        .thin-scrollbar{scrollbar-width:thin;scrollbar-color:#94a3b8 #e2e8f0;}
        .thin-scrollbar::-webkit-scrollbar{width:8px;height:8px;}
        .thin-scrollbar::-webkit-scrollbar-track{background:#e2e8f0;border-radius:8px;}
        .thin-scrollbar::-webkit-scrollbar-thumb{background:#94a3b8;border-radius:8px;}
        .thin-scrollbar::-webkit-scrollbar-thumb:hover{background:#64748b;}
        .table-responsive{border:1px solid #e2e8f0;border-radius:.75rem;background:#fff;}
        .badge-mini{display:inline-flex;align-items:center;font-size:.65rem;font-weight:600;padding:.25rem .5rem;border-radius:999px;letter-spacing:.03em;}
    </style>
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

    <div class="flex-1 px-4 md:px-8 pb-10 oc-edit-scope">
        <div class="max-w-7xl mx-auto main-card p-6 flex flex-col min-h-[80vh]">

            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center shadow-inner"><i class="fas fa-edit text-xl"></i></div>
                    <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Editar Orden de Compra</h1>
                </div>
                <div class="flex gap-3">
                    <a href="<?php echo e(route('ordenes_compra.show', $ordenCompra->id)); ?>" class="btn-base bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 text-sm">Ver Detalle</a>
                    <a href="<?php echo e(route('ordenes_compra.historial')); ?>" class="btn-base bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 text-sm">Volver</a>
                </div>
            </div>

            <?php if(session('success')): ?>
            <script>
                Swal.fire({ icon: 'success', title: 'Éxito', text: '<?php echo e(session('success')); ?>', confirmButtonText: 'Aceptar' });
            </script>
            <?php endif; ?>

            <?php if(session('error')): ?>
            <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700 text-sm">
                <?php echo e(session('error')); ?>

            </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
            <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700">
                <ul class="list-disc ml-5 text-sm">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
            <?php endif; ?>

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
            </div>
            <?php endif; ?>

            <div class="border p-6 mb-6 rounded-lg shadow bg-gray-50">
                <h2 class="text-xl font-medium text-gray-700 mb-4">Editar Productos de la OC</h2>

                <form id="orden-form" action="<?php echo e(route('ordenes_compra.update', $ordenCompra->id)); ?>" method="POST" class="space-y-6">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <input type="hidden" name="requisicion_id" value="<?php echo e($ordenCompra->requisicion_id); ?>">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-600 mb-2">Observaciones</label>
                        <textarea name="observaciones" rows="3" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-400" placeholder="Observaciones adicionales..."><?php echo e($ordenCompra->observaciones ?? ''); ?></textarea>
                    </div>

                    <div class="overflow-x-auto thin-scrollbar">
                        <table class="w-full border border-gray-200 rounded-lg overflow-hidden bg-white">
                            <thead class="bg-indigo-50 text-indigo-900 sticky top-0 z-10">
                                <tr>
                                    <th class="p-3 text-left">Producto</th>
                                    <th class="p-3 text-center">Unidad</th>
                                    <th class="p-3 text-center">Cantidad</th>
                                    <th class="p-3 text-left">Centro</th>
                                    <th class="p-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="productos-tbody">
                                <?php $__empty_1 = true; $__currentLoopData = $ordenCompra->ordencompraProductos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $linea): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $producto = $linea->producto;
                                    $lineaCentros = $ordenCompra->distribucionCentrosProductos->where('producto_id', $linea->producto_id);
                                ?>
                                <tr data-producto-id="<?php echo e($linea->producto_id); ?>" class="border-t">
                                    <td class="p-3">
                                        <div class="font-medium"><?php echo e($producto->name_produc ?? 'Producto #' . $linea->producto_id); ?></div>
                                        <input type="hidden" name="productos[<?php echo e($linea->producto_id); ?>][id]" value="<?php echo e($linea->producto_id); ?>">
                                    </td>
                                    <td class="p-3 text-center">
                                        <input type="text" name="productos[<?php echo e($linea->producto_id); ?>][unidad]" value="<?php echo e($producto->unit_produc ?? '-'); ?>" class="w-full border rounded p-2 text-center text-sm editable-unidad" placeholder="Unidad">
                                    </td>
                                    <td class="p-3 text-center">
                                        <input type="number" name="productos[<?php echo e($linea->producto_id); ?>][cantidad]" value="<?php echo e($linea->total); ?>" min="0" class="w-24 border rounded p-2 text-center" data-cantidad-original="<?php echo e($linea->total); ?>">
                                    </td>
                                    <td class="p-3">
                                        <div class="space-y-2 max-h-40 overflow-y-auto thin-scrollbar">
                                            <?php $__empty_2 = true; $__currentLoopData = $lineaCentros; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dist): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                                <div class="flex items-center justify-between bg-gray-50 px-2 py-1 rounded text-sm">
                                                    <span class="text-sm"><?php echo e($dist->centro->name_centro ?? 'Centro #' . $dist->centro_id); ?></span>
                                                    <input type="number" name="productos[<?php echo e($linea->producto_id); ?>][centros][<?php echo e($dist->centro_id); ?>]" value="<?php echo e($dist->amount); ?>" min="0" class="w-16 border rounded p-1 text-center text-sm ml-2">
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                                <div class="text-sm text-gray-500 italic">Sin distribución por centro</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="p-3 text-center">
                                        <button type="button" class="btn-trash text-red-600 hover:bg-red-50" onclick="eliminarProducto(<?php echo e($linea->producto_id); ?>)" title="Eliminar producto">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1H6a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-gray-500">No hay productos en esta orden de compra.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t">
                        <div class="text-sm text-gray-600">
                            <span id="total-productos"><?php echo e($ordenCompra->ordencompraProductos->count()); ?></span> producto(s) en la orden
                        </div>
                        <div class="flex gap-3">
                            <a href="<?php echo e(route('ordenes_compra.show', $ordenCompra->id)); ?>" class="btn-base bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2">Cancelar</a>
                            <button type="submit" class="btn-base btn-primary px-6 py-2">
                                <i class="fas fa-save mr-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-1"></i>
                    <div>
                        <h4 class="font-medium text-yellow-800">Información importante</h4>
                        <ul class="text-sm text-yellow-700 mt-1 list-disc list-inside space-y-1">
                            <li>Solo puede editar la orden si no tiene productos recibidos.</li>
                            <li>Si modifica las cantidades, la distribución por centros se actualizará automáticamente.</li>
                            <li>Para eliminar un producto, use el botón de elimination en la fila correspondiente.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Datos de productos para actualizar unidades dinámicamente
        const productosData = {
            <?php $__currentLoopData = $productos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php echo e($p->id); ?>: { name: '<?php echo e(addslashes($p->name_produc)); ?>', unit: '<?php echo e($p->unit_produc); ?>' },
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        };

        // Actualizar unidad cuando cambia el producto
        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('editable-producto')) {
                const productoId = parseInt(e.target.value);
                const row = e.target.closest('tr');
                if (row && productosData[productoId]) {
                    const unidadInput = row.querySelector('.editable-unidad');
                    if (unidadInput) {
                        unidadInput.value = productosData[productoId].unit;
                    }
                }
            }
        });

        function eliminarProducto(productoId) {
            Swal.fire({
                title: '¿Eliminar producto?',
                text: 'Esto eliminará el producto de la orden de compra.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const row = document.querySelector(`tr[data-producto-id="${productoId}"]`);
                    if (row) {
                        row.innerHTML = '<td colspan="5" class="p-3 text-center text-red-600"><em>Producto eliminado</em><input type="hidden" name="productos[' + productoId + '][eliminar]" value="1"></td>';
                    }
                    actualizarTotal();
                }
            });
        }

        function actualizarTotal() {
            const count = document.querySelectorAll('#productos-tbody tr[data-producto-id]').length;
            const totalEl = document.getElementById('total-productos');
            if (totalEl) totalEl.textContent = count;
        }

        document.getElementById('orden-form')?.addEventListener('submit', function(e) {
            const rows = document.querySelectorAll('#productos-tbody tr[data-producto-id]');
            if (rows.length === 0) {
                e.preventDefault();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Debe tener al menos un producto en la orden.' });
                return false;
            }
            return true;
        });
    </script>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Requisicion\resources\views/ordenes_compra/edit.blade.php ENDPATH**/ ?>