@extends('layouts.app')

@section('title', 'Crear Requisición')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<style>
    .swal2-confirm {
        background-color: #2563eb !important;
        border-color: #2563eb !important;
    }

    .swal2-confirm:hover {
        background-color: #1d4ed8 !important;
    }

    .border-red-500 {
        border-color: #ef4444;
    }
</style>
</head>
@section('content')
<x-sidebar />

<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 mt-20">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 px-6 py-4">
                <h2 class="text-white text-xl font-bold">Solicitar nuevo producto</h2>
            </div>

            <form id="productoForm" action="{{ route('nuevo-producto.store') }}" method="POST" class="px-6 py-4">
                @csrf

                <div class="mb-4">
                    <label for="nombre" class="block text-gray-700 text-sm font-bold mb-2">Nombre del producto</label>
                    <input type="text" id="nombre" name="nombre"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ingresa el nombre del producto" value="{{ old('nombre') }}" required>
                    @error('nombre')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="descripcion" class="block text-gray-700 text-sm font-bold mb-2">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Describe el producto que deseas solicitar"
                        required>{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-center">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                        Solicitar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('productoForm');
        
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            let isValid = true;
            const nombre = document.getElementById('nombre');
            const descripcion = document.getElementById('descripcion');
            
            // Reset errors
            nombre.classList.remove('border-red-500');
            descripcion.classList.remove('border-red-500');
            
            // Validar nombre
            if (!nombre.value.trim()) {
                isValid = false;
                nombre.classList.add('border-red-500');
            }
            
            // Validar descripción
            if (!descripcion.value.trim()) {
                isValid = false;
                descripcion.classList.add('border-red-500');
            }
            
            if (!isValid) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor, completa todos los campos correctamente.',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }
            
            // Confirmación antes de enviar
            Swal.fire({
                title: '¿Confirmar envío?',
                text: "¿Estás seguro de que deseas solicitar este producto?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, solicitar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar alerta de carga
                    Swal.fire({
                        title: 'Enviando solicitud...',
                        text: 'Por favor espera mientras procesamos tu solicitud.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    form.submit();
                }
            });
        });
        
        // Mostrar alerta de éxito si viene de una redirección con sesión flash
        @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('success') }}',
            confirmButtonColor: '#2563eb'
        });
        @endif
    });
</script>
@endsection
