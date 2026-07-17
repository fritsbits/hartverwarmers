<?php

namespace App\Http\Controllers;

use App\Services\ProductUpdates;
use Illuminate\View\View;

class WhatsNewController extends Controller
{
    public function index(): View
    {
        return view('whats-new', ['updates' => ProductUpdates::all()]);
    }
}
