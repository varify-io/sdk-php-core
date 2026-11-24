<?php

declare(strict_types=1);

namespace Varify\Core\Tests\Assignment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Varify\Core\Assignment\Bucket;
use Varify\Core\Id\ExperimentId;
use Varify\Core\Id\VisitorId;

#[CoversClass(Bucket::class)]
#[Group('unit')]
final class BucketTest extends TestCase
{
    #[Test]
    #[DataProvider('hashesLikeTheJsSdkDataProvider')]
    public function hashesLikeTheJsSdk(string $key, int $seed, int $expected): void
    {
        $hash = Bucket::murmur3($key, $seed);

        static::assertSame($expected, $hash);
    }

    /**
     * Reference values from varify-sdk-js `packages/core/test/murmur3.test.ts`, plus a UTF-8 key.
     *
     * @return iterable<string, array{string, int, int}>
     */
    public static function hashesLikeTheJsSdkDataProvider(): iterable
    {
        yield 'empty, seed 0' => ['', 0, 0];
        yield 'empty, seed 1' => ['', 1, 0x514e28b7];
        yield 'hello' => ['hello', 0, 0x248bfa47];
        yield 'quick brown fox' => ['The quick brown fox jumps over the lazy dog', 0, 0x2e4ff723];
        yield 'a' => ['a', 0x9747b28c, 0x7fa09ea6];
        yield 'aa' => ['aa', 0x9747b28c, 0x5d211726];
        yield 'aaa' => ['aaa', 0x9747b28c, 0x283e0130];
        yield 'aaaa' => ['aaaa', 0x9747b28c, 0x5a97808a];
        yield 'Hello, world!' => ['Hello, world!', 0x9747b28c, 0x24884cba];
        yield 'UTF-8' => ['269:ümlaut-ü', 1, 0x02ab2c98];
    }

    #[Test]
    #[DataProvider('bucketsLikeTheJsSdkDataProvider')]
    public function bucketsLikeTheJsSdk(int $experimentId, string $visitorId, float $expected): void
    {
        $bucket = Bucket::of(ExperimentId::make($experimentId), VisitorId::make($visitorId));

        static::assertSame($expected, $bucket);
    }

    /**
     * Computed with varify-sdk-js `bucket()`; shared fixtures keep both SDKs splitting visitors identically.
     *
     * @return iterable<string, array{int, string, float}>
     */
    public static function bucketsLikeTheJsSdkDataProvider(): iterable
    {
        yield 'experiment 48812' => [48812, '3f2a9c1e-5b7d-4e8a-9c21-7d4e5f6a8b90', 56.00723058450967];
        yield 'experiment 269' => [269, '3f2a9c1e-5b7d-4e8a-9c21-7d4e5f6a8b90', 61.73467603512108];
    }
}
