<?php

declare(strict_types=1);

namespace Varify\Core\Assignment;

use Closure;
use Varify\Core\Config\Config;
use Varify\Core\Config\Experiment;
use Varify\Core\Id\VisitorId;
use Varify\Core\Request\ForcedVariation;
use Varify\Core\State\State;

final readonly class Evaluator
{
    /**
     * @param Closure(): VisitorId $generateVisitorId
     */
    public function __construct(private Closure $generateVisitorId) {}

    /**
     * Assigns every experiment in the config: forced, then stored, then bucketed. Without a config the stored
     * assignments are kept untouched, so a transient error never loses stickiness.
     */
    public function evaluate(Config $config, State $stored, ForcedVariation $force): Evaluation
    {
        $visitorId = $stored->isAnonymous() ? ($this->generateVisitorId)() : $stored->visitorId;

        if (!$config->isAvailable()) {
            return new Evaluation(new State($visitorId, $stored->assignments), $stored->isAnonymous(), []);
        }

        $assignments = [];
        $stateAssignments = [];
        foreach ($config->experiments as $experiment) {
            $assignment = $this->resolve($experiment, $visitorId, $stored, $force);
            $assignments[$experiment->id->value] = $assignment;
            $stateAssignments[$experiment->id->value] = $assignment->variationId;
        }

        $next = new State($visitorId, $stateAssignments);

        return new Evaluation($next, !$stored->equals($next), $assignments);
    }

    private function resolve(
        Experiment $experiment,
        VisitorId $visitorId,
        State $stored,
        ForcedVariation $force,
    ): Assignment {
        if ($force->appliesTo($experiment->id)) {
            if ($force->forcesOriginal()) {
                return Assignment::original($experiment, AssignmentSource::Forced);
            }

            if ($experiment->hasVariation($force->variationId)) {
                return Assignment::variation(
                    $experiment,
                    $experiment->variation($force->variationId),
                    AssignmentSource::Forced,
                );
            }
        }

        if ($stored->has($experiment->id)) {
            $storedId = $stored->variationFor($experiment->id);
            if ($storedId === 0) {
                return Assignment::original($experiment, AssignmentSource::Stored);
            }

            if ($experiment->hasVariation($storedId)) {
                return Assignment::variation($experiment, $experiment->variation($storedId), AssignmentSource::Stored);
            }
        }

        $remaining = Bucket::of($experiment->id, $visitorId);
        foreach ($experiment->variations as $variation) {
            if ($remaining < $variation->trafficAllocation) {
                return Assignment::variation($experiment, $variation, AssignmentSource::Hash);
            }

            $remaining -= $variation->trafficAllocation;
        }

        return Assignment::original($experiment, AssignmentSource::Hash);
    }
}
