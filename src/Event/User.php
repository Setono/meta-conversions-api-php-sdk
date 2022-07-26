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

    protected function getMapping(string $context): array
    {
        $mapping = [
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

        if (self::PAYLOAD_CONTEXT_BROWSER === $context) {
            unset(
                $mapping['client_ip_address'],
                $mapping['client_user_agent'],
                $mapping['fbc'],
                $mapping['fbp'],
            );
        }

        return $mapping;
    }

    /**
     * @see \FacebookAds\Object\ServerSide\UserData::normalize
     */
    protected static function getNormalizedFields(): array
    {
        return [
            'em', 'ph', 'ge', 'db', 'ln', 'fn', 'ct', 'st', 'zp', 'country',
        ];
    }

    /**
     * @see \FacebookAds\Object\ServerSide\UserData::normalize
     */
    protected static function getHashedFields(): array
    {
        return [
            'em',
            'ph',
            'fn',
            'ln',
            'ge',
            'db',
            'ct',
            'st',
            'zp',
            'country',
        ];
    }
}
