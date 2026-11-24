<?php

declare(strict_types=1);

namespace Varify\Core\Assignment;

use Varify\Core\Id\ExperimentId;
use Varify\Core\Id\VisitorId;

use function hash;
use function hexdec;

/**
 * `murmur3("{experimentId}:{visitorId}", seed 1) / 2³² × 100`, exactly as varify-sdk-js computes it. Any change moves
 * visitors between variations of running experiments.
 */
final class Bucket
{
    public static function of(ExperimentId $experimentId, VisitorId $visitorId): float
    {
        return self::murmur3("{$experimentId->value}:{$visitorId->value}", 1) / 4294967296 * 100;
    }

    /**
     * MurmurHash3, x86 32-bit variant, over the UTF-8 bytes of `key`, as an unsigned integer.
     */
    public static function murmur3(string $key, int $seed): int
    {
        return (int) hexdec(hash('murmur3a', $key, false, ['seed' => $seed]));
    }
}
