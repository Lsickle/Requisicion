@extends('layouts.app')

@section('title', 'Menu')

<!-- Tailwind CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Estilos locales: evitar subrayado en botones del menú al hacer hover -->
<style>
    /* Quitar subrayado en enlaces dentro del grid de tarjetas y mantener accesibilidad visual */
    .menu-grid a { text-decoration: none; }
    .menu-grid a:hover, .menu-grid a:focus { text-decoration: none; outline: none; }
    .menu-grid a:focus-visible { outline: 2px solid rgba(59,130,246,0.6); outline-offset: 2px; }
</style>

@section('content')
    <x-sidebar/>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center mt-11">Sistema de Requisiciones</h1>
        
        <div class="grid gap-6 menu-grid grid-cols-[repeat(auto-fit,minmax(260px,1fr))] justify-center place-items-center max-w-7xl mx-auto">
            @php
                $permissions = array_map(fn($p) => mb_strtolower($p, 'UTF-8'), Session::get('user_permissions', []));
                $hasPermission = fn($perm) => in_array(mb_strtolower($perm, 'UTF-8'), $permissions, true);
            @endphp

            @if($hasPermission('crear requisicion'))
            <!-- Crear Requisiciones -->
            <div class="w-full max-w-sm min-h-[315px] bg-white rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-blue-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-plus text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Crear Requisiciones</h3>
                    <p class="text-gray-600 mb-4">Genera nuevas solicitudes de materiales o servicios</p>
                </div>
                <a href="{{ route('requisiciones.create') }}" class="text-center inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    Crear Nueva
                </a>
            </div>
            @endif

            @if($hasPermission('aprobar requisicion'))
            <!-- Aprobación de Requisiciones -->
            <div class="w-full max-w-sm min-h-[300px] bg-white rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-amber-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-circle-check text-amber-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Aprobación de Requisiciones</h3>
                    <p class="text-gray-600 mb-4">Revisa y aprueba requisiciones pendientes</p>
                </div>
                <a href="{{ route('requisiciones.aprobacion') }}" class="text-center inline-block bg-amber-600 hover:bg-amber-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    Ir al panel
                </a>
            </div>
            @endif

            @if($hasPermission('solicitar producto'))
            <!-- Solicitar Nuevo Producto -->
            <div class="w-full max-w-sm min-h-[315px] bg-white rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-purple-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-box text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Solicitar Nuevo Producto</h3>
                    <p class="text-gray-600 mb-4">Solicita la adición de nuevos productos al catálogo</p>
                </div>
                <a href="{{ route('productos.nuevoproducto') }}" class="text-center inline-block bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    Solicitar
                </a>
            </div>
            @endif

            @if($hasPermission('ver requisicion'))
            <!-- Historial de Requisiciones -->
            <div class="w-full max-w-sm min-h-[315px] bg-white rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-green-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-list text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Historial de Requisiciones</h3>
                    <p class="text-gray-600 mb-4">Consulta y gestiona todas las solicitudes existentes</p>
                </div>
                <a href="{{ route('requisiciones.historial') }}" class="text-center inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    Ver Listado
                </a>
            </div>
            @endif

            @if($hasPermission('total requisiciones'))
            <!-- Ver todas las requisiciones -->
            <div class="w-full max-w-sm min-h-[315px] bg-white rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-sky-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="w-16 h-16 bg-sky-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-list-alt text-sky-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Todas las Requisiciones</h3>
                    <p class="text-gray-600 mb-4">Visualiza todas las requisiciones del sistema</p>
                </div>
                <a href="{{ route('requisiciones.todas') }}" class="text-center inline-block bg-sky-600 hover:bg-sky-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    Ver Todas
                </a>
            </div>
            @endif

            @if($hasPermission('crear oc'))
            <!-- Generar Orden de Compra -->
            <div class="w-full max-w-sm min-h-[300px] bg-white rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-yellow-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-file-invoice-dollar text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Generar Orden de Compra</h3>
                    <p class="text-gray-600 mb-4">Crea nuevas órdenes de compra</p>
                </div>
                <a href="{{ route('ordenes_compra.lista') }}" class="text-center inline-block bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    Crear OC
                </a>
            </div>
            @endif

            @if($hasPermission('ver oc'))
            <!-- Historial de Ordenes de Compra -->
            <div class="w-full max-w-sm min-h-[300px] bg-white rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-orange-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-clipboard-list text-orange-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Historial de Órdenes de Compra</h3>
                    <p class="text-gray-600 mb-4">Consulta todas las órdenes de compra generadas</p>
                </div>
                <a href="{{ route('ordenes_compra.historial') }}" class="text-center inline-block bg-orange-600 hover:bg-orange-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    Ver Listado
                </a>
            </div>
            <!-- Verificar PDF OC -->
            <div class="w-full max-w-sm min-h-[315px] bg-white rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-indigo-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-file-pdf text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Verificar PDF de OC</h3>
                    <p class="text-gray-600 mb-4">Sube un PDF para verificar su hash frente al almacenado</p>
                </div>
                <a href="{{ route('ordenes.verify_upload') }}" class="text-center inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    Verificar PDF
                </a>
            </div>
            @endif

            @if($hasPermission('ver producto'))
            <!-- Ver Productos -->
            <div class="w-full max-w-sm min-h-[315px] bg-white rounded-xl shadow-lg transition-all duration-300 p-6 border border-gray-200 hover:shadow-2xl hover:scale-105 hover:border-teal-500 flex flex-col justify-between">
                <div class="flex flex-col items-center text-center w-full">
                    <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-boxes text-teal-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Ver Productos</h3>
                    <p class="text-gray-600 mb-4">Consulta todos los productos disponibles</p>
                </div>
                <a href="{{ route('productos.gestor')}}" class="text-center inline-block bg-teal-600 hover:bg-teal-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200">
                    Ver Productos
                </a>
            </div>
            @endif

        </div>
        </div>
@endsection