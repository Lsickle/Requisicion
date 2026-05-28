<?php $__env->startSection('title', 'Aprobación de Requisiciones'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex pt-20">
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
    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-white/95 rounded-2xl shadow-2xl border border-slate-200 ring-1 ring-slate-100 p-6 flex flex-col min-h-[80vh]">

            <!-- Encabezado mejorado -->
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-inner">
                        <i class="fas fa-clipboard-check text-xl"></i>
                    </div>
                    <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Panel de Aprobación de Requisiciones</h1>
                </div>
                <a href="<?php echo e(route('requisiciones.menu')); ?>" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow-sm transition">← Volver</a>
            </div>

            <!-- Búsqueda -->
            <div class="mb-4">
                <div class="relative w-full md:w-1/3">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 w-9"><i class="fas fa-search"></i></span>
                    <input type="text" id="busquedaAprob" placeholder="Buscar requisición..." class="pl-12 pr-3 py-2.5 border border-indigo-300 rounded-xl w-full shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-300/40 focus:outline-none" />
                </div>
            </div>

            <!-- Contenedor scrollable -->
            <div class="flex-1 overflow-y-auto thin-scrollbar">
                <!-- Tabla escritorio -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-x-auto hidden md:block">
                    <table class="min-w-full table-auto border-collapse">
                        <thead class="bg-indigo-50 text-indigo-900 text-xs uppercase tracking-wide sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">Detalle</th>
                                <th class="px-4 py-3 text-left">Prioridad</th>
                                <th class="px-4 py-3 text-left">Solicitante</th>
                                <th class="px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if(count($desktopRowsHtml) > 0): ?>
                                <?php echo implode('', $desktopRowsHtml); ?>

                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-500">No hay requisiciones para sus operaciones</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Vista móvil -->
                <div class="md:hidden space-y-4">
                    <?php if(count($mobileCardsHtml) > 0): ?>
                        <?php echo implode('', $mobileCardsHtml); ?>

                    <?php else: ?>
                        <p class="text-center text-gray-500 text-sm">No hay requisiciones para sus operaciones</p>
                    <?php endif; ?>
                </div>

                <!-- Paginación -->
                <div class="flex items-center justify-between mt-6" id="paginationBarAprob">
                    <div class="text-sm text-gray-700">
                        Mostrar
                        <select id="pageSizeSelectAprob" class="border rounded px-2 py-1 bg-white shadow-sm">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                        por página
                        <span id="paginationInfoAprob" class="ml-4 text-sm text-gray-600"></span>
                    </div>
                    <div class="flex flex-wrap gap-1" id="paginationControlsAprob"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales -->
    <?php if(count($modalsHtml) > 0): ?>
        <?php echo implode('', $modalsHtml); ?>

    <?php endif; ?>

    <!-- Modal global proveedores -->
    <div id="providerChoiceModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-60 p-4">
        <div class="bg-white rounded-xl shadow-2xl border border-slate-200 ring-1 ring-slate-100 w-full max-w-xl max-h-[80vh] overflow-y-auto thin-scrollbar">
            <div class="p-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 class="text-lg font-semibold text-gray-800">Seleccionar proveedor</h3>
                <button onclick="closeProviderChoiceModal()" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4">
                <div id="providerChoiceList" class="space-y-2"></div>
            </div>
            <div class="p-4 border-t flex justify-end gap-2 bg-gray-50">
                <button onclick="confirmProviderChoice()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-sm">Confirmar</button>
                <button onclick="closeProviderChoiceModal()" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancelar</button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<input type="hidden" id="csrf_token" value="<?php echo e(csrf_token()); ?>" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?php echo e(asset('js/aprobacion.js')); ?>"></script>
<?php $__env->stopSection(); ?>

<style>
/* Scrollbar fino */
.thin-scrollbar { scrollbar-width: thin; scrollbar-color: #94a3b8 #e2e8f0; }
.thin-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
.thin-scrollbar::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 8px; }
.thin-scrollbar::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 8px; }
.thin-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748b; }
</style>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Requisicion\resources\views/requisiciones/aprobacion.blade.php ENDPATH**/ ?>