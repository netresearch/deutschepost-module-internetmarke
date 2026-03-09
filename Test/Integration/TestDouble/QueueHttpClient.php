<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestDouble;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 HTTP client that returns queued responses in FIFO order.
 *
 * Throws a NetworkException when the queue is exhausted, simulating
 * a transport-level failure (no response available).
 */
class QueueHttpClient implements ClientInterface
{
    /** @var ResponseInterface[] */
    private array $responses = [];

    /** @var RequestInterface[] */
    private array $requests = [];

    public function addResponse(ResponseInterface $response): void
    {
        $this->responses[] = $response;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        if (empty($this->responses)) {
            throw new NetworkException('Response queue is empty', $request);
        }

        return array_shift($this->responses);
    }

    /**
     * @return RequestInterface[]
     */
    public function getRequests(): array
    {
        return $this->requests;
    }
}
