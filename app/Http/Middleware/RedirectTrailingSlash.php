<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        if ($path !== '/' && str_ends_with($path, '/')) {
            return redirect(rtrim($path, '/').($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
        }

        return $next($request);
    }
}
