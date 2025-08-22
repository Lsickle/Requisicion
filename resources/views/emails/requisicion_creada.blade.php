<!DOCTYPE html>
<html>
<head>
    <title>Nueva Requisición Creada #{{ $requisicion->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <div class="container mx-auto max-w-2xl p-4">
        <div class="header bg-gray-50 p-4 text-center rounded-t-lg border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-800">Nueva Requisición Creada</h1>
            <p class="text-gray-600 mt-2">Número de Requisición: #{{ $requisicion->id }}</p>
        </div>

        <div class="content bg-white p-6">
            <div class="details mb-6">
                <div class="detail-item mb-3">
                    <span class="detail-label font-semibold">Solicitante:</span>
                    <span class="ml-2">{{ $nombreSolicitante }}</span>
                </div>
                <div class="detail-item mb-3">
                    <span class="detail-label font-semibold">Prioridad:</span>
                    <span class="ml-2">{{ ucfirst($requisicion->prioridad_requisicion) }}</span>
                </div>
                <div class="detail-item mb-3">
                    <span class="detail-label font-semibold">Tipo:</span>
                    <span class="ml-2">{{ $requisicion->Recobrable }}</span>
                </div>
                <div class="detail-item mb-3">
                    <span class="detail-label font-semibold">Justificación:</span>
                    <span class="ml-2">{{ $requisicion->justify_requisicion }}</span>
                </div>
                <div class="detail-item mb-3">
                    <span class="detail-label font-semibold">Cantidad Total:</span>
                    <span class="ml-2">{{ $requisicion->amount_requisicion }}</span>
                </div>
            </div>

            <p class="mb-6 text-gray-700">Se ha creado una nueva requisición en el sistema. 
                Puedes ver los detalles completos accediendo al sistema o descargando el PDF adjunto.</p>
            
            <div class="text-center mt-8">
                <a href="{{ route('pdf.generar', ['tipo' => 'requisicion', 'id' => $requisicion->id]) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg inline-flex items-center transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 
                              012-2h5.586a1 1 0 01.707.293l5.414 
                              5.414a1 1 0 01.293.707V19a2 2 0 
                              01-2 2z"/>
                    </svg>
                    Descargar PDF
                </a>
            </div>
        </div>

        <div class="footer mt-8 pt-6 border-t border-gray-200 text-center text-sm text-gray-500">
            <p>Este es un mensaje automático, por favor no responda a este correo.</p>
            <p class="mt-2">&copy; {{ date('Y') }} Sistema de Requisiciones</p>
        </div>
    </div>
</body>
</html>
