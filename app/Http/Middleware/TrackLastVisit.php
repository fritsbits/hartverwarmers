<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackLastVisit
{
    /**
     * Update the authenticated user's last_visited_at timestamp (throttled to once per hour).
     * On the first return visit within 7 days of email verification (`email_verified_at`), also sets first_return_at.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (! $user) {
            return $response;
        }

        $shouldUpdateLastVisit = ! $user->last_visited_at || $user->last_visited_at->lt(now()->subHour());

        if ($shouldUpdateLastVisit) {
            $updates = ['last_visited_at' => now()];

            if (
                $user->first_return_at === null
                && $user->last_visited_at !== null
                && $user->email_verified_at !== null
                && $user->email_verified_at->gte(now()->subDays(7))
            ) {
                $updates['first_return_at'] = now();
            }

            $user->forceFill($updates)->saveQuietly();
        }

        return $response;
    }
}
