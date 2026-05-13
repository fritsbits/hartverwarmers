<?php

namespace App\Http\Controllers;

use App\Mail\FicheCommentDigestMail;
use App\Models\Fiche;
use App\Notifications\MonthlyDigestNotification;
use App\Notifications\OnboardingCuratedActivitiesNotification;
use App\Notifications\OnboardingDownloadMilestoneNotification;
use App\Notifications\OnboardingFirstBookmarkNotification;
use App\Notifications\OnboardingMilestone10BookmarksNotification;
use App\Notifications\OnboardingMilestone50BookmarksNotification;
use App\Notifications\OnboardingTopFiveNotification;
use App\Notifications\WelcomeNotification;
use App\Services\MonthlyDigest\Composer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\View\View;

class MailPreviewController extends Controller
{
    /**
     * Known email types with display metadata.
     *
     * @var array<string, array{label: string, description: string}>
     */
    private const EMAILS = [
        'verify-email' => [
            'label' => 'E-mailverificatie',
            'description' => 'Bij registratie en aanmaak gastaccount.',
        ],
        'reset-password' => [
            'label' => 'Wachtwoord resetten',
            'description' => 'Bij "wachtwoord vergeten" en na aanmaak gastaccount.',
        ],
        'welcome' => [
            'label' => 'Welkomstmail',
            'description' => 'Na e-mailverificatie, oriënterend en warm.',
        ],
        'fiche-comment-digest' => [
            'label' => 'Reactie-digest',
            'description' => 'Dagelijks of wekelijks overzicht van nieuwe reacties op je fiches.',
        ],
        'monthly-digest' => [
            'label' => 'Maandelijkse nieuwsbrief',
            'description' => 'Cohort-gebaseerde maandelijkse update: themadagen, diamantje, recente fiches.',
        ],
        'onboarding-curated-activities' => [
            'label' => 'Onboarding — Curated activiteiten',
            'description' => 'Dag 3–5 na activatie. 2–3 handgepickte fiches.',
        ],
        'onboarding-top-five' => [
            'label' => 'Onboarding — Top 5 activiteiten',
            'description' => 'Dag 7–14 na activatie. Dynamische top-5 meest gebookmarkte fiches.',
        ],
        'onboarding-contribute-invitation' => [
            'label' => 'Onboarding — Download milestone',
            'description' => 'Triggered after user has downloaded X activiteiten (based on fiche-download count).',
        ],
        'onboarding-first-bookmark' => [
            'label' => 'Onboarding — Eerste bookmark',
            'description' => 'Realtime: eerste bookmark op een fiche van de gebruiker.',
        ],
        'onboarding-milestone-10-bookmarks' => [
            'label' => 'Onboarding — 10 bookmarks',
            'description' => 'Realtime: 10e bookmark op fiches van de gebruiker.',
        ],
        'onboarding-milestone-50-bookmarks' => [
            'label' => 'Onboarding — 50 bookmarks',
            'description' => 'Realtime: 50e bookmark op fiches van de gebruiker.',
        ],
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        $emails = collect(self::EMAILS)->map(fn ($meta, $key) => [
            ...$meta,
            ...$this->getMailMeta($key, $user),
        ]);

        return view('admin.mails.index', ['emails' => $emails, 'emailTypes' => self::EMAILS]);
    }

    public function show(Request $request, string $email): View
    {
        if (! isset(self::EMAILS[$email])) {
            abort(404);
        }

        $user = $request->user();
        $meta = self::EMAILS[$email];

        return view('admin.mails.show', [
            'key' => $email,
            'email' => [...$meta, ...$this->getMailMeta($email, $user)],
            'emails' => self::EMAILS,
        ]);
    }

    public function preview(Request $request, string $email): Response
    {
        if (! isset(self::EMAILS[$email])) {
            abort(404);
        }

        $mail = $this->buildMail($email, $request->user());

        return response($this->renderHtml($mail))->header('Content-Type', 'text/html');
    }

    /**
     * @return array{subject: string, from: string}
     */
    private function getMailMeta(string $email, mixed $user): array
    {
        $mail = $this->buildMail($email, $user);

        if ($mail instanceof Mailable) {
            $envelope = $mail->envelope();
            $fromAddress = $envelope->from?->address ?? config('mail.from.address');
            $fromName = $envelope->from?->name ?? config('mail.from.name');

            return [
                'subject' => $envelope->subject ?? '',
                'from' => $fromName ? "{$fromName} <{$fromAddress}>" : $fromAddress,
            ];
        }

        $fromAddress = $mail->from[0] ?? config('mail.from.address');
        $fromName = $mail->from[1] ?? config('mail.from.name');

        return [
            'subject' => $mail->subject,
            'from' => $fromName ? "{$fromName} <{$fromAddress}>" : $fromAddress,
        ];
    }

    private function buildMail(string $email, mixed $user): MailMessage|Mailable
    {
        return match ($email) {
            'verify-email' => (new VerifyEmail)->toMail($user),
            'reset-password' => (new ResetPassword('fake-token-for-preview'))->toMail($user),
            'welcome' => (new WelcomeNotification)->toMail($user),
            'fiche-comment-digest' => $this->buildFicheCommentDigestMail($user),
            'monthly-digest' => $this->buildMonthlyDigestMail($user),
            'onboarding-curated-activities' => (new OnboardingCuratedActivitiesNotification)->toMail($user),
            'onboarding-top-five' => (new OnboardingTopFiveNotification)->toMail($user),
            'onboarding-contribute-invitation' => (new OnboardingDownloadMilestoneNotification(5))->toMail($user),
            'onboarding-first-bookmark' => $this->buildOnboardingFirstBookmarkMailMessage($user),
            'onboarding-milestone-10-bookmarks' => (new OnboardingMilestone10BookmarksNotification(10))->toMail($user),
            'onboarding-milestone-50-bookmarks' => (new OnboardingMilestone50BookmarksNotification(50))->toMail($user),
            default => throw new \InvalidArgumentException("Unknown email key: {$email}"),
        };
    }

    private function buildOnboardingFirstBookmarkMailMessage(mixed $user): MailMessage
    {
        $fiche = Fiche::published()->with(['user', 'initiative'])->firstOrFail();

        return (new OnboardingFirstBookmarkNotification($fiche))->toMail($user);
    }

    private function buildMonthlyDigestMail(mixed $user): MailMessage
    {
        $payload = app(Composer::class)->compose(now());
        $cycle = $user->currentDigestCycleNumber();

        return (new MonthlyDigestNotification($payload, cycle: $cycle))->toMail($user);
    }

    private function buildFicheCommentDigestMail(mixed $user): FicheCommentDigestMail
    {
        $fiche = Fiche::published()->with(['user', 'initiative'])->firstOrFail();

        $payloads = [
            [
                'comment_id' => 1,
                'commenter_name' => 'Anna Janssens',
                'body_excerpt' => 'Wat een mooi initiatief! Onze bewoners hebben er enorm van genoten. Bedankt voor de inspiratie.',
                'comment_url' => route('fiches.show', [$fiche->initiative, $fiche]).'#comment-1',
            ],
            [
                'comment_id' => 2,
                'commenter_name' => 'Sofie Peeters',
                'body_excerpt' => 'Hebben jullie ook tips voor bewoners met dementie? Ik wil dit graag aanpassen voor onze afdeling.',
                'comment_url' => route('fiches.show', [$fiche->initiative, $fiche]).'#comment-2',
            ],
        ];

        return new FicheCommentDigestMail($user, $fiche, $payloads);
    }

    private function renderHtml(MailMessage|Mailable $mail): string
    {
        $rendered = $mail->render();

        return is_string($rendered) ? $rendered : $rendered->toHtml();
    }
}
