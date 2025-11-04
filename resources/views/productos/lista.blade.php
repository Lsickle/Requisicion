@extends('layouts.app')

@section('title', 'Lista de Productos')

@section('content')
<div class="flex pt-20">
    <x-sidebar />

    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Lista de Productos</h1>
                <a href="{{ url()->previous() }}" class="px-4 py-2 text-sm rounded-lg border border-gray-300 bg-gray-50 hover:bg-gray-100">Volver</a>
            </div>

            <div class="mb-4">
                <input type="text" id="buscarProducto" placeholder="Buscar por SKU, nombre o categoría..." class="border px-4 py-2 rounded-lg w-full md:w-1/2 shadow-sm focus:ring focus:ring-blue-300 focus:outline-none">
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden bg-white" id="tablaProductos">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left">SKU</th>
                            <th class="px-4 py-2 text-left">Producto</th>
                            <th class="px-4 py-2 text-left">Categoría</th>
                            <th class="px-4 py-2 text-left">Unidad de medida</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($productos ?? collect()) as $p)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $p->sku ?? $p->id }}</td>
                            <td class="px-4 py-2">{{ $p->name_produc }}</td>
                            <td class="px-4 py-2">{{ $p->categoria_produc ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $p->unit_produc ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">No hay productos para mostrar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        const input = document.getElementById('buscarProducto');
        const rows = Array.from(document.querySelectorAll('#tablaProductos tbody tr'));
        input && input.addEventListener('input', function(){
            const q = (this.value || '').toLowerCase();
            rows.forEach(r => {
                const txt = (r.textContent || '').toLowerCase();
                r.style.display = txt.includes(q) ? '' : 'none';
            });
        });
    });
</script>
@endsection