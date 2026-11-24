<?php

declare(strict_types=1);

namespace Varify\Core\Assignment;

use Varify\Core\State\State;

final readonly class Evaluation
{
    /**
     * @param array<int, Assignment> $assignments keyed by experiment id
     */
    public function __construct(
        public State $state,
        /** Whether `state` differs from the stored state and has to be persisted. */
        public bool $changed,
        private array $assignments,
    ) {}

    public function assignment(int $experimentId): Assignment
    {
        return $this->assignments[$experimentId] ?? Assignment::fallback($experimentId);
    }

    /**
     * @return array<int, Assignment>
     */
    public function assignments(): array
    {
        return $this->assignments;
    }
}
