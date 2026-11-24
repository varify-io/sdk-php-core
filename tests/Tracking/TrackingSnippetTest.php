<?php

declare(strict_types=1);

namespace Varify\Core\Tests\Tracking;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Varify\Core\Assignment\Tracking;
use Varify\Core\Config\Experiment;
use Varify\Core\Config\Variation;
use Varify\Core\Id\ExperimentId;
use Varify\Core\Id\VariationId;
use Varify\Core\Tracking\TrackingSnippet;

#[CoversClass(TrackingSnippet::class)]
#[Group('unit')]
final class TrackingSnippetTest extends TestCase
{
    #[Test]
    public function rendersNothingWithoutExposures(): void
    {
        $exposures = [];

        $snippet = TrackingSnippet::render($exposures, '');

        static::assertSame('', $snippet);
    }

    #[Test]
    public function rendersThePayloadsVarifyJsExpects(): void
    {
        $experiment = self::experiment('checkout');
        $exposures = [
            Tracking::forVariation($experiment, $experiment->variation(311)),
            Tracking::forOriginal(self::experiment('hero')),
        ];

        $snippet = TrackingSnippet::render($exposures, '');

        static::assertStringContainsString(
            '[{"experiment":{"id":269,"name":"checkout"},"variation":{"id":311,"name":"new"}},'
            . '{"experiment":{"id":269,"name":"hero"},"variation":{"id":null,"name":null}}]',
            $snippet,
        );
        static::assertStringStartsWith('<script>', $snippet);
    }

    #[Test]
    public function cannotBeBrokenOutOfBySlugs(): void
    {
        $exposures = [Tracking::forOriginal(self::experiment('</script><script>alert(1)</script>'))];

        $snippet = TrackingSnippet::render($exposures, '');

        static::assertSame(1, substr_count($snippet, '</script>'));
    }

    #[Test]
    public function carriesTheCspNonce(): void
    {
        $exposures = [Tracking::forOriginal(self::experiment('checkout'))];

        $snippet = TrackingSnippet::render($exposures, 'r4nd"om');

        static::assertStringStartsWith('<script nonce="r4nd&quot;om">', $snippet);
    }

    private static function experiment(string $slug): Experiment
    {
        return new Experiment(
            ExperimentId::make(269),
            $slug,
            true,
            [new Variation(VariationId::make(311), 'new', 100.0)],
        );
    }
}
