<?php

namespace App\Http\Middleware;

use App\Services\Analytics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->isMethod('GET') && ! $request->ajax() && $response->getStatusCode() === 200) {
            Analytics::trackVisit($request);
        }

        return $response;
    }
}
