<?php

namespace App\Observers;

use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;

class FicheObserver
{
    public function created(Fiche $fiche): void
    {
        AssignFicheIcon::dispatch($fiche);
    }

    public function updated(Fiche $fiche): void
    {
        if ($fiche->isDirty('title')) {
            AssignFicheIcon::dispatch($fiche);
        }
    }
}
