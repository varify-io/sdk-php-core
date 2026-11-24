<?php

declare(strict_types=1);

namespace Varify\Core\Http;

use Closure;
use Psr\SimpleCache\CacheInterface;
use Throwable;
use Varify\Core\Config\Config;
use Varify\Core\Config\ConfigParser;
use Varify\Core\VarifyError;
use Varify\Core\VarifyErrorReason;

use function is_string;
use function sha1;

final readonly class ConfigLoader
{
    /**
     * @param Closure(VarifyError): void $onError
     */
    public function __construct(
        private Transport $transport,
        private CacheInterface $cache,
        private string $url,
        private int $timeoutMs,
        private int $cacheTtlSeconds,
        private Closure $onError,
    ) {}

    /**
     * Never throws. A failed load reports to `onError` and falls back to the last config that loaded, or to
     * `Config::unavailable()`.
     */
    public function load(): Config
    {
        $freshKey = 'varify.config.' . sha1($this->url);
        $lastGoodKey = $freshKey . '.last-good';

        $cached = $this->read($freshKey);
        if ($cached !== '') {
            return $this->parseCached($cached);
        }

        try {
            $body = $this->transport->get($this->url, $this->timeoutMs);
            $config = ConfigParser::parse($body);
        } catch (VarifyError $error) {
            ($this->onError)($error);
            $lastGood = $this->read($lastGoodKey);

            return $lastGood === '' ? Config::unavailable() : $this->parseCached($lastGood);
        }

        $this->write($freshKey, $body, $this->cacheTtlSeconds);
        $this->write($lastGoodKey, $body, null);

        return $config;
    }

    private function parseCached(string $body): Config
    {
        try {
            return ConfigParser::parse($body);
        } catch (VarifyError $error) {
            ($this->onError)($error);

            return Config::unavailable();
        }
    }

    private function read(string $key): string
    {
        try {
            $value = $this->cache->get($key);
        } catch (Throwable $exception) {
            $this->reportCacheFailure('Reading the Varify config cache failed', $exception);

            return '';
        }

        return is_string($value) ? $value : '';
    }

    private function write(string $key, string $body, ?int $ttlSeconds): void
    {
        try {
            $this->cache->set($key, $body, $ttlSeconds);
        } catch (Throwable $exception) {
            $this->reportCacheFailure('Writing the Varify config cache failed', $exception);
        }
    }

    private function reportCacheFailure(string $message, Throwable $exception): void
    {
        ($this->onError)(new VarifyError($message, VarifyErrorReason::Cache, $exception));
    }
}
