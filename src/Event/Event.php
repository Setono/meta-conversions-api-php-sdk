<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use Setono\MetaConversionsApi\Pixel\Pixel;

/**
 * The properties of this class is taken from Meta documentation: https://developers.facebook.com/docs/marketing-api/conversions-api/parameters
 */
final class Event extends Parameters
{
    public const ACTION_SOURCE_EMAIL = 'email';

    public const ACTION_SOURCE_WEBSITE = 'website';

    public const ACTION_SOURCE_PHONE_CALL = 'phone_call';

    public const ACTION_SOURCE_CHAT = 'chat';

    public const ACTION_SOURCE_PHYSICAL_STORE = 'physical_store';

    public const ACTION_SOURCE_SYSTEM_GENERATED = 'system_generated';

    public const ACTION_SOURCE_OTHER = 'other';

    public const EVENT_ADD_TO_CART = 'AddToCart';

    public const EVENT_ADD_PAYMENT_INFO = 'AddPaymentInfo';

    public const EVENT_ADD_TO_WISHLIST = 'AddToWishlist';

    public const EVENT_COMPLETE_REGISTRATION = 'CompleteRegistration';

    public const EVENT_CONTACT = 'Contact';

    public const EVENT_CUSTOMIZE_PRODUCT = 'CustomizeProduct';

    public const EVENT_DONATE = 'Donate';

    public const EVENT_FIND_LOCATION = 'FindLocation';

    public const EVENT_INITIATE_CHECKOUT = 'InitiateCheckout';

    public const EVENT_LEAD = 'Lead';

    public const EVENT_PURCHASE = 'Purchase';

    public const EVENT_SCHEDULE = 'Schedule';

    public const EVENT_SEARCH = 'Search';

    public const EVENT_START_TRIAL = 'StartTrial';

    public const EVENT_SUBMIT_APPLICATION = 'SubmitApplication';

    public const EVENT_SUBSCRIBE = 'Subscribe';

    public const EVENT_VIEW_CONTENT = 'ViewContent';

    /**
     * The pixel this event should be associated with
     *
     * @var list<Pixel>
     */
    public array $pixels = [];

    public string $eventName;

    public int $eventTime;

    public User $userData;

    public Custom $customData;

    public ?string $eventSourceUrl = null;

    public ?bool $optOut = null;

    public ?string $eventId = null;

    public ?string $actionSource = null;

    public array $dataProcessingOptions = [];

    public ?int $dataProcessingOptionsCountry = null;

    public ?int $dataProcessingOptionsState = null;

    public function __construct(string $eventName, string $actionSource = self::ACTION_SOURCE_WEBSITE)
    {
        // We set this by default because of deduplication
        // See https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/server-event#event-id
        $this->eventId = bin2hex(random_bytes(16));
        $this->eventName = $eventName;
        $this->eventTime = time();
        $this->userData = new User();
        $this->customData = new Custom();
        $this->actionSource = $actionSource;
    }

    /**
     * Returns true if the event is a custom event (i.e. not a standard event)
     *
     * See also
     * - https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/server-event#event-name
     * - https://developers.facebook.com/docs/meta-pixel/implementation/conversion-tracking#custom-events
     */
    public function isCustom(): bool
    {
        return !in_array($this->eventName, self::getEvents(), true);
    }

    /**
     * @return list<string>
     */
    public static function getEvents(): array
    {
        return [
            self::EVENT_ADD_TO_CART,
            self::EVENT_ADD_PAYMENT_INFO,
            self::EVENT_ADD_TO_WISHLIST,
            self::EVENT_COMPLETE_REGISTRATION,
            self::EVENT_CONTACT,
            self::EVENT_CUSTOMIZE_PRODUCT,
            self::EVENT_DONATE,
            self::EVENT_FIND_LOCATION,
            self::EVENT_INITIATE_CHECKOUT,
            self::EVENT_LEAD,
            self::EVENT_PURCHASE,
            self::EVENT_SCHEDULE,
            self::EVENT_SEARCH,
            self::EVENT_START_TRIAL,
            self::EVENT_SUBMIT_APPLICATION,
            self::EVENT_SUBSCRIBE,
            self::EVENT_VIEW_CONTENT,
        ];
    }

    public function normalize(): array
    {
        return [
            'event_name' => $this->eventName,
            'event_time' => $this->eventTime,
            'user_data' => $this->userData->normalize(),
            'custom_data' => $this->customData->normalize(),
            'event_source_url' => $this->eventSourceUrl,
            'opt_out' => $this->optOut,
            'event_id' => $this->eventId,
            'action_source' => self::normalizeField('action_source', $this->actionSource),
            'data_processing_options' => $this->dataProcessingOptions,
            'data_processing_options_country' => $this->dataProcessingOptionsCountry,
            'data_processing_options_state' => $this->dataProcessingOptionsState,
        ];
    }
}
