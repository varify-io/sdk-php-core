<?php

declare(strict_types=1);

namespace Varify\Core\Request;

use Varify\Core\Id\ExperimentId;

use function preg_match;

final readonly class ForcedVariation
{
    public const PARAM = 'varify-force';

    private function __construct(
        public int $experimentId,
        /** `0` forces the original. */
        public int $variationId,
    ) {}

    public static function none(): self
    {
        return new self(0, 0);
    }

    /**
     * Parses `{experimentId}-{variationId}` as varify.js does; anything else forces nothing.
     */
    public static function parse(string $value): self
    {
        if (preg_match('/^(\d{1,18})-(\d{1,18})$/', $value, $match) !== 1) {
            return self::none();
        }

        return new self((int) $match[1], (int) $match[2]);
    }

    public function appliesTo(ExperimentId $experimentId): bool
    {
        return $this->experimentId === $experimentId->value;
    }

    public function forcesOriginal(): bool
    {
        return $this->variationId === 0;
    }
}
