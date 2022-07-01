<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

final class User implements Parameters
{
    /** @var string|list<string>|null */
    public $email;

    /** @var string|list<string>|null */
    public $phoneNumber;

    /** @var string|list<string>|null */
    public $firstName;

    /** @var string|list<string>|null */
    public $lastName;

    /** @var string|list<string>|null */
    public $gender;

    /** @var \DateTimeInterface|list<\DateTimeInterface>|null */
    public $dateOfBirth;

    /** @var string|list<string>|null */
    public $city;

    /** @var string|list<string>|null */
    public $state;

    /** @var string|list<string>|null */
    public $zipCode;

    /** @var string|list<string>|null */
    public $country;

    /** @var string|list<string>|null */
    public $externalId;

    public ?string $clientIpAddress = null;

    public ?string $clientUserAgent = null;

    public ?string $fbc = null;

    public ?string $fbp = null;

    public ?string $subscriptionId = null;

    public ?int $fbLoginId = null;

    public ?int $leadId = null;
}
