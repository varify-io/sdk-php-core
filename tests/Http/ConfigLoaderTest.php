<?php

declare(strict_types=1);

namespace Varify\Core\Tests\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Varify\Core\Http\ConfigLoader;
use Varify\Core\Http\Transport;
use Varify\Core\Tests\Support\ArrayCache;
use Varify\Core\Tests\Support\FakeTransport;
use Varify\Core\Tests\Support\Fixtures;
use Varify\Core\VarifyError;
use Varify\Core\VarifyErrorReason;

#[CoversClass(ConfigLoader::class)]
#[Group('unit')]
final class ConfigLoaderTest extends TestCase
{
    /** @var list<VarifyError> */
    private array $errors = [];

    #[Test]
    public function loadsAndCachesTheConfig(): void
    {
        $cache = new ArrayCache();
        $loader = $this->loader(FakeTransport::responding(Fixtures::CONFIG_JSON), $cache);

        $config = $loader->load();

        static::assertCount(2, $config->experiments);
        static::assertSame([60, null], array_values($cache->ttls));
        static::assertSame([], $this->errors);
    }

    #[Test]
    public function servesACachedConfigWithoutARequest(): void
    {
        $cache = new ArrayCache();
        $transport = FakeTransport::responding(Fixtures::CONFIG_JSON);
        $this->loader($transport, $cache)->load();

        $config = $this->loader($transport, $cache)->load();

        static::assertCount(2, $config->experiments);
        static::assertSame(1, $transport->calls);
    }

    #[Test]
    public function reportsAFailedLoadAndFallsBackToUnavailable(): void
    {
        $error = new VarifyError('timed out', VarifyErrorReason::Timeout);
        $loader = $this->loader(FakeTransport::failing($error), new ArrayCache());

        $config = $loader->load();

        static::assertFalse($config->isAvailable());
        static::assertSame([$error], $this->errors);
    }

    #[Test]
    public function fallsBackToTheLastGoodConfigWhenALoadFails(): void
    {
        $cache = new ArrayCache();
        $this->loader(FakeTransport::responding(Fixtures::CONFIG_JSON), $cache)->load();
        foreach (array_keys($cache->values) as $key) {
            if (!str_ends_with($key, '.last-good')) {
                $cache->delete($key);
            }
        }
        $failing = FakeTransport::failing(new VarifyError('down', VarifyErrorReason::Http));

        $config = $this->loader($failing, $cache)->load();

        static::assertTrue($config->isAvailable());
        static::assertCount(2, $config->experiments);
        static::assertCount(1, $this->errors);
    }

    #[Test]
    public function reportsAnInvalidBody(): void
    {
        $loader = $this->loader(FakeTransport::responding('{"experiments":"nope"}'), new ArrayCache());

        $config = $loader->load();

        static::assertFalse($config->isAvailable());
        static::assertSame(VarifyErrorReason::InvalidConfig, $this->errors[0]->reason);
    }

    private function loader(Transport $transport, CacheInterface $cache): ConfigLoader
    {
        return new ConfigLoader(
            $transport,
            $cache,
            'https://delivery.varify.io/ss/team.json',
            300,
            60,
            function (VarifyError $error): void {
                $this->errors[] = $error;
            },
        );
    }
}
