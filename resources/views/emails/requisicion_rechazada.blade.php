<div style="font-family: Arial, sans-serif; color:#333;">
    <h2 style="margin:0 0 10px;">@if($estatusId===11) Se requiere corrección @elseif($estatusId===13) Rechazo por Gerencia @elseif($estatusId===9) Rechazo por Gerencia Financiera @else Requisición rechazada @endif - Requisición #{{ $requisicion->id }}</h2>
    <p>Hola {{ $requisicion->name_user ?? 'Usuario' }},</p>
    @if($estatusId===11)
        <p>Se necesita que haga correcciones en la requisición para poder ser aprobada. Por favor ingrese al sistema, revise los comentarios y realice los ajustes necesarios.</p>
    @elseif($estatusId===13)
        <p>Tu requisición fue rechazada por Gerencia.</p>
    @elseif($estatusId===9)
        <p>Tu requisición fue rechazada por Gerencia Financiera.</p>
    @else
        <p>Tu requisición fue rechazada.</p>
    @endif

    @if(!empty($comentario))
        <p><strong>Comentario:</strong> {{ $comentario }}</p>
    @endif

    <p style="margin-top: 15px;">Gracias.</p>
</div>
