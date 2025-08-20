<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vigía Plus Logistics</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
</head>

<body class="min-h-screen bg-lightgray flex flex-col">
    <!-- Navbar -->
    <x-navbar />

    <!-- Contenido principal -->
    <div class="flex-grow flex items-center justify-center p-4">
        <div class="login w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-xl p-8 w-full">
                <!-- Logo -->
                <div class="mb-8">
                    <img src="{{ asset('images/logo.jpg') }}" alt="Vigía Plus Logistics"
                        class="mx-auto h-14 rounded-2xl">
                </div>

                <!-- Formulario de login -->
                <form id="loginForm" method="POST" action="{{ route('api.login') }}">
                    @csrf
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

                    <div class="mb-6 text-right">
                        <a href="#" id="forgotPasswordLink"
                            class="text-sm text-primary hover:text-secondary transition duration-200 hover:underline">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-900 text-white py-3 px-4 rounded-lg font-medium hover:opacity-90 transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow-md">
                        Iniciar sesión
                    </button>
                </form>
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
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition duration-200"
                            required>
                        <span class="absolute inset-y-0 right-3 flex items-center text-gray-400">
                            <i class="far fa-envelope"></i>
                        </span>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-blue-900 text-white py-3 px-4 rounded-lg font-medium hover:opacity-90 transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow-md">
                    Enviar enlace de recuperación
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-4 text-center">
        <p class="text-sm">&copy; {{ date('Y') }} Vigía Plus Logistics. derechos reservados.</p>
    </footer>

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

        fetch("https://vpl-nexus-core-test-testing.up.railway.app/api/auth/login", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
            },
            body: JSON.stringify(loginData)
        });
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                console.log("Login exitoso:", data);

                // GUARDAR USUARIO EN SESIÓN
                if (data.user) {
                    sessionStorage.setItem('user', JSON.stringify(data.user));
                }

                // Guardar token
                let token = data.token || data.access_token;
                if (token) {
                    sessionStorage.setItem('auth_token', token);
                }

                // Validar token con /ping
                return fetch("https://vpl-nexus-core-test-testing.up.railway.app/api/ping", {
                    method: "GET",
                    headers: {
                        "Accept": "application/json",
                        "Authorization": `Bearer ${token}`
                    }
                });
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Token inválido o expirado.");
                }
                return response.json();
            })
            .then(pingData => {
                console.log("Token válido:", pingData);
                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',   
                    text: 'Login exitoso',
                    confirmButtonColor: '#1e40af',
                    confirmButtonText: 'Continuar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "/requisiciones/create";
                    }
                });
            })
            .catch(error => {
                console.error("Error:", error);
                sessionStorage.removeItem('auth_token');
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de autenticación',
                    text: error.message || 'Credenciales incorrectas o token inválido.',
                    confirmButtonColor: '#1e40af'
                });
            });
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

            fetch("https://vpl-nexus-core-test-testing.up.railway.app/api/auth/forgot-password", {
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
    </script>
</body>

</html>