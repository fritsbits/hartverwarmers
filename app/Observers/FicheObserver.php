<?php

namespace App\Observers;

use App\Jobs\AssessFicheQuality;
use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;

class FicheObserver
{
    private const CONTENT_FIELDS = ['description', 'materials', 'practical_tips', 'target_audience', 'title'];

    public function saving(Fiche $fiche): void
    {
        $fiche->completeness_score = $fiche->calculateCompletenessScore();
    }

    public function created(Fiche $fiche): void
    {
        AssignFicheIcon::dispatch($fiche);

        if ($fiche->published) {
            $this->dispatchQualityAssessment($fiche);
        }
    }

    public function updated(Fiche $fiche): void
    {
        if ($fiche->isDirty('title')) {
            AssignFicheIcon::dispatch($fiche);
        }

        $becamePublished = $fiche->isDirty('published') && $fiche->published;
        $contentChanged = $fiche->published && $fiche->isDirty(self::CONTENT_FIELDS);

        if ($becamePublished || $contentChanged) {
            $this->dispatchQualityAssessment($fiche);
        }
    }

    private function dispatchQualityAssessment(Fiche $fiche): void
    {
        if ($fiche->quality_assessed_at && $fiche->quality_assessed_at->diffInMinutes(now()) < 10) {
            return;
        }

        AssessFicheQuality::dispatch($fiche);
    }
}
