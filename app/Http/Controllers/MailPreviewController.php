<?php

namespace App\Http\Controllers;

use App\Mail\FicheCommentDigestMail;
use App\Models\Fiche;
use App\Notifications\ContributorAnniversaryNotification;
use App\Notifications\FicheDiamondAwardedNotification;
use App\Notifications\MonthlyDigestNotification;
use App\Notifications\OnboardingCuratedActivitiesNotification;
use App\Notifications\OnboardingDownloadMilestoneNotification;
use App\Notifications\OnboardingFirstBookmarkNotification;
use App\Notifications\OnboardingMilestone10BookmarksNotification;
use App\Notifications\OnboardingMilestone50BookmarksNotification;
use App\Notifications\OnboardingTopFiveNotification;
use App\Notifications\WelcomeNotification;
use App\Services\ContributorAnniversary\Composer as AnniversaryComposer;
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
    public const CATEGORIES = [
        'notifications' => 'Notificaties',
        'newsletter' => 'Nieuwsbrief',
        'onboarding' => 'Onboarding',
        'recognition' => 'Bijdragers',
        'transactional' => 'Transactioneel',
    ];

    /**
     * Known email types with display metadata.
     *
     * @var array<string, array{label: string, description: string, category: string, trigger: string}>
     */
    private const EMAILS = [
        'fiche-comment-digest' => [
            'label' => 'Reactie-digest',
            'description' => 'Dagelijks of wekelijks overzicht van nieuwe reacties op je fiches.',
            'category' => 'notifications',
            'trigger' => 'Dagelijks / wekelijks om 08:00',
        ],
        'onboarding-first-bookmark' => [
            'label' => 'Eerste bookmark',
            'description' => 'Realtime: eerste bookmark op een fiche van de gebruiker.',
            'category' => 'notifications',
            'trigger' => 'Eerste bookmark op een fiche',
        ],
        'onboarding-milestone-10-bookmarks' => [
            'label' => '10 bookmarks',
            'description' => 'Realtime: 10e bookmark op fiches van de gebruiker.',
            'category' => 'notifications',
            'trigger' => '10e bookmark op fiches',
        ],
        'onboarding-milestone-50-bookmarks' => [
            'label' => '50 bookmarks',
            'description' => 'Realtime: 50e bookmark op fiches van de gebruiker.',
            'category' => 'notifications',
            'trigger' => '50e bookmark op fiches',
        ],
        'monthly-digest' => [
            'label' => 'Maandelijkse nieuwsbrief',
            'description' => 'Cohort-gebaseerde maandelijkse update: themadagen, diamantje, recente fiches.',
            'category' => 'newsletter',
            'trigger' => 'Maandelijks (cohort, 08:00)',
        ],
        'welcome' => [
            'label' => 'Welkomstmail',
            'description' => 'Na e-mailverificatie, oriënterend en warm.',
            'category' => 'onboarding',
            'trigger' => 'Na e-mailverificatie',
        ],
        'onboarding-curated-activities' => [
            'label' => 'Curated activiteiten',
            'description' => 'Dag 3–5 na activatie. 2–3 handgepickte fiches.',
            'category' => 'onboarding',
            'trigger' => 'Dag 3–5 na activatie',
        ],
        'onboarding-top-five' => [
            'label' => 'Top 5 activiteiten',
            'description' => 'Dag 7–14 na activatie. Dynamische top-5 meest gebookmarkte fiches.',
            'category' => 'onboarding',
            'trigger' => 'Dag 7–14 na activatie',
        ],
        'onboarding-contribute-invitation' => [
            'label' => 'Uitnodiging bijdragen',
            'description' => 'Na het downloaden van een aantal activiteiten.',
            'category' => 'onboarding',
            'trigger' => 'Na X downloads',
        ],
        'diamond-awarded' => [
            'label' => 'Diamantje toegekend',
            'description' => 'Realtime: jouw fiche werd door ons uitgekozen als diamantje.',
            'category' => 'recognition',
            'trigger' => 'Wanneer has_diamond=true wordt gezet',
        ],
        'anniversary' => [
            'label' => 'Bijdragers-verjaardag',
            'description' => 'Jaarlijks op de verjaardag van iemands eerste fiche.',
            'category' => 'recognition',
            'trigger' => 'Dagelijks om 08:00 (cohort)',
        ],
        'verify-email' => [
            'label' => 'E-mailverificatie',
            'description' => 'Bij registratie en aanmaak gastaccount.',
            'category' => 'transactional',
            'trigger' => 'Bij registratie',
        ],
        'reset-password' => [
            'label' => 'Wachtwoord resetten',
            'description' => 'Bij "wachtwoord vergeten" en na aanmaak gastaccount.',
            'category' => 'transactional',
            'trigger' => 'Bij "wachtwoord vergeten"',
        ],
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        $emails = collect(self::EMAILS)->map(fn ($meta, $key) => [
            'key' => $key,
            ...$meta,
            ...$this->getMailMeta($key, $user),
        ]);

        $grouped = collect(self::CATEGORIES)
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'emails' => $emails->where('category', $key)->values(),
            ])
            ->values();

        return view('admin.mails.index', ['categories' => $grouped]);
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
            'diamond-awarded' => $this->buildDiamondAwardedMail($user),
            'anniversary' => $this->buildAnniversaryMail($user),
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

    private function buildDiamondAwardedMail(mixed $user): MailMessage
    {
        $fiche = Fiche::published()->with('initiative')->firstOrFail();

        return (new FicheDiamondAwardedNotification($fiche))->toMail($user);
    }

    private function buildAnniversaryMail(mixed $user): MailMessage
    {
        $payload = app(AnniversaryComposer::class)->compose($user);

        return (new ContributorAnniversaryNotification($payload, year: 1))->toMail($user);
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
