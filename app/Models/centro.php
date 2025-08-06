<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Centro extends Model
{
    use HasFactory;

    protected $table = 'centro';

    protected $fillable = [
        'name_centro'
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'productoxcentro')
                   ->withPivot('amount');
    }

    public function requisiciones()
    {
        return $this->belongsToMany(Requisicion::class, 'requisicionxcentro') 
                   ->withPivot('rc_amount');
    }
}