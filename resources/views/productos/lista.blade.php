@extends('layouts.app')

@section('title', 'Lista de Productos')

@section('content')
<div class="flex pt-20 min-h-screen">
    <x-sidebar />

    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-white/95 rounded-2xl shadow-2xl p-6 border border-slate-200 ring-1 ring-slate-100">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-inner">
                        <i class="fas fa-box-open text-xl"></i>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-800 tracking-tight">Lista de Productos</h1>
                </div>
                <a href="{{ url()->previous() }}" class="px-4 py-2 text-sm rounded-lg border border-slate-300 bg-slate-50 hover:bg-slate-100 shadow-sm transition">Volver</a>
            </div>

            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="w-full md:w-2/3 relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search"></i></span>
                    <input type="text" id="buscarProducto" placeholder="Buscar por SKU, nombre o categoría..." class="pl-10 border border-indigo-300 focus:border-indigo-400 focus:ring-indigo-300/40 rounded-xl w-full py-2.5 text-sm shadow-sm placeholder-gray-400" autocomplete="off">
                </div>
                <div class="flex items-center gap-4 text-sm text-gray-600">
                    <div>Resultados: <span id="resultCount" class="font-semibold">0</span></div>
                </div>
            </div>
 
            <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
                <table class="min-w-full bg-white text-sm" id="tablaProductos">
                    <thead class="bg-indigo-50/80 text-indigo-900 text-xs font-semibold tracking-wide sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-3 text-left">SKU</th>
                            <th class="px-4 py-3 text-left">Producto</th>
                            <th class="px-4 py-3 text-left">Categoría</th>
                            <th class="px-4 py-3 text-left">Unidad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse(($productos ?? collect()) as $p)
                        @php
                            $cat = trim((string)($p->categoria_produc ?? '-'));
                            $catLower = strtolower($cat);
                            $catColor = 'bg-slate-100 text-slate-700';
                            if(str_contains($catLower,'serv')) $catColor='bg-amber-100 text-amber-700';
                            elseif(str_contains($catLower,'alq')) $catColor='bg-fuchsia-100 text-fuchsia-700';
                            elseif(str_contains($catLower,'herr')) $catColor='bg-indigo-100 text-indigo-700';
                            elseif(str_contains($catLower,'cons')) $catColor='bg-emerald-100 text-emerald-700';
                        @endphp
                        <tr class="hover:bg-indigo-50/40 transition">
                            <td class="px-4 py-3 font-medium text-gray-700">{{ $p->sku ?? $p->id }}</td>
                            <td class="px-4 py-3 text-gray-800">
                                <div class="font-semibold line-clamp-2 leading-tight">{{ $p->name_produc }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $catColor }} shadow-sm">{{ $cat }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $p->unit_produc ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-inbox text-3xl text-gray-300"></i>
                                    <span>No hay productos para mostrar.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mt-6">
                <div class="text-sm text-gray-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 shadow-sm">Mostrar
                    <select id="pageSizeSelect" class="ml-2 border border-indigo-300 rounded px-2 py-1 focus:border-indigo-400 focus:ring-indigo-300/40">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                    por página
                </div>
                <div id="paginationControls" class="flex flex-wrap gap-2"></div>
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
        const rowsAll = Array.from(document.querySelectorAll('#tablaProductos tbody tr')).filter(r => !r.querySelector('.fa-inbox'));
        const resultCount = document.getElementById('resultCount');

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
            resultCount.textContent = matched.length;
            const totalPages = Math.max(1, Math.ceil(matched.length / pageSize));
            currentPage = Math.min(Math.max(1, page), totalPages);
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;

            rowsAll.forEach(r => r.style.display = 'none');
            matched.slice(start, end).forEach(r => r.style.display = '');

            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            paginationControls.innerHTML = '';
            const prev = document.createElement('button');
            prev.textContent = 'Anterior';
            prev.className = 'px-3 py-1 border rounded-lg text-sm bg-white shadow-sm ' + (currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50');
            prev.disabled = currentPage === 1;
            prev.onclick = () => renderPage(currentPage - 1);
            paginationControls.appendChild(prev);

            const start = Math.max(1, currentPage - 2);
            const end = Math.min(totalPages, currentPage + 2);
            for (let p = start; p <= end; p++) {
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = 'px-3 py-1 rounded-lg text-sm ' + (p === currentPage ? 'bg-indigo-600 text-white shadow' : 'border bg-white hover:bg-slate-50 shadow-sm');
                btn.onclick = () => renderPage(p);
                paginationControls.appendChild(btn);
            }

            const next = document.createElement('button');
            next.textContent = 'Siguiente';
            next.className = 'px-3 py-1 border rounded-lg text-sm bg-white shadow-sm ' + (currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50');
            next.disabled = currentPage === totalPages;
            next.onclick = () => renderPage(currentPage + 1);
            paginationControls.appendChild(next);
        }

        input && input.addEventListener('input', function(){ currentPage = 1; renderPage(1); });
        pageSizeSelect && pageSizeSelect.addEventListener('change', function(e){ pageSize = parseInt(e.target.value,10) || 10; renderPage(1); });

        renderPage(1);
     });
</script>
@endsection