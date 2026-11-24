<?php

declare(strict_types=1);

namespace Varify\Core;

final readonly class CookieOptions
{
    private function __construct(
        public string $name,
        public int $maxAgeSeconds,
        /** Empty for the current host only; `.example.com` shares assignments across subdomains. */
        public string $domain,
        public SameSite $sameSite,
    ) {}

    /**
     * `varify`, 400 days (the maximum browsers accept), current host, SameSite=Lax.
     */
    public static function defaults(): self
    {
        return new self('varify', 60 * 60 * 24 * 400, '', SameSite::Lax);
    }

    public function withName(string $name): self
    {
        return new self($name, $this->maxAgeSeconds, $this->domain, $this->sameSite);
    }

    public function withMaxAge(int $seconds): self
    {
        return new self($this->name, $seconds, $this->domain, $this->sameSite);
    }

    public function withDomain(string $domain): self
    {
        return new self($this->name, $this->maxAgeSeconds, $domain, $this->sameSite);
    }

    public function withSameSite(SameSite $sameSite): self
    {
        return new self($this->name, $this->maxAgeSeconds, $this->domain, $sameSite);
    }
}
