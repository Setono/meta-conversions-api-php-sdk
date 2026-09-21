<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use Setono\MetaConversionsApi\Assert;
use Setono\MetaConversionsApi\Exception\InvalidArgumentException;

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
 * @see \Setono\MetaConversionsApi\Exception\ResponseException::$errorResponse
 */
final class ErrorResponse
{
    private const EXPECTED_FORMAT = '{"error":{"message":"string","type":"string","code":int,"fbtrace_id":"string"}}';

    /**
     * @param string $json the raw json response
     * @param bool|null $transient true if Meta considers the error temporary, i.e. retrying may succeed. Null if Meta did not say
     */
    private function __construct(
        public readonly string $json,
        public readonly string $message,
        public readonly string $type,
        public readonly int $code,
        public readonly string $traceId,
        public readonly ?int $subcode,
        public readonly ?bool $transient,
        public readonly ?string $userTitle,
        public readonly ?string $userMessage,
    ) {
    }

    /**
     * @throws InvalidArgumentException if the JSON is invalid or is not in Meta's error format
     */
    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException(sprintf('The response is not valid JSON (%s): %s', $e->getMessage(), $json), previous: $e);
        }

        try {
            Assert::isArray($data);
            Assert::keyExists($data, 'error');

            $error = $data['error'];
            Assert::isArray($error);

            if (!isset($error['message'], $error['type'], $error['code'], $error['fbtrace_id'])) {
                throw new InvalidArgumentException('One of the required fields is missing');
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
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Expected a JSON response like %s, but got %s', self::EXPECTED_FORMAT, $json), previous: $e);
        }

        return new self($json, $message, $type, $code, $traceId, $subcode, $transient, $userTitle, $userMessage);
    }
}
