<?php

declare(strict_types=1);

namespace Varify\Core\Request;

use function preg_match;

/**
 * Bots get no id and see the original: no assignment writes for crawlers, and search engines see one stable page.
 */
final class BotDetector
{
    public static function isBot(string $userAgent): bool
    {
        // Same pattern as varify-sdk-js.
        return preg_match(
            '/bot|crawl|spider|slurp|facebookexternalhit|embedly|quora link preview|outbrain|pinterest|vkshare|'
            . 'w3c_validator|whatsapp|lighthouse|headlesschrome|prerender|preview/i',
            $userAgent,
        ) === 1;
    }
}
