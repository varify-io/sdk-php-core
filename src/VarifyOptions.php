<?php

declare(strict_types=1);

namespace Varify\Core;

use Closure;
use Psr\SimpleCache\CacheInterface;
use Varify\Core\Http\ConfigLoader;
use Varify\Core\Http\CurlTransport;
use Varify\Core\Http\NoCache;
use Varify\Core\Http\Transport;

use function rawurlencode;

final readonly class VarifyOptions
{
    /**
     * @param Closure(VarifyError): void $onError
     */
    private function __construct(
        public string $teamId,
        public string $configUrl,
        public int $timeoutMs,
        public CacheInterface $cache,
        public int $cacheTtlSeconds,
        public Transport $transport,
        public Closure $onError,
        public CookieOptions $cookie,
    ) {}

    /**
     * @param string $teamId the team's public id (the sqid), not the numeric `iid` from the varify.js snippet
     */
    public static function forTeam(string $teamId): self
    {
        return new self(
            $teamId,
            'https://delivery.varify.io/ss/' . rawurlencode($teamId) . '.json',
            300,
            new NoCache(),
            60,
            new CurlTransport(),
            static function (VarifyError $error): void {},
            CookieOptions::defaults(),
        );
    }

    public function configLoader(): ConfigLoader
    {
        return new ConfigLoader(
            $this->transport,
            $this->cache,
            $this->configUrl,
            $this->timeoutMs,
            $this->cacheTtlSeconds,
            $this->onError,
        );
    }

    /**
     * Load the config from your own cache or mirror instead. Serve the file unchanged and keep it fresh.
     */
    public function withConfigUrl(string $configUrl): self
    {
        return new self(
            $this->teamId,
            $configUrl,
            $this->timeoutMs,
            $this->cache,
            $this->cacheTtlSeconds,
            $this->transport,
            $this->onError,
            $this->cookie,
        );
    }

    public function withTimeout(int $timeoutMs): self
    {
        return new self(
            $this->teamId,
            $this->configUrl,
            $timeoutMs,
            $this->cache,
            $this->cacheTtlSeconds,
            $this->transport,
            $this->onError,
            $this->cookie,
        );
    }

    /**
     * Without a cache, every request loads the config over the network.
     */
    public function withCache(CacheInterface $cache, int $ttlSeconds): self
    {
        return new self(
            $this->teamId,
            $this->configUrl,
            $this->timeoutMs,
            $cache,
            $ttlSeconds,
            $this->transport,
            $this->onError,
            $this->cookie,
        );
    }

    public function withTransport(Transport $transport): self
    {
        return new self(
            $this->teamId,
            $this->configUrl,
            $this->timeoutMs,
            $this->cache,
            $this->cacheTtlSeconds,
            $transport,
            $this->onError,
            $this->cookie,
        );
    }

    /**
     * @param Closure(VarifyError): void $onError
     */
    public function withOnError(Closure $onError): self
    {
        return new self(
            $this->teamId,
            $this->configUrl,
            $this->timeoutMs,
            $this->cache,
            $this->cacheTtlSeconds,
            $this->transport,
            $onError,
            $this->cookie,
        );
    }

    public function withCookie(CookieOptions $cookie): self
    {
        return new self(
            $this->teamId,
            $this->configUrl,
            $this->timeoutMs,
            $this->cache,
            $this->cacheTtlSeconds,
            $this->transport,
            $this->onError,
            $cookie,
        );
    }
}
