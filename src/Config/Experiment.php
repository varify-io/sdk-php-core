<?php

declare(strict_types=1);

namespace Varify\Core\Config;

use LogicException;
use Varify\Core\Id\ExperimentId;

final readonly class Experiment
{
    /**
     * @param list<Variation> $variations
     */
    public function __construct(
        public ExperimentId $id,
        public string $slug,
        public bool $trackingEnabled,
        public array $variations,
    ) {}

    public function hasVariation(int $variationId): bool
    {
        foreach ($this->variations as $variation) {
            if ($variation->id->value === $variationId) {
                return true;
            }
        }

        return false;
    }

    public function variation(int $variationId): Variation
    {
        foreach ($this->variations as $variation) {
            if ($variation->id->value === $variationId) {
                return $variation;
            }
        }

        throw new LogicException("Experiment {$this->id->value} has no variation {$variationId}.");
    }
}
