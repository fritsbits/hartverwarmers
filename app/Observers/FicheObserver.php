<?php

namespace App\Observers;

use App\Jobs\AssessFicheQuality;
use App\Jobs\AssignFicheIcon;
use App\Models\Fiche;
use App\Models\OnboardingEmailLog;
use App\Notifications\FicheDiamondAwardedNotification;
use Illuminate\Database\UniqueConstraintViolationException;

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

        if ($fiche->wasChanged('has_diamond') && $fiche->has_diamond) {
            $this->notifyDiamondAwarded($fiche);
        }
    }

    private function notifyDiamondAwarded(Fiche $fiche): void
    {
        $owner = $fiche->user;
        if (! $owner || ! $owner->notify_on_kudos_milestones) {
            return;
        }

        $mailKey = "diamantje-{$fiche->id}";

        if (OnboardingEmailLog::where('user_id', $owner->id)->where('mail_key', $mailKey)->exists()) {
            return;
        }

        try {
            OnboardingEmailLog::create([
                'user_id' => $owner->id,
                'mail_key' => $mailKey,
                'sent_at' => now(),
            ]);
            $owner->notify(new FicheDiamondAwardedNotification($fiche));
        } catch (UniqueConstraintViolationException) {
            // Concurrent: another request already logged this diamantje.
        }
    }

    private function dispatchQualityAssessment(Fiche $fiche): void
    {
        if (empty(config('ai.providers.anthropic.key'))) {
            return;
        }

        if ($fiche->quality_assessed_at && $fiche->quality_assessed_at->diffInMinutes(now()) < 10) {
            return;
        }

        AssessFicheQuality::dispatch($fiche);
    }
}
