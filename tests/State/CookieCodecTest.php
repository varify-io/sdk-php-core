<?php

declare(strict_types=1);

namespace Varify\Core\Tests\State;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Varify\Core\Id\VisitorId;
use Varify\Core\State\CookieCodec;
use Varify\Core\State\State;
use Varify\Core\Tests\Support\Fixtures;

#[CoversClass(CookieCodec::class)]
#[Group('unit')]
final class CookieCodecTest extends TestCase
{
    #[Test]
    public function encodesSortedByExperimentWithZeroForTheOriginal(): void
    {
        $state = new State(VisitorId::make(Fixtures::VISITOR_ID), [48812 => 48839, 269 => 0]);

        $value = CookieCodec::encode($state);

        static::assertSame(Fixtures::VISITOR_ID . '.269-0_48812-48839', $value);
    }

    #[Test]
    public function decodesWhatItEncodes(): void
    {
        $value = Fixtures::VISITOR_ID . '.269-0_48812-48839';

        $state = CookieCodec::decode($value);

        static::assertSame(Fixtures::VISITOR_ID, $state->visitorId->value);
        static::assertSame([269 => 0, 48812 => 48839], $state->assignments);
    }

    #[Test]
    public function decodesAVisitorWithoutAssignments(): void
    {
        $value = Fixtures::VISITOR_ID . '.';

        $state = CookieCodec::decode($value);

        static::assertFalse($state->isAnonymous());
        static::assertSame([], $state->assignments);
    }

    #[Test]
    #[DataProvider('treatsMalformedValuesAsANewVisitorDataProvider')]
    public function treatsMalformedValuesAsANewVisitor(string $value): void
    {
        $state = CookieCodec::decode($value);

        static::assertTrue($state->isAnonymous());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function treatsMalformedValuesAsANewVisitorDataProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'no separator' => [Fixtures::VISITOR_ID];
        yield 'not a UUIDv4' => ['3f2a9c1e-5b7d-1e8a-9c21-7d4e5f6a8b90.269-0'];
        yield 'experiment id 0' => [Fixtures::VISITOR_ID . '.0-1'];
        yield 'leading zero' => [Fixtures::VISITOR_ID . '.0269-1'];
        yield 'garbage entry' => [Fixtures::VISITOR_ID . '.269-0_x'];
        yield 'oversized id' => [Fixtures::VISITOR_ID . '.1234567890123456789-1'];
    }
}
