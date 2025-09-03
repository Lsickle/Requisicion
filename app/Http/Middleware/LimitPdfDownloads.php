<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class LimitPdfDownloads
{
    public function handle(Request $request, Closure $next)
    {
        $userId = Auth::id();
        $reqId = $request->route('id');
        $cacheKey = "pdf_downloads_{$userId}_{$reqId}";

        $downloads = Cache::get($cacheKey, 0);

        if ($downloads >= 3) {
            return redirect()->back()->with('error', 'Solo puedes descargar este PDF un máximo de 3 veces por hora.');
        }

        Cache::put($cacheKey, $downloads + 1, now()->addHour());

        return $next($request);
    }
}
