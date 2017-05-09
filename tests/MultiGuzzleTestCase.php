<?php

namespace Tests\BBC\BrandingClient;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

abstract class MultiGuzzleTestCase extends TestCase
{
    protected function getClient(array $mockResponses = [], &$historyContainer = null)
    {
        // Mock Requests
        $mockHandler = new MockHandler($mockResponses);
        $handler = HandlerStack::create($mockHandler);

        // History
        if (!is_null($historyContainer)) {
            $handler->push(Middleware::history($historyContainer));
        }

        return new Client(['handler' => $handler]);
    }

    protected function getHistoryContainer()
    {
        return [];
    }

    protected function mockResponse($responseStatus, $responseHeaders, $responseBody)
    {
        // Guzzle 6 object
        return new Response(
            $responseStatus,
            $responseHeaders,
            $responseBody
        );
    }

    protected function getLastRequest($history)
    {
        if (is_array($history)) {
            return $history[0]['request'];
        }

        return $history->getLastRequest();
    }

    protected function getLastRequestUrl($history)
    {
        if (is_array($history)) {
            $lastRequest = $history[0]['request'];
            return (string) $lastRequest->getUri();
        }

        $lastRequest = $history->getLastRequest();
        return $lastRequest->getUrl();
    }
}
