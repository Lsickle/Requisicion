<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function productos()
    {
        return $this->belongsToMany(Producto::class)
                   ->withPivot('pr_amount');
    }

    public function centros()
    {
        return $this->belongsToMany(Centro::class)
                   ->withPivot('rc_amount');
    }

    public function estatus()
    {
        return $this->belongsToMany(Estatus::class);
    }
}