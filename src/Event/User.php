<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

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

    public ?string $fbc = null;

    public ?string $fbp = null;

    public ?string $subscriptionId = null;

    public ?int $fbLoginId = null;

    public ?int $leadId = null;

    public function normalize(): array
    {
        return [
            'em' => self::hash(self::normalizeField('em', $this->email)),
            'ph' => self::hash(self::normalizeField('ph', $this->phoneNumber)),
            'fn' => self::hash(self::normalizeField('fn', $this->firstName)),
            'ln' => self::hash(self::normalizeField('ln', $this->lastName)),
            'ge' => self::hash(self::normalizeField('ge', $this->gender)),
            'db' => self::hash(self::normalizeField('db', self::normalizeDateOfBirth($this->dateOfBirth))),
            'ct' => self::hash(self::normalizeField('ct', $this->city)),
            'st' => self::hash(self::normalizeField('st', $this->state)),
            'zp' => self::hash(self::normalizeField('zp', $this->zipCode)),
            'country' => self::hash(self::normalizeField('country', $this->country)),
            'external_id' => self::hash(self::normalizeField('external_id', $this->externalId)),
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
     * @param list<\DateTimeInterface> $dateOfBirth
     *
     * @return list<string>
     */
    private static function normalizeDateOfBirth(array $dateOfBirth): array
    {
        return array_map(static function (\DateTimeInterface $dateTime): string {
            return $dateTime->format('Ymd');
        }, $dateOfBirth);
    }
}
