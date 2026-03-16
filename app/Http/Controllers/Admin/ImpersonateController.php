<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImpersonateController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        abort_if($user->trashed(), 404);
        abort_if($user->id === $request->user()->id, 403, 'Je kan jezelf niet nabootsen.');
        abort_if(session()->has('original_user_id'), 403, 'Je bent al iemand aan het nabootsen.');

        $adminId = $request->user()->id;

        session()->put('original_user_id', $adminId);
        Auth::login($user);

        Log::info('Impersonation started', [
            'admin_id' => $adminId,
            'target_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->back()->with('success', "Je bekijkt de site nu als {$user->full_name}.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $originalId = session('original_user_id');

        abort_unless($originalId, 403);

        $admin = User::findOrFail($originalId);

        Log::info('Impersonation stopped', [
            'admin_id' => $originalId,
            'ip' => $request->ip(),
        ]);

        Auth::login($admin);
        session()->forget('original_user_id');
        session()->regenerate();

        return redirect()->route('admin.users.index')->with('success', 'Je bent terug als jezelf.');
    }
}
