<?php

declare(strict_types=1);

namespace Varify\Core\Tests\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Varify\Core\Config\ConfigParser;
use Varify\Core\Tests\Support\Fixtures;
use Varify\Core\VarifyError;
use Varify\Core\VarifyErrorReason;

#[CoversClass(ConfigParser::class)]
#[Group('unit')]
final class ConfigParserTest extends TestCase
{
    #[Test]
    public function parsesTheDeliveryFile(): void
    {
        $json = Fixtures::CONFIG_JSON;

        $config = ConfigParser::parse($json);

        static::assertTrue($config->isAvailable());
        static::assertCount(2, $config->experiments);
        static::assertSame(48812, $config->experiments[0]->id->value);
        static::assertTrue($config->experiments[0]->trackingEnabled);
        static::assertSame('green', $config->experiments[0]->variation(48839)->slug);
        static::assertSame(50.0, $config->experiments[0]->variation(48839)->trafficAllocation);
    }

    #[Test]
    #[DataProvider('rejectsAnythingElseAsInvalidConfigDataProvider')]
    public function rejectsAnythingElseAsInvalidConfig(string $json): void
    {
        $reason = null;

        try {
            ConfigParser::parse($json);
        } catch (VarifyError $error) {
            $reason = $error->reason;
        }

        static::assertSame(VarifyErrorReason::InvalidConfig, $reason);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function rejectsAnythingElseAsInvalidConfigDataProvider(): iterable
    {
        yield 'not JSON' => ['<html>'];
        yield 'no experiments' => ['{}'];
        yield 'experiments is an object' => ['{"experiments":{}}'];
        yield 'string id' => ['{"experiments":[{"id":"1","slug":"a","tracking_enabled":true,"variations":[]}]}'];
        yield 'id 0' => ['{"experiments":[{"id":0,"slug":"a","tracking_enabled":true,"variations":[]}]}'];
        yield 'missing tracking flag' => ['{"experiments":[{"id":1,"slug":"a","variations":[]}]}'];
        yield 'string allocation' => [
            '{"experiments":[{"id":1,"slug":"a","tracking_enabled":true,'
            . '"variations":[{"id":2,"slug":"b","traffic_allocation":"50"}]}]}',
        ];
    }
}
