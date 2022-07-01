<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

final class User extends Parameters
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

    public function normalize(): array
    {
        return [
            'em' => self::hash($this->email),
            'ph' => self::hash($this->phoneNumber),
            'fn' => self::hash($this->firstName),
            'ln' => self::hash($this->lastName),
            'ge' => self::hash($this->gender),
            'db' => self::hash(self::normalizeDateOfBirth($this->dateOfBirth)),
            'ct' => self::hash($this->city),
            'st' => self::hash($this->state),
            'zp' => self::hash($this->zipCode),
            'country' => self::hash($this->country),
            'external_id' => self::hash($this->externalId),
            'client_ip_address' => $this->clientIpAddress,
            'client_user_agent' => $this->clientUserAgent,
            'fbc' => $this->fbc,
            'fbp' => $this->fbp,
            'subscription_id' => $this->subscriptionId,
            'fb_login_id' => $this->fbLoginId,
            'lead_id' => $this->leadId,
        ];
    }

    /**
     * @param \DateTimeInterface|list<\DateTimeInterface>|null $dateOfBirth
     *
     * @return string|list<string>|null
     */
    private static function normalizeDateOfBirth($dateOfBirth)
    {
        if (null === $dateOfBirth) {
            return null;
        }

        if (is_array($dateOfBirth)) {
            return array_map(static function (\DateTimeInterface $dateTime): string {
                return $dateTime->format('Ymd');
            }, $dateOfBirth);
        }

        return $dateOfBirth->format('Ymd');
    }
}
