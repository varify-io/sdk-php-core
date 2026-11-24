<?php

declare(strict_types=1);

namespace Varify\Core\State;

use Varify\Core\Id\ExperimentId;
use Varify\Core\Id\VisitorId;

use function array_key_exists;
use function ksort;

final readonly class State
{
    /**
     * @param array<int, int> $assignments experiment id => variation id, `0` is the original
     */
    public function __construct(
        public VisitorId $visitorId,
        public array $assignments,
    ) {}

    public static function anonymous(): self
    {
        return new self(VisitorId::none(), []);
    }

    public function isAnonymous(): bool
    {
        return $this->visitorId->isNone();
    }

    public function has(ExperimentId $experimentId): bool
    {
        return array_key_exists($experimentId->value, $this->assignments);
    }

    public function variationFor(ExperimentId $experimentId): int
    {
        return $this->assignments[$experimentId->value] ?? 0;
    }

    public function equals(self $other): bool
    {
        $mine = $this->assignments;
        $theirs = $other->assignments;
        ksort($mine);
        ksort($theirs);

        return $this->visitorId->value === $other->visitorId->value && $mine === $theirs;
    }
}
