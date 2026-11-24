<?php

declare(strict_types=1);

namespace Varify\Core\Config;

final readonly class Config
{
    /**
     * @param list<Experiment> $experiments
     */
    private function __construct(
        public array $experiments,
        private bool $available,
    ) {}

    /**
     * @param list<Experiment> $experiments
     */
    public static function of(array $experiments): self
    {
        return new self($experiments, true);
    }

    /**
     * Loading failed: stored assignments are kept untouched and every experiment shows the original, untracked.
     */
    public static function unavailable(): self
    {
        return new self([], false);
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }
}
