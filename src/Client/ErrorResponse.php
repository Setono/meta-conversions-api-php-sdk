<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use Setono\MetaConversionsApi\Exception\ClientException;
use Webmozart\Assert\Assert;

/**
 * Represents the error envelope Meta/Facebook returns when a request fails.
 *
 * The minimal shape is:
 *
 *     {"error":{"message":"..","type":"..","code":100,"fbtrace_id":".."}}
 *
 * but Meta may also send the optional error_subcode, is_transient and the user facing
 * error_user_title / error_user_msg fields, e.g.:
 *
 *     {"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"error_subcode":2804050,"is_transient":false,"error_user_title":"..","error_user_msg":"..","fbtrace_id":".."}}
 *
 * Those optional fields are captured when present.
 *
 * @internal
 */
final class ErrorResponse
{
    /**
     * This is the raw json response
     */
    public string $json;

    public string $message;

    public string $type;

    public int $code;

    public string $traceId;

    public ?int $subcode = null;

    public ?bool $transient = null;

    public ?string $userTitle = null;

    public ?string $userMessage = null;

    private function __construct(string $json, string $message, string $type, int $code, string $traceId)
    {
        $this->json = $json;
        $this->message = $message;
        $this->type = $type;
        $this->code = $code;
        $this->traceId = $traceId;
    }

    /**
     * @throws ClientException if the JSON / response is invalid
     */
    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw ClientException::invalidJson($e, $json);
        }

        try {
            Assert::isArray($data);
            Assert::keyExists($data, 'error');

            $error = $data['error'];
            Assert::isArray($error);

            if (!isset($error['message'], $error['type'], $error['code'], $error['fbtrace_id'])) {
                throw ClientException::invalidResponseFormat($json);
            }

            ['message' => $message, 'type' => $type, 'code' => $code, 'fbtrace_id' => $traceId] = $error;

            Assert::string($message);
            Assert::string($type);
            Assert::integer($code);
            Assert::string($traceId);

            $subcode = $error['error_subcode'] ?? null;
            Assert::nullOrInteger($subcode);

            $transient = $error['is_transient'] ?? null;
            Assert::nullOrBoolean($transient);

            $userTitle = $error['error_user_title'] ?? null;
            Assert::nullOrString($userTitle);

            $userMessage = $error['error_user_msg'] ?? null;
            Assert::nullOrString($userMessage);
        } catch (\InvalidArgumentException $e) {
            throw ClientException::invalidResponseFormat($json);
        }

        $self = new self($json, $message, $type, $code, $traceId);
        $self->subcode = $subcode;
        $self->transient = $transient;
        $self->userTitle = $userTitle;
        $self->userMessage = $userMessage;

        return $self;
    }
}
