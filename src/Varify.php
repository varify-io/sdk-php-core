<?php

declare(strict_types=1);

namespace Varify\Core;

use Varify\Core\Assignment\Assignment;
use Varify\Core\Assignment\Evaluation;
use Varify\Core\Assignment\Evaluator;
use Varify\Core\Assignment\Tracking;
use Varify\Core\Http\ConfigLoader;
use Varify\Core\Id\VisitorId;
use Varify\Core\Request\BotDetector;
use Varify\Core\Request\ForcedVariation;
use Varify\Core\Request\Globals;
use Varify\Core\Request\VarifyRequest;
use Varify\Core\State\CookieCodec;
use Varify\Core\Tracking\TrackingSnippet;

use function array_key_exists;

/**
 * One request's assignments. Create it once per request, before any output, and read assignments while rendering.
 */
final class Varify
{
    /** @var array<int, true> */
    private array $exposed = [];

    /** @var list<Tracking> */
    private array $exposures = [];

    private function __construct(private readonly ?Evaluation $evaluation) {}

    /**
     * Never throws: a failed config load shows the original and tracks nothing. Bots get no id and see the original.
     */
    public static function forRequest(VarifyOptions $options, VarifyRequest $request): self
    {
        if (BotDetector::isBot($request->userAgent)) {
            return new self(null);
        }

        $loader = new ConfigLoader(
            $options->transport,
            $options->cache,
            $options->configUrl,
            $options->timeoutMs,
            $options->cacheTtlSeconds,
            $options->onError,
        );
        $evaluator = new Evaluator(VisitorId::generate(...));

        return new self($evaluator->evaluate(
            $loader->load(),
            CookieCodec::decode($request->cookieValue),
            ForcedVariation::parse($request->forceParam),
        ));
    }

    /**
     * Plain PHP: reads `$_COOKIE`, `$_GET` and the user agent, and sends the cookie with `setcookie()` when the
     * assignments changed. Call it before any output.
     */
    public static function fromGlobals(VarifyOptions $options): self
    {
        $varify = self::forRequest($options, Globals::request($options->cookie->name));
        if ($varify->needsCookieWrite()) {
            Globals::writeCookie($options->cookie, $varify->cookieValue());
        }

        return $varify;
    }

    /**
     * The visitor's assignment for an experiment, recorded as an exposure for `trackingSnippet()`. An unknown
     * experiment returns the original, untracked.
     *
     * @param positive-int $experimentId
     */
    public function assignment(int $experimentId): Assignment
    {
        $assignment = $this->evaluation === null
            ? Assignment::fallback($experimentId)
            : $this->evaluation->assignment($experimentId);

        if (!array_key_exists($experimentId, $this->exposed)) {
            $this->exposed[$experimentId] = true;
            if ($assignment->tracking->isEnabled()) {
                $this->exposures[] = $assignment->tracking;
            }
        }

        return $assignment;
    }

    /**
     * The id Varify buckets this visitor by, e.g. to store next to your own user id. `isNone()` for bots.
     */
    public function visitorId(): VisitorId
    {
        return $this->evaluation === null ? VisitorId::none() : $this->evaluation->state->visitorId;
    }

    /**
     * Whether the assignments changed and the cookie has to be written with `cookieValue()`. A response that writes
     * the cookie must not be stored by a shared cache.
     */
    public function needsCookieWrite(): bool
    {
        return $this->evaluation !== null && $this->evaluation->changed;
    }

    public function cookieValue(): string
    {
        return $this->evaluation === null ? '' : CookieCodec::encode($this->evaluation->state);
    }

    /**
     * A `<script>` reporting every experiment read through `assignment()` to varify.js, once each, the first
     * variation read winning. Render it after the experiments, e.g. before `</body>`.
     */
    public function trackingSnippet(string $nonce = ''): string
    {
        return TrackingSnippet::render($this->exposures, $nonce);
    }
}
