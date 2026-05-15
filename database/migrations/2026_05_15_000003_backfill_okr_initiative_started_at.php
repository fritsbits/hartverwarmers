<?php

use App\Models\Okr\Initiative;
use App\Models\Okr\InitiativeBaseline;
use App\Services\Okr\BaselineCapturer;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $startedAt = [
            'ai-suggesties' => '2026-03-17',
            'onboarding-emails' => '2026-04-02',
            'nieuwsbrief-systeem' => '2026-05-13',
        ];

        $capturer = app(BaselineCapturer::class);

        foreach ($startedAt as $slug => $date) {
            $initiative = Initiative::where('slug', $slug)->first();

            if ($initiative === null) {
                continue;
            }

            // Set started_at without firing the saved hook (we capture explicitly below).
            Initiative::withoutEvents(function () use ($initiative, $date) {
                $initiative->update(['started_at' => $date]);
            });

            $capturer->captureFor($initiative->fresh());
        }
    }

    public function down(): void
    {
        $slugs = ['ai-suggesties', 'onboarding-emails', 'nieuwsbrief-systeem'];

        $initiativeIds = Initiative::whereIn('slug', $slugs)->pluck('id');

        InitiativeBaseline::whereIn('initiative_id', $initiativeIds)->delete();

        Initiative::withoutEvents(function () use ($slugs) {
            Initiative::whereIn('slug', $slugs)->update(['started_at' => null]);
        });
    }
};
