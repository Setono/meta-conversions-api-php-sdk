# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A PHP SDK providing value objects and a client for Meta's (Facebook) [Conversions API](https://developers.facebook.com/docs/marketing-api/conversions-api). It is a library (`setono/meta-conversions-api-php-sdk`), not an application — there is no runnable entry point. Requires PHP >= 8.1.

## Commands

Composer scripts (defined in `composer.json`):

- `composer phpunit` — run the test suite (PHPUnit 10)
- `composer analyse` — PHPStan static analysis (`phpstan.dist.neon`: `level: max`, analysed against PHP 8.1)
- `composer check-style` / `composer fix-style` — ECS coding-standard check / autofix
- `vendor/bin/infection` — mutation testing (thresholds: minMsi 90, minCoveredMsi 90). Needs a coverage driver (pcov or Xdebug); CI uses pcov. With neither installed locally you'll get "No code coverage driver available".
- `vendor/bin/composer-dependency-analyser` — verify declared composer deps match actual usage

The dev tooling (PHPStan + extensions, ECS via `sylius-labs/coding-standard`, PHPUnit, Infection, Rector, composer-normalize, composer-dependency-analyser) is listed directly in `require-dev` rather than pulled in through the `setono/code-quality-pack` meta-package. The pack's current major requires PHP >= 8.2; inlining the tools keeps the whole toolchain runnable on PHP 8.1. When bumping a tool, pick the latest version that still supports PHP 8.1 (e.g. PHPUnit stays on `^10.5`, Infection on `^0.29`).

Run a single test by file or filter:

```bash
vendor/bin/phpunit tests/Event/UserTest.php
vendor/bin/phpunit --filter it_sends_event
```

Tests use the `@test` annotation with `snake_case` method names (no `test` prefix).

CI (`.github/workflows/build.yaml`) runs coding standards, dependency analysis, PHPStan, and PHPUnit against PHP 8.1–8.4 on both `lowest` and `highest` dependency versions, so check lowest-version compatibility when touching dependencies. A separate workflow runs Roave's backwards-compatibility check on PRs, comparing against the PR's base branch.

### Branches

There is no `master`. **`1.x`** is the default branch and holds the released 1.x line: bug fixes and additive changes only, and the BC check must stay green — this is a public library. **`2.x`** is the next major: BC breaks are allowed there, but every one must be documented in `UPGRADE-2.0.md`. Because the BC check compares against the PR's base, it is expected to be red on `2.x` PRs that break BC; its output should match what `UPGRADE-2.0.md` lists. Always pass `--base` to `gh pr create`. `Closes #123` only auto-closes issues when merged into the default branch, so issues fixed on `2.x` have to be closed by hand.

### LiveClientTest

`tests/Client/LiveClientTest.php` hits the real Meta API. It self-skips unless the env vars in `phpunit.xml.dist` are set (`PIXEL_ID`, `ACCESS_TOKEN`, `TEST_EVENT_CODE`, `URL`, `EMAIL`). Copy `phpunit.xml.dist` to `phpunit.xml` and fill them in to run it. With a filled-in `phpunit.xml`, every full `phpunit` run sends a real test event and Infection replays it for every mutant it covers — move `phpunit.xml` aside before running Infection.

## Architecture

The core abstraction is the serialization pipeline in `src/Event/Parameters.php`. Everything sent to Meta flows through it.

**`Parameters` (abstract base)** — each subclass implements `getMapping(string $context): array`, returning Meta's snake_case field names mapped to the object's (camelCase) PHP property values. `getPayload()` runs that mapping through `normalize()`, which recursively:
1. formats `DateTimeInterface` as `Ymd` and casts `Stringable` to string,
2. normalizes fields listed in `getNormalizedFields()` via `FacebookAds\Object\ServerSide\Normalizer`,
3. hashes fields listed in `getHashedFields()` via `FacebookAds\Object\ServerSide\Util::hash` (SHA-256 — this is how PII like email/phone is protected),
4. recurses into nested `Parameters` objects (calling their `getPayload()`),
5. strips empty values (`null`, `''`, `[]`) so they aren't sent.

So to add a field: add the public property, map it in `getMapping()`, and register it in `getNormalizedFields()`/`getHashedFields()` if Meta requires it. The lists of which fields normalize/hash mirror the corresponding `FacebookAds\Object\ServerSide\*` classes (see the `@see` annotations) — keep them in sync with that SDK.

**Two payload contexts** (`PAYLOAD_CONTEXT_SERVER` vs `PAYLOAD_CONTEXT_BROWSER`). The same objects serialize differently depending on whether they're sent server-side via the Conversions API or rendered into a client-side `fbq()` call. `User::getMapping()` strips server-only fields (IP, user agent, fbc, fbp) in browser context.

**`Parameters` subclasses:** `Event` (the aggregate root — holds `User $userData`, `Custom $customData`, a list of `Pixel`, plus `metadata` for app-internal use that is never sent), `User` (customer matching data), `Custom` (event-specific data like value/currency/contents), `Content` (a single item in `Custom::$contents`). `Event` auto-generates `eventId` (random, for [deduplication](https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/server-event#event-id)) and `eventTime` in its constructor. `Event` is intentionally **not** `final` so consumers can subclass it into domain-specific events; the other data objects are `final`.

**`Client` (`src/Client/Client.php`)** — `sendEvent()` delegates to `sendPreparedEvent($event->prepare())`. `Event::prepare()` returns a `PreparedEvent` (`src/Event/PreparedEvent.php`): the `getPayload()` array plus the delivery information (event name and id, pixels, test event code), made of scalars/arrays/`Pixel` only so consumers can hash at capture time and queue it. The pixels are cloned so it is a snapshot, and `withoutAccessTokens()`/`withAccessTokens()` (immutable) keep the tokens out of the queue and restore them by pixel id before sending. `sendPreparedEvent()` skips pixels without an access token and logs an error naming them (Meta's own error for a missing token does not mention the token, and a token-less pixel is legitimate, e.g. browser-only, so it must not block the others); only when no pixel has a token does it throw an `InvalidArgumentException`, before any request. It then POSTs the payload (form-encoded) to `graph.facebook.com/v{ApiConfig::APIVersion}/{pixelId}/events` once per pixel (each pixel carries its own access token). A failure of the HTTP client is wrapped in a `TransportException`, and a non-200 response throws a `ResponseException` carrying the status code, the raw body and, when the body is in Meta's error format, the parsed `ErrorResponse`. HTTP is fully PSR-based: PSR-18 client and PSR-17 factories are auto-discovered via `php-http/discovery` but can be injected with `setHttpClient()` / `setRequestFactory()` / etc. The client is `LoggerAware` and defaults to `NullLogger`.

**Exceptions (`src/Exception/`)** — everything the SDK throws implements `ExceptionInterface`. There are three concrete classes, one per thing a caller can do: `InvalidArgumentException` (extends SPL's; the caller's fault, never retry: bad cookie values, invalid event data, an event none of whose pixels has an access token, an unencodable payload), `TransportException` (no response; retry) and `ResponseException` (non-200; decide from `statusCode`/`errorResponse`). To keep that promise, never throw SPL exceptions or use `Webmozart\Assert\Assert` directly in `src/`: use `Setono\MetaConversionsApi\Assert`, an internal subclass whose failures throw the SDK's `InvalidArgumentException`, and wrap third-party exceptions (the Facebook `Normalizer`, PSR-18, `\JsonException`). `FbqGenerator` is the one place that does not throw: its output goes straight into a page, so it logs and returns an empty string when the data cannot be encoded.

**`FbqGenerator` (`src/Generator/FbqGenerator.php`)** — the client-side counterpart. Generates the `fbq('init', ...)` / `fbq('track', ...)` JavaScript snippets, using the browser-context payload and reusing the same `eventId` so server and browser events deduplicate. `Event::isCustom()` decides between `track` and `trackCustom`.

**Value objects (`src/ValueObject/`)** — `Fbc`/`Fbp` (extending `Fb`) model the `_fbc`/`_fbp` cookie values with `fromString()` validation and `value()` serialization; assignable to `User::$fbc`/`$fbp` as either the typed object or a raw string. Both accept the optional trailing appendix segment that Meta's parameter builder writes (`getAppendix()`/`withAppendix()`) and write it back unchanged, so a cookie value round-trips byte for byte.

The `facebook/php-business-sdk` dependency is used only for `Normalizer`, `Util::hash`, and `ApiConfig::APIVersion` (the API version is pinned to whatever that package ships).
