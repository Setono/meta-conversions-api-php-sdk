# Upgrade from 1.x to 2.0

The requirements are unchanged: PHP 8.1+ and the same dependencies as 1.x.

## `ClientInterface` has a second method, `sendPreparedEvent()`

This is the only backwards-compatibility break, and it only affects you if you implement `ClientInterface` yourself,
for instance in a decorator or a test double. Add:

```php
use Setono\MetaConversionsApi\Event\PreparedEvent;

public function sendPreparedEvent(PreparedEvent $preparedEvent): void;
```

It sends an event that was prepared earlier with `Event::prepare()`, i.e. with the payload already normalized and
hashed. `Client` implements it, and a decorator can simply forward the call.

## Behaviour change in `Client::sendEvent()`

`sendEvent()` now delegates to `sendPreparedEvent($event->prepare())`, so the payload is built before the client checks
whether the event has any pixels. The request that is sent is byte for byte the same as in 1.x. There is one observable
difference: an event without pixels whose data is invalid, an unknown `action_source` for instance, now throws when the
payload is built. In 1.x the client logged the missing pixels and returned without ever building the payload.

## Pixels without an access token are rejected before any request is made

In 1.x the client sent the request anyway, and Meta answered with an error that does not mention the access token
("Unsupported post request. Object with ID ... does not exist, cannot be loaded due to missing permissions ..."). In 2.0
the client throws a `ClientException` naming the pixels instead. The exception class is the same as before, but with
several pixels there is a difference: all pixels are checked first, so the pixels listed before the one without an
access token no longer receive the event.

## New in 2.0

Nothing you have to change, but worth knowing about: `Event::prepare()` returns a `PreparedEvent` that holds no raw
personal data and can be stored or queued, and `PreparedEvent::withoutAccessTokens()` / `withAccessTokens()` keep the
access tokens out of that storage. See "Sending events later, e.g. through a queue" in the README.
