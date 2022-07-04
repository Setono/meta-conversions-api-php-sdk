<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

final class User extends Parameters
{
    /** @var list<non-empty-string> */
    public array $email = [];

    /** @var list<non-empty-string> */
    public array $phoneNumber = [];

    /** @var list<non-empty-string> */
    public array $firstName = [];

    /** @var list<non-empty-string> */
    public array $lastName = [];

    /** @var list<non-empty-string> */
    public array $gender = [];

    /** @var list<\DateTimeInterface> */
    public array $dateOfBirth = [];

    /** @var list<non-empty-string> */
    public array $city = [];

    /** @var list<non-empty-string> */
    public array $state = [];

    /** @var list<non-empty-string> */
    public array $zipCode = [];

    /** @var list<non-empty-string> */
    public array $country = [];

    /** @var list<non-empty-string> */
    public array $externalId = [];

    public ?string $clientIpAddress = null;

    public ?string $clientUserAgent = null;

    public ?string $fbc = null;

    public ?string $fbp = null;

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
