<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        $isImpersonating = session()->has('original_user_id');

        View::share('isImpersonating', $isImpersonating);
        View::share('originalUserId', session('original_user_id'));

        if ($isImpersonating) {
            $routeName = $request->route()?->getName() ?? '';

            $curatorRoutes = ['admin.fiches.index', 'fiches.toggleDiamond'];
            $blockedRoutes = str_starts_with($routeName, 'admin.') && ! in_array($routeName, ['admin.impersonate.stop', ...$curatorRoutes]);
            $blockedRoutes = $blockedRoutes || $routeName === 'pulse';

            if ($blockedRoutes) {
                abort(403, 'Stop eerst met nabootsen voordat je admin-pagina\'s bezoekt.');
            }
        }

        return $next($request);
    }
}
