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
    
    # para requisición de compras
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'centro_ordencompra')
                   ->withPivot('rc_amount')
                   ->withTimestamps();
    }

    public function requisiciones()
    {
        return $this->belongsToMany(Requisicion::class) 
                   ->withPivot('rc_amount');
    }
}