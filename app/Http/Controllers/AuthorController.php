<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(): View
    {
        $authors = Author::query()
            ->orderBy('name')
            ->get();

        return view('authors.index', [
            'authors' => $authors,
        ]);
    }

    public function show(Author $author): View
    {
        return view('authors.show', [
            'author' => $author,
        ]);
    }
}
