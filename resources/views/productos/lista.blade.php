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

            <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                <div>
                    <input type="text" id="buscarProducto" placeholder="Buscar por SKU, nombre o categoría..." class="border px-4 py-2 rounded-lg w-full md:w-1/2 shadow-sm focus:ring focus:ring-blue-300 focus:outline-none">
                </div>
                <!-- Controles de paginación movidos al pie de la tabla -->
                <div></div>
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

            <!-- Paginación en el pie de la tabla -->
            <div class="flex items-center justify-between mt-4">
                <div class="text-sm text-gray-600">Mostrar
                    <select id="pageSizeSelect" class="ml-2 border rounded px-2 py-1">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                    por página
                </div>
                <div id="paginationControls" class="flex items-center gap-1"></div>
            </div>
         </div>
     </div>
 </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        const input = document.getElementById('buscarProducto');
        const pageSizeSelect = document.getElementById('pageSizeSelect');
        const paginationControls = document.getElementById('paginationControls');
        const rowsAll = Array.from(document.querySelectorAll('#tablaProductos tbody tr'));

        let currentPage = 1;
        let pageSize = parseInt(pageSizeSelect.value, 10) || 10;

        function getMatchedRows() {
            const q = (input.value || '').toLowerCase().trim();
            return rowsAll.filter(r => {
                const sku = (r.children[0]?.textContent || '').toLowerCase();
                const nombre = (r.children[1]?.textContent || '').toLowerCase();
                const categoria = (r.children[2]?.textContent || '').toLowerCase();
                const txt = sku + ' ' + nombre + ' ' + categoria;
                return q === '' || txt.includes(q);
            });
        }

        function renderPage(page = 1) {
            const matched = getMatchedRows();
            const totalPages = Math.max(1, Math.ceil(matched.length / pageSize));
            currentPage = Math.min(Math.max(1, page), totalPages);
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;

            // hide all
            rowsAll.forEach(r => r.style.display = 'none');
            // show slice
            matched.slice(start, end).forEach(r => r.style.display = '');

            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            paginationControls.innerHTML = '';
            const prev = document.createElement('button');
            prev.textContent = 'Anterior';
            prev.className = 'px-3 py-1 border rounded text-sm ' + (currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
            prev.disabled = currentPage === 1;
            prev.onclick = () => renderPage(currentPage - 1);
            paginationControls.appendChild(prev);

            const start = Math.max(1, currentPage - 2);
            const end = Math.min(totalPages, currentPage + 2);
            for (let p = start; p <= end; p++) {
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = 'px-3 py-1 rounded text-sm ' + (p === currentPage ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100');
                btn.onclick = () => renderPage(p);
                paginationControls.appendChild(btn);
            }

            const next = document.createElement('button');
            next.textContent = 'Siguiente';
            next.className = 'px-3 py-1 border rounded text-sm ' + (currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
            next.disabled = currentPage === totalPages;
            next.onclick = () => renderPage(currentPage + 1);
            paginationControls.appendChild(next);
        }

        // events
        input && input.addEventListener('input', function(){ currentPage = 1; renderPage(1); });
        pageSizeSelect && pageSizeSelect.addEventListener('change', function(e){ pageSize = parseInt(e.target.value,10) || 10; renderPage(1); });

        // initial render
        renderPage(1);
     });
</script>
@endsection