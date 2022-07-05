<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use Setono\MetaConversionsApi\Exception\ClientException;
use Webmozart\Assert\Assert;

/**
 * todo also handle a JSON response like this:
 *
 * {"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"error_subcode":2804050,"is_transient":false,"error_user_title":"Du har ikke tilf\u00f8jet tilstr\u00e6kkelige data om kundeoplysningsparametre for denne h\u00e6ndelse","error_user_msg":"Denne h\u00e6ndelse har ingen kundeoplysningsparametre, eller den har en kombination af kundeoplysningsparametre, der er s\u00e5 bred, at det er usandsynligt, at det vil v\u00e6re effektivt til matchning. Du kan l\u00f8se dette ved at g\u00e5 til de anbefalede fremgangsm\u00e5der for parametre p\u00e5 developers.facebook.com\/docs\/marketing-api\/conversions-api\/best-practices\/#req-rec-params","fbtrace_id":"Asu2mk752HZ0oT07IksFAwN"}}
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

            if (!isset($data['error']['message'], $data['error']['type'], $data['error']['code'], $data['error']['fbtrace_id'])) {
                throw ClientException::invalidResponseFormat($json);
            }

            ['message' => $message, 'type' => $type, 'code' => $code, 'fbtrace_id' => $traceId] = $data['error'];

            Assert::string($message);
            Assert::string($type);
            Assert::integer($code);
            Assert::string($traceId);
        } catch (\InvalidArgumentException $e) {
            throw ClientException::invalidResponseFormat($json);
        }

        return new self($json, $message, $type, $code, $traceId);
    }
}
