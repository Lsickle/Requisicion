<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Requisicion extends Model
{
    use HasFactory;

    protected $table = 'requisicion';

    protected $fillable = [
        'justify_requisicion',
        'detail_requisicion',
        'prioridad_requisicion',
        'amount_requisicion',
        'Recobrable'
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_requisicion', 'id_requisicion', 'id_producto')
            ->withPivot('pr_amount')
            ->withTimestamps();
    }

    public function centros()
    {
        return $this->belongsToMany(Centro::class)
            ->withPivot('rc_amount');
    }

    // 🔹 Relación para obtener el estatus actual
    public function estatus()
    {
        return $this->belongsToMany(Estatus::class, 'estatus_requisicion')
            ->withPivot('created_at', 'date_update')
            ->withTimestamps();
    }

    public function estatusHistorial()
    {
        return $this->hasMany(Estatus_Requisicion::class, 'requisicion_id')
            ->orderBy('created_at', 'desc');
    }

    // En el modelo Requisicion.php
    public function ultimoEstatus()
    {
        return $this->hasOne(Estatus_Requisicion::class, 'requisicion_id')
            ->where('estatus', 1)
            ->with('estatus')
            ->latest();
    }

    public function ordenCompra()
    {
        return $this->hasOne(\App\Models\OrdenCompra::class, 'requisicion_id');
    }

    // Y para obtener la última orden (si quieres mostrar el botón de editar para la última)
    public function ultimaOrdenCompra()
    {
        return $this->hasOne(OrdenCompra::class, 'requisicion_id')->latest();
    }
}
