<?php

declare(strict_types=1);

namespace Varify\Core\Id;

use InvalidArgumentException;

use function bin2hex;
use function chr;
use function ord;
use function preg_match;
use function random_bytes;
use function str_split;
use function vsprintf;

final readonly class VisitorId
{
    private function __construct(public string $value) {}

    public static function generate(): self
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return new self(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)));
    }

    /**
     * No id yet: a new visitor, a bot, or a malformed cookie.
     */
    public static function none(): self
    {
        return new self('');
    }

    public function isNone(): bool
    {
        return $this->value === '';
    }

    public static function make(string $value): self
    {
        if (!self::isValid($value)) {
            throw new InvalidArgumentException("A visitor id must be a UUIDv4, got \"{$value}\".");
        }

        return new self($value);
    }

    public static function isValid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1;
    }
}
