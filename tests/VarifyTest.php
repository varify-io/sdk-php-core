<?php

declare(strict_types=1);

namespace Varify\Core\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Varify\Core\Cache\CacheVariant;
use Varify\Core\Id\VisitorId;
use Varify\Core\Request\VarifyRequest;
use Varify\Core\Tests\Support\FakeTransport;
use Varify\Core\Tests\Support\Fixtures;
use Varify\Core\Varify;
use Varify\Core\VarifyError;
use Varify\Core\VarifyErrorReason;
use Varify\Core\VarifyOptions;

#[CoversClass(Varify::class)]
#[Group('unit')]
final class VarifyTest extends TestCase
{
    private const BROWSER = 'Mozilla/5.0 (Macintosh) AppleWebKit/537.36 Chrome/131.0.0.0 Safari/537.36';

    #[Test]
    public function assignsANewVisitorAndAsksForTheCookie(): void
    {
        $request = new VarifyRequest('', '', self::BROWSER);

        $varify = Varify::forRequest(self::options(), $request);

        static::assertTrue($varify->needsCookieWrite());
        static::assertMatchesRegularExpression('/^[0-9a-f-]{36}\.269-\d+_48812-\d+$/', $varify->cookieValue());
        static::assertTrue(VisitorId::isValid($varify->visitorId()->value));
    }

    #[Test]
    public function keepsAReturningVisitorWithoutRewritingTheCookie(): void
    {
        $request = new VarifyRequest(Fixtures::VISITOR_ID . '.269-311_48812-48838', '', self::BROWSER);

        $varify = Varify::forRequest(self::options(), $request);

        static::assertFalse($varify->needsCookieWrite());
        static::assertTrue($varify->assignment(48812)->is(48838));
        static::assertSame(Fixtures::VISITOR_ID, $varify->visitorId()->value);
    }

    #[Test]
    public function showsBotsTheOriginalWithoutAnId(): void
    {
        $request = new VarifyRequest('', '', 'Mozilla/5.0 (compatible; Googlebot/2.1)');

        $varify = Varify::forRequest(self::options(), $request);

        static::assertTrue($varify->assignment(48812)->isOriginal());
        static::assertFalse($varify->needsCookieWrite());
        static::assertTrue($varify->visitorId()->isNone());
        static::assertSame('', $varify->trackingSnippet());
    }

    #[Test]
    public function tracksEachReadExperimentOnceWithTrackingEnabledOnly(): void
    {
        $request = new VarifyRequest(Fixtures::VISITOR_ID . '.269-311_48812-48838', '', self::BROWSER);
        $varify = Varify::forRequest(self::options(), $request);
        $varify->assignment(48812);
        $varify->assignment(48812);
        $varify->assignment(269);

        $snippet = $varify->trackingSnippet();

        static::assertSame(1, substr_count($snippet, '"experiment":{"id":48812'));
        static::assertStringNotContainsString('"id":269', $snippet);
    }

    #[Test]
    public function showsTheOriginalWhenTheConfigFailsToLoad(): void
    {
        $failing = FakeTransport::failing(new VarifyError('down', VarifyErrorReason::Network));
        $request = new VarifyRequest(Fixtures::VISITOR_ID . '.48812-48838', '', self::BROWSER);

        $varify = Varify::forRequest(self::options()->withTransport($failing), $request);

        static::assertTrue($varify->assignment(48812)->isOriginal());
        static::assertFalse($varify->needsCookieWrite());
        static::assertSame('', $varify->trackingSnippet());
    }

    #[Test]
    public function itsCacheVariantIsTheOneTheVisitorsNextRequestLooksUp(): void
    {
        $varify = Varify::forRequest(self::options(), new VarifyRequest('', '', self::BROWSER));
        $nextRequest = new VarifyRequest($varify->cookieValue(), '', self::BROWSER);

        $variant = $varify->cacheVariant();

        static::assertSame(CacheVariant::forRequest(self::options(), $nextRequest)->key, $variant->key);
    }

    #[Test]
    public function readOnlyShowsAVisitorWithoutACookieTheOriginalWithoutAssigning(): void
    {
        $request = new VarifyRequest('', '', self::BROWSER);

        $varify = Varify::readOnly(self::options(), $request);

        static::assertTrue($varify->assignment(48812)->isOriginal());
        static::assertTrue($varify->visitorId()->isNone());
        static::assertFalse($varify->needsCookieWrite());
        static::assertFalse($varify->cacheVariant()->cacheable);
    }

    #[Test]
    public function readOnlyAssignsAKnownVisitorLikeThePageButNeverWritesTheCookie(): void
    {
        $request = new VarifyRequest(Fixtures::VISITOR_ID . '.48812-48838', '', self::BROWSER);

        $varify = Varify::readOnly(self::options(), $request);

        static::assertTrue($varify->assignment(48812)->is(48838));
        static::assertTrue($varify->assignment(269)->is(311));
        static::assertFalse($varify->needsCookieWrite());
    }

    private static function options(): VarifyOptions
    {
        return VarifyOptions::forTeam('team')->withTransport(FakeTransport::responding(Fixtures::CONFIG_JSON));
    }
}
