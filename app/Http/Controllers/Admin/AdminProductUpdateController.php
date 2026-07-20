<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminProductUpdateController extends Controller
{
    public function index(): View
    {
        return view('admin.product-updates.index');
    }
}
