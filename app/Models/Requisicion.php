<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class Requisicion extends Model
{
    use HasFactory, SoftDeletes;

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
            ->withPivot('pr_amount', 'id_productoxproveedor')
            ->withTimestamps();
    }

    public function centros()
    {
        // Relación usando la tabla centro_producto que contiene amount y requisicion_id
        return $this->belongsToMany(Centro::class, 'centro_producto', 'requisicion_id', 'centro_id')
            ->withPivot('amount', 'producto_id')
            ->withTimestamps();
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
            ->with('estatusRelation')
            ->orderBy('created_at', 'desc');
    }

    // En el modelo Requisicion.php
    public function ultimoEstatus()
    {
        return $this->hasOne(Estatus_Requisicion::class, 'requisicion_id')
            ->where('estatus', 1)
            ->with('estatusRelation')
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
