<?php

namespace Tests\Unit\Support;

use App\Support\EmailLink;
use PHPUnit\Framework\TestCase;

class EmailLinkTest extends TestCase
{
    public function test_appends_utm_params_to_a_plain_path(): void
    {
        $result = EmailLink::to('https://site.test/initiatieven', 'welcome', 'lifecycle');

        $this->assertSame(
            'https://site.test/initiatieven?utm_source=lifecycle&utm_medium=email&utm_campaign=welcome',
            $result,
        );
    }

    public function test_uses_ampersand_when_url_already_has_a_query_string(): void
    {
        $result = EmailLink::to('https://site.test/contact?reden=feedback', 'monthly-digest', 'newsletter', 'feedback');

        $this->assertSame(
            'https://site.test/contact?reden=feedback&utm_source=newsletter&utm_medium=email&utm_campaign=monthly-digest&utm_content=feedback',
            $result,
        );
    }

    public function test_inserts_utm_before_a_fragment_not_inside_it(): void
    {
        $result = EmailLink::to('https://site.test/themas#thema-muziek', 'monthly-digest', 'newsletter', 'themes');

        $this->assertSame(
            'https://site.test/themas?utm_source=newsletter&utm_medium=email&utm_campaign=monthly-digest&utm_content=themes#thema-muziek',
            $result,
        );
    }

    public function test_handles_both_query_and_fragment(): void
    {
        $result = EmailLink::to('https://site.test/fiches/1/2?foo=bar#comment-9', 'comment-digest', 'transactional', 'comment');

        $this->assertSame(
            'https://site.test/fiches/1/2?foo=bar&utm_source=transactional&utm_medium=email&utm_campaign=comment-digest&utm_content=comment#comment-9',
            $result,
        );
    }

    public function test_omits_utm_content_when_not_provided(): void
    {
        $result = EmailLink::to('https://site.test/x', 'welcome', 'lifecycle');

        $this->assertStringNotContainsString('utm_content', $result);
    }

    public function test_omits_utm_content_when_empty_string(): void
    {
        $result = EmailLink::to('https://site.test/x', 'welcome', 'lifecycle', '');

        $this->assertStringNotContainsString('utm_content', $result);
    }
}
