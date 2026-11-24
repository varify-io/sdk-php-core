<?php

declare(strict_types=1);

namespace Varify\Core\Id;

use InvalidArgumentException;

final readonly class VariationId
{
    /**
     * @param positive-int $value
     */
    private function __construct(public int $value) {}

    public static function make(int $value): self
    {
        if ($value < 1) {
            throw new InvalidArgumentException("A variation id must be positive, got {$value}.");
        }

        return new self($value);
    }
}
