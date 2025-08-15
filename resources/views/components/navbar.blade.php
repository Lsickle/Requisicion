<link rel="stylesheet" href="{{ asset('css/navbar.css') }}">

<div class="mi-navbar">
    <nav class="navbar">
        <button class="menu-btn" onclick="toggleSidebar()">☰</button>
        <h1 class="title">Requisición</h1>
    </nav>

    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="{{route('index')}}">index</a></li>
            <li><a href="#">requisicion</a></li>
            <li><a href="#">Opción 3</a></li>
            <li><a href="#">Opción 3</a></li>
        </ul>
    </div>

    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById("sidebar").classList.toggle("open");
        document.getElementById("overlay").classList.toggle("show");
    }
</script>
