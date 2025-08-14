<style>
    .navbar {
        background-color: #F27430;
        color: white;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between; /* separa botón y texto */
        align-items: center;
        width: 100%;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1100;
        box-sizing: border-box;
    }

    .menu-btn {
        background: none;
        border: none;
        color: white;
        font-size: 22px;
        cursor: pointer;
    }

    .title {
        margin: 0;
        font-size: 18px;
        text-align: right;
        white-space: nowrap;
    }

    /* SIDEBAR */
    .sidebar {
        position: fixed;
        top: 0;
        left: -250px;
        width: 250px;
        height: 100%;
        background-color: #2A327E;
        color: white;
        padding-top: 60px;
        transition: all 0.3s ease;
        z-index: 1000;
        box-sizing: border-box;
    }

    .sidebar.open {
        left: 0;
    }

    .sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar li {
        padding: 15px;
        text-align: center;
        font-size: 18px;
    }

    .sidebar li a {
        color: white;
        text-decoration: none;
        display: block;
        width: 100%;
    }

    .sidebar li:hover {
        background-color: #555;
    }

    /* OVERLAY */
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        z-index: 999;
    }

    .overlay.show {
        display: block;
    }

    /* Ajuste de espacio para el contenido */
    body {
        margin: 0;
        padding-top: 50px;
        font-family: sans-serif;
    }

    /*RESPONSIVE: menu que ocupa toda la pantalla */
    @media (max-width: 768px) {
        .title {
            font-size: 16px;
        }

        .sidebar {
            width: 100%;
            left: -100%;
            padding-top: 80px;
            text-align: center;
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar li {
            font-size: 20px;
            padding: 20px;
        }
    }

    @media (max-width: 480px) {
        .title {
            font-size: 14px;
        }

        .menu-btn {
            font-size: 20px;
        }
    }
</style>

<nav class="navbar">
    <button class="menu-btn" onclick="toggleSidebar()">☰</button>
    <h1 class="title">Requisición</h1>
</nav>

<div class="sidebar" id="sidebar">
    <ul>
        <li><a href="#">index</a></li>
        <li><a href="#">welcome</a></li>
        <li><a href="#">Opción 3</a></li>
        <li><a href="#">Opción 3</a></li>
    </ul>
</div>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<script>
    function toggleSidebar() {
        document.getElementById("sidebar").classList.toggle("open");
        document.getElementById("overlay").classList.toggle("show");
    }
</script>
