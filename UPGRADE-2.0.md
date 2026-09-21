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

## `FbqGenerator::generateTrack()` no longer throws a `\JsonException`

When the custom data cannot be encoded as JSON, it now logs an error and returns an empty string, which is what
`generateInit()` already did. The output of both goes straight into a page, where an exception would break the page.

## Pixels without an access token are rejected before any request is made

In 1.x the client sent the request anyway, and Meta answered with an error that does not mention the access token
("Unsupported post request. Object with ID ... does not exist, cannot be loaded due to missing permissions ..."). In 2.0
the client throws an `InvalidArgumentException` naming the pixels instead. With several pixels there is one more
difference: all pixels are checked first, so the pixels listed before the one without an access token no longer receive
the event.

## Behaviour change in `Client::sendEvent()`

`sendEvent()` now delegates to `sendPreparedEvent($event->prepare())`, so the payload is built before the client checks
whether the event has any pixels. The request that is sent is byte for byte the same as in 1.x. There is one observable
difference: an event without pixels whose data is invalid, an unknown `action_source` for instance, now throws when the
payload is built. In 1.x the client logged the missing pixels and returned without ever building the payload.

## New in 2.0

Nothing you have to change, but worth knowing about: `Event::prepare()` returns a `PreparedEvent` that holds no raw
personal data and can be stored or queued, and `PreparedEvent::withoutAccessTokens()` / `withAccessTokens()` keep the
access tokens out of that storage. See "Sending events later, e.g. through a queue" in the README.
