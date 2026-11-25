<?php

declare(strict_types=1);

namespace Varify\Core\Tests\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Varify\Core\Cache\CacheVariant;
use Varify\Core\Request\VarifyRequest;
use Varify\Core\Tests\Support\FakeTransport;
use Varify\Core\Tests\Support\Fixtures;
use Varify\Core\VarifyOptions;

#[CoversClass(CacheVariant::class)]
#[Group('unit')]
final class CacheVariantTest extends TestCase
{
    private const BROWSER = 'Mozilla/5.0 (Macintosh) AppleWebKit/537.36 Chrome/131.0.0.0 Safari/537.36';

    #[Test]
    public function keysAReturningVisitorByConfigVersionAndAssignmentsOnly(): void
    {
        $request = new VarifyRequest(Fixtures::VISITOR_ID . '.48812-48838_269-0', '', self::BROWSER);

        $variant = CacheVariant::forRequest(self::options(), $request);

        static::assertTrue($variant->cacheable);
        static::assertSame(Fixtures::config()->version . ':269-0_48812-48838', $variant->key);
    }

    #[Test]
    public function sharesTheKeyBetweenVisitorsWithTheSameAssignments(): void
    {
        $other = new VarifyRequest('9b1c2d3e-4f5a-4b6c-8d7e-8f9a0b1c2d3e.269-0_48812-48838', '', self::BROWSER);
        $mine = new VarifyRequest(Fixtures::VISITOR_ID . '.269-0_48812-48838', '', self::BROWSER);

        $variant = CacheVariant::forRequest(self::options(), $other);

        static::assertSame(CacheVariant::forRequest(self::options(), $mine)->key, $variant->key);
    }

    #[Test]
    public function aHumanWithoutACookieIsNotCacheable(): void
    {
        $request = new VarifyRequest('', '', self::BROWSER);

        $variant = CacheVariant::forRequest(self::options(), $request);

        static::assertFalse($variant->cacheable);
    }

    #[Test]
    public function botsShareOneVariant(): void
    {
        $request = new VarifyRequest('', '', 'Mozilla/5.0 (compatible; Googlebot/2.1)');

        $variant = CacheVariant::forRequest(self::options(), $request);

        static::assertTrue($variant->cacheable);
        static::assertSame('bot', $variant->key);
    }

    private static function options(): VarifyOptions
    {
        return VarifyOptions::forTeam('team')->withTransport(FakeTransport::responding(Fixtures::CONFIG_JSON));
    }
}
