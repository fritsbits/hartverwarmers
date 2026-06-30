<?php

namespace App\Notifications;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Middleware\RateLimited;

abstract class BaseMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Cap genuine send failures (Resend errors, view exceptions) so a truly
     * broken job fails fast instead of retrying for the whole retryUntil window.
     * Rate-limit releases are not exceptions, so they don't count against this.
     */
    public int $maxExceptions = 3;

    public function middleware(): array
    {
        return [new RateLimited('resend')];
    }

    /**
     * Use a time-based attempt ceiling instead of a count-based one. The
     * RateLimited('resend') middleware releases jobs back onto the queue when
     * the 4/sec limit is hit, and every release increments the attempt counter.
     * A burst send (e.g. the monthly cohort) would otherwise exhaust the
     * worker's max-tries and throw MaxAttemptsExceededException before the job
     * ever gets its turn to send. While retryUntil is set, the worker bypasses
     * the max-attempts check, so releases no longer accumulate toward a ceiling.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addHour();
    }
}
