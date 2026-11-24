<?php

declare(strict_types=1);

namespace Varify\Core\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Varify\Core\Id\ExperimentId;
use Varify\Core\Request\ForcedVariation;

#[CoversClass(ForcedVariation::class)]
#[Group('unit')]
final class ForcedVariationTest extends TestCase
{
    #[Test]
    public function parsesExperimentAndVariation(): void
    {
        $force = ForcedVariation::parse('48812-48838');

        static::assertTrue($force->appliesTo(ExperimentId::make(48812)));
        static::assertSame(48838, $force->variationId);
        static::assertFalse($force->forcesOriginal());
    }

    #[Test]
    public function variationZeroForcesTheOriginal(): void
    {
        $force = ForcedVariation::parse('48812-0');

        static::assertTrue($force->forcesOriginal());
    }

    #[Test]
    #[DataProvider('forcesNothingForAnythingElseDataProvider')]
    public function forcesNothingForAnythingElse(string $value): void
    {
        $force = ForcedVariation::parse($value);

        static::assertFalse($force->appliesTo(ExperimentId::make(48812)));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function forcesNothingForAnythingElseDataProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'one number' => ['48812'];
        yield 'letters' => ['abc-def'];
        yield 'two experiments' => ['48812-48838,269-311'];
    }
}
