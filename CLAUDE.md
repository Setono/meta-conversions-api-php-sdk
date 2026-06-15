# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A PHP SDK providing value objects and a client for Meta's (Facebook) [Conversions API](https://developers.facebook.com/docs/marketing-api/conversions-api). It is a library (`setono/meta-conversions-api-php-sdk`), not an application — there is no runnable entry point. Requires PHP >= 8.1.

## Commands

Composer scripts (defined in `composer.json`):

- `composer phpunit` — run the test suite (PHPUnit 10)
- `composer analyse` — PHPStan static analysis (`phpstan.dist.neon`: `level: max`, analysed against PHP 8.1)
- `composer check-style` / `composer fix-style` — ECS coding-standard check / autofix
- `vendor/bin/infection` — mutation testing (thresholds: minMsi 61.74, minCoveredMsi 76.77). Needs a coverage driver (pcov or Xdebug); CI uses pcov. With neither installed locally you'll get "No code coverage driver available".
- `vendor/bin/composer-dependency-analyser` — verify declared composer deps match actual usage

The dev tooling (PHPStan + extensions, ECS via `sylius-labs/coding-standard`, PHPUnit, Infection, Rector, composer-normalize, composer-dependency-analyser) is listed directly in `require-dev` rather than pulled in through the `setono/code-quality-pack` meta-package. The pack's current major requires PHP >= 8.2; inlining the tools keeps the whole toolchain runnable on PHP 8.1. When bumping a tool, pick the latest version that still supports PHP 8.1 (e.g. PHPUnit stays on `^10.5`, Infection on `^0.29`).

Run a single test by file or filter:

```bash
vendor/bin/phpunit tests/Event/UserTest.php
vendor/bin/phpunit --filter it_sends_event
```

Tests use the `@test` annotation with `snake_case` method names (no `test` prefix).

CI (`.github/workflows/build.yaml`) runs coding standards, dependency analysis, PHPStan, and PHPUnit against PHP 8.1–8.4 on both `lowest` and `highest` dependency versions, so check lowest-version compatibility when touching dependencies. A separate workflow runs Roave's backwards-compatibility check on PRs — this is a public library, so avoid BC breaks to the public API.

### LiveClientTest

`tests/Client/LiveClientTest.php` hits the real Meta API. It self-skips unless the env vars in `phpunit.xml.dist` are set (`PIXEL_ID`, `ACCESS_TOKEN`, `TEST_EVENT_CODE`, `URL`, `EMAIL`). Copy `phpunit.xml.dist` to `phpunit.xml` and fill them in to run it.

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

**`Parameters` subclasses:** `Event` (the aggregate root — holds `User $userData`, `Custom $customData`, a list of `Pixel`, plus `metadata` for app-internal use that is never sent), `User` (customer matching data), `Custom` (event-specific data like value/currency/contents). `Event` auto-generates `eventId` (random, for [deduplication](https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/server-event#event-id)) and `eventTime` in its constructor. `Event` is intentionally **not** `final` so consumers can subclass it into domain-specific events; the other data objects are `final`.

**`Client` (`src/Client/Client.php`)** — `sendEvent()` serializes the event once, then POSTs it (form-encoded) to `graph.facebook.com/v{ApiConfig::APIVersion}/{pixelId}/events` once per associated pixel (each pixel carries its own access token). Non-200 responses throw `ClientException` built from `ErrorResponse`. HTTP is fully PSR-based: PSR-18 client and PSR-17 factories are auto-discovered via `php-http/discovery` but can be injected with `setHttpClient()` / `setRequestFactory()` / etc. The client is `LoggerAware` and defaults to `NullLogger`.

**`FbqGenerator` (`src/Generator/FbqGenerator.php`)** — the client-side counterpart. Generates the `fbq('init', ...)` / `fbq('track', ...)` JavaScript snippets, using the browser-context payload and reusing the same `eventId` so server and browser events deduplicate. `Event::isCustom()` decides between `track` and `trackCustom`.

**Value objects (`src/ValueObject/`)** — `Fbc`/`Fbp` (extending `Fb`) model the `_fbc`/`_fbp` cookie values with `fromString()` validation and `value()` serialization; assignable to `User::$fbc`/`$fbp` as either the typed object or a raw string.

The `facebook/php-business-sdk` dependency is used only for `Normalizer`, `Util::hash`, and `ApiConfig::APIVersion` (the API version is pinned to whatever that package ships).
