<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestDouble;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Builds PSR-7 responses from SDK fixture files for HTTP-level test mocking.
 *
 * Fixture files live in the SDK package at:
 * vendor/deutschepost/sdk-api-internetmarke/test/Provider/_files/
 */
class HttpResponseFactory
{
    private const FIXTURE_BASE = __DIR__ . '/../../../../sdk-api-internetmarke/test/Provider/_files';

    public static function authSuccess(): ResponseInterface
    {
        return self::fromFixture(self::FIXTURE_BASE . '/authentication/success.json');
    }

    public static function checkoutPdfSuccess(): ResponseInterface
    {
        return self::fromFixture(self::FIXTURE_BASE . '/order/checkoutPdfSuccess.json');
    }

    public static function checkoutPngSuccess(): ResponseInterface
    {
        return self::fromFixture(self::FIXTURE_BASE . '/order/checkoutPngSuccess.json');
    }

    public static function binaryContent(string $content): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/octet-stream'], $content);
    }

    public static function error(int $statusCode, string $fixtureName): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            (string) file_get_contents(self::FIXTURE_BASE . '/error/' . $fixtureName),
        );
    }

    public static function refundSuccess(): ResponseInterface
    {
        return self::fromFixture(self::FIXTURE_BASE . '/refund/requestRefundSuccess.json');
    }

    public static function pageFormatsSuccess(): ResponseInterface
    {
        return self::fromFixture(self::FIXTURE_BASE . '/catalog/pageFormatsSuccess.json');
    }

    public static function publicCatalogSuccess(): ResponseInterface
    {
        return self::fromFixture(self::FIXTURE_BASE . '/catalog/publicCatalogSuccess.json');
    }

    public static function fromFixture(string $path): ResponseInterface
    {
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            (string) file_get_contents($path),
        );
    }
}
