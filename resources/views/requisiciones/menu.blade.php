@extends('layouts.app')

@section('title', 'Menu')

<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Estilos locales: evitar subrayado y mejorar tarjetas -->
<style>
    /* Quitar subrayado en enlaces dentro del grid de tarjetas y mantener accesibilidad visual */
    .menu-grid a { text-decoration: none; }
    .menu-grid a:hover, .menu-grid a:focus { text-decoration: none; outline: none; }
    .menu-grid a:focus-visible { outline: 2px solid rgba(59,130,246,0.6); outline-offset: 2px; }
    /* Tarjetas reutilizables */
    .menu-card { position: relative; background: linear-gradient(180deg,#ffffff,#f8fafc); transition: all .35s cubic-bezier(.4,0,.2,1); overflow:hidden; }
    .menu-card::after { content:""; position:absolute; inset:0; pointer-events:none; background:radial-gradient(circle at 30% 20%,rgba(99,102,241,0.12),transparent 60%); opacity:.5; transition:opacity .35s; }
    .menu-card:hover { transform: translateY(-4px); }
    .menu-card:hover::after { opacity:.75; }
    .menu-icon { transition: transform .45s cubic-bezier(.34,1.56,.64,1); }
    .menu-card:hover .menu-icon { transform: scale(1.12) rotate(4deg); }
    .menu-card a { transition: box-shadow .3s, transform .3s; }
    .menu-card a:focus-visible { box-shadow:0 0 0 3px rgba(99,102,241,.4); }
</style>

@section('content')
    <x-sidebar/>

    <div class="container mx-auto px-4 py-8">
        <div class="mt-11 grid gap-6 menu-grid grid-cols-[repeat(auto-fit,minmax(260px,1fr))] justify-center place-items-center max-w-7xl mx-auto">
            @php
                $permissions = array_map(fn($p) => mb_strtolower($p, 'UTF-8'), Session::get('user_permissions', []));
                $hasPermission = fn($perm) => in_array(mb_strtolower($perm, 'UTF-8'), $permissions, true);
            @endphp

            @if($hasPermission('crear requisicion'))
            <div class="menu-card w-full max-w-sm min-h-[315px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-blue-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-plus text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Crear Requisiciones</h3>
                    <p class="text-gray-600 mb-4">Genera nuevas solicitudes de materiales o servicios</p>
                </div>
                <a href="{{ route('requisiciones.create') }}" class="text-center inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg">Crear Nueva</a>
            </div>
            @endif

            @if($hasPermission('Especial'))
            <div class="menu-card w-full max-w-sm min-h-[315px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-fuchsia-600 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-fuchsia-100 rounded-full flex items-center justify-center mb-4 relative">
                        <i class="fas fa-star text-fuchsia-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Requisición Especial</h3>
                    <p class="text-gray-600 mb-4">Crear solicitud exclusiva para servicios o alquiler</p>
                </div>
                <a href="{{ route('requisiciones.especial') }}" class="text-center inline-block bg-fuchsia-600 hover:bg-fuchsia-700 text-white font-medium py-2 px-6 rounded-lg">Crear Especial</a>
            </div>
            @endif

            @if($hasPermission('aprobar requisicion'))
            <div class="menu-card w-full max-w-sm min-h-[300px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-amber-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-circle-check text-amber-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Aprobación de Requisiciones</h3>
                    <p class="text-gray-600 mb-4">Revisa y aprueba requisiciones pendientes</p>
                </div>
                <a href="{{ route('requisiciones.aprobacion') }}" class="text-center inline-block bg-amber-600 hover:bg-amber-700 text-white font-medium py-2 px-6 rounded-lg">Ir al panel</a>
            </div>
            @endif

            @if($hasPermission('solicitar producto'))
            <div class="menu-card w-full max-w-sm min-h-[315px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-purple-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-box text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Solicitar Nuevo Producto</h3>
                    <p class="text-gray-600 mb-4">Solicita la adición de nuevos productos al catálogo</p>
                </div>
                <a href="{{ route('productos.nuevoproducto') }}" class="text-center inline-block bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-6 rounded-lg">Solicitar</a>
            </div>
            @endif

            @if($hasPermission('ver requisicion'))
            <div class="menu-card w-full max-w-sm min-h-[315px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-green-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-list text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Historial de Requisiciones</h3>
                    <p class="text-gray-600 mb-4">Consulta y gestiona todas las solicitudes existentes</p>
                </div>
                <a href="{{ route('requisiciones.historial') }}" class="text-center inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg">Ver Listado</a>
            </div>
            @endif

            @if($hasPermission('total requisiciones'))
            <div class="menu-card w-full max-w-sm min-h-[315px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-sky-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-sky-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-list-alt text-sky-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Todas las Requisiciones</h3>
                    <p class="text-gray-600 mb-4">Visualiza todas las requisiciones del sistema</p>
                </div>
                <a href="{{ route('requisiciones.todas') }}" class="text-center inline-block bg-sky-600 hover:bg-sky-700 text-white font-medium py-2 px-6 rounded-lg">Ver Todas</a>
            </div>
            @endif

            @if($hasPermission('crear oc'))
            <div class="menu-card w-full max-w-sm min-h-[300px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-yellow-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-file-invoice-dollar text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Generar Orden de Compra</h3>
                    <p class="text-gray-600 mb-4">Crea nuevas órdenes de compra</p>
                </div>
                <a href="{{ route('ordenes_compra.lista') }}" class="text-center inline-block bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-6 rounded-lg">Crear OC</a>
            </div>
            @endif

            @if($hasPermission('ver oc'))
            <div class="menu-card w-full max-w-sm min-h-[300px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-orange-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-clipboard-list text-orange-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Historial de Órdenes de Compra</h3>
                    <p class="text-gray-600 mb-4">Consulta todas las órdenes de compra generadas</p>
                </div>
                <a href="{{ route('ordenes_compra.historial') }}" class="text-center inline-block bg-orange-600 hover:bg-orange-700 text-white font-medium py-2 px-6 rounded-lg">Ver Listado</a>
            </div>
            <div class="menu-card w-full max-w-sm min-h-[315px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-indigo-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-file-pdf text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Verificar PDF de OC</h3>
                    <p class="text-gray-600 mb-4">Sube un PDF para verificar su hash frente al almacenado</p>
                </div>
                <a href="{{ route('ordenes.verify_upload') }}" class="text-center inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg">Verificar PDF</a>
            </div>
            @endif

            @if($hasPermission('ver producto'))
            <div class="menu-card w-full max-w-sm min-h-[315px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-teal-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-boxes text-teal-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Ver Productos</h3>
                    <p class="text-gray-600 mb-4">Consulta todos los productos disponibles</p>
                </div>
                <a href="{{ route('productos.gestor')}}" class="text-center inline-block bg-teal-600 hover:bg-teal-700 text-white font-medium py-2 px-6 rounded-lg">Ver Productos</a>
            </div>
            @endif

            <div class="menu-card w-full max-w-sm min-h-[315px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-teal-400 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-teal-50 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-list text-teal-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Lista de Productos</h3>
                    <p class="text-gray-600 mb-4">Visualiza la lista completa de productos disponibles en el sistema</p>
                </div>
                <a href="{{ route('productos.lista') }}" class="text-center inline-block bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-6 rounded-lg">Ver Lista</a>
            </div>

            @if($hasPermission('Centros'))
            <div class="menu-card w-full max-w-sm min-h-[315px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-indigo-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-building text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Centros</h3>
                    <p class="text-gray-600 mb-4">Gestiona centros de costo</p>
                </div>
                <a href="/centros" class="text-center inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg">Ir a Centros</a>
            </div>
            @endif

            @if($hasPermission('Subcentros'))
            <div class="menu-card w-full max-w-sm min-h-[315px] rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-rose-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="menu-icon w-16 h-16 bg-rose-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-layer-group text-rose-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Subcentros</h3>
                    <p class="text-gray-600 mb-4">Gestiona subcentros y asignaciones</p>
                </div>
                <a href="/centros/user_subcentros" class="text-center inline-block bg-rose-600 hover:bg-rose-700 text-white font-medium py-2 px-6 rounded-lg">Ir a Subcentros</a>
            </div>
            @endif

        </div>
        </div>
@endsection