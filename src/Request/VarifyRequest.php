<?php

declare(strict_types=1);

namespace Varify\Core\Request;

/**
 * What the SDK needs from an incoming request. Integrations fill it from their framework's request object; an absent
 * value is an empty string.
 */
final readonly class VarifyRequest
{
    public function __construct(
        public string $cookieValue,
        public string $forceParam,
        public string $userAgent,
    ) {}
}
