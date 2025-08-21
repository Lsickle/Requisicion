<div class="relative">
    <!-- Navbar -->
    <nav
        class="bg-orange-500 text-white px-5 py-3 flex justify-between items-center fixed w-full top-0 left-0 z-50 shadow-md">
        <button class="text-white text-2xl" onclick="toggleSidebar()">☰</button>
        <div>
            <img src="{{ asset('images/logo_fondo_blanco.png') }}" alt="Vigía Plus Logistics"
                class="mx-auto h-10 w-35 rounded-md object-cover">
        </div>
    </nav>

    <!-- Sidebar -->
    <div id="sidebar"
        class="fixed top-0 left-0 w-64 md:w-64 h-full bg-blue-900 text-white pt-16 transform -translate-x-full transition-transform duration-300 z-40 shadow-xl flex flex-col justify-between">

        <!-- Información del usuario -->
        <div class="px-4 py-4 border-b border-blue-700">
            @if(Session::has('user'))
            <div class="text-sm">
                <p class="font-bold">{{ Session::get('user')['name'] ?? 'Usuario' }}</p>
                <p class="text-blue-300">{{ Session::get('user')['email'] ?? '' }}</p>
                <p class="text-blue-200 text-xs mt-1">
                    Roles: {{ implode(', ', Session::get('user_roles', [])) }}
                </p>
            </div>
            @endif
        </div>

        <!-- Opciones -->
        <ul class="list-none p-0 m-0 text-center flex-grow">
            @php
                $permissions = Session::get('user_permissions', []);
                $hasPermission = fn($perm) => in_array($perm, $permissions);
            @endphp

            <li class="px-4 py-4 hover:bg-blue-700"><a href="{{ route('requisiciones.menu') }}" class="block w-full">Menu</a></li>
            
            @if($hasPermission('crear requisicion'))
            <li class="px-4 py-4 hover:bg-blue-700"><a href="{{ route('requisiciones.create') }}" class="block w-full">Crear Requisición</a></li>
            @endif
            
            @if($hasPermission('ver requisicion'))
            <li class="px-4 py-4 hover:bg-blue-700"><a href="#" class="block w-full">Historial de Requisiciones</a></li>
            @endif
            
            @if($hasPermission('solicitar producto'))
            <li class="px-4 py-4 hover:bg-blue-700"><a href="{{ route('productos.nuevoproducto')}}" class="block w-full">Solicitar nuevo producto</a></li>
            @endif
            
            @if($hasPermission('crear oc'))
            <li class="px-4 py-4 hover:bg-blue-700"><a href="#" class="block w-full">Generar Orden de compra</a></li>
            @endif
            
            @if($hasPermission('ver oc'))
            <li class="px-4 py-4 hover:bg-blue-700"><a href="#" class="block w-full">Historial de ordenes de compra</a></li>
            @endif
            
            @if($hasPermission('ver producto'))
            <li class="px-4 py-4 hover:bg-blue-700"><a href="#" class="block w-full">Ver productos</a></li>
            @endif
            
            @if($hasPermission('Dashboard'))
            <li class="px-4 py-4 hover:bg-blue-700"><a href="#" class="block w-full">Dashboard</a></li>
            @endif
        </ul>

        <!-- Botón de Cerrar Sesión -->
        <div class="m-4">
            <form id="logoutForm" action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="button" id="logoutBtn"
                    class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 rounded-md text-white font-bold">
                    Cerrar sesión
                </button>
            </form>
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
