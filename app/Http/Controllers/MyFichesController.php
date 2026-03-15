<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MyFichesController extends Controller
{
    public function __invoke(): View
    {
        return view('my-fiches');
    }
}
