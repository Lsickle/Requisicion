<?php

namespace App\Http\Controllers\nuevo_producto;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Nuevo_producto;
use Illuminate\Support\Facades\DB;
use App\Jobs\NuevoProductoSolicitadoJob;
use App\Jobs\SendRequestedProductAddedEmail;
use App\Jobs\SendRequestedProductRejectedEmail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class NuevoProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Recupera todas las solicitudes (incluyendo las soft-deleted) y las muestra.
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $productos = Nuevo_producto::orderBy('nombre')->get();
        return view('requisiciones.menu', compact('productos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * Muestra el formulario para que un usuario solicite la inclusión de un nuevo producto.
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {
        // Preparar lista de solicitudes del usuario para la vista
        $sessionName = session('user.name') ?? null;
        $sessionEmail = session('user.email') ?? null;
        // Evitar llamar auth()->id() directamente (puede no estar disponible en todos los contextos)
        $userId = session('user.id') ?? (is_array(session('user')) && isset(session('user')['id']) ? session('user')['id'] : null);

        $query = DB::table('nuevo_producto');
        if ($sessionName || $sessionEmail) {
            $query->where(function($q) use ($sessionName, $sessionEmail) {
                if ($sessionName) $q->where('name_user', $sessionName);
                if ($sessionEmail) $q->orWhere('email_user', $sessionEmail);
            });
        } elseif ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->whereRaw('1=0');
        }

        $misSolicitudes = $query->orderBy('created_at', 'desc')->limit(20)->get();

        return view('productos.nuevoproducto', compact('misSolicitudes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * Valida la petición, crea la solicitud y despacha un job para notificar por correo al equipo.
     * Se usa transacción para mantener consistencia.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // Extraer usuario de sesión
            $nameUser  = session('user.name') ?? 'Desconocido';
            $emailUser = session('user.email') ?? 'no-email@dominio.com';

            // Crear registro incluyendo nombre y email del usuario
            $nuevoProducto = Nuevo_producto::create([
                'nombre'      => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
                'name_user'   => $nameUser,
                'email_user'  => $emailUser,
                'user_id'     => session('user.id') ?? (is_array(session('user')) && isset(session('user')['id']) ? session('user')['id'] : null),
            ]);

            // Despachar el Job para enviar el correo (no debe romper el flujo si falla)
            $mailError = null;
            try {
                NuevoProductoSolicitadoJob::dispatch($nuevoProducto);
            } catch (\Throwable $e) {
                // Registrar y continuar: la creación del registro no debe fallar por problemas SMTP/queue
                $mailError = $e->getMessage();
                \Illuminate\Support\Facades\Log::warning('NuevoProductoController::store - mail dispatch failed: ' . $mailError, ['producto_id' => $nuevoProducto->id]);
            }

            DB::commit();

            $message = 'Solicitud de producto creada exitosamente.';
            if ($mailError) {
                // Registrar detalle técnico en log y mostrar advertencia genérica al usuario
                Log::warning('NuevoProductoController::store - mail dispatch error: ' . $mailError, ['producto_id' => $nuevoProducto->id]);
                $message .= ' (Advertencia: Error en la notificación del correo.)';
            }

            if ($request->expectsJson()) {
                return response()->json([ 'success' => true, 'message' => $message, 'redirect' => route('productos.nuevoproducto') ], 200);
            }

            return redirect()->route('productos.nuevoproducto')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            $errMsg = 'Error al crear la solicitud: ' . $e->getMessage();
            if ($request->expectsJson()) {
                return response()->json([ 'success' => false, 'message' => $errMsg ], 500);
            }

            return back()->withInput()->withErrors([
                'error' => $errMsg
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Nuevo_producto $nuevoProducto
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show(Nuevo_producto $nuevoProducto)
    {
        return view('nuevo_producto.show', compact('nuevoProducto'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Nuevo_producto $nuevoProducto
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Nuevo_producto $nuevoProducto)
    {
        return view('nuevo_producto.edit', compact('nuevoProducto'));
    }

    /**
     * Update the specified resource in storage.
     *
     * Valida y actualiza la solicitud.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Nuevo_producto $nuevoProducto
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Nuevo_producto $nuevoProducto)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
        ]);

        $nuevoProducto->update($validated);

        return redirect()->route('productos.gestor')
            ->with('success', 'Solicitud de producto actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * Envía una notificación al solicitante indicando que su petición fue rechazada y elimina
     * el registro (soft delete). Guarda el comentario de rechazo si se proporciona.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Nuevo_producto $nuevoProducto
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, Nuevo_producto $nuevoProducto)
    {
        try {
            // Obtener comentario enviado desde la vista (puede venir vacío)
            $comentario = trim($request->input('comentario', ''));

            // Guardar comentario en el registro antes de eliminar si existe o si queremos registrar la razón
            if ($comentario !== '') {
                $nuevoProducto->comentario = $comentario;
                $nuevoProducto->save();
            }

            // Preparar datos para correo de rechazo incluyendo el comentario
            $payload = [
                'nombre' => $nuevoProducto->nombre,
                'descripcion' => $nuevoProducto->descripcion,
                'name_user' => $nuevoProducto->name_user,
                'email_user' => $nuevoProducto->email_user,
                'comentario' => $comentario,
            ];

            // Enviar correo en background
            SendRequestedProductRejectedEmail::dispatch($payload);

            $nuevoProducto->delete();

            return redirect()->route('productos.gestor')
                ->with('success', 'Solicitud de producto eliminada exitosamente. Se notificó al solicitante.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al rechazar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Notifica por correo que la solicitud fue atendida y el producto creado.
     *
     * Se utiliza para disparar un job en background que enviará el correo al solicitante.
     *
     * @param int $id Identificador de la solicitud (puede ser soft-deleted)
     * @return \Illuminate\Http\JsonResponse
     */
    public function notifyAdded($id)
    {
        $nuevo = Nuevo_producto::withTrashed()->findOrFail($id);

        $payload = [
            'nombre' => $nuevo->nombre,
            'descripcion' => $nuevo->descripcion,
            'name_user' => $nuevo->name_user,
            'email_user' => $nuevo->email_user,
        ];

        SendRequestedProductAddedEmail::dispatch($payload);

        return response()->json(['success' => true]);
    }

    // (restore and forceDelete removed: solicitudes rechazadas se soft-deletean y no se restauran desde UI)
}
