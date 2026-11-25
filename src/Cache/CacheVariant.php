<?php

declare(strict_types=1);

namespace Varify\Core\Cache;

use Varify\Core\Request\BotDetector;
use Varify\Core\Request\VarifyRequest;
use Varify\Core\State\CookieCodec;
use Varify\Core\State\State;
use Varify\Core\VarifyOptions;

/**
 * Which cached copy of a page fits a request: one per combination of assignments, never per visitor. A page cache
 * adds `key` to its cache key, and skips the cache entirely when the variant is not cacheable.
 */
final readonly class CacheVariant
{
    private function __construct(public bool $cacheable, public string $key) {}

    /**
     * Computed from the request alone, so a cache can call it before anything renders. A human without a cookie is
     * not cacheable: they would get a page rendered for someone else's assignments and never be assigned.
     */
    public static function forRequest(VarifyOptions $options, VarifyRequest $request): self
    {
        if (BotDetector::isBot($request->userAgent)) {
            return self::bot();
        }

        $state = CookieCodec::decode($request->cookieValue);
        if ($state->isAnonymous()) {
            return self::uncacheable();
        }

        return self::of($options->configLoader()->load()->version, $state);
    }

    public static function of(string $configVersion, State $state): self
    {
        return new self(true, "{$configVersion}:" . CookieCodec::encodeAssignments($state));
    }

    public static function bot(): self
    {
        return new self(true, 'bot');
    }

    public static function uncacheable(): self
    {
        return new self(false, '');
    }
}
