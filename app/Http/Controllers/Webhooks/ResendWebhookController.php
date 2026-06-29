<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\EmailBounce;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ResendWebhookController extends Controller
{
    /**
     * Receive Resend delivery webhooks and suppress addresses that hard-bounce
     * or file a spam complaint, so future campaigns skip them.
     */
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->signatureIsValid($request)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $type = (string) $request->input('type');
        $recipients = (array) $request->input('data.to', []);

        foreach ($recipients as $email) {
            if ($type === 'email.bounced' && $this->isPermanentBounce($request)) {
                $this->suppress($email, 'bounce', $request->input('data.bounce.message'));
            }

            if ($type === 'email.complained') {
                $this->suppress($email, 'complaint', null);
            }
        }

        return response()->json(['message' => 'ok']);
    }

    /**
     * Only permanent (hard) bounces are suppressed; a temporary bounce — a full
     * or briefly unreachable mailbox — must not knock a real recipient out.
     */
    protected function isPermanentBounce(Request $request): bool
    {
        $bounceType = (string) $request->input('data.bounce.type');

        return strtolower($bounceType) === 'permanent';
    }

    protected function suppress(string $email, string $type, ?string $reason): void
    {
        EmailBounce::updateOrCreate(
            ['email' => $email],
            ['type' => $type, 'reason' => $reason, 'bounced_at' => Carbon::now()],
        );
    }

    /**
     * Verify the Svix signature Resend sends, so the public endpoint can't be
     * forged to suppress arbitrary recipients.
     */
    protected function signatureIsValid(Request $request): bool
    {
        $secret = config('services.resend.webhook_secret');

        if (empty($secret)) {
            return false;
        }

        $id = $request->header('svix-id');
        $timestamp = $request->header('svix-timestamp');
        $signatureHeader = $request->header('svix-signature');

        if (! $id || ! $timestamp || ! $signatureHeader) {
            return false;
        }

        $secretBytes = base64_decode(substr($secret, strpos($secret, '_') + 1));
        $signedContent = "{$id}.{$timestamp}.{$request->getContent()}";
        $expected = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

        foreach (explode(' ', $signatureHeader) as $part) {
            [, $signature] = array_pad(explode(',', $part, 2), 2, '');

            if ($signature !== '' && hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
