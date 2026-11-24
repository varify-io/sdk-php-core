<?php

declare(strict_types=1);

namespace Varify\Core\Tracking;

use Varify\Core\Assignment\Tracking;

use function array_map;
use function htmlspecialchars;
use function json_encode;

use const ENT_QUOTES;
use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;

final class TrackingSnippet
{
    /**
     * Hands each exposure to `window.varify.trackServerSide`, or queues it in `window.varify.serverSideQueue` until
     * varify.js loads and drains it. varify.js applies consent before anything is sent.
     *
     * @param list<Tracking> $exposures
     */
    public static function render(array $exposures, string $nonce): string
    {
        if ($exposures === []) {
            return '';
        }

        $payloads = json_encode(
            array_map(static fn(Tracking $tracking): array => $tracking->payload(), $exposures),
            JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            | JSON_INVALID_UTF8_SUBSTITUTE,
        );
        $nonceAttribute = $nonce === '' ? '' : ' nonce="' . htmlspecialchars($nonce, ENT_QUOTES) . '"';

        return "<script{$nonceAttribute}>(function(w,p){var v=w.varify=w.varify||{};p.forEach(function(e){"
            . "if(typeof v.trackServerSide==='function'){v.trackServerSide(e)}"
            . "else{(v.serverSideQueue=v.serverSideQueue||[]).push(e)}})})(window,{$payloads});</script>";
    }
}
