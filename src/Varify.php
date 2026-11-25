<?php

declare(strict_types=1);

namespace Varify\Core;

use Varify\Core\Assignment\Assignment;
use Varify\Core\Assignment\Evaluation;
use Varify\Core\Assignment\Evaluator;
use Varify\Core\Assignment\Tracking;
use Varify\Core\Cache\CacheVariant;
use Varify\Core\Id\VisitorId;
use Varify\Core\Request\BotDetector;
use Varify\Core\Request\ForcedVariation;
use Varify\Core\Request\Globals;
use Varify\Core\Request\VarifyRequest;
use Varify\Core\State\CookieCodec;
use Varify\Core\State\State;
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

    private function __construct(
        private readonly ?Evaluation $evaluation,
        private readonly bool $writable,
        private readonly CacheVariant $cacheVariant,
    ) {}

    /**
     * Never throws: a failed config load shows the original and tracks nothing. Bots get no id and see the original.
     */
    public static function forRequest(VarifyOptions $options, VarifyRequest $request): self
    {
        if (BotDetector::isBot($request->userAgent)) {
            return self::none();
        }

        return self::evaluate($options, $request, CookieCodec::decode($request->cookieValue), true);
    }

    /**
     * For a request that must not assign or write the cookie, such as a fragment rendered separately from its page:
     * a visitor without a cookie sees the original, untracked. A visitor with one gets the same assignments as on the
     * page, because bucketing is deterministic.
     */
    public static function readOnly(VarifyOptions $options, VarifyRequest $request): self
    {
        $stored = CookieCodec::decode($request->cookieValue);
        if (BotDetector::isBot($request->userAgent) || $stored->isAnonymous()) {
            return new self(null, false, CacheVariant::uncacheable());
        }

        return self::evaluate($options, $request, $stored, false);
    }

    /**
     * Every experiment shows the original, untracked; for bots, or where Varify is not configured.
     */
    public static function none(): self
    {
        return new self(null, false, CacheVariant::bot());
    }

    private static function evaluate(
        VarifyOptions $options,
        VarifyRequest $request,
        State $stored,
        bool $writable,
    ): self {
        $config = $options->configLoader()->load();
        $evaluation = (new Evaluator(VisitorId::generate(...)))->evaluate(
            $config,
            $stored,
            ForcedVariation::parse($request->forceParam),
        );

        return new self($evaluation, $writable, CacheVariant::of($config->version, $evaluation->state));
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
        return $this->writable && $this->evaluation !== null && $this->evaluation->changed;
    }

    /**
     * The cache variant of the response this request renders, i.e. after the assignments made in it. It matches what
     * `CacheVariant::forRequest()` computes for this visitor's next request.
     */
    public function cacheVariant(): CacheVariant
    {
        return $this->cacheVariant;
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
