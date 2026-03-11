<?php

namespace App\Http\Controllers;

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
        };
    }

    private function renderMailMessage(MailMessage $message): string
    {
        return $message->render()->toHtml();
    }
}
