<?php

namespace App\Http\Controllers;

use App\Services\ProductUpdates;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WhatsNewController extends Controller
{
    public function index(): View
    {
        return view('whats-new.index', ['updates' => ProductUpdates::all()]);
    }

    public function show(string $uid): View
    {
        $update = ProductUpdates::find($uid);

        if ($update === null) {
            throw new NotFoundHttpException;
        }

        return view('whats-new.show', [
            'update' => $update,
            'content' => ProductUpdates::renderContent($update),
            'newer' => ProductUpdates::newerThan($uid),
            'older' => ProductUpdates::olderThan($uid),
        ]);
    }
}
