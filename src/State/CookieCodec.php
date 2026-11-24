<?php

declare(strict_types=1);

namespace Varify\Core\State;

use Varify\Core\Id\VisitorId;

use function explode;
use function implode;
use function ksort;
use function preg_match;
use function strpos;
use function substr;

/**
 * `{uid}.{experimentId}-{variationId}_…`, variation `0` for the original. Byte-identical to varify-sdk-js, so one
 * cookie serves both SDKs on a shared domain.
 */
final class CookieCodec
{
    public static function encode(State $state): string
    {
        $assignments = $state->assignments;
        ksort($assignments);

        $entries = [];
        foreach ($assignments as $experimentId => $variationId) {
            $entries[] = "{$experimentId}-{$variationId}";
        }

        return $state->visitorId->value . '.' . implode('_', $entries);
    }

    /**
     * The cookie is untrusted input: anything malformed yields an anonymous state, so the visitor gets a new id.
     */
    public static function decode(string $value): State
    {
        $separator = strpos($value, '.');
        if ($separator === false) {
            return State::anonymous();
        }

        $uid = substr($value, 0, $separator);
        if (!VisitorId::isValid($uid)) {
            return State::anonymous();
        }

        $entries = substr($value, $separator + 1);
        $assignments = [];
        foreach ($entries === '' ? [] : explode('_', $entries) as $entry) {
            if (preg_match('/^([1-9]\d{0,17})-(\d{1,18})$/', $entry, $match) !== 1) {
                return State::anonymous();
            }

            $assignments[(int) $match[1]] = (int) $match[2];
        }

        return new State(VisitorId::make($uid), $assignments);
    }
}
