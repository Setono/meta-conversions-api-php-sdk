<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApi\ValueObject\Fbp;

final class EventTest extends TestCase
{
    /**
     * Characterization test that pins the exact output of the whole serialization pipeline
     * (nested Parameters objects, lists, scalars, normalization, hashing, value objects,
     * custom properties and empty-value filtering) so any change to getPayload() is caught.
     *
     * @test
     */
    public function it_generates_the_full_payload(): void
    {
        $event = new Event(Event::EVENT_PURCHASE);
        $event->eventTime = 1658743659;
        $event->eventId = 'event_id';
        $event->eventSourceUrl = 'https://example.com/checkout';
        $event->optOut = false;
        $event->dataProcessingOptions = ['LDU'];
        $event->dataProcessingOptionsCountry = 1;
        $event->dataProcessingOptionsState = 1000;

        $event->userData->email[] = 'JohnDoe@Example.com';
        $event->userData->phoneNumber[] = '+1 (555) 123-4567';
        $event->userData->firstName[] = 'John';
        $event->userData->lastName[] = 'Doe';
        $event->userData->city[] = 'Copenhagen';
        $event->userData->country[] = 'DK';
        $event->userData->externalId[] = 'ext-123';
        $event->userData->clientIpAddress = '192.168.0.1';
        $event->userData->clientUserAgent = 'Chrome';
        $event->userData->fbc = Fbc::fromString('fb.1.1657051589577.ClickId');
        $event->userData->fbp = Fbp::fromString('fb.1.1656874832584.1088522659');

        $event->customData->value = 110.5;
        $event->customData->currency = 'DKK';
        $event->customData->contentIds = ['PROD_1'];
        $event->customData->contents[] = new Content('PROD_1', 2, 55.25, 'home_delivery');
        $event->customData->customProperties['my_custom'] = 'x';

        self::assertEquals([
            'event_name' => 'Purchase',
            'event_time' => 1658743659,
            'user_data' => [
                'em' => ['55e79200c1635b37ad31a378c39feb12f120f116625093a19bc32fff15041149'],
                'ph' => ['d6736136ea896c1bfdc553e0e86e702c70d060d805696ca3e4e9e0961353860a'],
                'fn' => ['96d9632f363564cc3032521409cf22a852f2032eec099ed5967c0d000cec607a'],
                'ln' => ['799ef92a11af918e3fb741df42934f3b568ed2d93ac1df74f1b8d41a27932a6f'],
                'ct' => ['842de7b239f0d6ab17dc08d3fcfe68c090a9af0eb5285cfbc5433b3304c5ebee'],
                'country' => ['867b4bf4357a7c0e415ffd537f61ea8785dd47113104000b534a130c98a42ce8'],
                'external_id' => ['ext-123'],
                'client_ip_address' => '192.168.0.1',
                'client_user_agent' => 'Chrome',
                'fbc' => 'fb.1.1657051589577.ClickId',
                'fbp' => 'fb.1.1656874832584.1088522659',
            ],
            'custom_data' => [
                'my_custom' => 'x',
                'content_ids' => ['PROD_1'],
                'contents' => [
                    ['id' => 'PROD_1', 'quantity' => 2, 'item_price' => 55.25, 'delivery_category' => 'home_delivery'],
                ],
                'currency' => 'dkk',
                'value' => 110.5,
            ],
            'event_source_url' => 'https://example.com/checkout',
            'opt_out' => false,
            'event_id' => 'event_id',
            'action_source' => 'website',
            'data_processing_options' => ['LDU'],
            'data_processing_options_country' => 1,
            'data_processing_options_state' => 1000,
        ], $event->getPayload());
    }

    /**
     * @test
     */
    public function it_normalizes_correct_fields(): void
    {
        $event = new Event(Event::EVENT_ADD_TO_CART);
        $event->eventTime = 123;
        $event->eventId = 'EventId';
        $event->testEventCode = 'TestEventCode';
        $event->eventSourceUrl = 'https://example.com/products/productId=123';
        $event->actionSource = Event::ACTION_SOURCE_SYSTEM_GENERATED;
        $event->optOut = true;

        self::assertSame([
            'event_name' => Event::EVENT_ADD_TO_CART,
            'event_time' => 123,
            'event_source_url' => 'https://example.com/products/productId=123',
            'opt_out' => true,
            'event_id' => 'EventId',
            'action_source' => Event::ACTION_SOURCE_SYSTEM_GENERATED,
        ], $event->getPayload());
    }

    /**
     * @test
     */
    public function it_filters(): void
    {
        $event = new Event(Event::EVENT_PURCHASE);
        $event->eventTime = 123;
        $event->eventId = 'event_id';
        $event->userData->email[] = 'johndoe@example.com';
        $event->userData->email[] = ''; // filtered out because it is empty

        $dateOfBirth = \DateTimeImmutable::createFromFormat('Y-m-d', '1986-07-11');
        self::assertNotFalse($dateOfBirth);
        $event->userData->dateOfBirth[] = $dateOfBirth;

        $event->customData->contents[] = new Content('content_id', 1);

        self::assertEquals([
            'event_id' => 'event_id',
            'event_name' => Event::EVENT_PURCHASE,
            'event_time' => 123,
            'custom_data' => [
                'contents' => [
                    ['id' => 'content_id', 'quantity' => 1],
                ],
            ],
            'user_data' => [
                'em' => [
                    '55e79200c1635b37ad31a378c39feb12f120f116625093a19bc32fff15041149',
                ],
                'db' => [
                    'cccd631dbe89ae6c982a960f248fabab8a4ae7f899853a3ea5bceef8ca1d6585',
                ],
            ],
            'action_source' => 'website',
        ], $event->getPayload());
    }

    /**
     * @test
     */
    public function it_knows_if_it_is_a_custom_event(): void
    {
        self::assertFalse((new Event(Event::EVENT_PURCHASE))->isCustom());
        self::assertTrue((new Event('SomeNonStandardEvent'))->isCustom());
    }

    /**
     * @test
     */
    public function it_tells_if_it_has_pixels(): void
    {
        $event = new Event(Event::EVENT_PURCHASE);
        self::assertFalse($event->hasPixels());

        $event->pixels[] = new Pixel('pixel_id');
        self::assertTrue($event->hasPixels());
    }
}
