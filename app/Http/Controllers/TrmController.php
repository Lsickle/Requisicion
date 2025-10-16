<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\trm\TrmController as RealTrmController;

class TrmController extends Controller
{
    private RealTrmController $impl;

    public function __construct()
    {
        $this->impl = app(RealTrmController::class);
    }

    // POST /trm/sync -> despacha el job manualmente
    public function sync(Request $request)
    {
        return $this->impl->sync($request);
    }

    // GET /trm/latest?currency=COP&base=USD
    public function latest(Request $request)
    {
        return $this->impl->latest($request);
    }
}
