# Meta (Facebook) Conversions API PHP SDK

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]
[![Mutation testing][ico-infection]][link-infection]

A small, typed PHP library for sending server-side events to Meta's (Facebook's)
[Conversions API](https://developers.facebook.com/docs/marketing-api/conversions-api), and for generating the
matching browser-side `fbq()` snippets.

It gives you plain, well-typed objects (`Event`, `User`, `Custom`, …) and takes care of the fiddly parts for you:

- **Automatic normalization & hashing** of customer information — you pass raw emails, phone numbers, names, etc. and
  the SDK normalizes and SHA-256 hashes them the way Meta requires. Never hash this data yourself.
- **Server + browser deduplication** — every event gets an `eventId` you can reuse on both sides so Meta counts it once.
- **Bring your own HTTP client** — built on PSR-18/PSR-17 with auto-discovery, so it works with any compliant client.

## Requirements

- PHP 8.1+
- A [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client and [PSR-17](https://www.php-fig.org/psr/psr-17/) factories
  (see [Installation](#installation))

## Installation

The SDK talks to the API through a PSR-18 client and PSR-17 factories, which it discovers automatically. The simplest
way is to install it together with an implementation:

```bash
composer require setono/meta-conversions-api-php-sdk kriswallsmith/buzz nyholm/psr7
```

`symfony/http-client` works just as well if you prefer it:

```bash
composer require setono/meta-conversions-api-php-sdk symfony/http-client nyholm/psr7
```

If your project already ships a PSR-18 client and PSR-17 factories you only need the SDK itself
(`composer require setono/meta-conversions-api-php-sdk`); see [Using your own HTTP client](#using-your-own-http-client).

## Quick start

```php
use Setono\MetaConversionsApi\Client\Client;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;

$event = new Event(Event::EVENT_VIEW_CONTENT);
$event->eventSourceUrl = 'https://example.com/products/blue-jeans';
$event->userData->clientUserAgent = $_SERVER['HTTP_USER_AGENT'];
$event->userData->clientIpAddress = $_SERVER['REMOTE_ADDR'];
$event->userData->email[] = 'johndoe@example.com'; // hashed for you before sending

// A pixel carries the id and the access token used to authenticate the request
$event->pixels[] = new Pixel('YOUR_PIXEL_ID', 'YOUR_ACCESS_TOKEN');

$client = new Client();
$client->sendEvent($event);
```

An `Event` is created with a random `eventId` and the current `eventTime` already set, and defaults to the `website`
action source. Pass a different source as the second constructor argument if needed (e.g.
`new Event(Event::EVENT_PURCHASE, Event::ACTION_SOURCE_PHYSICAL_STORE)`).

## Sending a richer event

`Event::$customData` holds the event-specific data (value, currency, contents, …) and `Event::$userData` holds the
customer-matching data:

```php
use Setono\MetaConversionsApi\Client\Client;
use Setono\MetaConversionsApi\Event\Content;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;

$event = new Event(Event::EVENT_PURCHASE);
$event->eventSourceUrl = 'https://example.com/checkout/complete';

// Customer information — pass raw values, the SDK normalizes and hashes them
$event->userData->email[] = 'johndoe@example.com';
$event->userData->phoneNumber[] = '+1 (555) 123-4567';
$event->userData->firstName[] = 'John';
$event->userData->lastName[] = 'Doe';
$event->userData->clientUserAgent = $_SERVER['HTTP_USER_AGENT'];
$event->userData->clientIpAddress = $_SERVER['REMOTE_ADDR'];

// Event data
$event->customData->currency = 'USD';
$event->customData->value = 142.52;
$event->customData->contents[] = new Content('SKU-1', 1, 99.99);
$event->customData->contents[] = new Content('SKU-2', 1, 42.53);

// Anything not covered by a typed property can go into customProperties
$event->customData->customProperties['membership_level'] = 'gold';

$event->pixels[] = new Pixel('YOUR_PIXEL_ID', 'YOUR_ACCESS_TOKEN');

(new Client())->sendEvent($event);
```

### Multiple pixels

Add more than one `Pixel` and the event is sent to each of them (every pixel carries its own access token):

```php
$event->pixels[] = new Pixel('PIXEL_ID_1', 'ACCESS_TOKEN_1');
$event->pixels[] = new Pixel('PIXEL_ID_2', 'ACCESS_TOKEN_2');
```

### Test events

While integrating, set a test event code so the event shows up in the *Test events* tool in Events Manager instead of
counting as real traffic:

```php
$event->testEventCode = 'TEST12345';
```

### Error handling

`sendEvent()` throws a `ClientException` if Meta returns a non-2xx response. The message contains Meta's error message,
code, trace id and the raw response (including the user-facing explanation when Meta provides one):

```php
use Setono\MetaConversionsApi\Exception\ClientException;

try {
    $client->sendEvent($event);
} catch (ClientException $e) {
    $logger->error('Could not send event to Meta', ['exception' => $e]);
}
```

## Browser-side tracking with deduplication

To get the best match quality Meta recommends sending events both server-side (this SDK) *and* from the browser, using
the same `eventId` so they are deduplicated. `FbqGenerator` produces the matching JavaScript:

```php
use Setono\MetaConversionsApi\Event\Parameters;
use Setono\MetaConversionsApi\Generator\FbqGenerator;

$generator = new FbqGenerator();

// In your <head>: initialise the pixel(s) and send a PageView
echo $generator->generateInit(
    $event->pixels,
    $event->userData->getPayload(Parameters::PAYLOAD_CONTEXT_BROWSER),
);

// Where the conversion happens: fire the same event in the browser.
// Because it reuses $event->eventId, Meta counts the server + browser event once.
echo $generator->generateTrack($event);
```

Both methods wrap the output in a `<script>` tag by default; pass `false` as the last argument to get the raw
JavaScript instead (e.g. to combine several calls into one tag).

## Custom events

Any event name that isn't one of the standard `Event::EVENT_*` constants is treated as a
[custom event](https://developers.facebook.com/docs/meta-pixel/implementation/conversion-tracking#custom-events).
`FbqGenerator` will emit `trackCustom` instead of `track` for these:

```php
$event = new Event('SubscribedToNewsletter');
$event->isCustom(); // true
```

## `fbc` / `fbp` cookies

The `_fbc` and `_fbp` cookies improve attribution. Assign them to the user data either as raw strings or as the typed
value objects, which validate the format:

```php
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApi\ValueObject\Fbp;

$event->userData->fbc = Fbc::fromString($_COOKIE['_fbc']);
$event->userData->fbp = Fbp::fromString($_COOKIE['_fbp']);
```

## Using your own HTTP client

By default the client auto-discovers a PSR-18 client and PSR-17 factories. To inject your own (e.g. a preconfigured
Guzzle client with timeouts and retries), use the setters:

```php
$client = new Client();
$client->setHttpClient($myPsr18Client);
$client->setRequestFactory($myPsr17RequestFactory);
$client->setStreamFactory($myPsr17StreamFactory);
```

## Logging

`Client` is `LoggerAware`. Pass any PSR-3 logger and the SDK will, for example, warn you when you try to send an event
that has no pixels associated:

```php
$client->setLogger($logger);
```

## Extending events

`Event` is intentionally **not** `final`, so you can build domain-specific events with sensible defaults:

```php
use Setono\MetaConversionsApi\Event\Event;

final class PurchaseEvent extends Event
{
    public function __construct()
    {
        parent::__construct(self::EVENT_PURCHASE);
    }
}
```

## Development

```bash
composer phpunit       # run the test suite
composer analyse       # static analysis (PHPStan)
composer check-style   # coding standard check (ECS)
composer fix-style     # fix coding standard violations
```

## License

This library is released under the [MIT License](LICENSE).

[ico-version]: https://poser.pugx.org/setono/meta-conversions-api-php-sdk/v/stable
[ico-license]: https://poser.pugx.org/setono/meta-conversions-api-php-sdk/license
[ico-github-actions]: https://github.com/Setono/meta-conversions-api-php-sdk/actions/workflows/build.yaml/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/meta-conversions-api-php-sdk/branch/master/graph/badge.svg
[ico-infection]: https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FSetono%2Fmeta-conversions-api-php-sdk%2Fmaster

[link-packagist]: https://packagist.org/packages/setono/meta-conversions-api-php-sdk
[link-github-actions]: https://github.com/Setono/meta-conversions-api-php-sdk/actions
[link-code-coverage]: https://codecov.io/gh/Setono/meta-conversions-api-php-sdk
[link-infection]: https://dashboard.stryker-mutator.io/reports/github.com/Setono/meta-conversions-api-php-sdk/master
