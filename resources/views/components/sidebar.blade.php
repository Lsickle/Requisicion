<div class="relative">
    <!-- Navbar -->
    <nav
        class="bg-slate-200 text-blue-900 border-b border-slate-300 px-4 py-2 flex justify-between items-center fixed w-full top-0 left-0 z-50 shadow-md h-14">
        <button class="text-blue-900 text-xl" onclick="toggleSidebar()">☰</button>
        <div>
            <img src="{{ asset('images/logo.png') }}" alt="Vigía Plus Logistics"
                class="mx-auto h-9 w-auto object-contain">
        </div>
    </nav>

    <!-- Sidebar -->
    <div id="sidebar"
        class="fixed top-14 left-0 w-64 h-[calc(100%-3.5rem)] bg-blue-950 text-white pt-6 transform -translate-x-full transition-transform duration-300 z-40 shadow-xl flex flex-col">


        <!-- Opciones -->
        <ul class="list-none p-0 m-0 flex-grow text-sm overflow-y-auto pr-1">
            @php
            $permissions = Session::get('user_permissions', []);
            $hasPermission = fn($perm) => in_array($perm, $permissions);
            @endphp

            <li>
                <a href="{{ route('requisiciones.menu') }}"
                    class="block px-6 py-3 hover:bg-blue-800 hover:text-orange-400 hover:no-underline transition">
                    Menú
                </a>
            </li>

            @if($hasPermission('crear requisicion'))
            <li>
                <a href="{{ route('requisiciones.create') }}"
                    class="block px-6 py-3 hover:bg-blue-800 hover:text-orange-400 hover:no-underline transition">
                    Crear Requisición
                </a>
            </li>
            @endif

            @if($hasPermission('aprobar requisicion'))
            <li>
                <a href="{{ route('requisiciones.aprobacion') }}"
                    class="block px-6 py-3 hover:bg-blue-800 hover:text-orange-400 hover:no-underline transition">
                    requisiciones por aprobar
                </a>
            </li>
            @endif

            @if($hasPermission('ver requisicion'))
            <li>
                <a href="{{ route('requisiciones.historial') }}"
                    class="block px-6 py-3 hover:bg-blue-800 hover:text-orange-400 hover:no-underline transition">
                    Historial de Requisiciones
                </a>
            </li>
            @endif

            @if($hasPermission('solicitar producto'))
            <li>
                <a href="{{ route('productos.nuevoproducto')}}"
                    class="block px-6 py-3 hover:bg-blue-800 hover:text-orange-400 hover:no-underline transition">
                    Solicitar nuevo producto
                </a>
            </li>
            @endif

            @if($hasPermission('crear oc'))
            <li>
                <a href="{{ route('ordenes_compra.lista') }}"
                    class="block px-6 py-3 hover:bg-blue-800 hover:text-orange-400 hover:no-underline transition">
                    Generar Orden de Compra
                </a>
            </li>
            @endif

            @if($hasPermission('ver oc'))
            <li>
                <a href="#"
                    class="block px-6 py-3 hover:bg-blue-800 hover:text-orange-400 hover:no-underline transition">
                    Historial de Órdenes de Compra
                </a>
            </li>
            @endif

            @if($hasPermission('ver producto'))
            <li>
                <a href="{{ route('productos.gestor')}}"
                    class="block px-6 py-3 hover:bg-blue-800 hover:text-orange-400 hover:no-underline transition">
                    Ver productos
                </a>
            </li>
            @endif

            @if($hasPermission('Dashboard'))
            <li>
                <a href="#"
                    class="block px-6 py-3 hover:bg-blue-800 hover:text-orange-400 hover:no-underline transition">
                    Dashboard
                </a>
            </li>
            @endif

            @if($hasPermission('total requisiciones'))
            <li>
                <a href="{{ route('requisiciones.todas') }}"
                    class="block px-6 py-3 hover:bg-blue-800 hover:text-orange-400 hover:no-underline transition">
                    Ver requisiciones
                </a>
            </li>
            @endif
        </ul>

        <!-- Información del usuario -->
        <div class="px-4 py-4 border-t border-blue-800 bg-blue-900 text-sm">
            @if(Session::has('user'))
            <div>
                <p class="font-semibold text-white">{{ Session::get('user')['name'] ?? 'Usuario' }}</p>
                <p class="text-blue-200 text-xs">{{ Session::get('user')['email'] ?? '' }}</p>
            </div>
            @endif

            <!-- Botón de Cerrar Sesión -->
            <div class="mt-4">
                <form id="logoutForm" action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="button" id="logoutBtn"
                        class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 rounded-md text-white font-semibold text-sm">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Overlay -->
    <div id="overlay" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 hidden z-30"
        onclick="toggleSidebar()"></div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("overlay");

        sidebar.classList.toggle('-translate-x-full'); 
        sidebar.classList.toggle('translate-x-0');     

        overlay.classList.toggle('hidden');
    }
</script>

<script>
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
        e.preventDefault();

        Swal.fire({
            title: '¿Cerrar sesión?',
            text: "¿Estás seguro de que quieres salir?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1e40af',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('logoutForm').submit();
            }
        });
    });
</script>