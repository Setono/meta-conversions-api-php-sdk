# PHP library with basic objects and more for working with Facebook/Metas Conversions API

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]
[![Mutation testing][ico-infection]][link-infection]

## Installation

The easiest way to install this library is by installing the library along with its HTTP client dependencies:

```bash
composer require setono/meta-conversions-api-php-sdk kriswallsmith/buzz nyholm/psr7
```

If you want to use your own HTTP client, just do `composer require setono/meta-conversions-api-php-sdk` and then
remember to set the HTTP client and factories when instantiating the `Setono\MetaConversionsApi\Client\Client`

## Usage

```php
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;

$event = new Event(Event::EVENT_VIEW_CONTENT);
$event->eventSourceUrl = 'https://example.com/products/blue-jeans';
$event->userData->clientUserAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36';
$event->userData->email[] = 'johndoe@example.com';
$event->pixels[] = new Pixel('INSERT YOUR PIXEL ID', 'INSERT YOUR ACCESS TOKEN');
// $event->testEventCode = 'test event code'; // uncomment this if you want to send a test event

$client = new Client();
$client->sendEvent($event);
```

[ico-version]: https://poser.pugx.org/setono/meta-conversions-api-php-sdk/v/stable
[ico-license]: https://poser.pugx.org/setono/meta-conversions-api-php-sdk/license
[ico-github-actions]: https://github.com/Setono/meta-conversions-api-php-sdk/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/meta-conversions-api-php-sdk/branch/master/graph/badge.svg
[ico-infection]: https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FSetono%2Fmeta-conversions-api-php-sdk%2Fmaster

[link-packagist]: https://packagist.org/packages/setono/meta-conversions-api-php-sdk
[link-github-actions]: https://github.com/Setono/meta-conversions-api-php-sdk/actions
[link-code-coverage]: https://codecov.io/gh/Setono/meta-conversions-api-php-sdk
[link-infection]: https://dashboard.stryker-mutator.io/reports/github.com/Setono/meta-conversions-api-php-sdk/master
