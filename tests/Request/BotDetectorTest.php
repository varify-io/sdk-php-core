<?php

declare(strict_types=1);

namespace Varify\Core\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Varify\Core\Request\BotDetector;

#[CoversClass(BotDetector::class)]
#[Group('unit')]
final class BotDetectorTest extends TestCase
{
    #[Test]
    #[DataProvider('recognisesBotsDataProvider')]
    public function recognisesBots(string $userAgent, bool $expected): void
    {
        $isBot = BotDetector::isBot($userAgent);

        static::assertSame($expected, $isBot);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function recognisesBotsDataProvider(): iterable
    {
        yield 'Googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', true];
        yield 'Lighthouse' => ['Mozilla/5.0 (Linux; Android 11) Chrome-Lighthouse', true];
        yield 'Headless Chrome' => ['Mozilla/5.0 HeadlessChrome/120.0.0.0', true];
        yield 'Chrome' => ['Mozilla/5.0 (Macintosh) AppleWebKit/537.36 Chrome/131.0.0.0 Safari/537.36', false];
        yield 'empty' => ['', false];
    }
}
