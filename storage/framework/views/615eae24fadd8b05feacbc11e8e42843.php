<?php $__env->startSection('title', 'Historial de Inventario'); ?>

<style>
    .badge-entrada { background-color: #d1fae5; color: #065f46; }
    .badge-salida { background-color: #fee2e2; color: #991b1b; }
    .historow-row:hover { background-color: #f9fafb; }
</style>

<?php $__env->startSection('content'); ?>
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

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-history text-blue-600"></i>
                Historial de Movimientos
            </h1>
            <p class="text-gray-500 text-sm mt-1">Registro de entradas y salidas de inventario</p>
        </div>
        <a href="<?php echo e(route('inventario.index')); ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-1"></i> Volver al Inventario
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-center">Tipo</th>
                        <th class="px-4 py-3 text-left">Producto</th>
                        <?php if($verTodas): ?>
                        <th class="px-4 py-3 text-left">Bodega</th>
                        <?php endif; ?>
                        <th class="px-4 py-3 text-center">Cantidad</th>
                        <th class="px-4 py-3 text-left">Usuario</th>
                        <th class="px-4 py-3 text-left">Comentario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $movimientos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mov): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-b historow-row">
                        <td class="px-4 py-3 text-gray-600">
                            <?php echo e($mov->created_at ? $mov->created_at->format('d/m/Y H:i') : '-'); ?>

                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold <?php echo e($mov->tipo === 'entrada' ? 'badge-entrada' : 'badge-salida'); ?>">
                                <?php echo e($mov->tipo === 'entrada' ? 'Entrada' : 'Salida'); ?>

                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800">
                            <?php echo e($mov->inventarioBodega->producto->name_produc ?? 'N/A'); ?>

                        </td>
                        <?php if($verTodas): ?>
                        <td class="px-4 py-3 text-gray-600">
                            <?php echo e($mov->inventarioBodega->bodega->name_centro ?? 'N/A'); ?>

                        </td>
                        <?php endif; ?>
                        <td class="px-4 py-3 text-center font-semibold <?php echo e($mov->tipo === 'entrada' ? 'text-green-600' : 'text-red-600'); ?>">
                            <?php echo e($mov->tipo === 'entrada' ? '+' : '-'); ?><?php echo e($mov->cantidad); ?>

                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <?php echo e($mov->user->name ?? 'N/A'); ?>

                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            <?php echo e($mov->comentario ?? '-'); ?>

                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e($verTodas ? 7 : 6); ?>" class="px-4 py-8 text-center text-gray-400">
                            <i class="fas fa-history text-3xl mb-2 block"></i>
                            No hay movimientos registrados.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($movimientos->hasPages()): ?>
        <div class="px-4 py-3 border-t flex justify-center">
            <?php echo $movimientos->links(); ?>

        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Requisicion\resources\views/inventario/historial.blade.php ENDPATH**/ ?>