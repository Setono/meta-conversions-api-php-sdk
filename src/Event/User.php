<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApi\ValueObject\Fbp;

final class User extends Parameters
{
    /** @var list<string> */
    public array $email = [];

    /** @var list<string> */
    public array $phoneNumber = [];

    /** @var list<string> */
    public array $firstName = [];

    /** @var list<string> */
    public array $lastName = [];

    /** @var list<string> */
    public array $gender = [];

    /** @var list<\DateTimeInterface> */
    public array $dateOfBirth = [];

    /** @var list<string> */
    public array $city = [];

    /** @var list<string> */
    public array $state = [];

    /** @var list<string> */
    public array $zipCode = [];

    /** @var list<string> */
    public array $country = [];

    /** @var list<string> */
    public array $externalId = [];

    public ?string $clientIpAddress = null;

    public ?string $clientUserAgent = null;

    /** @var string|Fbc|null */
    public $fbc;

    /** @var string|Fbp|null */
    public $fbp;

    public ?string $subscriptionId = null;

    public ?int $fbLoginId = null;

    public ?int $leadId = null;

    protected function getMapping(): array
    {
        return [
            'em' => $this->email,
            'ph' => $this->phoneNumber,
            'fn' => $this->firstName,
            'ln' => $this->lastName,
            'ge' => $this->gender,
            'db' => $this->dateOfBirth,
            'ct' => $this->city,
            'st' => $this->state,
            'zp' => $this->zipCode,
            'country' => $this->country,
            'external_id' => $this->externalId,
            'client_ip_address' => $this->clientIpAddress,
            'client_user_agent' => $this->clientUserAgent,
            'fbc' => $this->fbc,
            'fbp' => $this->fbp,
            'subscription_id' => $this->subscriptionId,
            'fb_login_id' => $this->fbLoginId,
            'lead_id' => $this->leadId,
        ];
    }
}
