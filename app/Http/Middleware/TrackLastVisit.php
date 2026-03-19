<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastVisit
{
    /**
     * Update the authenticated user's last_visited_at timestamp (throttled to once per hour).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if ($user && (! $user->last_visited_at || $user->last_visited_at->lt(now()->subHour()))) {
            $user->forceFill(['last_visited_at' => now()])->saveQuietly();
        }

        return $response;
    }
}
