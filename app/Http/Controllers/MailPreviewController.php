<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Fiche;
use App\Models\User;
use App\Notifications\FicheCommentNotification;
use App\Notifications\OnboardingContributeInvitationNotification;
use App\Notifications\OnboardingCuratedActivitiesNotification;
use App\Notifications\OnboardingFirstBookmarkNotification;
use App\Notifications\OnboardingMilestone10BookmarksNotification;
use App\Notifications\OnboardingMilestone50BookmarksNotification;
use App\Notifications\OnboardingTopFiveNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        'fiche-comment' => [
            'label' => 'Reactie op fiche',
            'description' => 'Notificatie naar de bijdrager wanneer iemand reageert op hun fiche.',
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
            'label' => 'Onboarding — Uitnodiging bijdragen',
            'description' => 'Dag 14–21, alleen als geen gepubliceerde fiche.',
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

        $user = $request->user();
        $mail = $this->buildMailMessage($email, $user);

        return response($this->renderMailMessage($mail))->header('Content-Type', 'text/html');
    }

    /**
     * @return array{subject: string, from: string}
     */
    private function getMailMeta(string $email, mixed $user): array
    {
        $mail = $this->buildMailMessage($email, $user);

        $fromAddress = $mail->from[0] ?? config('mail.from.address');
        $fromName = $mail->from[1] ?? config('mail.from.name');

        return [
            'subject' => $mail->subject,
            'from' => $fromName ? "{$fromName} <{$fromAddress}>" : $fromAddress,
        ];
    }

    private function buildMailMessage(string $email, mixed $user): MailMessage
    {
        return match ($email) {
            'verify-email' => (new VerifyEmail)->toMail($user),
            'reset-password' => (new ResetPassword('fake-token-for-preview'))->toMail($user),
            'welcome' => (new WelcomeNotification)->toMail($user),
            'fiche-comment' => $this->buildFicheCommentMailMessage(),
            'onboarding-curated-activities' => (new OnboardingCuratedActivitiesNotification)->toMail($user),
            'onboarding-top-five' => (new OnboardingTopFiveNotification)->toMail($user),
            'onboarding-contribute-invitation' => (new OnboardingContributeInvitationNotification)->toMail($user),
            'onboarding-first-bookmark' => $this->buildOnboardingFirstBookmarkMailMessage($user),
            'onboarding-milestone-10-bookmarks' => (new OnboardingMilestone10BookmarksNotification(10))->toMail($user),
            'onboarding-milestone-50-bookmarks' => (new OnboardingMilestone50BookmarksNotification(50))->toMail($user),
            default => throw new \InvalidArgumentException("Unknown email key: {$email}"),
        };
    }

    private function buildFicheCommentMailMessage(): MailMessage
    {
        $fiche = Fiche::published()->with(['user', 'initiative'])->firstOrFail();
        $commenter = User::factory()->make(['first_name' => 'Liesbet']);
        $comment = new Comment(['body' => 'Wat een mooi initiatief!', 'user_id' => $commenter->id]);
        $comment->setRelation('commentable', $fiche);
        $comment->setRelation('user', $commenter);

        return (new FicheCommentNotification($comment))->toMail($fiche->user);
    }

    private function buildOnboardingFirstBookmarkMailMessage(mixed $user): MailMessage
    {
        $fiche = Fiche::published()->with(['user', 'initiative'])->firstOrFail();

        return (new OnboardingFirstBookmarkNotification($fiche))->toMail($user);
    }

    private function renderMailMessage(MailMessage $message): string
    {
        return $message->render()->toHtml();
    }
}
