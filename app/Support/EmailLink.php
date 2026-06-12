<?php

namespace App\Support;

class EmailLink
{
    /**
     * Append UTM analytics parameters to an email link.
     *
     * `utm_medium` is always "email". `$source` is the coarse roll-up bucket
     * (newsletter | lifecycle | transactional). `$campaign` names the email
     * type. `$content` (optional) names the link's location within the email.
     *
     * Correctly handles URLs that already carry a query string or a fragment:
     * the UTM parameters always land in the query string, before any `#fragment`.
     */
    public static function to(string $url, string $campaign, string $source, ?string $content = null): string
    {
        $query = http_build_query(array_filter([
            'utm_source' => $source,
            'utm_medium' => 'email',
            'utm_campaign' => $campaign,
            'utm_content' => $content,
        ], static fn (?string $value): bool => $value !== null && $value !== ''));

        $fragment = '';

        if (($hashPosition = strpos($url, '#')) !== false) {
            $fragment = substr($url, $hashPosition);
            $url = substr($url, 0, $hashPosition);
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.$query.$fragment;
    }
}
