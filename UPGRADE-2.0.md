# Upgrade from 1.x to 2.0

## `ClientInterface` has a second method, `sendPreparedEvent()`

If you implement `ClientInterface` yourself, add:

```php
public function sendPreparedEvent(PreparedEvent $preparedEvent): void;
```

It sends an event that was prepared earlier with `Event::prepare()`, i.e. with the payload already normalized and
hashed, which makes it safe to store or queue. `Client` implements it. `sendEvent()` is unchanged and now delegates to
`sendPreparedEvent($event->prepare())`.
