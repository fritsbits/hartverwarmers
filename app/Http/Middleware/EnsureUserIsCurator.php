<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsCurator
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin() && ! $request->user()?->isCurator()) {
            abort(403);
        }

        return $next($request);
    }
}
