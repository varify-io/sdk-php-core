<?php

declare(strict_types=1);

namespace Varify\Core\Tests\Assignment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Varify\Core\Assignment\AssignmentSource;
use Varify\Core\Assignment\Evaluator;
use Varify\Core\Config\Config;
use Varify\Core\Id\VisitorId;
use Varify\Core\Request\ForcedVariation;
use Varify\Core\State\State;
use Varify\Core\Tests\Support\Fixtures;

#[CoversClass(Evaluator::class)]
#[Group('unit')]
final class EvaluatorTest extends TestCase
{
    #[Test]
    public function bucketsANewVisitorAndAsksToPersist(): void
    {
        $evaluator = self::evaluator();

        $evaluation = $evaluator->evaluate(Fixtures::config(), State::anonymous(), ForcedVariation::none());

        static::assertTrue($evaluation->changed);
        static::assertSame(Fixtures::VISITOR_ID, $evaluation->state->visitorId->value);
        static::assertSame([48812 => 48839, 269 => 311], $evaluation->state->assignments);
        static::assertSame(AssignmentSource::Hash, $evaluation->assignment(48812)->source);
    }

    #[Test]
    public function keepsStoredAssignmentsWithoutAskingToPersist(): void
    {
        $stored = self::stored([48812 => 48838, 269 => 311]);

        $evaluation = self::evaluator()->evaluate(Fixtures::config(), $stored, ForcedVariation::none());

        static::assertFalse($evaluation->changed);
        static::assertTrue($evaluation->assignment(48812)->is(48838));
        static::assertSame(AssignmentSource::Stored, $evaluation->assignment(48812)->source);
    }

    #[Test]
    public function keepsAStoredOriginal(): void
    {
        $stored = self::stored([48812 => 0, 269 => 311]);

        $evaluation = self::evaluator()->evaluate(Fixtures::config(), $stored, ForcedVariation::none());

        static::assertTrue($evaluation->assignment(48812)->isOriginal());
        static::assertSame(AssignmentSource::Stored, $evaluation->assignment(48812)->source);
    }

    #[Test]
    public function rebucketsWhenTheStoredVariationNoLongerExists(): void
    {
        $stored = self::stored([48812 => 99999, 269 => 311]);

        $evaluation = self::evaluator()->evaluate(Fixtures::config(), $stored, ForcedVariation::none());

        static::assertTrue($evaluation->changed);
        static::assertTrue($evaluation->assignment(48812)->is(48839));
        static::assertSame(AssignmentSource::Hash, $evaluation->assignment(48812)->source);
    }

    #[Test]
    public function aForcedVariationWinsOverTheStoredOne(): void
    {
        $stored = self::stored([48812 => 48839, 269 => 311]);

        $evaluation = self::evaluator()->evaluate(Fixtures::config(), $stored, ForcedVariation::parse('48812-48838'));

        static::assertTrue($evaluation->assignment(48812)->is(48838));
        static::assertSame(AssignmentSource::Forced, $evaluation->assignment(48812)->source);
        static::assertTrue($evaluation->changed);
    }

    #[Test]
    public function forcingVariationZeroShowsTheOriginal(): void
    {
        $stored = self::stored([48812 => 48839, 269 => 311]);

        $evaluation = self::evaluator()->evaluate(Fixtures::config(), $stored, ForcedVariation::parse('48812-0'));

        static::assertTrue($evaluation->assignment(48812)->isOriginal());
        static::assertSame(AssignmentSource::Forced, $evaluation->assignment(48812)->source);
    }

    #[Test]
    public function ignoresAForcedVariationTheExperimentDoesNotHave(): void
    {
        $stored = self::stored([48812 => 48839, 269 => 311]);

        $evaluation = self::evaluator()->evaluate(Fixtures::config(), $stored, ForcedVariation::parse('48812-12345'));

        static::assertTrue($evaluation->assignment(48812)->is(48839));
        static::assertSame(AssignmentSource::Stored, $evaluation->assignment(48812)->source);
    }

    #[Test]
    public function dropsEndedExperimentsFromTheState(): void
    {
        $stored = self::stored([48812 => 48838, 269 => 311, 777 => 1]);

        $evaluation = self::evaluator()->evaluate(Fixtures::config(), $stored, ForcedVariation::none());

        static::assertTrue($evaluation->changed);
        static::assertSame([48812 => 48838, 269 => 311], $evaluation->state->assignments);
    }

    #[Test]
    public function keepsStoredAssignmentsUntouchedWhenTheConfigIsUnavailable(): void
    {
        $stored = self::stored([48812 => 48838, 777 => 1]);

        $evaluation = self::evaluator()->evaluate(Config::unavailable(), $stored, ForcedVariation::none());

        static::assertFalse($evaluation->changed);
        static::assertSame([48812 => 48838, 777 => 1], $evaluation->state->assignments);
        static::assertSame(AssignmentSource::Fallback, $evaluation->assignment(48812)->source);
    }

    #[Test]
    public function givesANewVisitorAnIdEvenWhenTheConfigIsUnavailable(): void
    {
        $evaluator = self::evaluator();

        $evaluation = $evaluator->evaluate(Config::unavailable(), State::anonymous(), ForcedVariation::none());

        static::assertTrue($evaluation->changed);
        static::assertSame(Fixtures::VISITOR_ID, $evaluation->state->visitorId->value);
        static::assertSame([], $evaluation->state->assignments);
    }

    private static function evaluator(): Evaluator
    {
        return new Evaluator(static fn(): VisitorId => VisitorId::make(Fixtures::VISITOR_ID));
    }

    /**
     * @param array<int, int> $assignments
     */
    private static function stored(array $assignments): State
    {
        return new State(VisitorId::make(Fixtures::VISITOR_ID), $assignments);
    }
}
