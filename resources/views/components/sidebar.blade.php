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
        class="fixed top-0 left-0 w-64 md:w-64 h-full bg-blue-900 text-white pt-16 transform -translate-x-full transition-transform duration-300 z-40 shadow-xl">
        <ul class="list-none p-0 m-0 text-center">
            <li class="px-4 py-4 hover:bg-blue-700"><a href="{{route('index')}}" class="block w-full">Index</a></li>
            <li class="px-4 py-4 hover:bg-blue-700"><a href="{{route('requisiciones.create')}}" class="block w-full">Requisición</a></li>
            <li class="px-4 py-4 hover:bg-blue-700"><a href="#" class="block w-full">Opción 3</a></li>
            <li class="px-4 py-4 hover:bg-blue-700"><a href="#" class="block w-full">Opción 4</a></li>
        </ul>
    </div>

    <!-- Overlay -->
    <div id="overlay" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 hidden z-30"
        onclick="toggleSidebar()"></div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("overlay");

        // Toggle sidebar usando transform
        sidebar.classList.toggle('-translate-x-full'); // Oculta
        sidebar.classList.toggle('translate-x-0');     // Muestra

        // Toggle overlay
        overlay.classList.toggle('hidden');
    }
</script>