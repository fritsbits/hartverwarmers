<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'curator' THEN 2 WHEN 'contributor' THEN 3 WHEN 'member' THEN 4 ELSE 5 END")
            ->orderBy('first_name')
            ->get();

        return view('admin.users.index', ['users' => $users]);
    }
}
