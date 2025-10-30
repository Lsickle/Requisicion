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

            <form id="productoForm" action="{{ route('nuevo_producto.store') }}" method="POST" class="px-6 py-4">
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
    if (!form) return;

    // obtener token CSRF desde el formulario
    const csrfToken = form.querySelector('input[name="_token"]')?.value || '';

    function showValidationError(messages) {
        let html = '<ul style="text-align:left;margin:0;padding-left:18px;">';
        messages.forEach(m => { html += `<li>${m}</li>`; });
        html += '</ul>';
        Swal.fire({ icon: 'error', title: 'Error', html: html, confirmButtonColor: '#2563eb' });
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const nombre = document.getElementById('nombre');
        const descripcion = document.getElementById('descripcion');

        // Reset visual errors
        nombre.classList.remove('border-red-500');
        descripcion.classList.remove('border-red-500');

        // Client-side validation
        const errors = [];
        if (!nombre || !nombre.value.trim()) { errors.push('El nombre es requerido'); nombre.classList.add('border-red-500'); }
        if (!descripcion || !descripcion.value.trim()) { errors.push('La descripción es requerida'); descripcion.classList.add('border-red-500'); }

        if (errors.length) {
            showValidationError(errors);
            return;
        }

        // Confirmación con SweetAlert
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
            if (!result.isConfirmed) return;

            // Mostrar loading
            Swal.fire({ title: 'Enviando solicitud...', text: 'Por favor espera...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            // Enviar por fetch (AJAX) para manejar respuesta y errores sin depender del comportamiento de submit del navegador
            const fd = new FormData(form);

            fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: fd
            }).then(async (res) => {
                let data = null;
                try { data = await res.json(); } catch (e) { data = null; }

                // Si el servidor redirige (res.redirected), seguir la redirección
                if (res.redirected) {
                    window.location.href = res.url;
                    return;
                }

                // Cerrar loading antes de mostrar resultado
                Swal.close();

                if (res.ok) {
                    // Si envia mensaje de éxito en JSON
                    const msg = (data && (data.success ? (data.message || 'Solicitud enviada') : (data.message || '')) ) || 'Solicitud enviada';
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: msg, confirmButtonColor: '#2563eb' }).then(() => {
                        // Si el servidor indica una ruta de redirect, usarla; sino recargar
                        if (data && data.redirect) window.location.href = data.redirect;
                        else window.location.reload();
                    });
                } else {
                    // Manejar errores de validación u otros
                    if (data && data.errors) {
                        // data.errors puede ser objeto de arrays
                        const msgs = [];
                        Object.keys(data.errors).forEach(k => { (data.errors[k] || []).forEach(m => msgs.push(m)); });
                        showValidationError(msgs);
                    } else if (data && data.message) {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#2563eb' });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Error al enviar la solicitud', confirmButtonColor: '#2563eb' });
                    }
                }
            }).catch((err) => {
                console.error('Network error sending nuevo producto:', err);
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de red al enviar la solicitud', confirmButtonColor: '#2563eb' });
            });
        });
    });

    // Mostrar alerta de éxito si viene de una redirección con sesión flash (caso no-AJAX)
    @if(session('success'))
        Swal.fire({ icon: 'success', title: '¡Éxito!', text: '{{ session('success') }}', confirmButtonColor: '#2563eb' });
    @endif
});
</script>
@endsection
