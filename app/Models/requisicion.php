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
        'date_requisicion',
        'justify_requisicion',
        'detail_requisicion',
        'prioridad_requisicion',
        'amount_requisicion',
        'Recobreble'
    ];

    // Añade estos métodos para convertir los campos de fecha
    protected $dates = ['date_requisicion'];

    public function getDateRequisicionAttribute($value)
    {
        return Carbon::parse($value);
    }

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
}