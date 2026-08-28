<?php

namespace App\Http\Middleware;

use App\Support\PreviewMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnablePreviewMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('preview');

        if (! is_string($token)) {
            return $next($request);
        }

        if ($token === 'off') {
            $request->session()->forget(PreviewMode::SESSION_KEY);

            return $next($request);
        }

        $expected = config('preview.token');

        if (filled($token) && filled($expected) && hash_equals((string) $expected, $token)) {
            $request->session()->put(PreviewMode::SESSION_KEY, true);
        }

        return $next($request);
    }
}
