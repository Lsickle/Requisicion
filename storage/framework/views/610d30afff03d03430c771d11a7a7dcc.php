<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'titulo'); ?></title>
    <?php echo $__env->yieldContent('styles'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <link rel="icon" type="image/png" href="<?php echo e(asset('images/favicon1.png')); ?>">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Forzar que SweetAlert2 aparezca por encima de los modales en toda la app */
        .swal2-container {
            z-index: 3000000 !important;
        }

        .swal2-container .swal2-popup {
            z-index: 3000001 !important;
        }

        .swal2-container .swal2-toast {
            z-index: 3000002 !important;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans text-gray-800">


    <main class="p-6">
        <?php echo $__env->yieldContent('content'); ?>
        <?php echo $__env->yieldContent('scripts'); ?>
    </main>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html><?php /**PATH C:\laragon\www\Requisicion\resources\views/layouts/app.blade.php ENDPATH**/ ?>