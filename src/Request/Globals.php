<?php

declare(strict_types=1);

namespace Varify\Core\Request;

use Varify\Core\CookieOptions;

use function header;
use function is_string;
use function setcookie;
use function time;

/**
 * @internal plain-PHP glue for `Varify::fromGlobals()`
 */
final class Globals
{
    public static function request(string $cookieName): VarifyRequest
    {
        return new VarifyRequest(
            self::string($_COOKIE[$cookieName] ?? ''),
            self::string($_GET[ForcedVariation::PARAM] ?? ''),
            self::string($_SERVER['HTTP_USER_AGENT'] ?? ''),
        );
    }

    public static function writeCookie(CookieOptions $cookie, string $value): void
    {
        setcookie($cookie->name, $value, [
            'expires' => time() + $cookie->maxAgeSeconds,
            'path' => '/',
            'domain' => $cookie->domain,
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => $cookie->sameSite->value,
        ]);
        // A shared cache must never hand this visitor's id to anyone else.
        header('Cache-Control: private, no-store, max-age=0');
    }

    private static function isHttps(): bool
    {
        $https = self::string($_SERVER['HTTPS'] ?? '');

        return $https !== '' && $https !== 'off';
    }

    private static function string(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
