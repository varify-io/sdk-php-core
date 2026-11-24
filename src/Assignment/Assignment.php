<?php

declare(strict_types=1);

namespace Varify\Core\Assignment;

use Varify\Core\Config\Experiment;
use Varify\Core\Config\Variation;

final readonly class Assignment
{
    private function __construct(
        public int $experimentId,
        /** `0` is the original. */
        public int $variationId,
        public Tracking $tracking,
        public AssignmentSource $source,
    ) {}

    public static function variation(Experiment $experiment, Variation $variation, AssignmentSource $source): self
    {
        return new self(
            $experiment->id->value,
            $variation->id->value,
            Tracking::forVariation($experiment, $variation),
            $source,
        );
    }

    public static function original(Experiment $experiment, AssignmentSource $source): self
    {
        return new self($experiment->id->value, 0, Tracking::forOriginal($experiment), $source);
    }

    /**
     * The original without tracking: for an unknown experiment, a failed config load, or a visitor without an id.
     */
    public static function fallback(int $experimentId): self
    {
        return new self($experimentId, 0, Tracking::none(), AssignmentSource::Fallback);
    }

    public function is(int $variationId): bool
    {
        return $variationId !== 0 && $this->variationId === $variationId;
    }

    public function isOriginal(): bool
    {
        return $this->variationId === 0;
    }
}
