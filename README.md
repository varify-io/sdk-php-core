# varify-io/core

The framework-agnostic core of the Varify PHP SDKs: it loads a team's experiment config, assigns visitors locally and keeps their assignments sticky in a cookie. PHP 8.2+, `ext-curl`, and the `psr/simple-cache` interface.

For Shopware, use `varify-io/shopware` (planned) instead. Use this package directly in plain PHP or to build an integration for another framework.

## Requirements

- An experiment of type **server-side** in Varify
- varify.js on the page, if exposures should be tracked. The SDK hands every exposure to varify.js, which applies consent and sends it to your tracking provider.

## Install

```sh
composer require varify-io/core
```

## Plain PHP

```php
use Varify\Core\Varify;
use Varify\Core\VarifyOptions;

$options = VarifyOptions::forTeam('your-team-id')
    ->withCache($psr16Cache, 60);

// Once per request, before any output: reads the cookie and sets it when the assignments changed.
$varify = Varify::fromGlobals($options);

$assignment = $varify->assignment(269);

if ($assignment->is(311)) {
    echo $newCheckoutButton;
} else {
    echo $checkoutButton;
}

// After the experiments, e.g. before </body>.
echo $varify->trackingSnippet();
```

`teamId` is your team's public id, a string of letters and digits. It is not the numeric id (`iid`) in the varify.js snippet.

## Building an integration

`Varify::forRequest()` takes what the SDK needs from your framework's request and leaves the response to you:

```php
use Varify\Core\Request\VarifyRequest;

$varify = Varify::forRequest($options, new VarifyRequest(
    $request->cookies->get('varify', ''),
    $request->query->get('varify-force', ''),
    $request->headers->get('User-Agent', ''),
));

if ($varify->needsCookieWrite()) {
    // Write $varify->cookieValue() as the `varify` cookie (see $options->cookie),
    // and keep this response out of every shared cache.
}
```

## API

| Method                                  | Description                                                                                                          |
| --------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| `Varify::fromGlobals($options)`         | Plain PHP: reads `$_COOKIE`, `$_GET`, the user agent; sets the cookie and `Cache-Control: private, no-store` when needed |
| `Varify::forRequest($options, $request)`| For integrations: evaluates, writes nothing                                                                           |
| `$varify->assignment(int $experimentId)`| The visitor's `Assignment`; records the exposure. Unknown experiments return the original, untracked                  |
| `$assignment->is(int $variationId)`     | Whether the visitor sees this variation                                                                                |
| `$assignment->isOriginal()`             | Whether the visitor sees the original                                                                                  |
| `$varify->trackingSnippet($nonce = '')` | A `<script>` reporting each read experiment to varify.js once, first variation read wins                               |
| `$varify->visitorId()`                  | The `VisitorId` Varify buckets this visitor by (`->value`), to store next to your own user id. `->isNone()` for bots    |
| `$varify->needsCookieWrite()`, `cookieValue()` | For integrations that write the cookie themselves                                                               |

## Options

`VarifyOptions::forTeam($teamId)` with:

| Wither                        | Default                                       | Description                                                                  |
| ----------------------------- | --------------------------------------------- | ---------------------------------------------------------------------------- |
| `withCache($cache, $ttl)`     | none                                          | A PSR-16 cache for the config. Without one, every request loads it over the network |
| `withTimeout($ms)`            | `300`                                         | How long to wait for the config before showing the original                   |
| `withOnError($closure)`       | none                                          | Called with a `VarifyError`; `reason`: `timeout`, `network`, `http`, `invalid-config`, `cache` |
| `withConfigUrl($url)`         | `https://delivery.varify.io/ss/{teamId}.json` | Your own mirror of the config. Serve it unchanged and keep it fresh           |
| `withCookie(CookieOptions)`   | `varify`, 400 days, current host, Lax         | `CookieOptions::defaults()->withDomain('.example.com')` shares across subdomains |
| `withTransport(Transport)`    | curl                                          | Replace the HTTP client, e.g. in tests                                       |

## Behaviour to know

- **Sticky assignments.** A visitor keeps their variation for as long as the cookie lasts, even if the traffic split changes later. Bucketing is deterministic and identical to the JavaScript SDK, so both can share the cookie.
- **Failure.** If the config can't be loaded, the SDK falls back to the last config it loaded through the cache; without one, every experiment shows the original and nothing is tracked, while stored assignments are kept. Your pages never break because of Varify.
- **Bots** get no id and always see the original.
- **Caching.** A response that writes the cookie must not be stored by a shared cache. `fromGlobals()` sends `Cache-Control: private, no-store, max-age=0` for it.

## Previewing a variation

Add `?varify-force={experimentId}-{variationId}` to any URL, for example `?varify-force=269-311`. Variation `0` forces the original. The forced variation is stored in the cookie, so it sticks for later pages, and it is tracked like any other assignment.
