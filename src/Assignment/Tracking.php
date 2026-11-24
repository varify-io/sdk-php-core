<?php

declare(strict_types=1);

namespace Varify\Core\Assignment;

use Varify\Core\Config\Experiment;
use Varify\Core\Config\Variation;

final readonly class Tracking
{
    private function __construct(
        private bool $enabled,
        private int $experimentId,
        private string $experimentName,
        private int $variationId,
        private string $variationName,
    ) {}

    public static function none(): self
    {
        return new self(false, 0, '', 0, '');
    }

    public static function forVariation(Experiment $experiment, Variation $variation): self
    {
        if (!$experiment->trackingEnabled) {
            return self::none();
        }

        return new self(true, $experiment->id->value, $experiment->slug, $variation->id->value, $variation->slug);
    }

    public static function forOriginal(Experiment $experiment): self
    {
        if (!$experiment->trackingEnabled) {
            return self::none();
        }

        return new self(true, $experiment->id->value, $experiment->slug, 0, '');
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * The shape `window.varify.trackServerSide` expects; the original has `null` as variation id and name.
     *
     * @return array{experiment: array{id: int, name: string}, variation: array{id: int|null, name: string|null}}
     */
    public function payload(): array
    {
        $isOriginal = $this->variationId === 0;

        return [
            'experiment' => ['id' => $this->experimentId, 'name' => $this->experimentName],
            'variation' => [
                'id' => $isOriginal ? null : $this->variationId,
                'name' => $isOriginal ? null : $this->variationName,
            ],
        ];
    }
}
