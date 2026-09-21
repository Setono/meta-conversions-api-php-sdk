# Upgrade from 1.x to 2.0

The requirements are unchanged: PHP 8.1+ and the same dependencies as 1.x.

## `ClientInterface` has a second method, `sendPreparedEvent()`

This only affects you if you implement `ClientInterface` yourself, for instance in a decorator or a test double. Add:

```php
use Setono\MetaConversionsApi\Event\PreparedEvent;

public function sendPreparedEvent(PreparedEvent $preparedEvent): void;
```

It sends an event that was prepared earlier with `Event::prepare()`, i.e. with the payload already normalized and
hashed. `Client` implements it, and a decorator can simply forward the call.

## `ClientException` is gone; everything the SDK throws implements `ExceptionInterface`

There are three concrete exceptions in `Setono\MetaConversionsApi\Exception`, one for each thing you can do about a
failure. See "Error handling" in the README for how to use them.

| In 1.x | In 2.0 |
|---|---|
| `ClientException`, when Meta answered with an error | `ResponseException`. The error is no longer only a message: `$e->statusCode`, `$e->body` and `$e->errorResponse` (code, subcode, type, trace id, the transient flag, user-facing texts) |
| `ClientException`, when the error response could not be parsed | `ResponseException` with `$e->errorResponse` being null |
| The PSR-18 `ClientExceptionInterface` of your HTTP client escaped from `sendEvent()` | `TransportException`, with the PSR-18 exception as the previous exception |
| A `\JsonException` escaped from `sendEvent()` when the payload could not be encoded | `InvalidArgumentException` |
| `\InvalidArgumentException` or `Webmozart\Assert\InvalidArgumentException` from `Fbc::fromString()`, `Fbp::fromString()`, the `with*()` methods and `getPayload()` | `Setono\MetaConversionsApi\Exception\InvalidArgumentException` |

What to change:

- Replace `catch (ClientException $e)` with `catch (ExceptionInterface $e)`, or with the specific classes. If you caught
  the PSR-18 exception around `sendEvent()`, catch `TransportException` instead.
- The SDK's `InvalidArgumentException` extends PHP's `\InvalidArgumentException`, so existing
  `catch (\InvalidArgumentException $e)` blocks keep working. A `catch` of `Webmozart\Assert\InvalidArgumentException`
  does not.
- When event data is invalid, e.g. an unknown `action_source`, the message now names the field, and the exception from
  `facebook/php-business-sdk` is the previous exception.
- `ErrorResponse` is no longer `@internal`. Its properties are now readonly, and `ErrorResponse::fromJson()` throws
  `InvalidArgumentException` instead of `ClientException`.

## The payload context is an enum

`Parameters::PAYLOAD_CONTEXT_SERVER` and `Parameters::PAYLOAD_CONTEXT_BROWSER` are replaced by the
`Setono\MetaConversionsApi\Event\PayloadContext` enum. With a string, a typo silently gave you the server payload, IP
address, user agent, `fbc` and `fbp` included, which is exactly what must not be printed into a page.

```php
// 1.x
$event->userData->getPayload(Parameters::PAYLOAD_CONTEXT_BROWSER);

// 2.0
$event->userData->getPayload(PayloadContext::Browser);
```

If you subclass `Event` and override `getMapping()`, change its signature from `getMapping(string $context)` to
`getMapping(PayloadContext $context)`. A subclass that only overrides the constructor is not affected.

The context is now also passed on to nested objects. In 1.x, `$event->getPayload(Parameters::PAYLOAD_CONTEXT_BROWSER)`
still serialized the user data in the server context. `FbqGenerator` was not affected, since it asks the user data and
the custom data directly.

## Stricter types on `User`, `Fb` and `Fbp`

- **`Fbp::$randomNumber` is private.** It was the only public, mutable property on the otherwise immutable cookie value
  objects. Use `getRandomNumber()` and `withRandomNumber()`.
- **`User::$fbc` and `User::$fbp` are natively typed**, as `Fbc|string|null` and `Fbp|string|null`. They were untyped, so
  anything could be assigned. Assigning something else now throws a `\TypeError`.
- **`Fb::withCreationTime()` takes `int|\DateTimeInterface` natively.** Passing anything else throws a `\TypeError`. It
  used to throw an `InvalidArgumentException`.
- `withSubdomainIndex()`, `withCreationTime()` and `withAppendix()` declare `static` as their return type. They already
  returned the concrete class; this only matters if you extend `Fb` yourself and override them.

A `\TypeError` is PHP's own error for a programming mistake and does not implement `ExceptionInterface`.

## `FbqGenerator::generateTrack()` no longer throws a `\JsonException`

When the custom data cannot be encoded as JSON, it now logs an error and returns an empty string, which is what
`generateInit()` already did. The output of both goes straight into a page, where an exception would break the page.

## Pixels without an access token are skipped

In 1.x the client sent the request anyway, and Meta answered with an error that does not mention the access token
("Unsupported post request. Object with ID ... does not exist, cannot be loaded due to missing permissions ..."). That
exception also kept the pixels listed after it from receiving the event.

In 2.0 the client sends to every pixel that has an access token, skips the ones that do not, and logs an error naming
them. A pixel without an access token is a legitimate state, for a pixel that is only used in the browser for instance,
so it no longer gets in the way of the others. Only when none of the pixels has an access token does the client throw
an `InvalidArgumentException`, before any request is made. That is what you will see if you forget
`PreparedEvent::withAccessTokens()` after `withoutAccessTokens()`.

## Behaviour change in `Client::sendEvent()`

`sendEvent()` now delegates to `sendPreparedEvent($event->prepare())`, so the payload is built before the client checks
whether the event has any pixels. The request that is sent is byte for byte the same as in 1.x. There is one observable
difference: an event without pixels whose data is invalid, an unknown `action_source` for instance, now throws when the
payload is built. In 1.x the client logged the missing pixels and returned without ever building the payload.

## New in 2.0

Nothing you have to change, but worth knowing about: `Event::prepare()` returns a `PreparedEvent` that holds no raw
personal data and can be stored or queued, and `PreparedEvent::withoutAccessTokens()` / `withAccessTokens()` keep the
access tokens out of that storage. See "Sending events later, e.g. through a queue" in the README.
