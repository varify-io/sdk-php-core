<?php

declare(strict_types=1);

namespace Varify\Core\Config;

use Varify\Core\Id\VariationId;

final readonly class Variation
{
    public function __construct(
        public VariationId $id,
        public string $slug,
        public float $trafficAllocation,
    ) {}
}
