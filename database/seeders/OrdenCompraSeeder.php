<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ordenCompra;

class OrdenCompraSeeder extends Seeder
{
    public function run()
    {
        OrdenCompra::factory()->count(6)->create();
    }
}