<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vigía Plus Logistics</title>
    <link rel="icon" type="image/png" href="<?php echo e(asset('images/favicon1.png')); ?>">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <!-- Evitar cacheo para que no se pueda volver atrás al cerrar sesión -->
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        lightgray: '#ebebeb',  // Gris claro personalizado
                        primary: '#1e40af',    // Azul oscuro
                        secondary: '#3b82f6',  // Azul más claro
                    }
                }
            }
        }
    </script>
    <style>
        /* Fondo dinámico con patrón y blobs */
        .bg-grid {
            background-image: radial-gradient(rgba(0, 0, 0, 0.06) 1px, transparent 1px);
            background-size: 22px 22px;
        }

        @media (prefers-color-scheme: dark) {
            .bg-grid {
                background-image: radial-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px);
            }
        }

        .blob {
            position: absolute;
            border-radius: 9999px;
            filter: blur(44px);
            opacity: .35;
            pointer-events: none;
        }

        @keyframes blobMove {
            0% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(40px, 20px) scale(1.1);
            }

            100% {
                transform: translate(0, 0) scale(1);
            }
        }

        @keyframes blobMove2 {
            0% {
                transform: translate(0, 0) scale(1);
            }

            50% {
                transform: translate(-30px, 10px) scale(1.08);
            }

            100% {
                transform: translate(0, 0) scale(1);
            }
        }
    </style>
</head>

<body class="min-h-screen bg-lightgray flex flex-col">
    <!-- Navbar -->
    <?php if (isset($component)) { $__componentOriginala591787d01fe92c5706972626cdf7231 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala591787d01fe92c5706972626cdf7231 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.navbar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('navbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala591787d01fe92c5706972626cdf7231)): ?>
<?php $attributes = $__attributesOriginala591787d01fe92c5706972626cdf7231; ?>
<?php unset($__attributesOriginala591787d01fe92c5706972626cdf7231); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala591787d01fe92c5706972626cdf7231)): ?>
<?php $component = $__componentOriginala591787d01fe92c5706972626cdf7231; ?>
<?php unset($__componentOriginala591787d01fe92c5706972626cdf7231); ?>
<?php endif; ?>

    <!-- Contenido principal con fondo dinámico -->
    <div class="relative flex-grow px-4 pt-24 pb-10">
        <div class="absolute inset-0 bg-gradient-to-b from-white/70 to-slate-100/80"></div>
        <div class="absolute inset-0 bg-grid"></div>

        <div class="relative flex items-start justify-center">
            <div class="login w-full max-w-[19rem] sm:max-w-[26rem] md:max-w-[28rem]">
                <div class="bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/90 rounded-2xl shadow-2xl border border-slate-200 ring-1 ring-slate-100 p-6 md:p-7 transition-transform duration-300 hover:-translate-y-0.5">
                    <!-- Logo sin fondo de colores -->
                    <div class="mb-6 md:mb-7 relative flex justify-center">
                        <img src="/images/VigiaLogoC.png" alt="Vigía Plus Logistics"
                            class="mx-auto h-14 md:h-16 w-auto rounded-lg drop-shadow">
                    </div>

                    <!-- Formulario de login (IDs y acción sin cambios) -->
                    <form id="loginForm" method="POST" action="/auth/api-login">
                        <?php echo csrf_field(); ?>
                        <div class="mb-5">
                            <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Correo
                                electrónico</label>
                            <div class="relative">
                                <input id="email" type="email" name="email" placeholder="tu.correo@ejemplo.com"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition duration-200"
                                    required autofocus>
                                <span class="absolute inset-y-0 right-3 flex items-center text-gray-400">
                                    <i class="far fa-envelope"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Contraseña</label>
                            <div class="relative">
                                <input id="password" type="password" name="password" placeholder="••••••••"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition duration-200"
                                    required>
                                <span
                                    class="absolute inset-y-0 right-3 flex items-center text-gray-400 cursor-pointer toggle-password">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mb-5 text-right">
                            <a href="#" id="forgotPasswordLink"
                                class="text-xs md:text-sm text-primary hover:text-secondary transition duration-200 hover:underline underline-offset-4">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>

                        <button type="submit"
                            class="w-full bg-blue-900 text-white py-2 md:py-2.5 px-4 rounded-lg font-medium text-sm md:text-base hover:opacity-90 transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow">
                            Iniciar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para recuperación de contraseña -->
    <div id="forgotPasswordModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Recuperar contraseña</h3>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <p class="text-gray-600 mb-4">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu
                contraseña.</p>

            <form id="forgotPasswordForm">
                <div class="mb-5">
                    <label for="recoveryEmail" class="block text-gray-700 text-sm font-medium mb-2">Correo
                        electrónico</label>
                    <div class="relative">
                        <input id="recoveryEmail" type="email" name="email" placeholder="tu.correo@ejemplo.com"
                            class="w-full px-3 py-2 md:px-4 md:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition duration-200 text-sm md:text-base"
                            required>
                        <span class="absolute inset-y-0 right-3 flex items-center text-gray-400">
                            <i class="far fa-envelope"></i>
                        </span>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-blue-900 text-white py-2 md:py-2.5 px-4 rounded-lg font-medium text-sm md:text-base hover:opacity-90 transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow">
                    Enviar enlace de recuperación
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-4 text-center">
        <p class="text-sm">&copy; <?php echo e(date('Y')); ?> Vigía Plus Logistics. derechos reservados.</p>
    </footer>

    <!-- Bloqueo de navegación hacia atrás en pantalla de login con alerta inmediata -->
    <script>
        (function() {
            function showLoginAlert(){
                try { Swal.fire({ icon:'warning', title:'Sesión requerida', text:'Por favor inicia sesión', confirmButtonColor:'#1e40af' }); } catch(_){}
            }
            if (window.history && window.history.pushState) {
                history.pushState(null, '', location.href);
                window.addEventListener('popstate', function () {
                    // Mostrar alerta y volver a insertar estado para evitar navegación
                    showLoginAlert();
                    history.pushState(null, '', location.href);
                });
            }
            // Si el navegador entrega la página desde bfcache, forzar recarga y alerta
            window.onpageshow = function(evt) {
                if (evt.persisted) { showLoginAlert(); window.location.reload(); }
            };
        })();
    </script>

    <script>
        // Mostrar/ocultar contraseña
        document.querySelector('.toggle-password').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Login con fetch
        const form = document.getElementById('loginForm');
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const loginData = {
                email: document.getElementById('email').value,
                password: document.getElementById('password').value
            };

            Swal.fire({
                title: 'Iniciando sesión',
                text: 'Por favor espere...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            // Usar el formulario tradicional en lugar de fetch para mantener la sesión
            document.getElementById('loginForm').submit();
        });

        // Recuperación de contraseña
        const forgotPasswordLink = document.getElementById('forgotPasswordLink');
        const forgotPasswordModal = document.getElementById('forgotPasswordModal');
        const closeModal = document.getElementById('closeModal');
        const forgotPasswordForm = document.getElementById('forgotPasswordForm');

        forgotPasswordLink.addEventListener('click', function (e) {
            e.preventDefault();
            forgotPasswordModal.classList.remove('hidden');
        });

        closeModal.addEventListener('click', function () {
            forgotPasswordModal.classList.add('hidden');
        });

        forgotPasswordModal.addEventListener('click', function (e) {
            if (e.target === forgotPasswordModal) {
                forgotPasswordModal.classList.add('hidden');
            }
        });

        forgotPasswordForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const email = document.getElementById('recoveryEmail').value;

            Swal.fire({
                title: 'Enviando solicitud',
                text: 'Por favor espere...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            fetch("<?php echo e(rtrim(env('VPL_CORE'), '/')); ?>" + "/api/auth/forgot-password", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ email: email })
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw err; });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Correo de recuperación enviado:", data);
                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Se ha enviado un enlace de recuperación a tu correo electrónico.',
                        confirmButtonColor: '#1e40af'
                    });

                    forgotPasswordModal.classList.add('hidden');
                    document.getElementById('recoveryEmail').value = '';
                })
                .catch(error => {
                    console.error("Error:", error);
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo enviar el correo de recuperación. Por favor, intente nuevamente.',
                        confirmButtonColor: '#1e40af'
                    });
                });
        });

        // Mostrar mensajes de éxito/error
        <?php if(session('success')): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: '<?php echo e(session('success')); ?>',
                confirmButtonColor: '#1e40af'
            });
        <?php endif; ?>

        <?php if(session('error')): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo e(session('error')); ?>',
                confirmButtonColor: '#1e40af'
            });
        <?php endif; ?>

        <?php if($errors->any()): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: '<?php echo e($errors->first()); ?>',
                confirmButtonColor: '#1e40af'
            });
        <?php endif; ?>

        <?php if(session('error')): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo e(session('error')); ?>',
            confirmButtonColor: '#1e40af'
        });
    <?php endif; ?>

    <?php if(session('success')): ?>
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '<?php echo e(session('success')); ?>',
            confirmButtonColor: '#1e40af'
        });
    <?php endif; ?>

    <?php if($errors->any()): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: '<?php echo e($errors->first()); ?>',
            confirmButtonColor: '#1e40af'
        });
    <?php endif; ?>
    </script>
</body>

</html><?php /**PATH C:\laragon\www\Requisicion\resources\views/index.blade.php ENDPATH**/ ?>